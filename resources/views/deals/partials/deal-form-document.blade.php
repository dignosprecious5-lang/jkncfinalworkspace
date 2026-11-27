@php
    $data = $dealFormData ?? ($detail ?? []);
    $logoBase64 = $data['logo_base64'] ?? ($logoBase64 ?? '');
    if (! filled($logoBase64)) {
        $logoPath = public_path('images/imaglogo.png');
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }
    }

    $val = static function ($key, $fallback = '-') use ($data): string {
        $v = $data[$key] ?? null;
        if (is_array($v)) {
            $v = collect($v)->flatten()->filter(fn ($i) => filled($i))->implode(', ');
        }
        return filled($v) ? (string) $v : $fallback;
    };

    $requirements = $data['client_requirements'] ?? [];
    if (! is_array($requirements) || count($requirements) === 0) {
        $statusMap = is_array($data['requirements_status_map'] ?? null) ? $data['requirements_status_map'] : [];
        $requirements = [
            [
                'requirement' => 'Client Contact Form',
                'provided' => 'Yes',
                'pending' => '-',
                'required_action' => '-',
            ],
            [
                'requirement' => 'Deal Form',
                'provided' => 'Yes',
                'pending' => '-',
                'required_action' => '-',
            ],
            [
                'requirement' => 'Business Information Form',
                'provided' => 'Yes',
                'pending' => '-',
                'required_action' => '-',
            ],
            [
                'requirement' => 'Client Information Form',
                'provided' => '-',
                'pending' => 'Yes',
                'required_action' => '-',
            ],
            [
                'requirement' => 'Service Task Activation & Routing Tracker (Start)',
                'provided' => '-',
                'pending' => 'Yes',
                'required_action' => '-',
            ],
        ];
    }
@endphp

<div class="mx-auto w-full max-w-5xl bg-white shadow-sm border border-[#203354] text-[11px] font-sans leading-tight text-[#0f172a]">

    <!-- 1. HEADER -->
    <div class="flex items-center justify-between border-b border-[#203354] px-4 py-3 bg-white">
        <div class="w-1/2">
            @if(filled($logoBase64))
                <img src="{{ $logoBase64 }}" class="h-10 max-h-12 w-auto object-contain" alt="John Kelly & Company">
            @else
                <div class="font-serif text-xl font-bold tracking-tight text-[#0b1d3a]">John Kelly</div>
                <div class="font-serif text-sm italic text-[#0b1d3a]">&amp; Company</div>
            @endif
        </div>
        <div class="w-1/2 text-right">
            <h1 class="text-base font-extrabold uppercase tracking-wide text-[#0f172a]">CONSULTING &amp; DEAL FORM</h1>
            <div class="mt-1 text-[11px] text-slate-700">
                Deal No. <strong class="font-bold text-black">{{ $val('deal_no', $val('deal_form_no', '-')) }}</strong> &nbsp;&nbsp;&nbsp;&nbsp; Date <strong class="font-bold text-black">{{ $val('header_date', $val('date', date('m/d/Y'))) }}</strong>
            </div>
        </div>
    </div>

    <!-- 2. DEAL SUMMARY -->
    <div class="grid grid-cols-12 border-b border-[#203354] text-[10px]">
        <div class="col-span-4 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Deal Name</span>
            <div class="mt-0.5 text-[11px] font-medium text-slate-900">{{ $val('deal_name', $val('deal_title', '-')) }}</div>
        </div>
        <div class="col-span-2 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Stage</span>
            <div class="mt-0.5 text-[11px] font-medium text-slate-900">{{ $val('stage', '-') }}</div>
        </div>
        <div class="col-span-3 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Engagement Type</span>
            <div class="mt-0.5 text-[11px] font-medium text-slate-900">{{ $val('engagement_type', $val('engagement_term', '-')) }}</div>
        </div>
        <div class="col-span-3 p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Total Value</span>
            <div class="mt-0.5 text-[11px] font-semibold text-slate-900">{{ $val('total_value', 'P0.00') }}</div>
        </div>
    </div>

    <!-- 3. CONTACT INFORMATION -->
    <div class="border-b border-[#203354] bg-[#0b2a68] px-3 py-1 text-center text-[11px] font-bold uppercase tracking-wider text-white">Contact Information</div>
    <div class="grid grid-cols-12 border-b border-[#203354] text-[10px]">
        <div class="col-span-2 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Salutation</span>
            <div class="mt-0.5 text-[11px]">{{ $val('salutation', '-') }}</div>
        </div>
        <div class="col-span-2 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">First Name</span>
            <div class="mt-0.5 text-[11px]">{{ $val('first_name', '-') }}</div>
        </div>
        <div class="col-span-2 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Middle Initial</span>
            <div class="mt-0.5 text-[11px]">{{ $val('middle_initial', '-') }}</div>
        </div>
        <div class="col-span-2 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Last Name</span>
            <div class="mt-0.5 text-[11px]">{{ $val('last_name', '-') }}</div>
        </div>
        <div class="col-span-2 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Name Extension</span>
            <div class="mt-0.5 text-[11px]">{{ $val('name_extension', '-') }}</div>
        </div>
        <div class="col-span-2 p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Sex</span>
            <div class="mt-0.5 text-[11px]">{{ $val('sex', '-') }}</div>
        </div>
    </div>
    <div class="grid grid-cols-12 border-b border-[#203354] text-[10px]">
        <div class="col-span-3 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Date of Birth</span>
            <div class="mt-0.5 text-[11px]">{{ $val('date_of_birth', '-') }}</div>
        </div>
        <div class="col-span-3 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Email Address</span>
            <div class="mt-0.5 text-[11px]">{{ $val('email', $val('email_address', '-')) }}</div>
        </div>
        <div class="col-span-3 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Mobile Number</span>
            <div class="mt-0.5 text-[11px]">{{ $val('mobile', $val('mobile_number', '-')) }}</div>
        </div>
        <div class="col-span-3 p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Position / Designation</span>
            <div class="mt-0.5 text-[11px]">{{ $val('position', $val('contact_person_position', '-')) }}</div>
        </div>
    </div>
    <div class="grid grid-cols-12 border-b border-[#203354] text-[10px]">
        <div class="col-span-4 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Address</span>
            <div class="mt-0.5 text-[11px]">{{ $val('address', '-') }}</div>
        </div>
        <div class="col-span-4 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Company</span>
            <div class="mt-0.5 text-[11px]">{{ $val('company', $val('company_name', '-')) }}</div>
        </div>
        <div class="col-span-4 p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Company Address</span>
            <div class="mt-0.5 text-[11px]">{{ $val('company_address', '-') }}</div>
        </div>
    </div>

    <!-- 4. SERVICE IDENTIFICATION -->
    <div class="border-b border-[#203354] bg-[#0b2a68] px-3 py-1 text-center text-[11px] font-bold uppercase tracking-wider text-white">Service Identification</div>
    <div class="grid grid-cols-12 border-b border-[#203354] text-[10px]">
        <div class="col-span-6 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Service Area</span>
            <div class="mt-0.5 text-[11px]">{{ $val('service_area', '-') }}</div>
        </div>
        <div class="col-span-6 p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Services</span>
            <div class="mt-0.5 text-[11px]">{{ $val('services', '-') }}</div>
        </div>
    </div>

    <!-- 5. PRODUCTS -->
    <div class="border-b border-[#203354] bg-[#0b2a68] px-3 py-1 text-center text-[11px] font-bold uppercase tracking-wider text-white">Products</div>
    <div class="grid grid-cols-12 border-b border-[#203354] text-[10px]">
        <div class="col-span-6 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Products / Deliverables</span>
            <div class="mt-0.5 text-[11px]">{{ $val('products', $val('products_deliverables', '-')) }}</div>
        </div>
        <div class="col-span-6 p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Scope of Work</span>
            <div class="mt-0.5 text-[11px]">{{ $val('scope_of_work', '-') }}</div>
        </div>
    </div>

    <!-- 6. CLIENT REQUIREMENTS -->
    <div class="border-b border-[#203354] bg-[#163b7a] px-3 py-1 text-center text-[11px] font-bold uppercase tracking-wider text-white">Client Requirements</div>
    <table class="w-full border-collapse border-b border-[#203354] text-[10px]">
        <thead>
            <tr class="border-b border-[#203354]">
                <th class="border-r border-[#203354] p-2 text-left font-bold uppercase text-black">Requirement</th>
                <th class="w-24 border-r border-[#203354] p-2 text-center font-bold uppercase text-black">Provided</th>
                <th class="w-24 border-r border-[#203354] p-2 text-center font-bold uppercase text-black">Pending</th>
                <th class="w-36 p-2 text-center font-bold uppercase text-black">Required Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requirements as $req)
                <tr class="border-b border-[#203354] last:border-b-0">
                    <td class="border-r border-[#203354] p-2 text-[11px]">{{ $req['requirement'] ?? '-' }}</td>
                    <td class="border-r border-[#203354] p-2 text-center text-[11px]">{{ $req['provided'] ?? '-' }}</td>
                    <td class="border-r border-[#203354] p-2 text-center text-[11px]">{{ $req['pending'] ?? '-' }}</td>
                    <td class="p-2 text-center text-[11px]">{{ $req['required_action'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- 7. TIMELINE & ASSESSMENT -->
    <div class="border-b border-[#203354] bg-[#163b7a] px-3 py-1 text-center text-[11px] font-bold uppercase tracking-wider text-white">Timeline &amp; Assessment</div>
    <div class="grid grid-cols-12 border-b border-[#203354] text-[10px]">
        <div class="col-span-3 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Planned Start Date</span>
            <div class="mt-0.5 text-[11px]">{{ $val('planned_start_date', '-') }}</div>
        </div>
        <div class="col-span-3 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Estimated Duration</span>
            <div class="mt-0.5 text-[11px]">{{ $val('estimated_duration', '-') }}</div>
        </div>
        <div class="col-span-3 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Estimated Completion Date</span>
            <div class="mt-0.5 text-[11px]">{{ $val('estimated_completion_date', '-') }}</div>
        </div>
        <div class="col-span-3 p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Confirmed Delivery Date</span>
            <div class="mt-0.5 text-[11px]">{{ $val('confirmed_delivery_date', '-') }}</div>
        </div>
    </div>
    <div class="grid grid-cols-12 border-b border-[#203354] text-[10px]">
        <div class="col-span-4 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Timeline Notes</span>
            <div class="mt-0.5 text-[11px]">{{ $val('timeline_notes', '-') }}</div>
        </div>
        <div class="col-span-3 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Service Complexity</span>
            <div class="mt-0.5 text-[11px]">{{ $val('service_complexity', '-') }}</div>
        </div>
        <div class="col-span-5 p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Professional Support Required</span>
            <div class="mt-0.5 text-[11px]">{{ $val('professional_support_required', $val('support_required', '-')) }}</div>
        </div>
    </div>

    <!-- 8. FEES & PAYMENT -->
    <div class="border-b border-[#203354] bg-[#0b2a68] px-3 py-1 text-center text-[11px] font-bold uppercase tracking-wider text-white">Fees &amp; Payment</div>
    <div class="grid grid-cols-12 border-b border-[#203354] text-[10px]">
        <div class="col-span-3 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Professional Fee</span>
            <div class="mt-0.5 text-[11px]">{{ $val('professional_fee', $val('estimated_professional_fee', '-')) }}</div>
        </div>
        <div class="col-span-3 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Government Fees</span>
            <div class="mt-0.5 text-[11px]">{{ $val('government_fees', $val('estimated_government_fees', 'P0.00')) }}</div>
        </div>
        <div class="col-span-3 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Service Support Fee</span>
            <div class="mt-0.5 text-[11px]">{{ $val('service_support_fee', $val('estimated_service_support_fee', 'P0.00')) }}</div>
        </div>
        <div class="col-span-3 p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Payment Terms</span>
            <div class="mt-0.5 text-[11px]">{{ $val('payment_terms', '-') }}</div>
        </div>
    </div>

    <!-- 9. PROPOSAL & INTERNAL ASSIGNMENT -->
    <div class="border-b border-[#203354] bg-[#0b2a68] px-3 py-1 text-center text-[11px] font-bold uppercase tracking-wider text-white">Proposal &amp; Internal Assignment</div>
    <div class="grid grid-cols-12 border-b border-[#203354] text-[10px]">
        <div class="col-span-4 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Proposal Decision</span>
            <div class="mt-0.5 text-[11px]">{{ $val('proposal_decision', '-') }}</div>
        </div>
        <div class="col-span-4 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Decline Reason</span>
            <div class="mt-0.5 text-[11px]">{{ $val('decline_reason', '-') }}</div>
        </div>
        <div class="col-span-4 p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Service Department / Unit</span>
            <div class="mt-0.5 text-[11px]">{{ $val('service_department_unit', $val('service_department', '-')) }}</div>
        </div>
    </div>
    <div class="grid grid-cols-12 border-b border-[#203354] text-[10px]">
        <div class="col-span-6 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Assigned Consultant</span>
            <div class="mt-0.5 text-[11px]">{{ $val('assigned_consultant', $val('lead_consultant', '-')) }}</div>
        </div>
        <div class="col-span-6 p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Assigned Associate</span>
            <div class="mt-0.5 text-[11px]">{{ $val('assigned_associate', $val('lead_associate_assigned', '-')) }}</div>
        </div>
    </div>

    <!-- 10. NOTES & APPROVAL -->
    <div class="border-b border-[#203354] bg-[#0b2a68] px-3 py-1 text-center text-[11px] font-bold uppercase tracking-wider text-white">Notes &amp; Approval</div>
    <div class="grid grid-cols-12 border-b border-[#203354] text-[10px]">
        <div class="col-span-6 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Consultant Notes</span>
            <div class="mt-0.5 text-[11px] min-h-[20px]">{{ $val('consultant_notes', '-') }}</div>
        </div>
        <div class="col-span-6 p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Associate Notes</span>
            <div class="mt-0.5 text-[11px] min-h-[20px]">{{ $val('associate_notes', '-') }}</div>
        </div>
    </div>

    <!-- 11. SIGNATURES AND APPROVAL RECORDS -->
    <div class="grid grid-cols-12 border-b border-[#203354] text-[10px]">
        <div class="col-span-3 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Prepared By</span>
            <div class="mt-0.5 text-[11px]">{{ $val('prepared_by', '-') }}</div>
        </div>
        <div class="col-span-3 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Reviewed By</span>
            <div class="mt-0.5 text-[11px]">{{ $val('reviewed_by', '-') }}</div>
        </div>
        <div class="col-span-3 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Date</span>
            <div class="mt-0.5 text-[11px]">{{ $val('internal_date', date('M d, Y')) }}</div>
        </div>
        <div class="col-span-3 p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Client Fullname &amp; Signature</span>
            <div class="mt-0.5 text-[11px]">{{ $val('client_fullname_signature', '-') }}</div>
        </div>
    </div>
    <div class="grid grid-cols-12 text-[10px]">
        <div class="col-span-2 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Referred / Closed By</span>
            <div class="mt-0.5 text-[11px]">{{ $val('referred_closed_by', '-') }}</div>
        </div>
        <div class="col-span-2 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Sales &amp; Marketing</span>
            <div class="mt-0.5 text-[11px]">{{ $val('internal_sales_marketing', $val('sales_marketing', '-')) }}</div>
        </div>
        <div class="col-span-2 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Lead Consultant</span>
            <div class="mt-0.5 text-[11px]">{{ $val('lead_consultant', '-') }}</div>
        </div>
        <div class="col-span-2 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Lead Associate</span>
            <div class="mt-0.5 text-[11px]">{{ $val('lead_associate_assigned', $val('lead_associate', '-')) }}</div>
        </div>
        <div class="col-span-2 border-r border-[#203354] p-2">
            <span class="block text-[9px] font-bold uppercase text-black">Finance</span>
            <div class="mt-0.5 text-[11px]">{{ $val('internal_finance', $val('finance', '-')) }}</div>
        </div>
        <div class="col-span-2 p-2">
            <span class="block text-[9px] font-bold uppercase text-black">President</span>
            <div class="mt-0.5 text-[11px]">{{ $val('internal_president', $val('president', 'John Kelly Abalde')) }}</div>
        </div>
    </div>

    <!-- 12. FOOTER -->
    <div class="border-t border-[#203354] px-3 py-1 text-right text-[10px] italic text-slate-500">
        John Kelly &amp; Company
    </div>

</div>
