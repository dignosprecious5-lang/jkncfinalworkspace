<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Visitor;
use App\Services\VisitorContactResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitorController extends Controller
{
    public function __construct(private readonly VisitorContactResolver $visitorContactResolver)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $visitors = Visitor::query()
            ->with('contact')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->search($search)
                        ->orWhereHas('contact', fn (Builder $contactQuery) => $this->applyContactSearch($contactQuery, $search));
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(fn (Visitor $visitor): array => $this->serializeVisitor($visitor));

        $contacts = $search === ''
            ? collect()
            : Contact::query()
                ->whereDoesntHave('visitor')
                ->where(fn (Builder $query) => $this->applyContactSearch($query, $search))
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->limit(50)
                ->get()
                ->map(fn (Contact $contact): array => $this->serializeContactSearchResult($contact));

        $results = $visitors
            ->concat($contacts)
            ->sortBy(fn (array $result): string => mb_strtolower((string) $result['name']))
            ->take(50)
            ->values();

        return response()->json(['visitors' => $results]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visitor_reference' => ['prohibited'],
            'visitor_id' => ['prohibited'],
            'name' => ['nullable', 'required_without:contact_id', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
        ]);

        if (isset($validated['contact_id'])) {
            $resolution = $this->visitorContactResolver->resolve((int) $validated['contact_id']);

            return response()->json(
                $this->serializeVisitor($resolution['visitor']),
                $resolution['created'] ? 201 : 200
            );
        }

        $visitor = Visitor::query()->create($validated)->load('contact');

        return response()->json($this->serializeVisitor($visitor), 201);
    }

    public function resolveContact(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
        ]);

        $resolution = $this->visitorContactResolver->resolve((int) $validated['contact_id']);

        return response()->json(
            $this->serializeVisitor($resolution['visitor']),
            $resolution['created'] ? 201 : 200
        );
    }

    public function serializeVisitor(Visitor $visitor): array
    {
        $contact = $visitor->relationLoaded('contact') ? $visitor->contact : null;

        return [
            'id' => $visitor->id,
            'source_type' => 'visitor',
            'visitor_reference' => $visitor->visitor_reference,
            'visitor_id' => $visitor->id,
            'name' => $visitor->name,
            'mobile' => $visitor->mobile,
            'email' => $visitor->email,
            'company' => $visitor->company_name,
            'company_name' => $visitor->company_name,
            'contact_id' => $visitor->contact_id,
            'cif_no' => $contact?->cif_no,
            'has_visitor' => true,
            'contact' => $contact ? [
                'id' => $contact->id,
                'name' => $contact->full_name ?: $contact->company_name,
                'company_name' => $contact->company_name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'cif_no' => $contact->cif_no,
            ] : null,
        ];
    }

    private function serializeContactSearchResult(Contact $contact): array
    {
        return [
            'id' => $contact->id,
            'source_type' => 'contact',
            'visitor_id' => null,
            'contact_id' => $contact->id,
            'name' => $contact->full_name ?: $contact->company_name,
            'visitor_reference' => null,
            'cif_no' => $contact->cif_no,
            'mobile' => $contact->phone,
            'email' => $contact->email,
            'company' => $contact->company_name,
            'company_name' => $contact->company_name,
            'has_visitor' => false,
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->full_name ?: $contact->company_name,
                'company_name' => $contact->company_name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'cif_no' => $contact->cif_no,
            ],
        ];
    }

    private function applyContactSearch(Builder $query, string $search): void
    {
        $term = '%'.addcslashes($search, '%_\\').'%';
        $nameTerms = collect(preg_split('/\s+/', trim($search)) ?: [])
            ->filter()
            ->map(fn (string $value): string => '%'.addcslashes($value, '%_\\').'%');

        $query->where(function (Builder $contactQuery) use ($term, $nameTerms): void {
            $contactQuery
                ->where('first_name', 'like', $term)
                ->orWhere('middle_initial', 'like', $term)
                ->orWhere('middle_name', 'like', $term)
                ->orWhere('last_name', 'like', $term)
                ->orWhere('name_extension', 'like', $term)
                ->orWhere('phone', 'like', $term)
                ->orWhere('email', 'like', $term)
                ->orWhere('company_name', 'like', $term)
                ->orWhere('cif_no', 'like', $term);

            if ($nameTerms->isNotEmpty()) {
                $contactQuery->orWhere(function (Builder $fullNameQuery) use ($nameTerms): void {
                    foreach ($nameTerms as $nameTerm) {
                        $fullNameQuery->where(function (Builder $namePartQuery) use ($nameTerm): void {
                            $namePartQuery
                                ->where('first_name', 'like', $nameTerm)
                                ->orWhere('middle_initial', 'like', $nameTerm)
                                ->orWhere('middle_name', 'like', $nameTerm)
                                ->orWhere('last_name', 'like', $nameTerm)
                                ->orWhere('name_extension', 'like', $nameTerm);
                        });
                    }
                });
            }
        });
    }
}
