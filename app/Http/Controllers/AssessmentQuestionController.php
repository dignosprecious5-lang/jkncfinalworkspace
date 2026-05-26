<?php

namespace App\Http\Controllers;

use App\Models\AssessmentType;
use App\Models\AssessmentQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssessmentQuestionController extends Controller
{
    private function authorizeAdmin(): void
    {
        $user = auth()->user();

        if (!$user || !in_array($user->role, ['SuperAdmin', 'Admin'], true)) {
            abort(403, 'Only Admin and SuperAdmin can manage assessment questions.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $types = AssessmentType::with([
            'questions' => function ($query) {
                $query->orderBy('sort_order')->orderBy('id');
            }
        ])
            ->orderBy('name')
            ->get();

        $selectedTypeId = $request->query('type');

        if (!$selectedTypeId && $types->count() > 0) {
            $selectedTypeId = $types->first()->id;
        }

        $selectedType = $types->firstWhere('id', (int) $selectedTypeId);

        $questions = collect();

        if ($selectedType) {
            $questions = AssessmentQuestion::where('assessment_type_id', $selectedType->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }

        return view('human-capital.assessment-questionnaire-editor', compact(
            'types',
            'selectedType',
            'questions'
        ));
    }

    public function storeType(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:assessment_types,name'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ]);

        AssessmentType::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('assessment-questions.index')
            ->with('success', 'Assessment type added successfully.');
    }

    public function updateType(Request $request, AssessmentType $type)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:assessment_types,name,' . $type->id],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ]);

        $type->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('assessment-questions.index', ['type' => $type->id])
            ->with('success', 'Assessment type updated successfully.');
    }

    public function destroyType(AssessmentType $type)
    {
        $this->authorizeAdmin();

        $type->delete();

        return redirect()
            ->route('assessment-questions.index')
            ->with('success', 'Assessment type deleted successfully.');
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'assessment_type_id' => ['required', 'exists:assessment_types,id'],
            'question' => ['required', 'string'],
            'choice_a' => ['required', 'string', 'max:1000'],
            'choice_b' => ['required', 'string', 'max:1000'],
            'choice_c' => ['required', 'string', 'max:1000'],
            'choice_d' => ['required', 'string', 'max:1000'],
            'correct_answer' => ['required', 'integer', 'between:0,3'],
            'is_active' => ['nullable'],
        ]);

        $nextOrder = AssessmentQuestion::where('assessment_type_id', $validated['assessment_type_id'])
            ->max('sort_order');

        AssessmentQuestion::create([
            'assessment_type_id' => $validated['assessment_type_id'],
            'question' => $validated['question'],
            'choice_a' => $validated['choice_a'],
            'choice_b' => $validated['choice_b'],
            'choice_c' => $validated['choice_c'],
            'choice_d' => $validated['choice_d'],
            'correct_answer' => $validated['correct_answer'],
            'sort_order' => ((int) $nextOrder) + 1,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('assessment-questions.index', ['type' => $validated['assessment_type_id']])
            ->with('success', 'Question added successfully.');
    }

    public function update(Request $request, AssessmentQuestion $question)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'assessment_type_id' => ['required', 'exists:assessment_types,id'],
            'question' => ['required', 'string'],
            'choice_a' => ['required', 'string', 'max:1000'],
            'choice_b' => ['required', 'string', 'max:1000'],
            'choice_c' => ['required', 'string', 'max:1000'],
            'choice_d' => ['required', 'string', 'max:1000'],
            'correct_answer' => ['required', 'integer', 'between:0,3'],
            'is_active' => ['nullable'],
        ]);

        $oldTypeId = $question->assessment_type_id;
        $newTypeId = (int) $validated['assessment_type_id'];

        $sortOrder = $question->sort_order;

        if ((int) $oldTypeId !== $newTypeId) {
            $sortOrder = AssessmentQuestion::where('assessment_type_id', $newTypeId)->max('sort_order') + 1;
        }

        $question->update([
            'assessment_type_id' => $newTypeId,
            'question' => $validated['question'],
            'choice_a' => $validated['choice_a'],
            'choice_b' => $validated['choice_b'],
            'choice_c' => $validated['choice_c'],
            'choice_d' => $validated['choice_d'],
            'correct_answer' => $validated['correct_answer'],
            'sort_order' => $sortOrder,
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->normalizeQuestionOrder($oldTypeId);
        $this->normalizeQuestionOrder($newTypeId);

        return redirect()
            ->route('assessment-questions.index', ['type' => $newTypeId])
            ->with('success', 'Question updated successfully.');
    }

    public function destroy(AssessmentQuestion $question)
    {
        $this->authorizeAdmin();

        $typeId = $question->assessment_type_id;

        $question->delete();

        $this->normalizeQuestionOrder($typeId);

        return redirect()
            ->route('assessment-questions.index', ['type' => $typeId])
            ->with('success', 'Question deleted successfully.');
    }

    public function reorder(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'assessment_type_id' => ['required', 'exists:assessment_types,id'],
            'question_ids' => ['required', 'array'],
            'question_ids.*' => ['required', 'integer', 'exists:assessment_questions,id'],
        ]);

        foreach ($validated['question_ids'] as $index => $questionId) {
            AssessmentQuestion::where('id', $questionId)
                ->where('assessment_type_id', $validated['assessment_type_id'])
                ->update([
                    'sort_order' => $index + 1,
                ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Question order updated successfully.',
            ]);
        }

        return redirect()
            ->route('assessment-questions.index', ['type' => $validated['assessment_type_id']])
            ->with('success', 'Question order updated successfully.');
    }

    public function moveUp(AssessmentQuestion $question)
    {
        $this->authorizeAdmin();

        $previous = AssessmentQuestion::where('assessment_type_id', $question->assessment_type_id)
            ->where('sort_order', '<', $question->sort_order)
            ->orderByDesc('sort_order')
            ->first();

        if ($previous) {
            $currentOrder = $question->sort_order;

            $question->update([
                'sort_order' => $previous->sort_order,
            ]);

            $previous->update([
                'sort_order' => $currentOrder,
            ]);

            $this->normalizeQuestionOrder($question->assessment_type_id);
        }

        return redirect()
            ->route('assessment-questions.index', ['type' => $question->assessment_type_id])
            ->with('success', 'Question moved up successfully.');
    }

    public function moveDown(AssessmentQuestion $question)
    {
        $this->authorizeAdmin();

        $next = AssessmentQuestion::where('assessment_type_id', $question->assessment_type_id)
            ->where('sort_order', '>', $question->sort_order)
            ->orderBy('sort_order')
            ->first();

        if ($next) {
            $currentOrder = $question->sort_order;

            $question->update([
                'sort_order' => $next->sort_order,
            ]);

            $next->update([
                'sort_order' => $currentOrder,
            ]);

            $this->normalizeQuestionOrder($question->assessment_type_id);
        }

        return redirect()
            ->route('assessment-questions.index', ['type' => $question->assessment_type_id])
            ->with('success', 'Question moved down successfully.');
    }

    private function normalizeQuestionOrder($assessmentTypeId): void
    {
        $questions = AssessmentQuestion::where('assessment_type_id', $assessmentTypeId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($questions as $index => $question) {
            $question->update([
                'sort_order' => $index + 1,
            ]);
        }
    }
}
