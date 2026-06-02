@extends('layouts.app')

@section('title', 'Assessment Questionnaire Editor')

@section('content')
    <div class="min-h-screen bg-gray-50 p-6">
        <div class="max-w-7xl mx-auto space-y-6">

            {{-- Header --}}
            <div class="bg-white rounded-3xl shadow-sm border border-gray-200 p-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.25em] text-blue-600">
                            Recruitment
                        </p>
                        <h1 class="text-2xl font-black text-gray-900 mt-1">
                            Assessment Questionnaire Editor
                        </h1>
                        <p class="text-sm text-gray-500 mt-2">
                            Manage assessment types and questions used in the candidate assessment test.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="button"
                            onclick="openTypeModal()"
                            class="px-5 py-3 rounded-2xl bg-gray-900 hover:bg-gray-800 text-white text-xs font-black uppercase tracking-widest transition">
                            Add Assessment Type
                        </button>

                        @if($selectedType)
                            <button type="button"
                                onclick="openQuestionModal()"
                                class="px-5 py-3 rounded-2xl bg-blue-700 hover:bg-blue-800 text-white text-xs font-black uppercase tracking-widest transition">
                                Add Question
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Alerts --}}
            @if(session('success'))
                <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                    <p class="font-black mb-2">Please fix the following:</p>
                    <ul class="list-disc ml-5 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

                {{-- Left: Assessment Types --}}
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100">
                            <h2 class="font-black text-gray-900">Assessment Types</h2>
                            <p class="text-xs text-gray-500 mt-1">Select type to manage questions.</p>
                        </div>

                        <div class="p-3 space-y-2">
                            @forelse($types as $type)
                                <div class="rounded-2xl border {{ $selectedType && $selectedType->id === $type->id ? 'border-blue-200 bg-blue-50' : 'border-gray-100 bg-white' }}">
                                    <a href="{{ route('assessment-questions.index', ['type' => $type->id]) }}"
                                       class="block px-4 py-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="font-black text-sm {{ $selectedType && $selectedType->id === $type->id ? 'text-blue-700' : 'text-gray-900' }}">
                                                    {{ $type->name }}
                                                </p>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    {{ $type->questions->count() }} question(s)
                                                </p>
                                            </div>

                                            <span class="shrink-0 text-[10px] font-black px-2 py-1 rounded-full
                                                {{ $type->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                                {{ $type->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </a>

                                    <div class="px-4 pb-3 flex gap-2">
                                        <button type="button"
                                            onclick='openTypeModal(@json($type))'
                                            class="text-[11px] font-black uppercase tracking-widest text-blue-700 hover:text-blue-900">
                                            Edit
                                        </button>

                                        <form method="POST"
                                            action="{{ route('assessment-types.destroy', $type->id) }}"
                                            onsubmit="return confirm('Delete this assessment type? All questions under it will also be deleted.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-[11px] font-black uppercase tracking-widest text-red-600 hover:text-red-800">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-10">
                                    <p class="text-sm font-bold text-gray-500">No assessment types yet.</p>
                                    <button type="button"
                                        onclick="openTypeModal()"
                                        class="mt-4 px-4 py-2 rounded-xl bg-blue-700 text-white text-xs font-black uppercase tracking-widest">
                                        Add Type
                                    </button>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Right: Questions --}}
                <div class="lg:col-span-3">
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-100">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div>
                                    <h2 class="font-black text-gray-900">
                                        {{ $selectedType ? $selectedType->name : 'Questions' }}
                                    </h2>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Questions are shown to candidates based on their assessment type.
                                    </p>
                                </div>

                                @if($selectedType)
                                    <div class="flex gap-2">
                                        <button type="button"
                                            onclick='openTypeModal(@json($selectedType))'
                                            class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 text-xs font-black uppercase tracking-widest">
                                            Edit Type
                                        </button>

                                        <button type="button"
                                            onclick="openQuestionModal()"
                                            class="px-4 py-2 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs font-black uppercase tracking-widest">
                                            Add Question
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if(!$selectedType)
                            <div class="p-10 text-center">
                                <p class="text-gray-500 font-semibold">Please add or select an assessment type first.</p>
                            </div>
                        @else
                            <div class="p-6 space-y-4">
                                @forelse($questions as $question)
                                    <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
                                        <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-4">
                                            <div class="flex-1">
                                                <div class="flex flex-wrap items-center gap-2 mb-3">
                                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-blue-50 text-blue-700 text-xs font-black">
                                                        {{ $loop->iteration }}
                                                    </span>

                                                    <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full
                                                        {{ $question->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                                        {{ $question->is_active ? 'Active' : 'Inactive' }}
                                                    </span>

                                                    <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full bg-gray-100 text-gray-600">
                                                        Correct: {{ ['A', 'B', 'C', 'D'][$question->correct_answer] ?? 'N/A' }}
                                                    </span>
                                                </div>

                                                <h3 class="text-base font-black text-gray-900 leading-relaxed">
                                                    {{ $question->question }}
                                                </h3>

                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
                                                    @php
                                                        $choices = [
                                                            ['label' => 'A', 'text' => $question->choice_a, 'index' => 0],
                                                            ['label' => 'B', 'text' => $question->choice_b, 'index' => 1],
                                                            ['label' => 'C', 'text' => $question->choice_c, 'index' => 2],
                                                            ['label' => 'D', 'text' => $question->choice_d, 'index' => 3],
                                                        ];
                                                    @endphp

                                                    @foreach($choices as $choice)
                                                        <div class="rounded-2xl border p-3
                                                            {{ $question->correct_answer === $choice['index'] ? 'border-green-200 bg-green-50' : 'border-gray-100 bg-gray-50' }}">
                                                            <p class="text-xs font-black {{ $question->correct_answer === $choice['index'] ? 'text-green-700' : 'text-gray-500' }}">
                                                                {{ $choice['label'] }}
                                                            </p>
                                                            <p class="text-sm font-semibold text-gray-800 mt-1">
                                                                {{ $choice['text'] }}
                                                            </p>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <div class="flex xl:flex-col gap-2 shrink-0">
                                                <form method="POST" action="{{ route('assessment-questions.move-up', $question->id) }}">
                                                    @csrf
                                                    <button type="submit"
                                                        class="w-full px-3 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 text-xs font-black">
                                                        ↑
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('assessment-questions.move-down', $question->id) }}">
                                                    @csrf
                                                    <button type="submit"
                                                        class="w-full px-3 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 text-xs font-black">
                                                        ↓
                                                    </button>
                                                </form>

                                                <button type="button"
                                                    onclick='openQuestionModal(@json($question))'
                                                    class="px-4 py-2 rounded-xl bg-yellow-100 hover:bg-yellow-200 text-yellow-800 text-xs font-black uppercase tracking-widest">
                                                    Edit
                                                </button>

                                                <form method="POST"
                                                    action="{{ route('assessment-questions.destroy', $question->id) }}"
                                                    onsubmit="return confirm('Delete this question?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="w-full px-4 py-2 rounded-xl bg-red-100 hover:bg-red-200 text-red-700 text-xs font-black uppercase tracking-widest">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-14">
                                        <div class="w-16 h-16 rounded-3xl bg-blue-50 mx-auto flex items-center justify-center text-blue-700 font-black text-2xl">
                                            ?
                                        </div>
                                        <h3 class="font-black text-gray-900 mt-4">No questions yet</h3>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Add the first question for {{ $selectedType->name }}.
                                        </p>
                                        <button type="button"
                                            onclick="openQuestionModal()"
                                            class="mt-5 px-5 py-3 rounded-2xl bg-blue-700 hover:bg-blue-800 text-white text-xs font-black uppercase tracking-widest">
                                            Add Question
                                        </button>
                                    </div>
                                @endforelse
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Assessment Type Modal --}}
    <div id="typeModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.25em] text-blue-600">Assessment Type</p>
                    <h3 id="typeModalTitle" class="text-xl font-black text-gray-900 mt-1">Add Assessment Type</h3>
                </div>

                <button type="button"
                    onclick="closeTypeModal()"
                    class="w-10 h-10 rounded-2xl bg-gray-100 hover:bg-gray-200 text-gray-700 font-black">
                    ×
                </button>
            </div>

            <form id="typeForm" method="POST" action="{{ route('assessment-types.store') }}" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="_method" id="typeMethod" value="POST">

                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2">
                        Type Name
                    </label>
                    <input type="text" name="name" id="typeName"
                        class="w-full rounded-2xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Example: Technical Test" required>
                </div>

                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2">
                        Description
                    </label>
                    <textarea name="description" id="typeDescription" rows="3"
                        class="w-full rounded-2xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Optional description"></textarea>
                </div>

                <label class="flex items-center gap-3 rounded-2xl border border-gray-200 p-4 cursor-pointer">
                    <input type="checkbox" name="is_active" id="typeIsActive" value="1"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" checked>
                    <span class="text-sm font-bold text-gray-700">Active</span>
                </label>

                <div class="flex justify-end gap-3 pt-3">
                    <button type="button"
                        onclick="closeTypeModal()"
                        class="px-5 py-3 rounded-2xl border border-gray-200 text-gray-700 text-xs font-black uppercase tracking-widest">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-3 rounded-2xl bg-blue-700 hover:bg-blue-800 text-white text-xs font-black uppercase tracking-widest">
                        Save Type
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Question Modal --}}
    <div id="questionModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl overflow-hidden max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.25em] text-blue-600">Question</p>
                    <h3 id="questionModalTitle" class="text-xl font-black text-gray-900 mt-1">Add Question</h3>
                </div>

                <button type="button"
                    onclick="closeQuestionModal()"
                    class="w-10 h-10 rounded-2xl bg-gray-100 hover:bg-gray-200 text-gray-700 font-black">
                    ×
                </button>
            </div>

            @if($selectedType)
                <form id="questionForm" method="POST" action="{{ route('assessment-questions.store') }}" class="p-6 space-y-5">
                    @csrf
                    <input type="hidden" name="_method" id="questionMethod" value="POST">

                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2">
                            Assessment Type
                        </label>
                        <select name="assessment_type_id" id="questionTypeId"
                            class="w-full rounded-2xl border-gray-200 focus:border-blue-500 focus:ring-blue-500" required>
                            @foreach($types as $type)
                                <option value="{{ $type->id }}" {{ $selectedType && $selectedType->id === $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2">
                            Question
                        </label>
                        <textarea name="question" id="questionText" rows="3"
                            class="w-full rounded-2xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Enter question here..." required></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2">
                                Choice A
                            </label>
                            <input type="text" name="choice_a" id="choiceA"
                                class="w-full rounded-2xl border-gray-200 focus:border-blue-500 focus:ring-blue-500" required>
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2">
                                Choice B
                            </label>
                            <input type="text" name="choice_b" id="choiceB"
                                class="w-full rounded-2xl border-gray-200 focus:border-blue-500 focus:ring-blue-500" required>
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2">
                                Choice C
                            </label>
                            <input type="text" name="choice_c" id="choiceC"
                                class="w-full rounded-2xl border-gray-200 focus:border-blue-500 focus:ring-blue-500" required>
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2">
                                Choice D
                            </label>
                            <input type="text" name="choice_d" id="choiceD"
                                class="w-full rounded-2xl border-gray-200 focus:border-blue-500 focus:ring-blue-500" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2">
                            Correct Answer
                        </label>
                        <select name="correct_answer" id="correctAnswer"
                            class="w-full rounded-2xl border-gray-200 focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="0">A</option>
                            <option value="1">B</option>
                            <option value="2">C</option>
                            <option value="3">D</option>
                        </select>
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 p-4 cursor-pointer">
                        <input type="checkbox" name="is_active" id="questionIsActive" value="1"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" checked>
                        <span class="text-sm font-bold text-gray-700">Active</span>
                    </label>

                    <div class="flex justify-end gap-3 pt-3">
                        <button type="button"
                            onclick="closeQuestionModal()"
                            class="px-5 py-3 rounded-2xl border border-gray-200 text-gray-700 text-xs font-black uppercase tracking-widest">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-5 py-3 rounded-2xl bg-blue-700 hover:bg-blue-800 text-white text-xs font-black uppercase tracking-widest">
                            Save Question
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openTypeModal(type = null) {
            const modal = document.getElementById('typeModal');
            const form = document.getElementById('typeForm');
            const method = document.getElementById('typeMethod');
            const title = document.getElementById('typeModalTitle');

            const name = document.getElementById('typeName');
            const description = document.getElementById('typeDescription');
            const isActive = document.getElementById('typeIsActive');

            if (type) {
                title.textContent = 'Edit Assessment Type';
                form.action = "{{ url('/human-capital/recruitment/assessment-questionnaire-editor/types') }}/" + type.id;
                method.value = 'PUT';

                name.value = type.name ?? '';
                description.value = type.description ?? '';
                isActive.checked = !!type.is_active;
            } else {
                title.textContent = 'Add Assessment Type';
                form.action = "{{ route('assessment-types.store') }}";
                method.value = 'POST';

                name.value = '';
                description.value = '';
                isActive.checked = true;
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeTypeModal() {
            const modal = document.getElementById('typeModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function openQuestionModal(question = null) {
            const modal = document.getElementById('questionModal');

            if (!modal) return;

            const form = document.getElementById('questionForm');
            const method = document.getElementById('questionMethod');
            const title = document.getElementById('questionModalTitle');

            const typeId = document.getElementById('questionTypeId');
            const questionText = document.getElementById('questionText');
            const choiceA = document.getElementById('choiceA');
            const choiceB = document.getElementById('choiceB');
            const choiceC = document.getElementById('choiceC');
            const choiceD = document.getElementById('choiceD');
            const correctAnswer = document.getElementById('correctAnswer');
            const isActive = document.getElementById('questionIsActive');

            if (question) {
                title.textContent = 'Edit Question';
                form.action = "{{ url('/human-capital/recruitment/assessment-questionnaire-editor/questions') }}/" + question.id;
                method.value = 'PUT';

                typeId.value = question.assessment_type_id;
                questionText.value = question.question ?? '';
                choiceA.value = question.choice_a ?? '';
                choiceB.value = question.choice_b ?? '';
                choiceC.value = question.choice_c ?? '';
                choiceD.value = question.choice_d ?? '';
                correctAnswer.value = question.correct_answer ?? 0;
                isActive.checked = !!question.is_active;
            } else {
                title.textContent = 'Add Question';
                form.action = "{{ route('assessment-questions.store') }}";
                method.value = 'POST';

                typeId.value = "{{ $selectedType->id ?? '' }}";
                questionText.value = '';
                choiceA.value = '';
                choiceB.value = '';
                choiceC.value = '';
                choiceD.value = '';
                correctAnswer.value = 0;
                isActive.checked = true;
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeQuestionModal() {
            const modal = document.getElementById('questionModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeTypeModal();
                closeQuestionModal();
            }
        });
    </script>
@endpush
