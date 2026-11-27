<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GeneratesPdfPreview;
use App\Models\Deal;
use App\Models\DealProposal;
use App\Models\DealStage;
use App\Models\Company;
use App\Models\FormTemplate;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Services\DealProposalAggregationService;
use App\Services\DealProposalTemplateService;
use App\Services\ProjectProvisioner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\View;

class DealProposalController extends Controller
{
    use GeneratesPdfPreview;

    public function __construct(
        private readonly DealProposalTemplateService $templateService,
        private readonly ProjectProvisioner $projectProvisioner,
    )
    {
    }

    private const COMPANY_PHONE = '0995-535-8729';
    private const COMPANY_EMAIL = 'start@jknc.io';
    private const COMPANY_WEBSITE = 'jknc.io';
    private const COMPANY_ADDRESS = '3F, Cebu Holdings Center, Cebu Business Park, Cebu City, Philippines 6000.';
    private const CLIENT_LINK_TTL_DAYS = 14;
    private const GLOBAL_TEMPLATE_TYPE = 'deal_proposal';

    private const EXECUTIVE_SUMMARY = [
        "John Kelly & Company is a management consulting and corporate advisory company that assists businesses in growing, improving how they operate, and making better decisions for the future. With over 30 years of combined experience across the public and private sectors, the company works closely with organizations to strengthen systems, improve management discipline, and support compliance and governance needs with clarity and professionalism. John Kelly & Company is supported by a multidisciplinary team with legal, financial, operational, and governance expertise, including Atty. Jose B. Ogang, CPA, MMPSM, former Mediator-Arbiter of the Department of Labor and Employment (DOLE); Jose Tomayo Rio, MM-BA, CPA, former Municipal Accountant of LGU Madridejos, Cebu; Lyndon Earl P. Rio, RN, CB, with extensive experience as an accountant and bookkeeper across various industries; and John Kelly Abalde, CLSSBB, CPM, a corporate secretary and board director serving organizations across multiple industries-working together to support clients in managing their businesses effectively and sustainably.",
        "Our vision is to build a world where people, systems, and ideas work together where management is disciplined, individuals are empowered, and progress is shared. By 2030, we aim to be a global example of how well-managed and forward-thinking businesses can create lasting, positive change.",
        "Our mission is simple: to build future-ready businesses today. We do this by helping leaders and teams become more organized, capable, and confident - creating strong foundations that make growth sustainable, innovation achievable, and success possible for everyone involved.",
    ];

    private const ROLE_AND_VALUE = [
        "As your trusted partner, John Kelly & Company is here to guide and support you in keeping your business well-managed, transparent, and compliant. We believe that good management is not just about following rules, it's about building trust, responsibility, and confidence in how a business is run.",
        'You can count on us to handle the details carefully while keeping communication open and simple. We work closely with you to make sure every step is clear and well-coordinated. Our goal is to help your business stay organized, compliant, and ready to grow with the assurance that everything is managed properly and with integrity.',
    ];

    private const WHY_PARTNER = [
        "At John Kelly & Company, we believe in helping businesses grow with clarity, honesty, and purpose. Over the years, we've worked closely with trusted mentors, lawyers, and certified public accountants who share our goal of helping businesses run better and stronger. Through these partnerships and our hands-on approach, we continue to guide companies toward steady growth and lasting success.",
        'Our range of services is designed to help businesses stay organized, compliant, and ready for growth through practical guidance, reliable support, and professional care which include the following areas of support.',
    ];

    private const SERVICE_AREAS = [
        [
            'no' => '1',
            'service_area' => 'Corporate & Regulatory Advisory',
            'scope' => [
                'Business Registration and Licensing (SEC, DTI, BIR, LGU)',
                'Corporate Secretary Outsourcing and Board Documentation',
                'Corporate Housekeeping (GIS, AFS, Resolutions, Minutes)',
                'Regulatory Compliance Review and Legal Due Diligence',
                'Ownership and Asset Transfers (Shares, Land Titles, Vehicles, Business Assets)',
                'DOLE Representation and Labor Compliance Support',
                'Government Accreditation and Permit Coordination (PEZA, BOI, DOST, CDA, DTI Export Bureau)',
                'Tax Incentives and Special Law Applications (CREATE Act and related fiscal incentives)',
                'Intellectual Property Registration (Trademark, Copyright, Patent - IPOPhil)',
                'Private and Government Grant Applications (DOST, DTI, NEDA, NICP, and related programs)',
                'Foreign Business Entry and Establishment Advisory',
            ],
        ],
        [
            'no' => '2',
            'service_area' => 'Accounting & Compliance Advisory',
            'scope' => [
                'Bookkeeping and General Accounting',
                'Financial Statement Preparation and Management Reporting',
                'Tax Preparation, Filing, and Advisory (VAT, Withholding, Income Tax)',
                'Payroll Processing and Statutory Remittances (SSS, PhilHealth, Pag-IBIG)',
                'Internal Audit Assistance and Financial Control Review',
                'Accounting System Setup and Implementation',
                'Audit Coordination and Compliance Documentation',
            ],
        ],
        [
            'no' => '3',
            'service_area' => 'Governance & Policy Advisory',
            'scope' => [
                'HR, Finance, Sales, and Administrative Policy Development',
                'Policy Audit and Internal Control Evaluation',
                'Corporate Governance Frameworks and Board or Committee Charters',
                'Risk and Compliance Manual Creation',
                'Organizational Structuring and Role Definition',
                'Business Process Optimization and Systems Efficiency Consulting',
                'Process Mapping and Workflow Standardization',
                'Governance Integration for Foreign-Linked Entities',
                'Policy Implementation and Compliance Monitoring',
            ],
        ],
        [
            'no' => '4',
            'service_area' => 'People & Talent Solutions',
            'scope' => [
                'Staffing for Accounting, HR, and Administrative Roles',
                'Executive Outsourcing (CFO-for-Hire, COO-for-Hire, Corporate Secretary-for-Hire)',
                'HR and Payroll Administration Services',
                'Managed Business Support Teams (Local and International)',
                'Remote Workforce Oversight and Productivity Reporting',
                'Cross-Border HR, Finance, and Administrative Outsourcing',
                'Data Privacy, Confidentiality, and Compliance Monitoring',
            ],
        ],
        [
            'no' => '5',
            'service_area' => 'Learning & Capability Development',
            'scope' => [
                'Lean Six Sigma Certification (White, Yellow, Green Belt)',
                'Corporate Governance and Revised Corporation Code Training',
                'Taxation, Payroll, and Compliance Workshops',
                'Leadership and Management Development Programs',
                'Business Process Improvement and Productivity Seminars',
            ],
        ],
    ];

    private const HIGHLIGHTS = [
        [
            'title' => 'Guided Support',
            'body' => "We're here to help your business stay on track by providing steady guidance and keeping things well-prepared and easy to follow.",
        ],
        [
            'title' => 'Personalized Approach',
            'body' => 'Every business is different, so we take the time to understand your needs and adjust our process to fit what works best for you.',
        ],
        [
            'title' => 'Open Communication',
            'body' => 'We keep you informed at all times so decisions are clear, updates are timely, and everyone moves forward together with confidence.',
        ],
        [
            'title' => 'Integrity and Care',
            'body' => 'We value honesty, respect, and responsibility in everything we do - ensuring that our work always reflects the trust you place in us.',
        ],
    ];

    private const COMMITMENT = [
        'Every business we work with deserves clarity, respect, and dependable support. That is why we make it our promise to handle every task with honesty, care, and attention to detail. We understand that behind every document and process are people working hard to build something meaningful and we aim to make their work easier, more organized, and more secure.',
        'Our approach is guided by consistency and genuine partnership. Over the years, we have worked hand in hand with trusted mentors, lawyers, and accountants who share our vision of helping businesses grow the right way - with discipline, transparency, and integrity at every step.',
    ];

    private const AGREEMENT_INCLUSIONS = [
        'Permit facilitation services performed strictly within the scope of this Agreement and solely for its implementation, including lawful coordination, submission, and follow-up with the government offices covered by and coordinated under this Agreement.',
        'Printing, photocopying, mailing, and preparation of basic documentation as may be reasonably required within the scope of this Agreement for its proper performance and execution in relation to the government offices coordinated with.',
        'Limited transportation (fuel) expenses necessarily and actually incurred within the scope of this Agreement, exclusively for permit-related acts undertaken pursuant hereto in connection with the government offices coordinated with, and confined to the applicable jurisdiction.',
    ];

    private const AGREEMENT_EXCLUSIONS = [
        'Transportation and accommodation expenses for off-site work.',
        'Government permit, license, and filing fees, including fees imposed by national and local government agencies.',
        'Notarization fees, penalties, surcharges, or late fees, if any.',
        'Costs of physical certificates, official forms, and certificate paper, when required.',
        'Third-party service fees, including courier services, document authentication, translation, printing, or similar incidental expenses.',
        'Rush processing fees or special handling requests required to meet accelerated timelines.',
        'Other incidental matters, such as document authentication, translation, rush requests, third-party service fees, etc., that may be required to complete a task or filing. Additional similar expenses not listed but reasonably necessary for task completion may also apply as agreed upon by both parties.',
    ];

    private const SUPPLEMENTAL_FEES = [
        ['service' => 'Printing of Client Document', 'description' => 'Printing of any client-provided document on A4, Legal, or Short Bond paper.', 'fee' => 'P5 per page'],
        ['service' => 'Archive Fee', 'description' => 'Fee for retrieving a document from archives and reissuing it to clients or stakeholders.', 'fee' => 'P50 per document'],
        ['service' => 'Delivery Fee (Metro Cebu)', 'description' => 'Delivery from JK&C office to a designated drop-off point within Metro Cebu.', 'fee' => 'P250 per drop-off'],
        ['service' => 'Photocopy (A4, Legal, Short Bond Paper)', 'description' => 'Photocopying of documents in A4, Legal, or Short Bond paper.', 'fee' => 'P5 per page'],
        ['service' => 'Digital Archive Copy', 'description' => 'Providing a digital copy of a document from the archive, sent to the client digitally.', 'fee' => 'P50 per document'],
        ['service' => 'Notarization of Simple Documents', 'description' => 'Notarization of simple documents, as defined by the Integrated Bar of the Philippines (IBP) - those that are single-party or routine in nature, not involving property rights or financial obligations.', 'fee' => 'P800 per document'],
        ['service' => 'Notarization of Complex Documents', 'description' => "Notarization of complex or non-simple documents, which involve property transfers, corporate acts, financial transactions, or multiple parties. Price varies depending on the lawyer's evaluation and complexity of verification required.", 'fee' => 'Subject to Evaluation'],
    ];

    private const TERMS_AND_CONDITIONS = [
        [
            'title' => 'Acceptance and Commencement',
            'items' => [
                "Work will commence upon the client's formal acceptance of this proposal and in accordance with the payment terms agreed by both parties.",
                'Any payment milestones, due dates, and balances will follow the payment terms stated in this proposal or otherwise agreed in writing.',
            ],
        ],
        [
            'title' => "Client's Responsibilities",
            'intro' => 'To ensure a smooth and timely process, the client is expected to:',
            'items' => [
                'Provide accurate, complete, and timely information, documents, and approvals as may be required by John Kelly & Company and the relevant city or municipal government.',
                'Complete and submit all documents required by the city or municipality where the business operates, in accordance with applicable local regulations.',
                'Review, confirm, and provide feedback on drafts or documents prior to signing or submission.',
                'Notify John Kelly & Company in advance of any meetings, inspections, or activities that require our attendance, coordination, or documentation.',
                'Settle all agreed professional fees, government charges, and reimbursable expenses within three (3) calendar days from billing or written notice.',
            ],
        ],
        [
            'title' => "John Kelly & Company's Responsibilities",
            'intro' => 'In return, John Kelly & Company will:',
            'items' => [
                'Perform all agreed tasks with care, honesty, and professionalism, in accordance with the Scope of Service/Assistance outlined in this proposal.',
                "Review available documents and identify applicable requirements based on the client's business type and location.",
                'Prepare, organize, and assist in completing required forms and supporting documents within the agreed scope.',
                'Coordinate and follow up with the relevant government offices, as authorized by the client.',
                'Provide updates on progress and inform the client of material developments or requirements.',
                'Keep accurate, organized, and secure records of all documents prepared, submitted, or received in connection with the engagement.',
            ],
        ],
        [
            'title' => 'Client Compliance, Regulatory Delays, and Immediate Demandability',
            'paragraphs' => [
                'The client acknowledges that the processing, approval, or issuance of BIR registrations, filings, clearances, or related documents is subject to compliance with requirements imposed by the Bureau of Internal Revenue (BIR) and other relevant government agencies, as applicable. Such requirements may include, but are not limited to, the submission of prescribed forms, supporting documents, tax filings, payments, clarifications, or responses to official notices, as determined by the concerned authorities.',
                "John Kelly & Company's engagement is strictly limited to assistance, coordination, documentation preparation, submission, and follow-up within the scope of this proposal. John Kelly & Company does not guarantee approval, acceptance, processing timelines, assessments, or outcomes, all of which are determined solely by the BIR and other relevant government agencies.",
                "Any delay, suspension, or non-issuance of registrations, filings, or related documents arising from the client's failure, refusal, or inability to comply with requirements imposed by the BIR or other relevant government agencies-including incomplete submissions, unresolved findings, unpaid taxes or penalties, or failure to act on official directives-shall not be attributable to John Kelly & Company.",
                "Where the client fails or refuses to comply with applicable requirements, or otherwise prevents the completion of the process, John Kelly & Company shall be deemed to have performed its obligations to the extent permitted by the client's cooperation and the agreed scope of service.",
                'In such cases, professional fees already incurred, together with any outstanding balances and reimbursable expenses, shall remain payable in accordance with the agreed terms of this engagement.',
            ],
        ],
        [
            'title' => 'No Responsibility for Government Assessment or Financial Declarations',
            'paragraphs' => [
                "The client acknowledges that all sales figures, income, expenses, declarations, and financial information submitted to the city or municipality for purposes of assessment, taxation, or permit computation are provided by the client or derived from the client's records.",
                "John Kelly & Company does not prepare, alter, underdeclare, overdeclare, adjust, or manipulate any figures relating to sales, income, expenses, or other financial data for local government assessment purposes. The company relies solely on information furnished by the client and submits such information as provided.",
                'All assessments, tax computations, fees, penalties, or charges imposed by the city or municipality are determined exclusively by the relevant government authorities. John Kelly & Company has no control over, and shall not be held responsible for, the amount assessed, billed, or required by any local government unit.',
                "Any liability arising from inaccurate, incomplete, or false information provided by the client-including penalties, surcharges, or delays-shall be the sole responsibility of the client, and shall not be attributed to John Kelly & Company.",
            ],
        ],
        [
            'title' => 'Release, Waiver, and Quitclaim',
            'paragraphs' => [
                "The client expressly acknowledges that John Kelly & Company is engaged solely to provide assistance, coordination, documentation, and advisory support within the scope of this proposal and is not involved in the actual operation, management, or day-to-day conduct of the client's business.",
                'To the fullest extent permitted by law, the client fully releases, waives, and forever discharges John Kelly & Company, including its stockholders, directors, officers, managers, consultants, employees, suppliers, agents, and any related stakeholders, from any and all claims, demands, actions, liabilities, losses, damages, penalties, fines, costs, or expenses of whatever kind or nature, whether known or unknown, that may arise from or relate to:',
            ],
            'items' => [
                'Any act, omission, fault, negligence, or non-compliance of the client.',
                'Any assessment, penalty, surcharge, fine, sanction, or enforcement action imposed by the city, municipality, or any government authority.',
                "Any findings, violations, deficiencies, or issues discovered during inspections or evaluations by government agencies.",
                "Any operational, financial, regulatory, health, safety, environmental, fire, building, water testing, HVAC, or similar compliance matters outside the scope of John Kelly & Company's engagement.",
            ],
            'outro' => "The client further acknowledges that any penalties, charges, or adverse findings imposed by the city or municipality are the result of the client's operations, records, declarations, or compliance status, and shall not be attributed to John Kelly & Company. The client hereby executes this release, waiver, and quitclaim voluntarily and with full understanding, and agrees that it shall not file, cause to be filed, or pursue any claim, complaint, action, or proceeding against John Kelly & Company or any of its covered persons arising from the matters described above. This release and waiver shall survive the completion or termination of this engagement and shall remain fully binding upon the client.",
        ],
        [
            'title' => 'Communication and Coordination',
            'paragraphs' => [
                "All communication will go through the client's designated representative.",
                'Coordination will be done through official email or any agreed channels to ensure proper documentation and accountability.',
            ],
        ],
        [
            'title' => 'Severability',
            'paragraphs' => [
                'If any provision of this proposal or agreement is declared invalid, illegal, or unenforceable by a court or competent authority, such provision shall be severed from the agreement and shall not affect the validity, legality, or enforceability of the remaining provisions.',
                'All other provisions shall continue to remain in full force and effect, as if the invalid or unenforceable provision had never been included.',
            ],
        ],
        [
            'title' => 'Termination',
            'paragraphs' => [
                'Either party may end the engagement with thirty (30) days written notice, provided that all pending obligations are settled.',
            ],
        ],
        [
            'title' => 'No Employer-Employee Relationship',
            'paragraphs' => [
                'This engagement does not create an employer-employee, principal-agent, partnership, or joint venture relationship between the client and John Kelly & Company, or any of its consultants, officers, employees, or representatives.',
                'John Kelly & Company acts solely as an independent service provider, and nothing in this proposal or engagement shall be construed to give either party authority to bind the other. Each party shall remain fully responsible for its own personnel, obligations, liabilities, taxes, and statutory compliance.',
            ],
        ],
        [
            'title' => 'Confidentiality',
            'paragraphs' => [
                'All information and documents shared during the engagement will be kept strictly confidential and used only for legitimate business purposes, unless disclosure is required by law or approved by the client.',
            ],
        ],
        [
            'title' => 'Governing Law and Venue',
            'paragraphs' => [
                'This agreement shall follow the laws of the Republic of the Philippines. Any disputes shall be resolved through good faith discussion, and if necessary, submitted to the proper courts of Cebu City.',
            ],
        ],
    ];

    private const ENGAGEMENT_TEAM = [
        ['name' => 'Mr. John Kelly D. Abalde', 'designation' => 'Senior Consultant', 'branch' => 'Cebu City HQ Branch', 'email' => 'john.abalde@jknc.io'],
        ['name' => 'Mr. Lyndon Earl Rio', 'designation' => 'Senior Consultant', 'branch' => 'Cebu City HQ Branch', 'email' => 'l.rio@jknc.io'],
        ['name' => 'Ms. Ma. Lourdes T. Mata', 'designation' => 'Associate', 'branch' => 'Cebu City HQ Branch', 'email' => 'm.mata@jknc.io'],
        ['name' => 'Ms. Rubeca Potayre', 'designation' => 'Associate', 'branch' => 'Cebu City HQ Branch', 'email' => 'r.potayre@jknc.io'],
        ['name' => 'Ms. Immaculate Espina', 'designation' => 'Associate', 'branch' => 'Lapu-Lapu Branch', 'email' => ''],
        ['name' => 'Ms. Carmela Ortiz', 'designation' => 'Associate', 'branch' => 'Cebu City HQ Branch', 'email' => ''],
    ];

    public function show(Deal $deal): View
    {
        $deal->loadMissing('contact', 'proposal');

        $versionId = request()->integer('proposal_version');
        $proposal = $versionId ? $deal->proposals()->findOrFail($versionId) : $deal->proposal;
        $readOnlyPreview = $proposal
            && ($proposal->client_form_sent_at || $proposal->client_submitted_at || strtolower((string) $proposal->status) === 'approved' || filled($proposal->client_approved_at));

        $proposal ??= new DealProposal($this->defaultProposalPayload($deal));

        return $this->renderProposalPage($deal, $proposal, (bool) $readOnlyPreview);
    }

    public function previewPage(Deal $deal): View
    {
        $deal->loadMissing('contact', 'proposal');

        $proposal = $deal->proposal ?: new DealProposal($this->defaultProposalPayload($deal));

        return $this->renderProposalPage($deal, $proposal, true);
    }

    private function renderProposalPage(Deal $deal, DealProposal $proposal, bool $readOnlyPreview): View
    {
        if ($proposal->exists) {
            $proposal->refresh();
        }

        $documentData = $this->documentData($deal, $proposal);
        $requirementGroup = $this->selectedRequirementGroup($deal);
        $baseName = Str::slug((string) ($deal->deal_code ?: 'deal-proposal')).'-proposal.docx';
        $generatedPdfPath = $this->generateProposalPdf($documentData, $baseName);
        $previewError = null;

        return view('deals.proposal.show', [
            'deal' => $deal,
            'proposal' => $proposal,
            'documentData' => $documentData,
            'proposalDocumentHtml' => $this->resolveProposalDocumentHtml($documentData, $proposal->document_html, false),
            'pendingGlobalTemplate' => $this->pendingGlobalProposalTemplate(),
            'generatedPdfUrl' => $generatedPdfPath ? route('uploads.show', ['path' => $generatedPdfPath]) : null,
            'generatedPdfDownloadUrl' => $proposal->exists ? route('deals.proposal.download', $deal) : ($generatedPdfPath ? route('uploads.show', ['path' => $generatedPdfPath, 'download' => 1]) : null),
            'requirementGroup' => $requirementGroup,
            'previewError' => $previewError,
            'readOnlyPreview' => $readOnlyPreview,
        ]);
    }

    public function update(Request $request, Deal $deal): RedirectResponse
    {
        $deal->loadMissing('contact', 'proposal');
        $proposal = $deal->proposal ?: DealProposal::create($this->defaultProposalPayload($deal));

        abort_if($proposal->client_form_sent_at || $proposal->client_submitted_at, 409, 'Use Create Revision to edit an issued proposal.');

        $validated = $this->validatedPayload($request);

        $proposal->fill($validated);
        $proposal->status = $proposal->status === 'approved' ? 'approved' : 'saved';
        $proposal->save();

        if ($request->boolean('submit_global_template')) {
            $this->storePendingGlobalProposalTemplate($this->documentData($deal, $proposal), (string) ($validated['document_html'] ?? ''));

            return redirect()
                ->route('deals.proposal.show', $deal)
                ->with('success', 'Saved this proposal and submitted the edited preview as a global proposal template request for admin approval.');
        }

        return redirect()
            ->route('deals.proposal.show', $deal)
            ->with('success', 'Saved current proposal changes. The downloadable PDF now uses these saved details.');
    }

    public function proposalTemplatesIndex(): View
    {
        $this->abortUnlessAdminReviewer();
        abort_if(! Schema::hasTable('form_templates'), 500, 'Templates table is not available.');

        $templates = FormTemplate::query()
            ->where('type', self::GLOBAL_TEMPLATE_TYPE)
            ->when(Schema::hasColumn('form_templates', 'status'), fn ($query) => $query->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 WHEN 'rejected' THEN 2 ELSE 3 END"))
            ->latest()
            ->get();

        return view('admin.deal-proposal-templates', [
            'templates' => $templates,
        ]);
    }

    public function approveProposalTemplate(FormTemplate $formTemplate): RedirectResponse
    {
        $this->abortUnlessAdminReviewer();
        abort_unless($formTemplate->type === self::GLOBAL_TEMPLATE_TYPE, 404);

        if (Schema::hasColumn('form_templates', 'status')) {
            FormTemplate::query()
                ->where('type', self::GLOBAL_TEMPLATE_TYPE)
                ->where('id', '!=', $formTemplate->id)
                ->where('status', 'approved')
                ->update(['status' => 'superseded']);

            $formTemplate->forceFill([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_note' => null,
            ])->save();
        }

        return redirect()
            ->route('admin.dashboard.section', 'deals')
            ->with('success', 'Global proposal template approved. New deal proposals will use it.');
    }

    public function rejectProposalTemplate(Request $request, FormTemplate $formTemplate): RedirectResponse
    {
        $this->abortUnlessAdminReviewer();
        abort_unless($formTemplate->type === self::GLOBAL_TEMPLATE_TYPE, 404);

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if (Schema::hasColumn('form_templates', 'status')) {
            $formTemplate->forceFill([
                'status' => 'rejected',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_note' => $validated['review_note'] ?? null,
            ])->save();
        }

        return redirect()
            ->route('admin.dashboard.section', 'deals')
            ->with('success', 'Global proposal template request rejected.');
    }

    public function download(Deal $deal): \Symfony\Component\HttpFoundation\Response
    {
        $deal->loadMissing('contact', 'proposal');
        $proposal = $deal->proposal ?: DealProposal::create($this->defaultProposalPayload($deal));

        return $this->proposalPdfDownloadResponse($deal, $proposal);
    }

    public function sendClientProposal(Request $request, Deal $deal): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor && ! $actor->isClient() && ! $actor->isDisabled() && ! $actor->isArchived(), 403);
        $deal->loadMissing('contact', 'proposal');

        $validated = $request->validate([
            'recipient_email' => ['required', 'email', 'max:255'],
        ]);

        $proposal = $deal->proposal ?: DealProposal::create($this->defaultProposalPayload($deal));
        abort_if($proposal->client_submitted_at || $proposal->itemResponses()->exists() || ($proposal->client_form_sent_at && in_array(strtolower((string) $proposal->status), ['approved','rejected','partially_approved'], true)), 409, 'Create a new revision before resending a proposal with client decisions.');
        if (Schema::hasColumn('deal_proposals', 'handler_user_id') && ! $proposal->handler_user_id) {
            $proposal->forceFill(['handler_user_id'=>$actor->id, 'handler_name'=>$actor->name, 'handler_email'=>$actor->email]);
        }
        if (Schema::hasColumn('deal_proposals', 'client_decision_status')) $proposal->client_decision_status = 'pending_client_review';
        $token = $this->ensureClientAccessToken($proposal);
        $expiresAt = $proposal->client_access_expires_at ?: now()->addDays(self::CLIENT_LINK_TTL_DAYS);
        $recipientEmail = trim((string) $validated['recipient_email']);

        $currentCommercialTotal = (float) ($proposal->price_total > 0 ? $proposal->price_total : ($deal->total_estimated_engagement_value ?: ($deal->amount ?: 0)));
        $lineItemsSnapshot = $proposal->line_items_snapshot;
        if (! $proposal->client_form_sent_at && ! is_array($lineItemsSnapshot)) {
            $lineItemsSnapshot = $this->snapshotDealLineItems($deal);
        }

        $proposal->update([
            'status' => 'sent',
            'client_access_token' => $token,
            'client_access_expires_at' => $expiresAt,
            'client_form_sent_to_email' => $recipientEmail,
            'client_form_sent_at' => now(),
            'line_items_snapshot' => $lineItemsSnapshot,
            'price_total' => $currentCommercialTotal > 0 ? $currentCommercialTotal : $proposal->price_total,
        ]);

        $deal->update([
            'proposal_sent_at' => now(),
        ]);

        $clientUrl = route('deals.proposal.client.show', ['token' => $token]);
        $clientName = $this->clientNameForDeal($deal);

        $emailHtml = view('emails.deals.proposal-client-link', [
            'deal' => $deal,
            'proposal' => $proposal,
            'clientName' => $clientName,
            'clientUrl' => $clientUrl,
            'expiresAt' => $expiresAt,
        ])->render();

        $mailResult = app(\App\Services\EmailNotificationService::class)->sendHtml(
            $recipientEmail,
            'Proposal for '.($deal->company_name ?: $deal->deal_name ?: $deal->deal_code),
            $emailHtml
        );

        $targetRedirect = $request->input('return_to', url()->previous() ?: route('deals.show', $deal));

        if (! $mailResult['success']) {
            return redirect()
                ->to($targetRedirect)
                ->with('error', "Failed to send proposal email to {$recipientEmail}: " . $mailResult['error'])
                ->with('proposal_client_link', $clientUrl);
        }

        return redirect()
            ->to($targetRedirect)
            ->with('success', "Proposal link sent to {$recipientEmail}.")
            ->with('proposal_client_link', $clientUrl);
    }

    public function clientProposal(Request $request, string $token): View
    {
        $proposal = $this->findProposalByClientToken($token);
        $this->abortExpiredClientProposal($proposal);
        $deal = $proposal->deal()->with('contact')->firstOrFail();
        $documentData = $this->documentData($deal, $proposal);
        $clientName = $this->clientNameForDeal($deal);
        $itemResponses = $proposal->itemResponses()
            ->orderByDesc('responded_at')
            ->orderByDesc('id')
            ->get()
            ->unique('line_item_id')
            ->keyBy(fn ($response): string => (string) $response->line_item_id);
        $hasSavedItemDecisions = $itemResponses->isNotEmpty();
        $proposalLineItems = collect($proposal->line_items_snapshot ?? [])
            ->values()
            ->map(function ($item, int $index) use ($itemResponses): array {
                $item = is_array($item) ? $item : ['name' => (string) $item];
                $itemId = (string) ($item['id'] ?? ('legacy-'.($index + 1)));
                $response = $itemResponses->get($itemId);
                $unitPrice = $item['unitPrice'] ?? $item['unit_price'] ?? $item['price'] ?? null;
                $quantity = $item['qty'] ?? null;
                $total = $item['line_total'] ?? null;

                if ($total === null && is_numeric($unitPrice) && is_numeric($quantity)) {
                    $total = max(
                        0,
                        ((float) $quantity * (float) $unitPrice)
                        - (float) ($item['discount'] ?? 0)
                        + (float) ($item['tax'] ?? 0)
                    );
                }

                return [
                    'id' => $itemId,
                    'name' => (string) ($item['name'] ?? 'Commercial Scope Item'),
                    'type' => strtoupper((string) ($item['type'] ?? '')),
                    'description' => (string) ($item['description'] ?? ''),
                    'qty' => $quantity,
                    'unit' => (string) ($item['unit'] ?? ''),
                    'unit_price' => $unitPrice,
                    'total' => $total,
                    'decision' => $response?->decision,
                    'comment' => $response?->comment,
                ];
            });

        return view('deals.proposal.client-form', [
            'deal' => $deal,
            'proposal' => $proposal,
            'clientName' => $clientName,
            'proposalDocumentHtml' => $this->resolveProposalDocumentHtml($documentData, $proposal->document_html, false),
            'downloadUrl' => route('deals.proposal.client.download', ['token' => $token]),
            'itemDecisionsUrl' => route('deals.proposal.client.items', ['token' => $token]),
            'proposalLineItems' => $proposalLineItems,
            'hasSavedItemDecisions' => $hasSavedItemDecisions,
        ]);
    }

    public function approveClientProposal(Request $request, string $token): RedirectResponse
    {
        $proposal = $this->findProposalByClientToken($token);
        $this->abortExpiredClientProposal($proposal);
        $this->assertClientProposalCanRespond($proposal);

        $validated = $request->validate([
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_feedback' => ['nullable', 'string', 'max:2000'],
            'approval_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $deal = $proposal->deal;
        $clientName = trim((string) ($validated['client_name'] ?? '')) ?: $this->clientNameForDeal($deal);
        $note = trim((string) ($validated['client_feedback'] ?? $validated['approval_note'] ?? '')) ?: null;

        \Illuminate\Support\Facades\DB::transaction(function () use ($proposal, $deal, $clientName, $note): void {
            $lockedProposal = DealProposal::query()->whereKey($proposal->id)->lockForUpdate()->firstOrFail();
            $this->assertClientProposalCanRespond($lockedProposal);
            $this->recordProposalItemDecisions($lockedProposal, 'accepted', $clientName, $note);
            $lockedProposal->update([
                'status' => 'approved',
                'client_approved_at' => now(),
                'client_approved_by_name' => $clientName,
                'client_approval_note' => $note,
            ]);
            app(\App\Services\DealProposalDecisions::class)->finalize($lockedProposal, $clientName);

            $dealPayload = [
                'proposal_decision' => 'Approved',
            ];
            if (Schema::hasColumn('deals', 'proposal_status')) {
                $dealPayload['proposal_status'] = 'Approved';
            }
            if (Schema::hasColumn('deals', 'approval_date')) {
                $dealPayload['approval_date'] = now();
            }
            $deal?->update($dealPayload);
        });

        $this->notifyProposalCreatorOfClientDecision(
            $proposal,
            $deal,
            $clientName,
            'Proposal approved',
            $this->proposalDecisionItems($proposal, 'accepted', $note)
        );

        if ($deal) {
            $this->moveDealToStage($deal, 'Negotiation');

            try {
                app(\App\Services\DealHistoryService::class)->logActivity(
                    $deal,
                    \App\Models\DealHistory::TYPE_PROPOSAL_ACCEPTED ?? 'Proposal Approved',
                    "Proposal approved by client ({$clientName})" . ($note ? ": {$note}" : "."),
                    [
                        'proposal_id' => $proposal->id,
                        'client_name' => $clientName,
                        'note' => $note,
                    ]
                );
            } catch (\Throwable $e) {
                // Silently skip if history logger is unavailable
            }
        }

        return redirect()
            ->route('deals.proposal.client.show', ['token' => $token])
            ->with('success', 'Proposal approved successfully. Thank you.');
    }

    public function declineClientProposal(Request $request, string $token): RedirectResponse
    {
        $proposal = $this->findProposalByClientToken($token);
        $this->abortExpiredClientProposal($proposal);
        $this->assertClientProposalCanRespond($proposal);

        $validated = $request->validate([
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $deal = $proposal->deal;
        $clientName = trim((string) ($validated['client_name'] ?? '')) ?: $this->clientNameForDeal($deal);
        $feedback = trim((string) ($validated['client_feedback'] ?? '')) ?: null;

        \Illuminate\Support\Facades\DB::transaction(function () use ($proposal, $deal, $clientName, $feedback): void {
            $lockedProposal = DealProposal::query()->whereKey($proposal->id)->lockForUpdate()->firstOrFail();
            $this->assertClientProposalCanRespond($lockedProposal);
            $this->recordProposalItemDecisions($lockedProposal, 'declined', $clientName, $feedback);
            $lockedProposal->update([
                'status' => 'rejected',
                'client_approved_at' => now(),
                'client_approved_by_name' => $clientName,
                'client_approval_note' => $feedback,
            ]);
            app(\App\Services\DealProposalDecisions::class)->finalize($lockedProposal, $clientName);

            $deal?->update([
                'proposal_decision' => 'Declined',
            ]);
        });

        $this->notifyProposalCreatorOfClientDecision(
            $proposal,
            $deal,
            $clientName,
            'Proposal declined',
            $this->proposalDecisionItems($proposal, 'declined', $feedback)
        );

        if ($deal) {
            try {
                app(\App\Services\DealHistoryService::class)->logActivity(
                    $deal,
                    \App\Models\DealHistory::TYPE_PROPOSAL_REJECTED ?? 'Proposal Declined',
                    "Proposal declined by client ({$clientName})" . ($feedback ? ": {$feedback}" : "."),
                    [
                        'proposal_id' => $proposal->id,
                        'client_name' => $clientName,
                        'feedback' => $feedback,
                    ]
                );
            } catch (\Throwable $e) {
                // Silently skip if history logger is unavailable
            }
        }

        return redirect()
            ->route('deals.proposal.client.show', ['token' => $token])
            ->with('warning', 'Proposal has been declined. Your feedback has been forwarded to John Kelly & Company.');
    }

    public function submitClientProposalItemDecisions(Request $request, string $token): RedirectResponse
    {
        $proposal = $this->findProposalByClientToken($token);
        $this->abortExpiredClientProposal($proposal);
        $this->assertClientProposalCanRespond($proposal);
        $validated = $request->validate([
            'client_name'=>['required','string','max:255'],
            'responses'=>['required','array','min:1'],
            'responses.*.line_item_id'=>['required','string','max:255','distinct'],
            'responses.*.decision'=>['nullable','in:accepted,declined'],
            'responses.*.comment'=>['nullable','string','max:2000'],
        ]);
        $clientName=trim($validated['client_name']);
        abort_if($clientName === '',422,'Enter the name of the client representative.');
        \Illuminate\Support\Facades\DB::transaction(function () use($proposal,$validated,$clientName): void {
            Deal::whereKey($proposal->deal_id)->lockForUpdate()->firstOrFail();
            $locked=DealProposal::whereKey($proposal->id)->lockForUpdate()->firstOrFail();
            $this->assertClientProposalCanRespond($locked);
            $allowed=collect($locked->line_items_snapshot ?? [])->values()->mapWithKeys(function($item,$index){
                $item=is_array($item)?$item:['name'=>(string)$item];
                return [(string)($item['id'] ?? ('legacy-'.($index+1)))=>(string)($item['name'] ?? 'Proposal item')];
            });
            abort_if($allowed->isEmpty(),422,'This issued proposal has no saved items.');
            $decided=0;
            foreach($validated['responses'] as $response){
                abort_unless($allowed->has($response['line_item_id']),422,'An item does not belong to this proposal version.');
                if(empty($response['decision'])) continue;
                $locked->itemResponses()->create([
                    'line_item_id'=>$response['line_item_id'],'line_item_name'=>$allowed[$response['line_item_id']],
                    'decision'=>$response['decision'],'comment'=>trim((string)($response['comment'] ?? '')) ?: null,
                    'client_name'=>$clientName,'responded_at'=>now(),
                ]);
                $decided++;
            }
            abort_if($decided===0,422,'Choose at least one item before submitting your final decision.');
            app(\App\Services\DealProposalDecisions::class)->finalize($locked,$clientName);
        },3);
        app(\App\Services\DealProposalDecisions::class)->notify($proposal->fresh());
        return redirect()->route('deals.proposal.client.show',['token'=>$token])
            ->with('success','Your final decision has been submitted. Thank you.');
    }
    public function approveProposalAdmin(Request $request, Deal $deal): RedirectResponse
    {
        $proposal = $deal->proposal;
        if (! $proposal) {
            return back()->with('error', 'Proposal not found.');
        }

        $proposal->update([
            'status' => 'approved',
            'client_approved_at' => $proposal->client_approved_at ?: now(),
        ]);

        $dealPayload = [
            'proposal_decision' => 'Approved',
        ];
        if (Schema::hasColumn('deals', 'proposal_status')) {
            $dealPayload['proposal_status'] = 'Approved';
        }
        if (Schema::hasColumn('deals', 'approval_date')) {
            $dealPayload['approval_date'] = $deal->approval_date ?: now();
        }
        $deal->update($dealPayload);

        $this->moveDealToStage($deal, 'Negotiation');

        return back()->with('success', 'Proposal approved internally. Deal moved to Negotiation stage.');
    }

    public function rejectProposalAdmin(Request $request, Deal $deal): RedirectResponse
    {
        $proposal = $deal->proposal;
        if (! $proposal) {
            return back()->with('error', 'Proposal not found.');
        }

        $proposal->update([
            'status' => 'rejected',
        ]);

        $deal->update([
            'proposal_decision' => 'Rejected',
        ]);

        return back()->with('success', 'Proposal rejected internally.');
    }

    public function downloadClientProposal(string $token): \Symfony\Component\HttpFoundation\Response
    {
        $proposal = $this->findProposalByClientToken($token);
        $this->abortExpiredClientProposal($proposal);
        $deal = $proposal->deal()->with('contact')->firstOrFail();

        return $this->proposalPdfDownloadResponse($deal, $proposal);
    }

    public function sendClientQuotation(Request $request, Deal $deal): RedirectResponse
    {
        $deal->loadMissing('contact', 'proposal');

        $validated = $request->validate([
            'recipient_email' => ['required', 'email', 'max:255'],
        ]);

        $proposal = $deal->proposal ?: DealProposal::create($this->defaultProposalPayload($deal));
        $token = $this->ensureClientAccessToken($proposal);
        $recipientEmail = trim((string) $validated['recipient_email']);
        $clientUrl = route('deals.proposal.client.show', ['token' => $token]);

        $emailHtml = view('emails.deals.quotation-client-link', [
            'deal' => $deal,
            'proposal' => $proposal,
            'clientName' => $this->clientNameForDeal($deal),
            'clientUrl' => $clientUrl,
            'expiresAt' => $proposal->client_access_expires_at,
        ])->render();

        $mailResult = app(\App\Services\EmailNotificationService::class)->sendHtml(
            $recipientEmail,
            'Quotation for '.($deal->company_name ?: $deal->deal_name ?: $deal->deal_code),
            $emailHtml
        );

        if (! $mailResult['success']) {
            return redirect()
                ->route('deals.show', $deal)
                ->with('error', "Failed to send quotation to {$recipientEmail}: " . $mailResult['error'])
                ->with('proposal_client_link', $clientUrl);
        }

        return redirect()
            ->route('deals.show', $deal)
            ->with('success', "Quotation link sent to {$recipientEmail}.")
            ->with('proposal_client_link', $clientUrl);
    }

    public function sendFinanceQuotation(Request $request, Deal $deal): RedirectResponse
    {
        $deal->loadMissing('contact', 'proposal', 'assignedFinance');

        $validated = $request->validate([
            'recipient_email' => ['nullable', 'email', 'max:255'],
        ]);

        $proposal = $deal->proposal ?: DealProposal::create($this->defaultProposalPayload($deal));
        $recipientEmail = trim((string) (!empty($validated['recipient_email']) ? $validated['recipient_email'] : ($deal->assignedFinance?->email ?? '')));
        if ($recipientEmail === '') {
            return redirect()
                ->route('deals.show', $deal)
                ->with('error', 'Assign a finance user or enter a finance email before sending the quotation.');
        }
        $proposalUrl = route('deals.quotation.finance', $deal);

        $emailHtml = view('emails.deals.quotation-finance-link', [
            'deal' => $deal,
            'proposal' => $proposal,
            'financeName' => $deal->assignedFinance?->name,
            'clientName' => $this->clientNameForDeal($deal),
            'proposalUrl' => $proposalUrl,
        ])->render();

        $mailResult = app(\App\Services\EmailNotificationService::class)->sendHtml(
            $recipientEmail,
            'Immediate Issuance of Quotation',
            $emailHtml
        );

        if (! $mailResult['success']) {
            return redirect()
                ->route('deals.show', $deal)
                ->with('error', "Failed to send quotation to finance at {$recipientEmail}: " . $mailResult['error']);
        }

        return redirect()
            ->route('deals.show', $deal)
            ->with('success', "Quotation sent to finance at {$recipientEmail}.");
    }

    public function financeQuotation(Deal $deal): View
    {
        $deal->loadMissing('contact', 'proposal', 'assignedFinance');

        $proposal = $deal->proposal ?: DealProposal::create($this->defaultProposalPayload($deal));
        $documentData = $this->documentData($deal, $proposal);

        return view('deals.quotation.finance', [
            'deal' => $deal,
            'proposal' => $proposal,
            'documentData' => $documentData,
            'clientName' => $this->clientNameForDeal($deal),
            'proposalDownloadUrl' => route('deals.proposal.download', $deal),
        ]);
    }

    public function approveQuotation(Deal $deal): RedirectResponse
    {
        $proposal = $deal->proposal ?: DealProposal::create($this->defaultProposalPayload($deal));

        if ($proposal->quotation_approved_at || $proposal->quotation_status === 'approved') {
            return redirect()
                ->route('deals.quotation.finance', $deal)
                ->with('success', 'Quotation is already approved.');
        }

        $financeName = auth()->user()?->name ?: 'Finance';
        $proposal->update([
            'status' => 'approved',
            'quotation_status' => 'approved',
            'quotation_approved_at' => now(),
            'quotation_approved_by_name' => $financeName,
        ]);

        $this->markDealApprovedByFinance($deal, $financeName);
        $this->moveDealToStage($deal, 'Payment');

        return redirect()
            ->route('deals.quotation.finance', $deal)
            ->with('success', 'Quotation approved by finance. Deal moved to Payment.');
    }

    public function uploadFinanceQuotation(Request $request, Deal $deal): RedirectResponse
    {
        abort_unless(app(\App\Services\DealFinanceDocuments::class)->canUpload($request->user()), 403);
        $proposal = $deal->proposal ?: DealProposal::create($this->defaultProposalPayload($deal));
        if ($proposal->quotation_approved_at || $proposal->quotation_status === 'approved') {
            return redirect()
                ->route('deals.quotation.finance', $deal)
                ->with('error', 'Approved quotations cannot be replaced unless the deal is renegotiated.');
        }
        $uploadRequest = Request::create($request->url(), 'POST', ['document_type'=>'quotation'], [], ['document'=>$request->file('quotation_file')]);
        $uploadRequest->setUserResolver($request->getUserResolver());
        app(DealFinanceDocumentController::class)->store($uploadRequest, $deal, app(\App\Services\DealFinanceDocuments::class));

        return redirect()
            ->route('deals.quotation.finance', $deal)
            ->with('success', 'Finance quotation uploaded.');
    }

    public function paymentInvoice(Deal $deal): View
    {
        $deal->loadMissing('contact', 'proposal', 'assignedFinance');

        $proposal = $deal->proposal ?: DealProposal::create($this->defaultProposalPayload($deal));
        $documentData = $this->documentData($deal, $proposal);

        return view('deals.invoice.payment', [
            'deal' => $deal,
            'proposal' => $proposal,
            'documentData' => $documentData,
            'clientName' => $this->clientNameForDeal($deal),
        ]);
    }

    public function uploadInvoice(Request $request, Deal $deal): RedirectResponse
    {
        $proposal = $deal->proposal ?: DealProposal::create($this->defaultProposalPayload($deal));
        if ($proposal->payment_confirmed_at || in_array($proposal->invoice_status, ['approved', 'payment_confirmed'], true)) {
            return redirect()
                ->route('deals.invoice.payment', $deal)
                ->with('error', 'Invoices cannot be replaced after payment is confirmed.');
        }
        $this->storeInvoiceUpload($request, $proposal);

        return redirect()
            ->route('deals.invoice.payment', $deal)
            ->with('success', 'Invoice uploaded successfully.');
    }

    public function confirmPayment(Deal $deal): RedirectResponse
    {
        $proposal = $deal->proposal ?: DealProposal::create($this->defaultProposalPayload($deal));

        if ($proposal->payment_confirmed_at || in_array($proposal->invoice_status, ['approved', 'payment_confirmed'], true)) {
            return redirect()
                ->route('deals.invoice.payment', $deal)
                ->with('success', 'Payment / invoice is already approved.');
        }

        $financeName = auth()->user()?->name ?: 'Finance';
        $proposal->forceFill([
            'status' => 'approved',
            'invoice_status' => 'approved',
            'payment_confirmed_at' => now(),
            'payment_confirmed_by_name' => $financeName,
        ])->save();

        $this->markDealApprovedByFinance($deal, $financeName);

        // Finance approval confirms payment, not the engagement's final business outcome.

        return redirect()
            ->route('deals.invoice.payment', $deal)
            ->with('success', 'Payment / invoice approved by finance.');
    }

    public function uploadClientQuotation(Request $request, string $token): RedirectResponse
    {
        $proposal = $this->findProposalByClientToken($token);
        $this->abortExpiredClientProposal($proposal);
        $this->storeQuotationUpload($request, $proposal, 'client');

        return redirect()
            ->route('deals.proposal.client.show', ['token' => $token])
            ->with('success', 'Quotation uploaded successfully.');
    }

    public function preview(Request $request, Deal $deal): JsonResponse
    {
        $deal->loadMissing('contact', 'proposal');
        $proposal = $deal->proposal ?: new DealProposal($this->defaultProposalPayload($deal));

        $validated = $this->validatedPayload($request);
        $previewProposal = new DealProposal(array_merge(
            $proposal->toArray(),
            $validated,
            ['deal_id' => $deal->id],
        ));

        $documentData = $this->documentData($deal, $previewProposal);
        $baseName = Str::slug((string) ($deal->deal_code ?: 'deal-proposal')).'-proposal-preview.docx';
        $pdfPath = $this->generateProposalPdf($documentData, $baseName);

        return response()->json([
            'html' => $this->resolveProposalDocumentHtml($documentData, $validated['document_html'] ?? null, false),
            'pdf_url' => $pdfPath ? route('uploads.show', ['path' => $pdfPath]) : null,
            'pdf_download_url' => $pdfPath ? route('uploads.show', ['path' => $pdfPath, 'download' => 1]) : null,
        ]);
    }

    private function defaultProposalPayload(Deal $deal): array
    {
        $contact = $deal->contact;
        $clientName = trim(collect([
            $deal->first_name ?: $contact?->first_name,
            $deal->middle_name ?: $contact?->middle_name,
            $deal->last_name ?: $contact?->last_name,
        ])->filter()->implode(' '));

        $serviceType = $deal->service_area ?: $deal->deal_title ?: $deal->services;
        $requirementGroup = $this->selectedRequirementGroup($deal);
        $defaultFees = $this->defaultProposalData($deal);
        $dealRequirements = collect($deal->client_requirements ?? [])
            ->filter(fn ($requirement): bool => is_scalar($requirement) && filled($requirement))
            ->map(fn ($requirement): string => trim((string) $requirement))
            ->implode("\n");
        $dealLineItems = app(DealProposalAggregationService::class)->resolveDealLineItems($deal);
        $deliverables = collect($dealLineItems)
            ->map(fn (array $item): string => trim((string) ($item['name'] ?? '')))
            ->filter()
            ->unique()
            ->implode("\n");

        $catalogDefaults = app(\App\Services\ProductCatalogConfiguration::class)->forDeal($deal);
        $deliverables = \App\Services\ProductCatalogConfiguration::summarize($catalogDefaults, 'deliverables') ?: $deliverables;

        return [
            'deal_id' => $deal->id,
            'reference_id' => 'PROP-'.($deal->deal_code ?: $deal->id),
            'crud_id' => 'DEAL-'.$deal->id,
            'proposal_date' => $deal->proposal_date ?: now()->toDateString(),
            'location' => $deal->company_address ?: $deal->address,
            'service_type' => $serviceType,
            'scope_of_service' => (string) ($deal->scope_of_work ?: \App\Services\ProductCatalogConfiguration::summarize($catalogDefaults, 'scope_of_work')),
            'what_you_will_receive' => $deliverables,
            'our_proposal_text' => '',
            'terms' => $deal->payment_terms ?: '',
            'requirements_sole' => $requirementGroup === 'sole'
                ? $dealRequirements
                : '',
            'requirements_juridical' => $requirementGroup === 'juridical'
                ? $dealRequirements
                : '',
            'requirements_optional' => '',
            ...$defaultFees,
            'prepared_by_name' => $deal->assigned_consultant ?: (string) auth()->user()?->name,
            'prepared_by_id' => (string) auth()->id(),
        ];
    }

    private function documentData(Deal $deal, DealProposal $proposal): array
    {
        $deal->loadMissing('contact', 'company');
        $contact = $deal->contact;
        $clientName = trim(collect([
            $deal->first_name ?: $contact?->first_name,
            $deal->middle_name ?: $contact?->middle_name,
            $deal->last_name ?: $contact?->last_name,
        ])->filter()->implode(' ')) ?: (string) ($deal->primary_contact_name ?: '');

        $commercialData = $this->proposalCommercialData($deal, $proposal);
        $productCatalog = app(\App\Services\ProductCatalogConfiguration::class)->forDeal($deal, $proposal);
        $serviceAreas = collect([
            $deal->service_area,
            $deal->service_areas,
            collect($commercialData['line_items'])->pluck('service_area')->all(),
        ])->flatten(2)
            ->filter(fn ($area): bool => is_scalar($area) && filled($area))
            ->map(fn ($area): string => trim((string) $area))
            ->unique()
            ->values();
        $serviceType = $proposal->service_type
            ?: $serviceAreas->implode(', ')
            ?: ($deal->deal_title ?: $deal->services ?: '');
        $proposalScope = trim((string) $proposal->scope_of_service);
        $scope = $proposalScope !== '' && $proposalScope !== 'Scope of work needs confirmation.'
            ? $proposalScope
            : (string) ($deal->scope_of_work ?: \App\Services\ProductCatalogConfiguration::summarize($productCatalog, 'scope_of_work'));
        $deliverableNames = collect($commercialData['line_items'])
            ->map(fn (array $item): string => trim((string) ($item['name'] ?? '')))
            ->filter()
            ->unique()
            ->implode("\n");
        $proposalDeliverables = trim((string) $proposal->what_you_will_receive);
        $deliverables = $proposalDeliverables !== '' && $proposalDeliverables !== 'Deliverables need confirmation.'
            ? $proposalDeliverables
            : (\App\Services\ProductCatalogConfiguration::summarize($productCatalog, 'deliverables') ?: $deliverableNames);
        $proposalText = $proposal->our_proposal_text ?: '';
        $location = $proposal->location ?: ($deal->company_address ?: ($deal->address ?: $deal->company?->address));
        $requirementGroup = $this->selectedRequirementGroup($deal);
        $serviceItems = $this->buildProposalItemsForDocument(
            $commercialData['line_items'],
            'SERVICE',
            $scope ?: (string) $deal->scope_of_work,
            '',
            '',
            optional($deal->estimated_completion_date)->format('F d, Y') ?: ''
        );
        $productItems = $this->buildProposalItemsForDocument(
            $commercialData['line_items'],
            'PRODUCT',
            $scope ?: (string) $deal->scope_of_work,
            '',
            '',
            optional($deal->estimated_completion_date)->format('F d, Y') ?: ''
        );
        $dealRequirements = collect($deal->client_requirements ?? [])
            ->filter(fn ($requirement): bool => is_scalar($requirement) && filled($requirement))
            ->map(fn ($requirement): string => trim((string) $requirement))
            ->implode("\n");
        $requirementsSole = $proposal->requirements_sole ?: ($requirementGroup === 'sole' ? $dealRequirements : '');
        $requirementsJuridical = $proposal->requirements_juridical ?: ($requirementGroup === 'juridical' ? $dealRequirements : '');
        $requirementRows = $this->buildRequirementRows(
            $requirementGroup === 'sole' ? $requirementsSole : '',
            $requirementGroup === 'juridical' ? $requirementsJuridical : '',
            $proposal->requirements_optional ?: ''
        );
        return [
            'year' => optional($proposal->proposal_date)->format('Y') ?: now()->format('Y'),
            'date' => optional($proposal->proposal_date ?: $deal->proposal_date)->format('F d, Y') ?: now()->format('F d, Y'),
            'service_type' => $serviceType,
            'client_name' => $clientName,
            'business_name' => $deal->company_name ?: ($deal->company?->company_name ?: ''),
            'deal_code' => (string) ($deal->deal_code ?: ''),
            'deal_title' => (string) ($deal->deal_title ?: ''),
            'reference_id' => $proposal->reference_id ?: 'PROP-'.($deal->deal_code ?: $deal->id),
            'crud_id' => $proposal->crud_id ?: 'DEAL-'.$deal->id,
            'location' => $location,
            'scope_of_service' => $scope,
            'what_you_will_receive' => $deliverables,
            'our_proposal_text' => $proposalText,
            'payment_terms' => $proposal->terms ?: ($deal->payment_terms ?: ''),
            'requirements_sole' => $requirementGroup === 'sole' ? $requirementsSole : '',
            'requirements_juridical' => $requirementGroup === 'juridical' ? $requirementsJuridical : '',
            'requirements_optional' => $proposal->requirements_optional ?: '',
            'service_items' => $serviceItems,
            'product_items' => $productItems,
            'requirement_rows' => $requirementRows,
            'service_fee_rows' => $commercialData['service_fee_rows'],
            'product_fee_rows' => $commercialData['product_fee_rows'],
            'price_regular' => $commercialData['price_regular'],
            'price_products' => $commercialData['price_products'],
            'price_discount' => $commercialData['price_discount'],
            'price_subtotal' => $commercialData['price_subtotal'],
            'price_tax' => $commercialData['price_tax'],
            'price_total' => $commercialData['price_total'],
            'price_down' => $commercialData['price_down'],
            'price_balance' => $commercialData['price_balance'],
            'pricing_complete' => $commercialData['pricing_complete'],
            'preserve_issued_document_html' => $commercialData['preserve_issued_document_html'],
            'is_issued_proposal' => filled($proposal->client_form_sent_at),
            'prepared_by_name' => $proposal->prepared_by_name ?: (string) auth()->user()?->name,
            'prepared_by_id' => $proposal->prepared_by_id ?: (string) auth()->id(),
            'document_html' => $proposal->document_html,
            'company_phone' => self::COMPANY_PHONE,
            'company_email' => self::COMPANY_EMAIL,
            'company_website' => self::COMPANY_WEBSITE,
            'company_address' => self::COMPANY_ADDRESS,
            'executive_summary' => self::EXECUTIVE_SUMMARY,
            'role_and_value' => self::ROLE_AND_VALUE,
            'why_partner' => self::WHY_PARTNER,
            'service_areas' => $this->serviceAreaSupportRows(),
            'product_areas' => $this->productAreaOfferRows(),
            'proposal_highlights' => self::HIGHLIGHTS,
            'commitment' => self::COMMITMENT,
            'product_catalog' => $productCatalog,
            'agreement_inclusions' => self::AGREEMENT_INCLUSIONS,
            'agreement_exclusions' => self::AGREEMENT_EXCLUSIONS,
            'supplemental_fees' => self::SUPPLEMENTAL_FEES,
            'terms_and_conditions' => array_merge([
                [
                    'title' => 'Payment Terms',
                    'paragraphs' => [
                        filled($proposal->terms ?: $deal->payment_terms)
                            ? (string) ($proposal->terms ?: $deal->payment_terms)
                            : 'Payment terms need confirmation.',
                    ],
                ],
            ], self::TERMS_AND_CONDITIONS),
            'engagement_team' => self::ENGAGEMENT_TEAM,
            'engagement_team_intro' => "John Kelly & Company assigns a team of consultants and associates who collectively take responsibility for overseeing the project engagement, ensuring consistent guidance, clear communication, and smooth coordination throughout the duration of the engagement.",
            'supplemental_fee_note' => '(All rates are exclusive of VAT and/or withholding tax, if applicable)',
            'proposal_intro' => 'Our Proposal',
            'requirements_intro' => 'To proceed smoothly, we may request the following:',
            'requirements_note' => 'Additional requirements not listed above may be requested depending on the specific circumstances of the business and the requirements of the relevant government agency. Any such requirements will be communicated if and when identified. These requirements are determined by the applicable authority and are outside our control.',
            'system_note' => "This proposal is system-generated through the John Kelly & Company BIR Compliance Services Management System and was generated electronically under Reference ID: ".($proposal->crud_id ?: 'DEAL-'.$deal->id)." in the ordinary course of business; no handwritten or electronic signature is required for its validity, and this document shall be considered legally valid, binding for reference and evaluation purposes, and admissible as an official business record pursuant to applicable Philippine laws on electronic documents and electronic transactions, with any subsequent approval, payment, or engagement arising from this proposal to be governed by the final service agreement, official receipt, or written confirmation issued by John Kelly & Company.",
        ];
    }

    private function proposalCommercialData(Deal $deal, ?DealProposal $proposal): array
    {
        $snapshotItems = ($proposal?->client_form_sent_at || $proposal?->client_approved_at || is_array($proposal?->catalog_snapshot)) && is_array($proposal->line_items_snapshot)
            ? $proposal->line_items_snapshot
            : null;
        $usingIssuedSnapshot = $snapshotItems !== null;
        $issuedSnapshotHasSavedPrices = $usingIssuedSnapshot && collect($snapshotItems)->every(
            fn ($item): bool => is_array($item) && collect(['unitPrice', 'price', 'unit_price', 'amount'])
                ->contains(fn (string $key): bool => is_numeric($item[$key] ?? null))
        );
        $lineItems = app(DealProposalAggregationService::class)->resolveDealLineItems($deal, $snapshotItems);
        $serviceItems = collect($lineItems)->where('type', 'SERVICE')->values();
        $productItems = collect($lineItems)->where('type', 'PRODUCT')->values();

        $serviceDealTotal = $usingIssuedSnapshot
            ? null
            : $this->firstAvailableAmount($deal->total_service_fee, $deal->estimated_professional_fee, $deal->est_professional_fee);
        $productDealTotal = $usingIssuedSnapshot ? null : $this->firstAvailableAmount($deal->total_product_fee);
        $serviceTotal = $this->resolveLineItemTotal(
            $serviceItems,
            $serviceDealTotal,
            $proposal?->price_regular,
            $usingIssuedSnapshot && ! $issuedSnapshotHasSavedPrices
        );
        $productTotal = $this->resolveLineItemTotal(
            $productItems,
            $productDealTotal,
            null
        );
        $serviceUsesDealAggregate = $serviceDealTotal !== null
            && $serviceItems->isNotEmpty()
            && ! $serviceItems->every(fn (array $item): bool => $item['has_saved_price']);
        $productUsesDealAggregate = $productDealTotal !== null
            && $productItems->isNotEmpty()
            && ! $productItems->every(fn (array $item): bool => $item['has_saved_price']);

        $lineDiscount = collect($lineItems)->sum(fn (array $item): float => (float) $item['discount']);
        $dealDiscount = max(
            $this->firstPositiveAmount($deal->discount, $deal->deal_discount),
            $lineDiscount
        );
        $discount = $usingIssuedSnapshot
            ? $this->firstPositiveAmount($proposal?->price_discount, $lineDiscount)
            : $this->firstPositiveAmount($dealDiscount, $proposal?->price_discount);
        $lineTax = collect($lineItems)->sum(fn (array $item): float => (float) $item['tax']);
        $tax = $usingIssuedSnapshot
            ? $this->firstPositiveAmount($proposal?->price_tax, $lineTax)
            : $this->firstPositiveAmount($lineTax, $proposal?->price_tax);
        $pricingComplete = $serviceTotal !== null && $productTotal !== null;
        $subtotal = $pricingComplete
            ? max(($serviceTotal + $productTotal) - $discount, 0)
            : $this->firstAvailableAmount($proposal?->price_subtotal);
        $total = $subtotal !== null ? $subtotal + $tax : null;

        if ($usingIssuedSnapshot && ! $issuedSnapshotHasSavedPrices && (float) ($proposal?->price_total ?? 0) > 0) {
            $serviceTotal = $this->firstPositiveAmount($proposal?->price_regular, $serviceTotal);
            $productTotal = $productTotal ?? null;
            $subtotal = $this->firstAvailableAmount($proposal?->price_subtotal);
            $total = (float) $proposal->price_total;
            $pricingComplete = false;
        }

        if ($total === null && (float) ($proposal?->price_total ?? 0) > 0) {
            $total = (float) $proposal->price_total;
        }

        $paymentTerms = $proposal?->terms ?: $deal->payment_terms;
        [$down, $balance] = $this->resolvePaymentSplit($total, $paymentTerms, $proposal);

        return [
            'line_items' => $lineItems,
            'service_fee_rows' => $this->buildProposalFeeRows($serviceItems, $serviceTotal, 'SERVICE', $serviceUsesDealAggregate),
            'product_fee_rows' => $this->buildProposalFeeRows($productItems, $productTotal, 'PRODUCT', $productUsesDealAggregate),
            'price_regular' => $serviceTotal,
            'price_products' => $productTotal,
            'price_discount' => $discount,
            'price_subtotal' => $subtotal,
            'price_tax' => $tax,
            'price_total' => $total,
            'price_down' => $down,
            'price_balance' => $balance,
            'pricing_complete' => $pricingComplete,
            'preserve_issued_document_html' => $usingIssuedSnapshot
                && ! $issuedSnapshotHasSavedPrices
                && filled($proposal?->document_html),
        ];
    }

    private function resolveLineItemTotal(
        \Illuminate\Support\Collection $items,
        ?float $dealTotal,
        mixed $proposalTotal,
        bool $preferProposalTotal = false
    ): ?float
    {
        $lineTotal = $items->sum(fn (array $item): float => (float) $item['qty'] * (float) $item['unit_price']);

        if ($items->isEmpty()) {
            return $dealTotal ?? 0.0;
        }

        if ($items->isNotEmpty() && $items->every(fn (array $item): bool => $item['has_saved_price'])) {
            return round($lineTotal, 2);
        }

        if ($preferProposalTotal && (float) ($proposalTotal ?? 0) > 0) {
            return round((float) $proposalTotal, 2);
        }

        if ($dealTotal !== null) {
            return round($dealTotal, 2);
        }

        if ($items->every(fn (array $item): bool => $item['price_available'])) {
            return round($lineTotal, 2);
        }

        if (is_numeric($proposalTotal) && (float) $proposalTotal > 0) {
            return round((float) $proposalTotal, 2);
        }

        return null;
    }

    private function buildProposalFeeRows(
        \Illuminate\Support\Collection $items,
        ?float $aggregateTotal,
        string $type,
        bool $useDealAggregate
    ): array
    {
        if ($items->isEmpty()) {
            return [];
        }

        $rows = $items->values()->map(function (array $item, int $index) use ($type): array {
            $model = $item['model'] ?? null;
            $catalogId = $type === 'PRODUCT'
                ? ($model instanceof Product ? $model->product_id : null)
                : ($model instanceof Service ? $model->service_id : null);
            $hasPrice = (bool) ($item['price_available'] ?? false);
            $unitPrice = $hasPrice ? (float) $item['unit_price'] : null;
            $baseTotal = $hasPrice ? (float) $item['qty'] * (float) $unitPrice : null;
            $discount = (float) $item['discount'];
            $tax = (float) $item['tax'];

            return [
                'item_no' => $index + 1,
                'name' => $item['name'],
                'service_id' => $catalogId ?: $item['item_code'],
                'quantity' => $item['qty'],
                'unit' => $item['unit'],
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'tax' => $tax,
                'price' => $baseTotal === null ? null : round($baseTotal, 2),
                'line_total' => $baseTotal === null ? null : round(max(0, $baseTotal - $discount + $tax), 2),
                'price_available' => $hasPrice,
                'aggregate' => false,
            ];
        });

        if ($useDealAggregate) {
            $discount = round($items->sum(fn (array $item): float => (float) $item['discount']), 2);
            $tax = round($items->sum(fn (array $item): float => (float) $item['tax']), 2);

            return [[
                'item_no' => 1,
                'name' => $items->pluck('name')->filter()->implode(', '),
                'service_id' => $rows->pluck('service_id')->filter()->unique()->implode(', '),
                'quantity' => null,
                'unit' => null,
                'unit_price' => null,
                'discount' => $discount,
                'tax' => $tax,
                'price' => $aggregateTotal === null ? null : round($aggregateTotal, 2),
                'line_total' => $aggregateTotal === null ? null : round(max(0, $aggregateTotal - $discount + $tax), 2),
                'price_available' => $aggregateTotal !== null,
                'aggregate' => true,
            ]];
        }

        if ($aggregateTotal !== null && $rows->every(fn (array $row): bool => ! $row['price_available'])) {
            return [[
                'item_no' => 1,
                'name' => $items->pluck('name')->filter()->implode(', '),
                'service_id' => $rows->first()['service_id'],
                'quantity' => null,
                'unit' => null,
                'unit_price' => null,
                'discount' => round($items->sum(fn (array $item): float => (float) $item['discount']), 2),
                'tax' => round($items->sum(fn (array $item): float => (float) $item['tax']), 2),
                'price' => round($aggregateTotal, 2),
                'line_total' => round(max(0, $aggregateTotal - $items->sum(fn (array $item): float => (float) $item['discount']) + $items->sum(fn (array $item): float => (float) $item['tax'])), 2),
                'price_available' => true,
                'aggregate' => true,
            ]];
        }

        return $rows->all();
    }

    private function buildProposalItemsForDocument(
        array $lineItems,
        string $type,
        string $fallbackDescription,
        string $activityOutput,
        string $frequency,
        string $deadline
    ): array {
        return collect($lineItems)
            ->where('type', $type)
            ->values()
            ->map(fn (array $item, int $index): array => [
                'item_no' => $index + 1,
                'name' => $item['name'],
                'service_area' => $item['service_area'] ?? '',
                'description' => ($item['description'] ?? '') ?: $fallbackDescription,
                'activity_output' => ($item['activity_output'] ?? '') ?: $activityOutput,
                'frequency' => ($item['frequency'] ?? '') ?: $frequency,
                'deadline' => ($item['deadline'] ?? '') ?: $deadline,
                'quantity' => $item['qty'],
                'unit' => $item['unit'],
                'unit_price' => $item['price_available'] ? $item['unit_price'] : null,
                'price_available' => $item['price_available'],
                'discount' => $item['discount'],
                'tax' => $item['tax'],
                'line_total' => $item['price_available'] ? $item['line_total'] : null,
            ])
            ->all();
    }

    private function firstAvailableAmount(mixed ...$amounts): ?float
    {
        foreach ($amounts as $amount) {
            if (is_numeric($amount)) {
                return round((float) $amount, 2);
            }
        }

        return null;
    }

    private function resolvePaymentSplit(?float $total, ?string $terms, ?DealProposal $proposal): array
    {
        if ($total === null || blank($terms)) {
            return [null, null];
        }

        if (preg_match('/\b100\s*%|\bfull payment\b/i', $terms)) {
            return [round($total, 2), 0.0];
        }

        if (preg_match('/\b(\d{1,3}(?:\.\d+)?)\s*%/', $terms, $matches)) {
            $percentage = min((float) $matches[1], 100);
            $down = round($total * ($percentage / 100), 2);

            return [$down, max(round($total - $down, 2), 0)];
        }

        return [null, null];
    }

    private function firstPositiveAmount(mixed ...$amounts): float
    {
        foreach ($amounts as $amount) {
            if (is_numeric($amount) && (float) $amount > 0) {
                return round((float) $amount, 2);
            }
        }

        return 0.0;
    }

    private function buildRequirementRows(string $sole, string $juridical, string $optional): array
    {
        $hasContent = filled($sole) || filled($juridical) || filled($optional);

        if (! $hasContent) {
            return [];
        }

        return [[
            'item_no' => 1,
            'name' => 'Client documentary requirements',
            'sole' => $sole,
            'juridical' => $juridical,
            'optional' => $optional,
        ]];
    }

    private function serviceAreaSupportRows(): array
    {
        $services = Service::query()
            ->whereNull('company_id')
            ->where('status', 'Active')
            ->orderBy('service_name')
            ->get(['service_name', 'service_area', 'service_area_other']);

        $grouped = [];

        foreach ($services as $service) {
            $areas = collect($service->service_area ?? [])
                ->map(fn ($area) => trim((string) $area))
                ->filter()
                ->values();

            if ($areas->contains('Others') && filled($service->service_area_other)) {
                $areas = $areas
                    ->reject(fn ($area) => $area === 'Others')
                    ->push(trim((string) $service->service_area_other))
                    ->values();
            }

            if ($areas->isEmpty() && filled($service->service_area_other)) {
                $areas = collect([trim((string) $service->service_area_other)]);
            }

            foreach ($areas as $area) {
                $grouped[$area] ??= [];
                $grouped[$area][] = (string) $service->service_name;
            }
        }

        if ($grouped === []) {
            return self::SERVICE_AREAS;
        }

        return $this->catalogAreaRows($grouped, 'service_area', 'scope');
    }

    private function productAreaOfferRows(): array
    {
        $products = Product::query()
            ->where('status', 'Active')
            ->orderBy('product_name')
            ->get(['product_name', 'product_area', 'product_area_other']);

        $grouped = [];

        foreach ($products as $product) {
            $areas = collect($product->product_area ?? [])
                ->map(fn ($area) => trim((string) $area))
                ->filter()
                ->values();

            if ($areas->contains('Others') && filled($product->product_area_other)) {
                $areas = $areas
                    ->reject(fn ($area) => $area === 'Others')
                    ->push(trim((string) $product->product_area_other))
                    ->values();
            }

            if ($areas->isEmpty() && filled($product->product_area_other)) {
                $areas = collect([trim((string) $product->product_area_other)]);
            }

            foreach ($areas as $area) {
                $grouped[$area] ??= [];
                $grouped[$area][] = (string) $product->product_name;
            }
        }

        return $this->catalogAreaRows($grouped, 'product_area', 'products');
    }

    private function catalogAreaRows(array $grouped, string $areaKey, string $itemsKey): array
    {
        return collect($grouped)
            ->sortKeys()
            ->map(fn (array $scope, string $area): array => [
                $areaKey => $area,
                $itemsKey => collect($scope)->unique()->values()->all(),
            ])
            ->values()
            ->map(fn (array $row, int $index): array => [
                'no' => (string) ($index + 1),
                $areaKey => $row[$areaKey],
                $itemsKey => $row[$itemsKey],
            ])
            ->all();
    }

    private function validatedPayload(Request $request): array
    {
        return $request->validate([
            'reference_id' => ['nullable', 'string', 'max:255'],
            'crud_id' => ['nullable', 'string', 'max:255'],
            'proposal_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'service_type' => ['nullable', 'string', 'max:255'],
            'scope_of_service' => ['nullable', 'string'],
            'what_you_will_receive' => ['nullable', 'string'],
            'our_proposal_text' => ['nullable', 'string'],
            'requirements_sole' => ['nullable', 'string'],
            'requirements_juridical' => ['nullable', 'string'],
            'requirements_optional' => ['nullable', 'string'],
            'price_regular' => ['nullable', 'numeric'],
            'price_discount' => ['nullable', 'numeric'],
            'price_subtotal' => ['nullable', 'numeric'],
            'price_tax' => ['nullable', 'numeric'],
            'price_total' => ['nullable', 'numeric'],
            'price_down' => ['nullable', 'numeric'],
            'price_balance' => ['nullable', 'numeric'],
            'prepared_by_name' => ['nullable', 'string', 'max:255'],
            'prepared_by_id' => ['nullable', 'string', 'max:255'],
            'document_html' => ['nullable', 'string'],
        ]);
    }

    private function defaultProposalData(Deal $deal): array
    {
        $commercialData = $this->proposalCommercialData($deal, null);

        return [
            'price_regular' => $commercialData['price_regular'],
            'price_discount' => $commercialData['price_discount'],
            'price_subtotal' => $commercialData['price_subtotal'],
            'price_tax' => $commercialData['price_tax'],
            'price_total' => $commercialData['price_total'],
            'price_down' => $commercialData['price_down'],
            'price_balance' => $commercialData['price_balance'],
        ];
    }

    private function selectedRequirementGroup(Deal $deal): string
    {
        if (strtolower((string) $deal->customer_type) !== 'business') {
            return 'sole';
        }

        $organization = $this->resolvedBusinessOrganization($deal);

        return $organization === 'sole_proprietorship' ? 'sole' : 'juridical';
    }

    private function resolvedBusinessOrganization(Deal $deal): ?string
    {
        $contact = $deal->contact;
        $companyName = trim((string) $deal->company_name);

        if ($companyName !== '') {
            $linkedCompany = Company::query()
                ->with('latestBif')
                ->where('company_name', $companyName)
                ->first();

            if (filled($linkedCompany?->latestBif?->business_organization)) {
                return (string) $linkedCompany->latestBif->business_organization;
            }
        }

        foreach ([
            $contact?->organization_type,
            $contact?->business_type_organization,
        ] as $value) {
            $mapped = $this->mapContactOrganizationToBusinessOrganization((string) $value);
            if ($mapped !== null) {
                return $mapped;
            }
        }

        return null;
    }

    private function mapContactOrganizationToBusinessOrganization(string $value): ?string
    {
        $normalized = Str::lower(trim($value));

        return match ($normalized) {
            'sole proprietorship', 'sole_proprietorship', 'sole proprietor', 'individual', 'natural person' => 'sole_proprietorship',
            'partnership' => 'partnership',
            'corporation', 'stock' => 'corporation',
            'cooperative' => 'cooperative',
            'ngo', 'non-stock' => 'ngo',
            'others', 'other' => 'other',
            default => null,
        };
    }

    private function renderProposalDocument(array $documentData): string
    {
        return ViewFacade::make('deals.proposal.partials.document', [
            'documentData' => $documentData,
            'editable' => false,
        ])->render();
    }

    private function renderEditableProposalDocument(array $documentData): string
    {
        return ViewFacade::make('deals.proposal.partials.document', [
            'documentData' => $documentData,
            'editable' => true,
        ])->render();
    }

    private function resolveProposalDocumentHtml(array $documentData, ?string $documentHtml, bool $editable): string
    {
        $globalTemplateHtml = $this->approvedGlobalProposalTemplateHtml($documentData);

        $html = filled($documentHtml)
            ? $this->sanitizeProposalHtml($documentHtml, $editable)
            : ($globalTemplateHtml ?: ($editable ? $this->renderEditableProposalDocument($documentData) : $this->renderProposalDocument($documentData)));

        $freshHtml = $this->renderProposalDocument($documentData);
        if (! ($documentData['product_catalog']['legacy_issued'] ?? false)) {
            $html = $this->synchronizeProductCatalog($html, $freshHtml);
        }

        if (($documentData['preserve_issued_document_html'] ?? false) && filled($documentHtml)) {
            return $html;
        }

        $freshHtml = $this->renderProposalDocument($documentData);
        $html = $this->synchronizeProposalLineItemTables($html, $freshHtml);

        return ($documentData['is_issued_proposal'] ?? false)
            ? $html
            : $this->synchronizeDefaultAcceptanceTerms($html, $freshHtml);
    }

    private function synchronizeProductCatalog(string $html, string $freshHtml): string
    {
        $parse = static function ($markup) {
            $doc = new \DOMDocument('1.0', 'UTF-8');
            $previous = libxml_use_internal_errors(true);
            try { $doc->loadHTML('<meta charset="utf-8"><div id="catalog-root">'.$markup.'</div>', LIBXML_NONET); }
            finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
            return $doc;
        };
        $doc = $parse($html); $fresh = $parse($freshHtml);
        $xpath = new \DOMXPath($doc);
        foreach (iterator_to_array($xpath->query('//*[@data-product-catalog]')) as $node) {
            $node->parentNode->removeChild($node);
        }
        $root = $xpath->query('//*[@id="catalog-root"]')->item(0);
        $target = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " proposal-doc ")]')->item(0) ?: $root;
        foreach ((new \DOMXPath($fresh))->query('//*[@data-product-catalog]') as $node) {
            $target->appendChild($doc->importNode($node, true));
        }
        $result = '';
        foreach ($root->childNodes as $node) { $result .= $doc->saveHTML($node); }
        return $result;
    }

    private function synchronizeDefaultAcceptanceTerms(string $html, string $freshHtml): string
    {
        $parseDocument = static function (string $contents): array {
            $document = new \DOMDocument('1.0', 'UTF-8');
            $previousLibxmlState = libxml_use_internal_errors(true);

            try {
                $loaded = $document->loadHTML(
                    '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body><div id="proposal-content">'.$contents.'</div></body></html>',
                    LIBXML_NONET
                );
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previousLibxmlState);
            }

            if (! $loaded) {
                throw new \RuntimeException('Unable to parse proposal terms while synchronizing saved content.');
            }

            $root = (new \DOMXPath($document))->query('//*[@id="proposal-content"]')->item(0);
            if (! $root) {
                throw new \RuntimeException('Unable to locate proposal terms while synchronizing saved content.');
            }

            return [$document, $root];
        };

        [$document, $root] = $parseDocument($html);
        [$freshDocument, $freshRoot] = $parseDocument($freshHtml);
        $findAcceptanceBlock = static function (\DOMXPath $xpath, \DOMElement $root): ?\DOMElement {
            $blocks = $xpath->query(
                './/div[contains(concat(" ", normalize-space(@class), " "), " proposal-term-block ")]',
                $root
            );
            foreach ($blocks as $block) {
                $heading = $xpath->query('.//h3', $block)->item(0);
                $title = preg_replace('/^\s*\d+[\.\)]\s*/u', '', trim((string) ($heading?->textContent ?? '')));
                if ($title === 'Acceptance and Commencement') {
                    return $block instanceof \DOMElement ? $block : null;
                }
            }

            return null;
        };

        $xpath = new \DOMXPath($document);
        $freshXPath = new \DOMXPath($freshDocument);
        $acceptanceBlock = $findAcceptanceBlock($xpath, $root);
        $freshAcceptanceBlock = $findAcceptanceBlock($freshXPath, $freshRoot);
        if (! $acceptanceBlock || ! $freshAcceptanceBlock
            || ! preg_match('/\b50\s*%|fifty percent/i', (string) $acceptanceBlock->textContent)) {
            return $html;
        }

        $acceptanceBlock->parentNode?->replaceChild(
            $document->importNode($freshAcceptanceBlock, true),
            $acceptanceBlock
        );
        $updatedHtml = '';
        foreach ($root->childNodes as $child) {
            $updatedHtml .= $document->saveHTML($child);
        }

        return $updatedHtml;
    }

    private function synchronizeProposalLineItemTables(string $html, string $freshHtml): string
    {
        $htmlDocument = new \DOMDocument('1.0', 'UTF-8');
        $freshDocument = new \DOMDocument('1.0', 'UTF-8');
        $previousLibxmlState = libxml_use_internal_errors(true);

        try {
            $htmlLoaded = $htmlDocument->loadHTML(
                '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body><div id="proposal-content">'.$html.'</div></body></html>',
                LIBXML_NONET
            );
            $freshLoaded = $freshDocument->loadHTML(
                '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body><div id="proposal-content">'.$freshHtml.'</div></body></html>',
                LIBXML_NONET
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousLibxmlState);
        }

        if (! $htmlLoaded || ! $freshLoaded) {
            throw new \RuntimeException('Unable to synchronize saved proposal line-item tables.');
        }

        $htmlRoot = (new \DOMXPath($htmlDocument))->query('//*[@id="proposal-content"]')->item(0);
        $freshRoot = (new \DOMXPath($freshDocument))->query('//*[@id="proposal-content"]')->item(0);
        if (! $htmlRoot || ! $freshRoot) {
            throw new \RuntimeException('Unable to locate proposal content while synchronizing line-item tables.');
        }

        $tableClasses = ['proposal-availed-table', 'proposal-fee-detail-table', 'proposal-pricing-table'];
        foreach ($tableClasses as $tableClass) {
            $classExpression = 'contains(concat(" ", normalize-space(@class), " "), " '.$tableClass.' ")';
            $existingTables = (new \DOMXPath($htmlDocument))->query('.//table['.$classExpression.']', $htmlRoot);
            $freshTables = (new \DOMXPath($freshDocument))->query('.//table['.$classExpression.']', $freshRoot);

            foreach ($existingTables as $index => $existingTable) {
                $freshTable = $freshTables->item($index);
                if (! $freshTable || ! $existingTable->parentNode) {
                    continue;
                }

                $existingTable->parentNode->replaceChild(
                    $htmlDocument->importNode($freshTable, true),
                    $existingTable
                );
            }
        }

        $result = '';
        foreach ($htmlRoot->childNodes as $child) {
            $result .= $htmlDocument->saveHTML($child);
        }

        return $result;
    }

    private function sanitizeProposalHtml(?string $html, bool $editable = false): string
    {
        $clean = (string) $html;
        $clean = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $clean) ?? $clean;
        $clean = preg_replace('/\son\w+="[^"]*"/i', '', $clean) ?? $clean;
        $clean = preg_replace("/\son\w+='[^']*'/i", '', $clean) ?? $clean;
        $clean = str_replace(' (Continued)', '', $clean);

        if (! $editable) {
            $clean = preg_replace('/\scontenteditable="[^"]*"/i', '', $clean) ?? $clean;
            $clean = preg_replace('/\sdata-[a-z0-9_-]+="[^"]*"/i', '', $clean) ?? $clean;
        }

        return $clean;
    }

    private function approvedGlobalProposalTemplateHtml(array $documentData): ?string
    {
        $template = $this->approvedGlobalProposalTemplate();
        $html = $template?->payload['document_html'] ?? null;

        return filled($html) ? $this->hydrateGlobalProposalTemplate((string) $html, $documentData) : null;
    }

    private function approvedGlobalProposalTemplate(): ?FormTemplate
    {
        if (! Schema::hasTable('form_templates')) {
            return null;
        }

        return FormTemplate::query()
            ->where('type', self::GLOBAL_TEMPLATE_TYPE)
            ->when(Schema::hasColumn('form_templates', 'status'), fn ($query) => $query->where('status', 'approved'))
            ->latest()
            ->first();
    }

    private function pendingGlobalProposalTemplate(): ?FormTemplate
    {
        if (! Schema::hasTable('form_templates') || ! Schema::hasColumn('form_templates', 'status')) {
            return null;
        }

        return FormTemplate::query()
            ->where('type', self::GLOBAL_TEMPLATE_TYPE)
            ->where('status', 'pending')
            ->latest()
            ->first();
    }

    private function storePendingGlobalProposalTemplate(array $documentData, string $documentHtml): void
    {
        abort_if(! Schema::hasTable('form_templates'), 500, 'Templates table is not available.');

        $templateHtml = $this->depersonalizeGlobalProposalTemplate(
            $this->sanitizeProposalHtml($documentHtml, false),
            $documentData
        );

        $attributes = [
            'type' => self::GLOBAL_TEMPLATE_TYPE,
            'name' => 'Deal Proposal Template '.now()->format('Y-m-d H:i'),
            'payload' => [
                'document_html' => $templateHtml,
            ],
            'created_by' => auth()->id(),
        ];

        if (Schema::hasColumn('form_templates', 'status')) {
            $attributes['status'] = 'pending';
        }

        FormTemplate::query()->create($attributes);
    }

    private function depersonalizeGlobalProposalTemplate(string $html, array $documentData): string
    {
        foreach ($this->globalTemplateVariables($documentData) as $key => $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            $token = '{{proposal:'.$key.'}}';
            $html = str_replace([$value, e($value)], $token, $html);
        }

        return $html;
    }

    private function hydrateGlobalProposalTemplate(string $html, array $documentData): string
    {
        foreach ($this->globalTemplateVariables($documentData) as $key => $value) {
            $html = str_replace('{{proposal:'.$key.'}}', e((string) $value), $html);
        }

        return $this->sanitizeProposalHtml($html, false);
    }

    private function globalTemplateVariables(array $documentData): array
    {
        return collect($documentData)
            ->only([
                'year',
                'date',
                'reference_id',
                'crud_id',
                'service_type',
                'client_name',
                'business_name',
                'location',
                'company_phone',
                'company_email',
                'company_website',
                'company_address',
                'prepared_by_name',
                'prepared_by_id',
            ])
            ->map(fn ($value) => is_scalar($value) ? (string) $value : '')
            ->all();
    }

    private function abortUnlessAdminReviewer(): void
    {
        abort_unless(in_array((string) (auth()->user()?->role ?? ''), ['Admin', 'SuperAdmin'], true), 403);
    }

    private function generateProposalPdf(array $documentData, string $docxFileName): ?string
    {
        $relativePdfPath = 'generated-proposals/deals/'.preg_replace('/\.docx$/i', '.pdf', $docxFileName);

        return $this->generatePdfPreview('deals.proposal.pdf', [
            'documentData' => $documentData,
            'proposalHtml' => $this->resolveProposalDocumentHtml($documentData, $documentData['document_html'] ?? null, false),
        ], $relativePdfPath);
    }

    private function proposalPdfDownloadResponse(Deal $deal, DealProposal $proposal): \Symfony\Component\HttpFoundation\Response
    {
        $documentData = $this->documentData($deal, $proposal);
        $fileName = Str::slug((string) ($proposal->reference_id ?: $deal->deal_code ?: 'proposal')).'.pdf';
        $targetPath = 'generated-proposals/deals/'.$fileName;

        $pdfPath = $this->generatePdfPreview('deals.proposal.pdf', [
            'documentData' => $documentData,
            'proposalHtml' => $this->resolveProposalDocumentHtml($documentData, $proposal->document_html, false),
        ], $targetPath);

        if (! $pdfPath || ! Storage::disk('public')->exists($pdfPath)) {
            return redirect()->back()->with('error', 'Unable to generate PDF. Please ensure Chrome or Edge is installed on this server.');
        }

        return Storage::disk('public')->download($pdfPath, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function findProposalByClientToken(string $token): DealProposal
    {
        return DealProposal::query()
            ->where('client_access_token', $token)
            ->whereNotNull('client_form_sent_at')
            ->firstOrFail();
    }

    private function ensureClientAccessToken(DealProposal $proposal): string
    {
        if (filled($proposal->client_access_token) && ! ($proposal->client_access_expires_at?->isPast())) {
            return (string) $proposal->client_access_token;
        }

        $proposal->forceFill([
            'client_access_token' => Str::random(64),
            'client_access_expires_at' => now()->addDays(self::CLIENT_LINK_TTL_DAYS),
        ])->save();

        return (string) $proposal->client_access_token;
    }

    private function snapshotDealLineItems(Deal $deal): array
    {
        $items = app(DealProposalAggregationService::class)->resolveDealLineItems($deal);
        $sourceItems = is_array($deal->services_products) ? array_values($deal->services_products) : [];
        $snapshot = [];
        $usedIds = [];

        foreach (array_values($items) as $index => $item) {
            $sourceItem = $sourceItems[$index] ?? null;
            $itemId = is_array($sourceItem) && filled($sourceItem['id'] ?? null)
                ? (string) $sourceItem['id']
                : 'legacy-'.($index + 1);
            $baseId = $itemId;
            $suffix = 1;

            while (isset($usedIds[$itemId])) {
                $itemId = $baseId.'-'.$suffix++;
            }

            $usedIds[$itemId] = true;
            $snapshot[] = [
                'id' => $itemId,
                'type' => $item['type'],
                'name' => $item['name'],
                'item_code' => $item['item_code'],
                'service_id' => $item['service_id'],
                'product_id' => $item['product_id'],
                'associated_service_id' => $item['associated_service_id'] ?? null,
                'description' => $item['description'],
                'qty' => $item['qty'],
                'unit' => $item['unit'],
                'unitPrice' => $item['unit_price'],
                'price' => $item['unit_price'],
                'line_total' => $item['line_total'],
                'discount' => $item['discount'],
                'tax' => $item['tax'],
                'billing' => $item['billing'],
                'route' => $item['route'],
            ];
        }

        return $snapshot;
    }

    private function recordProposalItemDecisions(DealProposal $proposal, string $decision, string $clientName, ?string $comment): void
    {
        foreach (collect($proposal->line_items_snapshot ?? [])->values() as $index => $item) {
            $item = is_array($item) ? $item : ['name' => (string) $item];
            $proposal->itemResponses()->create([
                'line_item_id' => (string) ($item['id'] ?? ('legacy-'.($index + 1))),
                'line_item_name' => (string) ($item['name'] ?? 'Commercial Scope Item'),
                'decision' => $decision,
                'comment' => $comment,
                'client_name' => $clientName,
                'responded_at' => now(),
            ]);
        }
    }

    /**
     * @param array<int, array{line_item_name?: string, name?: string, decision?: string, comment?: ?string}> $items
     */
    private function notifyProposalCreatorOfClientDecision(
        DealProposal $proposal, ?Deal $deal, string $clientName, string $summary, array $items
    ): void {
        app(\App\Services\DealProposalDecisions::class)->notify($proposal->fresh());
    }
    /**
     * @return array<int, array{line_item_name: string, decision: string, comment: ?string}>
     */
    private function proposalDecisionItems(DealProposal $proposal, string $decision, ?string $comment): array
    {
        return collect($proposal->line_items_snapshot ?? [])
            ->values()
            ->map(function ($item) use ($decision, $comment): array {
                $item = is_array($item) ? $item : ['name' => (string) $item];

                return [
                    'line_item_name' => (string) ($item['name'] ?? 'Commercial Scope Item'),
                    'decision' => $decision,
                    'comment' => $comment,
                ];
            })
            ->all();
    }

    private function storeQuotationUpload(Request $request, DealProposal $proposal, string $source): void
    {
        $validated = $request->validate([
            'quotation_file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ]);

        $path = $validated['quotation_file']->store("deal-proposals/{$proposal->id}/quotations", 'public');
        $column = $source === 'client' ? 'quotation_client_file_path' : 'quotation_finance_file_path';

        if ($proposal->{$column} && Storage::disk('public')->exists($proposal->{$column})) {
            Storage::disk('public')->delete($proposal->{$column});
        }

        $proposal->forceFill([
            $column => $path,
            'quotation_status' => $source === 'finance' ? 'uploaded' : ($proposal->quotation_status ?: 'client_uploaded'),
        ])->save();
    }

    private function storeInvoiceUpload(Request $request, DealProposal $proposal): void
    {
        $validated = $request->validate([
            'invoice_file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ]);

        $path = $validated['invoice_file']->store("deal-proposals/{$proposal->id}/invoices", 'public');

        if ($proposal->invoice_file_path && Storage::disk('public')->exists($proposal->invoice_file_path)) {
            Storage::disk('public')->delete($proposal->invoice_file_path);
        }

        $proposal->forceFill([
            'invoice_file_path' => $path,
            'invoice_status' => 'uploaded',
            'invoice_uploaded_at' => now(),
        ])->save();
    }

    private function moveDealToStage(Deal $deal, string $stageName): void
    {
        $payload = ['stage' => $stageName];

        if (Schema::hasTable('deal_stages') && Schema::hasColumn('deals', 'stage_id')) {
            $stage = DealStage::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [Str::lower(trim($stageName))])
                ->first();

            if (! $stage) {
                $maxOrder = (int) DealStage::query()->max('order');
                $stage = DealStage::query()->create([
                    'name' => $stageName,
                    'order' => $maxOrder + 1,
                    'color' => $stageName === 'Activation' ? '#7c3aed' : ($stageName === 'Payment' ? '#059669' : '#1e293b'),
                ]);
            }

            $stageId = $stage->id;
            if ($stageId) {
                $payload['stage_id'] = $stageId;
            }
        }

        $deal->update($payload);

        if ($stageName === 'Closed Won') {
            $freshDeal = $deal->fresh();
            if ($freshDeal) {
                if ($freshDeal->projects()->doesntExist()) {
                    $this->projectProvisioner->createShellWorkspace($freshDeal);
                    $freshDeal->refresh();
                }

                $workspaces = $freshDeal->projects()->with('starts')->get();

                foreach ($workspaces as $workspace) {
                    $start = $workspace->starts()->latest()->first();
                    if ($start && strtolower((string) $start->status) !== 'approved') {
                        $start->forceFill([
                            'status' => 'pending_approval',
                            'approved_at' => null,
                            'approved_by_name' => null,
                            'rejected_at' => null,
                            'rejected_by_name' => null,
                            'rejection_reason' => null,
                        ])->save();
                    }
                }
            }
        }
    }

    private function markDealApprovedByFinance(Deal $deal, string $financeName): void
    {
        $payload = [
            'proposal_decision' => 'Approved',
        ];

        if (Schema::hasColumn('deals', 'proposal_status')) {
            $payload['proposal_status'] = 'Approved';
        }

        if (Schema::hasColumn('deals', 'deal_status')) {
            $payload['deal_status'] = 'approved';
        }

        if (Schema::hasColumn('deals', 'approved_at')) {
            $payload['approved_at'] = now();
        }

        if (Schema::hasColumn('deals', 'approved_by_name')) {
            $payload['approved_by_name'] = $financeName;
        }

        $deal->forceFill($payload)->save();
    }

    private function abortExpiredClientProposal(DealProposal $proposal): void
    {
        abort_if(
            $proposal->client_access_expires_at && $proposal->client_access_expires_at->isPast(),
            403,
            'This proposal link has expired.'
        );
    }

    private function assertClientProposalCanRespond(DealProposal $proposal): void
    {
        abort_unless($proposal->client_form_sent_at, 403, 'This proposal has not been issued to a client.');
        abort_unless($proposal->is_current && ! $proposal->client_submitted_at, 409, 'This proposal version is closed for decisions. Request a new revision for changes.');
        abort_unless(
            in_array(strtolower((string) $proposal->status), ['sent', 'under_review'], true),
            409,
            'This proposal has already received a final decision.'
        );
    }

    private function clientNameForDeal(?Deal $deal): string
    {
        if (! $deal) {
            return 'Client';
        }

        return trim(collect([
            $deal->first_name ?: $deal->contact?->first_name,
            $deal->middle_name ?: $deal->contact?->middle_name,
            $deal->last_name ?: $deal->contact?->last_name,
        ])->filter()->implode(' ')) ?: ($deal->company_name ?: 'Client');
    }
}
