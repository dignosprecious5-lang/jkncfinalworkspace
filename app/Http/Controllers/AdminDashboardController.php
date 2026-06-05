<?php

namespace App\Http\Controllers;

use App\Models\CatalogChangeRequest;
use App\Models\CompanyBif;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Product;
use App\Models\ProjectStart;
use App\Models\Service;
use App\Models\TownHallCommunication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminDashboardController extends Controller
{
    private const SECTION_TOWN_HALL = 'town-hall';
    private const SECTION_CONTACTS = 'contacts';
    private const SECTION_COMPANY = 'company';
    private const SECTION_DEALS = 'deals';
    private const SECTION_PROJECT = 'project';
    private const SECTION_REGULAR = 'regular';
    private const SECTION_SERVICES = 'services';
    private const SECTION_PRODUCTS = 'products';

    public function index(Request $request, ?string $section = null)
    {
        if (
            ! Auth::user()->hasPermission('access_admin_dashboard') &&
            ! Auth::user()->hasPermission('approve_townhall')
        ) {
            abort(403, 'Unauthorized');
        }

        $section = $section ?: self::SECTION_TOWN_HALL;
        $sectionConfig = $this->sectionConfigurations()[$section] ?? null;
        abort_unless($sectionConfig !== null, 404);

        $userNames = User::query()->pluck('name', 'id');
        $items = $this->sectionItems($section, $userNames);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'module' => (string) $request->query('module', 'all'),
            'department' => (string) $request->query('department', 'all'),
            'status' => (string) $request->query('status', 'all'),
        ];

        $filteredItems = $this->applyFilters($items, $filters)->values();

        $counts = [
            'pending' => $filteredItems->where('status', 'Pending Approval')->count(),
            'approved' => $filteredItems->where('status', 'Approved')->count(),
            'rejected' => $filteredItems->where('status', 'Rejected')->count(),
            'revision' => $filteredItems->where('status', 'Needs Revision')->count(),
            'expired' => $filteredItems->where('status', 'Expired')->count(),
        ];

        $perPage = 10;
        $currentPage = max((int) $request->query('page', 1), 1);
        $paginator = new LengthAwarePaginator(
            $filteredItems->forPage($currentPage, $perPage)->values(),
            $filteredItems->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('admin.admin-dashboard', [
            'items' => $paginator,
            'pendingCount' => $counts['pending'],
            'approvedCount' => $counts['approved'],
            'rejectedCount' => $counts['rejected'],
            'revisionCount' => $counts['revision'],
            'expiredCount' => $counts['expired'],
            'filters' => $filters,
            'moduleOptions' => $items->pluck('module')->filter()->unique()->sort()->values(),
            'departmentOptions' => $items->pluck('department')->filter()->unique()->sort()->values(),
            'statusOptions' => collect(['Pending Approval', 'Approved', 'Rejected', 'Needs Revision', 'Expired']),
            'dashboardRoute' => $section === self::SECTION_TOWN_HALL
                ? route('admin.dashboard')
                : route('admin.dashboard.section', ['section' => $section]),
            'pageTitle' => $sectionConfig['title'],
            'pageDescription' => $sectionConfig['description'],
            'currentSection' => $section,
        ]);
    }

    private function sectionConfigurations(): array
    {
        return [
            self::SECTION_TOWN_HALL => [
                'title' => 'Town Hall Approval Dashboard',
                'description' => 'Review Town Hall submissions and route approvals through admin.',
            ],
            self::SECTION_CONTACTS => [
                'title' => 'Contacts Approval Dashboard',
                'description' => 'Review CIF submissions and contact change requests awaiting admin approval.',
            ],
            self::SECTION_COMPANY => [
                'title' => 'Company Approval Dashboard',
                'description' => 'Review company KYC submissions and BIF change requests awaiting admin approval.',
            ],
            self::SECTION_DEALS => [
                'title' => 'Deals Approval Dashboard',
                'description' => 'Review deal qualification submissions and Closed Won START form approvals awaiting admin review.',
            ],
            self::SECTION_PROJECT => [
                'title' => 'Project Approval Dashboard',
                'description' => 'Review project START submissions awaiting admin approval.',
            ],
            self::SECTION_REGULAR => [
                'title' => 'Regular Approval Dashboard',
                'description' => 'Review regular engagement START submissions awaiting admin approval.',
            ],
            self::SECTION_SERVICES => [
                'title' => 'Services Approval Dashboard',
                'description' => 'Review service records and service catalog change requests awaiting admin approval.',
            ],
            self::SECTION_PRODUCTS => [
                'title' => 'Products Approval Dashboard',
                'description' => 'Review product records and product catalog change requests awaiting admin approval.',
            ],
        ];
    }

    private function sectionItems(string $section, Collection $userNames): Collection
    {
        return match ($section) {
            self::SECTION_TOWN_HALL => $this->townHallItems(),
            self::SECTION_CONTACTS => collect()
                ->merge($this->contactApprovalItems())
                ->merge($this->contactChangeRequestItems()),
            self::SECTION_COMPANY => collect()
                ->merge($this->companyApprovalItems($userNames))
                ->merge($this->companyChangeRequestItems($userNames)),
            self::SECTION_DEALS => collect()
                ->merge($this->dealApprovalItems())
                ->merge($this->dealDeletionApprovalItems($userNames))
                ->merge($this->dealStartApprovalItems())
                ->merge($this->dealProposalTemplateItems()),
            self::SECTION_PROJECT => $this->startApprovalItems(false),
            self::SECTION_REGULAR => $this->startApprovalItems(true),
            self::SECTION_SERVICES => collect()
                ->merge($this->serviceApprovalItems($userNames))
                ->merge($this->catalogChangeRequestItems('service')),
            self::SECTION_PRODUCTS => collect()
                ->merge($this->productApprovalItems($userNames))
                ->merge($this->catalogChangeRequestItems('product')),
            default => collect(),
        };
    }

    private function townHallItems(): Collection
    {
        return TownHallCommunication::query()
            ->with(['uploader', 'approver'])
            ->latest()
            ->get()
            ->map(function (TownHallCommunication $communication): object {
                $status = $communication->is_archived
                    ? 'Expired'
                    : $this->normalizeStatus((string) ($communication->approval_status ?? 'Pending'));

                return (object) [
                    'ref_no' => $communication->ref_no ?: 'TH-'.$communication->id,
                    'module' => 'Town Hall',
                    'file_name' => $communication->subject ?: 'No Subject',
                    'department' => $communication->department_stakeholder ?: 'Town Hall',
                    'uploaded_by' => $communication->from_name ?: ($communication->uploader->name ?? 'Unknown'),
                    'date_uploaded' => $this->displayDate($communication->communication_date ?: $communication->created_at),
                    'approver' => $communication->approver?->name ?: '-',
                    'priority' => $communication->priority ?? ($status === 'Pending Approval' ? 'High' : 'Low'),
                    'status' => $status,
                    'show_route' => route('townhall.show', $communication->id),
                    'approve_route' => ! $communication->is_archived && $status === 'Pending Approval' ? route('townhall.approve', $communication->id) : null,
                    'reject_route' => ! $communication->is_archived && $status === 'Pending Approval' ? route('townhall.reject', $communication->id) : null,
                    'revise_route' => ! $communication->is_archived && $status === 'Pending Approval' ? route('townhall.revise', $communication->id) : null,
                    'date_sort' => $this->sortTimestamp($communication->communication_date ?: $communication->created_at),
                ];
            });
    }

    private function contactApprovalItems(): Collection
    {
        return Contact::query()
            ->whereIn('cif_status', ['pending', 'approved', 'rejected'])
            ->latest('cif_submitted_at')
            ->latest('updated_at')
            ->get()
            ->map(function (Contact $contact): object {
                $fullName = trim(collect([
                    $contact->salutation,
                    $contact->first_name,
                    $contact->middle_name,
                    $contact->last_name,
                ])->filter()->implode(' '));

                return (object) [
                    'ref_no' => $contact->cif_no ?: 'CIF-'.$contact->id,
                    'module' => 'Contacts',
                    'file_name' => $fullName !== '' ? $fullName : 'Contact #'.$contact->id,
                    'department' => 'Contacts',
                    'uploaded_by' => $contact->created_by ?: ($contact->owner_name ?: 'Unknown'),
                    'date_uploaded' => $this->displayDate($contact->cif_submitted_at ?: $contact->updated_at),
                    'approver' => $contact->cif_reviewed_by ?: '-',
                    'priority' => strtolower((string) $contact->cif_status) === 'pending' ? 'High' : 'Low',
                    'status' => $this->normalizeStatusFromKey((string) $contact->cif_status),
                    'show_route' => route('contacts.show', ['contact' => $contact->id, 'tab' => 'kyc']),
                    'approve_route' => strtolower((string) $contact->cif_status) === 'pending' ? route('contacts.kyc.approve', $contact->id) : null,
                    'reject_route' => strtolower((string) $contact->cif_status) === 'pending' ? route('contacts.kyc.reject', $contact->id) : null,
                    'revise_route' => null,
                    'date_sort' => $this->sortTimestamp($contact->cif_submitted_at ?: $contact->updated_at),
                ];
            });
    }

    private function companyApprovalItems(Collection $userNames): Collection
    {
        return CompanyBif::query()
            ->with('company')
            ->whereIn('status', ['pending_approval', 'approved', 'rejected'])
            ->latest('submitted_at')
            ->latest('updated_at')
            ->get()
            ->map(function (CompanyBif $bif) use ($userNames): object {
                $statusKey = strtolower((string) $bif->status);

                return (object) [
                    'ref_no' => $bif->bif_no ?: 'BIF-'.$bif->id,
                    'module' => 'Company',
                    'file_name' => $bif->title ?: ($bif->business_name ?: 'Business Information Form'),
                    'department' => 'Corporate',
                    'uploaded_by' => $userNames->get((int) $bif->created_by, $bif->change_requested_by_name ?: 'Unknown'),
                    'date_uploaded' => $this->displayDate($bif->submitted_at ?: $bif->updated_at),
                    'approver' => $bif->approved_by_name ?: ($bif->rejected_by_name ?: '-'),
                    'priority' => $statusKey === 'pending_approval' ? 'High' : 'Low',
                    'status' => $this->normalizeStatusFromKey($statusKey),
                    'show_route' => route('company.kyc', ['company' => $bif->company_id, 'tab' => 'business-client-information']),
                    'approve_route' => $statusKey === 'pending_approval' ? route('company.kyc.approve', $bif->company_id) : null,
                    'reject_route' => $statusKey === 'pending_approval' ? route('company.kyc.reject', $bif->company_id) : null,
                    'revise_route' => null,
                    'date_sort' => $this->sortTimestamp($bif->submitted_at ?: $bif->updated_at),
                ];
            });
    }

    private function contactChangeRequestItems(): Collection
    {
        return Contact::query()
            ->where('cif_status', 'approved')
            ->latest('updated_at')
            ->get()
            ->map(function (Contact $contact): ?object {
                $cifData = $this->loadContactCifData($contact);
                $statusKey = strtolower(trim((string) ($cifData['change_request_status'] ?? '')));

                if (! in_array($statusKey, ['pending', 'approved', 'rejected'], true)) {
                    return null;
                }

                $fullName = trim(collect([
                    $contact->salutation,
                    $contact->first_name,
                    $contact->middle_name,
                    $contact->last_name,
                ])->filter()->implode(' '));

                $date = $statusKey === 'pending'
                    ? ($cifData['change_requested_at'] ?? null)
                    : ($cifData['change_reviewed_at'] ?? ($cifData['change_requested_at'] ?? null));

                return (object) [
                    'ref_no' => ($contact->cif_no ?: 'CIF-'.$contact->id).'-CR',
                    'module' => 'Contacts',
                    'file_name' => ($fullName !== '' ? $fullName : 'Contact #'.$contact->id).' Change Request',
                    'department' => 'Contacts',
                    'uploaded_by' => trim((string) ($cifData['change_requested_by'] ?? '')) !== '' ? (string) $cifData['change_requested_by'] : ($contact->created_by ?: ($contact->owner_name ?: 'Unknown')),
                    'date_uploaded' => $this->displayDate($date),
                    'approver' => trim((string) ($cifData['change_reviewed_by'] ?? '')) !== '' ? (string) $cifData['change_reviewed_by'] : '-',
                    'priority' => $statusKey === 'pending' ? 'High' : 'Low',
                    'status' => $this->normalizeStatusFromKey($statusKey),
                    'show_route' => route('contacts.show', ['contact' => $contact->id, 'tab' => 'kyc']),
                    'approve_route' => $statusKey === 'pending' ? route('contacts.kyc.change-request.approve', $contact->id) : null,
                    'reject_route' => $statusKey === 'pending' ? route('contacts.kyc.change-request.reject', $contact->id) : null,
                    'revise_route' => null,
                    'date_sort' => $this->sortTimestamp($date),
                ];
            })
            ->filter();
    }

    private function companyChangeRequestItems(Collection $userNames): Collection
    {
        return CompanyBif::query()
            ->with('company')
            ->whereIn('change_request_status', ['pending', 'approved', 'rejected'])
            ->latest('change_requested_at')
            ->latest('change_reviewed_at')
            ->get()
            ->map(function (CompanyBif $bif) use ($userNames): object {
                $statusKey = strtolower(trim((string) $bif->change_request_status));
                $date = $statusKey === 'pending' ? $bif->change_requested_at : ($bif->change_reviewed_at ?: $bif->change_requested_at);

                return (object) [
                    'ref_no' => ($bif->bif_no ?: 'BIF-'.$bif->id).'-CR',
                    'module' => 'Company',
                    'file_name' => ($bif->title ?: ($bif->business_name ?: 'Business Information Form')).' Change Request',
                    'department' => 'Corporate',
                    'uploaded_by' => $bif->change_requested_by_name ?: $userNames->get((int) $bif->created_by, 'Unknown'),
                    'date_uploaded' => $this->displayDate($date),
                    'approver' => $bif->change_reviewed_by_name ?: '-',
                    'priority' => $statusKey === 'pending' ? 'High' : 'Low',
                    'status' => $this->normalizeStatusFromKey($statusKey),
                    'show_route' => route('company.kyc', ['company' => $bif->company_id, 'tab' => 'business-client-information']),
                    'approve_route' => $statusKey === 'pending' ? route('company.bif.change-request.approve', ['company' => $bif->company_id, 'bif' => $bif->id]) : null,
                    'reject_route' => $statusKey === 'pending' ? route('company.bif.change-request.reject', ['company' => $bif->company_id, 'bif' => $bif->id]) : null,
                    'revise_route' => null,
                    'date_sort' => $this->sortTimestamp($date),
                ];
            });
    }

    private function dealApprovalItems(): Collection
    {
        return Deal::query()
            ->with('proposal')
            ->latest('updated_at')
            ->get()
            ->filter(function (Deal $deal): bool {
                $statusKey = strtolower((string) ($deal->deal_status ?? 'pending'));
                $stage = $deal->stage;
                $proposalStatus = strtolower((string) ($deal->proposal?->status ?? ''));

                return in_array($statusKey, ['pending', 'approved', 'rejected'], true)
                    || $stage === 'Qualification'
                    || ($stage === 'Proposal' && $deal->proposal && $proposalStatus !== 'approved');
            })
            ->map(function (Deal $deal): object {
                $statusKey = strtolower((string) ($deal->deal_status ?? 'pending'));
                $stage = $deal->stage;
                $proposalStatus = strtolower((string) ($deal->proposal?->status ?? ''));

                $isPendingCreation = $statusKey === 'pending';
                $isPendingQualification = $stage === 'Qualification';
                $isPendingProposal = $stage === 'Proposal' && $deal->proposal && $proposalStatus !== 'approved';

                $isActionable = $isPendingCreation || $isPendingQualification || $isPendingProposal;

                $approveRoute = null;
                $rejectRoute = null;
                $displayStatus = $this->normalizeStatusFromKey($statusKey);

                if ($isPendingCreation) {
                    $approveRoute = route('deals.approve', $deal->id);
                    $rejectRoute = route('deals.reject', $deal->id);
                } elseif ($isPendingQualification) {
                    $approveRoute = route('deals.internal-approve', $deal->id);
                    $rejectRoute = route('deals.reject', $deal->id);
                    $displayStatus = 'Pending Qualification';
                } elseif ($isPendingProposal) {
                    $approveRoute = route('deals.proposal.admin-approve', $deal->id);
                    $rejectRoute = route('deals.proposal.admin-reject', $deal->id);
                    $displayStatus = 'Pending Proposal Approval';
                }

                return (object) [
                    'ref_no' => $deal->deal_code ?: 'DEAL-'.$deal->id,
                    'module' => 'Deals',
                    'file_name' => $deal->deal_code ?: ($deal->deal_name ?: 'Deal #'.$deal->id),
                    'department' => 'Deals',
                    'uploaded_by' => $deal->created_by ?: 'Unknown',
                    'date_uploaded' => $this->displayDate($deal->created_at),
                    'approver' => $deal->approved_by_name ?: ($deal->rejected_by_name ?: '-'),
                    'priority' => $isActionable ? 'High' : 'Low',
                    'status' => $displayStatus,
                    'show_route' => route('deals.show', $deal->id),
                    'approve_route' => $approveRoute,
                    'reject_route' => $rejectRoute,
                    'revise_route' => null,
                    'date_sort' => $this->sortTimestamp($deal->updated_at ?: $deal->created_at),
                ];
            });
    }

    private function dealProposalTemplateItems(): Collection
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('form_templates')) {
            return collect();
        }

        return \App\Models\FormTemplate::query()
            ->where('type', 'deal_proposal_global_template')
            ->latest('updated_at')
            ->get()
            ->map(function ($template): object {
                $statusKey = strtolower((string) ($template->status ?? 'pending'));
                $isPending = $statusKey === 'pending';

                return (object) [
                    'ref_no' => 'TMPL-'.$template->id,
                    'module' => 'Deals',
                    'file_name' => $template->name ?: 'Proposal Global Template',
                    'department' => 'Deals',
                    'uploaded_by' => 'System',
                    'date_uploaded' => $this->displayDate($template->created_at),
                    'approver' => $template->reviewed_at ? 'Admin' : '-',
                    'priority' => $isPending ? 'High' : 'Low',
                    'status' => $this->normalizeStatusFromKey($statusKey),
                    'show_route' => null,
                    'approve_route' => $isPending ? route('admin.deal-proposal-templates.approve', $template->id) : null,
                    'reject_route' => $isPending ? route('admin.deal-proposal-templates.reject', $template->id) : null,
                    'revise_route' => null,
                    'date_sort' => $this->sortTimestamp($template->updated_at ?: $template->created_at),
                ];
            });
    }

    private function dealDeletionApprovalItems(Collection $userNames): Collection
    {
        return Deal::query()
            ->latest('delete_requested_at')
            ->get()
            ->filter(function (Deal $deal): bool {
                return in_array(strtolower((string) $deal->delete_request_status), ['pending', 'rejected'], true);
            })
            ->map(function (Deal $deal) use ($userNames): object {
                $statusKey = strtolower((string) $deal->delete_request_status);

                return (object) [
                    'ref_no' => ($deal->deal_code ?: 'DEAL-'.$deal->id).'-DEL',
                    'module' => 'Deals',
                    'file_name' => ($deal->deal_code ?: ($deal->deal_name ?: 'Deal #'.$deal->id)) . ' Deletion Request',
                    'department' => 'Deals',
                    'uploaded_by' => $userNames->get((int) $deal->delete_requested_by, 'Unknown'),
                    'date_uploaded' => $this->displayDate($deal->delete_requested_at),
                    'approver' => $statusKey === 'rejected' ? 'System' : '-',
                    'priority' => $statusKey === 'pending' ? 'High' : 'Low',
                    'status' => $this->normalizeStatusFromKey($statusKey),
                    'show_route' => route('deals.show', $deal->id),
                    'approve_route' => $statusKey === 'pending' ? route('deals.approve-deletion', $deal->id) : null,
                    'reject_route' => $statusKey === 'pending' ? route('deals.reject-deletion', $deal->id) : null,
                    'revise_route' => null,
                    'date_sort' => $this->sortTimestamp($deal->delete_requested_at),
                ];
            });
    }

    private function dealStartApprovalItems(): Collection
    {
        // Only show START forms for shell workspaces (is_shell = true).
        // Shell projects are created at Closed Won and stay here until the
        // START form is admin-approved, which then triggers service memo
        // generation and activates the full Project / Regular workspace.
        return ProjectStart::query()
            ->with(['project.deal', 'project.company', 'project.contact'])
            ->whereHas('project', function ($query): void {
                $query->whereNotNull('deal_id')
                    ->whereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.is_shell')), 'false') = 'true'");
            })
            ->latest('updated_at')
            ->get()
            ->groupBy('project_id')
            ->map(function (Collection $starts): ?ProjectStart {
                return $starts->sort(function ($left, $right) {
                    $rank = fn ($item) => match (strtolower((string) ($item->status ?? ''))) {
                        'approved' => 1,
                        'pending_approval' => 2,
                        'rejected' => 3,
                        default => 4,
                    };

                    $leftRank = $rank($left);
                    $rightRank = $rank($right);
                    if ($leftRank !== $rightRank) {
                        return $leftRank <=> $rightRank;
                    }

                    $leftTime = optional($left->updated_at)->getTimestamp() ?? 0;
                    $rightTime = optional($right->updated_at)->getTimestamp() ?? 0;
                    if ($leftTime !== $rightTime) {
                        return $rightTime <=> $leftTime;
                    }

                    return ((int) $right->id) <=> ((int) $left->id);
                })->first();
            })
            ->filter()
            ->filter(function (ProjectStart $start): bool {
                return in_array(strtolower((string) ($start->status ?? 'draft')), ['pending_approval', 'approved', 'rejected'], true);
            })
            ->map(function (ProjectStart $start): object {
                $statusKey = strtolower((string) ($start->status ?? 'draft'));
                $project = $start->project;
                $deal = $project?->deal;
                $contactName = trim(collect([$project?->contact?->first_name, $project?->contact?->last_name])->filter()->implode(' '))
                    ?: ($project?->client_name ?: ($deal?->first_name ? trim($deal->first_name.' '.($deal->last_name ?? '')) : 'Project #'.$start->project_id));
                $businessName = $project?->business_name
                    ?: ($project?->company?->company_name
                        ?: ($deal?->company_name
                            ?: ($deal?->deal_name ?: 'Deal')));

                return (object) [
                    'ref_no' => ($deal?->deal_code ?: 'DEAL-'.($project?->deal_id ?? $start->project_id)).'-START',
                    'module' => 'Deals',
                    'file_name' => $businessName.' - '.$contactName.' (START Form)',
                    'department' => 'Deals',
                    'uploaded_by' => $project?->assigned_consultant ?: ($deal?->assigned_consultant ?: 'Unknown'),
                    'date_uploaded' => $this->displayDate($start->updated_at ?: $start->created_at),
                    'approver' => $start->approved_by_name ?: ($start->rejected_by_name ?: '-'),
                    'priority' => $statusKey === 'pending_approval' ? 'High' : 'Low',
                    'status' => $this->normalizeStatusFromKey($statusKey),
                    'show_route' => route('deals.show', $project?->deal_id ?? 0),
                    'approve_route' => $statusKey === 'pending_approval' ? route('project.start.approve', $project) : null,
                    'reject_route' => $statusKey === 'pending_approval' ? route('project.start.reject', $project) : null,
                    'revise_route' => null,
                    'date_sort' => $this->sortTimestamp($start->updated_at ?: $start->created_at),
                ];
            });
    }

    private function startApprovalItems(?bool $regular = null): Collection
    {
        // Exclude shell workspaces — those belong to the Deals section until
        // the START form is approved, the service memo is generated, and the
        // full Project / Regular workspace is activated (is_shell becomes false).
        return ProjectStart::query()
            ->with(['project.deal', 'project.company', 'project.contact'])
            ->whereHas('project', function ($query): void {
                $query->whereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.is_shell')), 'false') != 'true'");
            })
            ->latest('updated_at')
            ->get()
            ->groupBy('project_id')
            ->map(function (Collection $starts): ?ProjectStart {
                return $starts->sort(function ($left, $right) {
                    $rank = fn ($item) => match (strtolower((string) ($item->status ?? ''))) {
                        'approved' => 1,
                        'pending_approval' => 2,
                        'rejected' => 3,
                        default => 4,
                    };

                    $leftRank = $rank($left);
                    $rightRank = $rank($right);
                    if ($leftRank !== $rightRank) {
                        return $leftRank <=> $rightRank;
                    }

                    $leftTime = optional($left->updated_at)->getTimestamp() ?? 0;
                    $rightTime = optional($right->updated_at)->getTimestamp() ?? 0;
                    if ($leftTime !== $rightTime) {
                        return $rightTime <=> $leftTime;
                    }

                    return ((int) $right->id) <=> ((int) $left->id);
                })->first();
            })
            ->filter()
            ->filter(function (ProjectStart $start): bool {
                return in_array(strtolower((string) ($start->status ?? 'draft')), ['pending_approval', 'approved', 'rejected'], true);
            })
            ->filter(function (ProjectStart $start) use ($regular): bool {
                if ($regular === null) {
                    return true;
                }

                return $this->isRegularEngagement($start->project?->engagement_type) === $regular;
            })
            ->map(function (ProjectStart $start): object {
                $statusKey = strtolower((string) ($start->status ?? 'draft'));
                $project = $start->project;
                $isRegular = $this->isRegularEngagement($project?->engagement_type);
                $contactName = trim(collect([$project?->contact?->first_name, $project?->contact?->last_name])->filter()->implode(' '))
                    ?: ($project?->client_name ?: 'Project #'.$start->project_id);
                $businessName = $project?->business_name ?: ($project?->company?->company_name ?: 'Project');

                return (object) [
                    'ref_no' => $start->start_code ?: 'START-'.$start->id,
                    'module' => $isRegular ? 'Regular' : 'Project',
                    'file_name' => $businessName.' - '.$contactName,
                    'department' => $isRegular ? 'Regular' : 'Projects',
                    'uploaded_by' => $project?->assigned_consultant ?: 'Unknown',
                    'date_uploaded' => $this->displayDate($start->updated_at ?: $start->created_at),
                    'approver' => $start->approved_by_name ?: ($start->rejected_by_name ?: '-'),
                    'priority' => $statusKey === 'pending_approval' ? 'High' : 'Low',
                    'status' => $this->normalizeStatusFromKey($statusKey),
                    'show_route' => route('project.show', ['project' => $project?->id, 'tab' => 'sow']),
                    'approve_route' => $statusKey === 'pending_approval' ? route('project.start.approve', $project) : null,
                    'reject_route' => $statusKey === 'pending_approval' ? route('project.start.reject', $project) : null,
                    'revise_route' => null,
                    'date_sort' => $this->sortTimestamp($start->updated_at ?: $start->created_at),
                ];
            });
    }

    private function serviceApprovalItems(Collection $userNames): Collection
    {
        return Service::query()
            ->latest('updated_at')
            ->get()
            ->filter(function (Service $service): bool {
                return in_array((string) $service->status, ['Pending Approval', 'Active', 'Rejected'], true);
            })
            ->map(function (Service $service) use ($userNames): object {
                $status = $service->status === 'Active' ? 'Approved' : $this->normalizeStatus((string) $service->status);

                return (object) [
                    'ref_no' => 'SRV-'.$service->id,
                    'module' => 'Services',
                    'file_name' => $service->service_name ?: 'Service #'.$service->id,
                    'department' => 'Services',
                    'uploaded_by' => $userNames->get((int) $service->created_by, $service->created_by ?: 'Unknown'),
                    'date_uploaded' => $this->displayDate($service->created_at),
                    'approver' => $userNames->get((int) $service->approved_by, '-'),
                    'priority' => $status === 'Pending Approval' ? 'High' : 'Low',
                    'status' => $status,
                    'show_route' => route('services.show', $service->id),
                    'approve_route' => $status === 'Pending Approval' ? route('services.approve', $service->id) : null,
                    'reject_route' => $status === 'Pending Approval' ? route('services.reject', $service->id) : null,
                    'revise_route' => null,
                    'date_sort' => $this->sortTimestamp($service->updated_at ?: $service->created_at),
                ];
            });
    }

    private function productApprovalItems(Collection $userNames): Collection
    {
        return Product::query()
            ->latest('updated_at')
            ->get()
            ->filter(function (Product $product): bool {
                return in_array((string) $product->status, ['Pending Approval', 'Active', 'Rejected'], true);
            })
            ->map(function (Product $product) use ($userNames): object {
                $status = $product->status === 'Active' ? 'Approved' : $this->normalizeStatus((string) $product->status);

                return (object) [
                    'ref_no' => $product->product_id ?: 'PRD-'.$product->id,
                    'module' => 'Products',
                    'file_name' => $product->product_name ?: 'Product #'.$product->id,
                    'department' => 'Products',
                    'uploaded_by' => $product->created_by ?: 'Unknown',
                    'date_uploaded' => $this->displayDate($product->created_at),
                    'approver' => $this->resolveReviewerName($product->approved_by, $userNames),
                    'priority' => $status === 'Pending Approval' ? 'High' : 'Low',
                    'status' => $status,
                    'show_route' => route('products.show', $product->product_id),
                    'approve_route' => $status === 'Pending Approval' ? route('products.approve', $product->product_id) : null,
                    'reject_route' => $status === 'Pending Approval' ? route('products.reject', $product->product_id) : null,
                    'revise_route' => null,
                    'date_sort' => $this->sortTimestamp($product->updated_at ?: $product->created_at),
                ];
            });
    }

    private function catalogChangeRequestItems(?string $moduleFilter = null): Collection
    {
        return CatalogChangeRequest::query()
            ->with(['submitter', 'reviewer'])
            ->latest('updated_at')
            ->get()
            ->filter(function (CatalogChangeRequest $request) use ($moduleFilter): bool {
                if (! in_array((string) $request->module, ['product', 'service'], true)) {
                    return false;
                }

                return $moduleFilter === null || (string) $request->module === $moduleFilter;
            })
            ->map(function (CatalogChangeRequest $changeRequest): object {
                $module = $changeRequest->module === 'product' ? 'Products' : 'Services';
                $status = $this->normalizeStatus((string) $changeRequest->status);

                return (object) [
                    'ref_no' => strtoupper((string) $changeRequest->module).'-CR-'.$changeRequest->id,
                    'module' => $module,
                    'file_name' => trim(($changeRequest->record_name ?: $module).' - '.ucfirst((string) $changeRequest->action).' request'),
                    'department' => $module,
                    'uploaded_by' => $changeRequest->submitter?->name ?: 'Unknown',
                    'date_uploaded' => $this->displayDate($changeRequest->updated_at),
                    'approver' => $changeRequest->reviewer?->name ?: '-',
                    'priority' => $status === 'Pending Approval' ? 'High' : 'Low',
                    'status' => $status,
                    'show_route' => $changeRequest->module === 'product'
                        ? route('products.show', $changeRequest->record_public_id)
                        : route('services.show', $changeRequest->record_id),
                    'approve_route' => $status === 'Pending Approval' ? route('catalog-change-requests.approve', $changeRequest) : null,
                    'reject_route' => $status === 'Pending Approval' ? route('catalog-change-requests.reject', $changeRequest) : null,
                    'revise_route' => null,
                    'date_sort' => $this->sortTimestamp($changeRequest->updated_at),
                ];
            });
    }

    private function isRegularEngagement(?string $engagementType): bool
    {
        return Str::contains(Str::lower(trim((string) $engagementType)), 'regular');
    }

    private function applyFilters(Collection $items, array $filters): Collection
    {
        $search = strtolower($filters['search']);
        $module = $filters['module'];
        $department = $filters['department'];
        $status = $filters['status'];

        return $items
            ->when($search !== '', function (Collection $collection) use ($search): Collection {
                return $collection->filter(function (object $item) use ($search): bool {
                    $haystack = strtolower(implode(' ', [
                        $item->ref_no,
                        $item->module,
                        $item->file_name,
                        $item->department,
                        $item->uploaded_by,
                        $item->approver,
                        $item->status,
                    ]));

                    return str_contains($haystack, $search);
                });
            })
            ->when($module !== 'all', fn (Collection $collection): Collection => $collection->where('module', $module))
            ->when($department !== 'all', fn (Collection $collection): Collection => $collection->where('department', $department))
            ->when($status !== 'all', fn (Collection $collection): Collection => $collection->where('status', $status))
            ->sortByDesc('date_sort');
    }

    private function normalizeStatus(string $status): string
    {
        $normalized = strtolower(trim($status));

        return match ($normalized) {
            'pending', 'pending approval', 'pending_approval' => 'Pending Approval',
            'approved', 'active' => 'Approved',
            'rejected' => 'Rejected',
            'needs revision', 'needs_revision' => 'Needs Revision',
            'expired', 'archived' => 'Expired',
            default => $status !== '' ? $status : 'Pending Approval',
        };
    }

    private function normalizeStatusFromKey(string $status): string
    {
        return match (strtolower(trim($status))) {
            'draft' => 'Needs Revision',
            'pending', 'pending approval', 'pending_approval' => 'Pending Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Pending Approval',
        };
    }

    private function loadContactCifData(Contact $contact): array
    {
        $path = 'contact-cif-data/'.$contact->id.'.json';

        if (! Storage::disk('local')->exists($path)) {
            return [];
        }

        return json_decode((string) Storage::disk('local')->get($path), true) ?: [];
    }

    private function resolveReviewerName(mixed $value, Collection $userNames): string
    {
        if (is_numeric($value) && $userNames->has((int) $value)) {
            return (string) $userNames->get((int) $value);
        }

        $resolved = trim((string) $value);

        return $resolved !== '' ? $resolved : '-';
    }

    private function displayDate(mixed $value): string
    {
        if (blank($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('M d, Y');
        } catch (\Throwable) {
            return '-';
        }
    }

    private function sortTimestamp(mixed $value): int
    {
        if (blank($value)) {
            return 0;
        }

        try {
            return Carbon::parse($value)->getTimestamp();
        } catch (\Throwable) {
            return 0;
        }
    }
}
