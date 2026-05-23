@php
    $selectedCitizenshipType = old('citizenship_type', $cifData['citizenship_type'] ?? '');
    $selectedCivilStatus = old('civil_status', $cifData['civil_status'] ?? '');
    $defaultClientSignatureName = trim(implode(' ', array_filter([
        $contact->first_name,
        $contact->middle_name,
        $contact->last_name,
    ]))) ?: 'Client';
    $defaultClientSignaturePosition = $contact->position ?: 'Client';
    $defaultReferredDate = optional($contact->created_at ?: $contact->business_date ?: $contact->intake_date)->toDateString() ?? '';
    $defaultReferredBy = $contact->created_by ?: ($contact->owner_name ?: ($contact->referred_by ?: ''));
    $defaultSalesMarketing = $contact->sales_marketing ?: ($contact->owner_name ?: $defaultReferredBy);
    $defaultPresident = 'John Kelly Abalde';
@endphp

<form method="POST" action="{{ route('contacts.cif.save', $contact->id) }}" class="space-y-5" data-cif-card-form>
    @csrf

    <section class="rounded-lg border border-gray-200 p-4">
        <h3 class="mb-3 text-sm font-semibold text-gray-900">Top / Meta</h3>
        <div class="grid gap-4 md:grid-cols-2">
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Date</label><input type="date" name="cif_date" value="{{ old('cif_date', $cifData['cif_date'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">CIF No.</label><input name="cif_no" value="{{ old('cif_no', $cifData['cif_no'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 p-4">
        <h3 class="mb-3 text-sm font-semibold text-gray-900">Identity</h3>
        <div class="grid gap-4 md:grid-cols-2">
            <div><label class="mb-1 block text-sm font-medium text-gray-700">First Name</label><input required name="first_name" value="{{ old('first_name', $cifData['first_name'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Last Name</label><input required name="last_name" value="{{ old('last_name', $cifData['last_name'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Name Extension</label><input name="name_extension" value="{{ old('name_extension', $cifData['name_extension'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Middle Name</label><input name="middle_name" value="{{ old('middle_name', $cifData['middle_name'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
        </div>
        <div class="mt-3 grid gap-2 sm:grid-cols-2">
            <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm"><input type="checkbox" name="no_middle_name" value="1" @checked(old('no_middle_name', $cifData['no_middle_name'] ?? false))> I have no Middle Name</label>
            <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm"><input type="checkbox" name="only_first_name" value="1" @checked(old('only_first_name', $cifData['only_first_name'] ?? false))> I only have a First Name</label>
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 p-4">
        <h3 class="mb-3 text-sm font-semibold text-gray-900">Address</h3>
        <div class="grid gap-4 md:grid-cols-3">
            <div class="md:col-span-2"><label class="mb-1 block text-sm font-medium text-gray-700">Present Address</label><input name="present_address_line1" value="{{ old('present_address_line1', $cifData['present_address_line1'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">ZIP Code</label><input name="zip_code" value="{{ old('zip_code', $cifData['zip_code'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div class="md:col-span-3"><label class="mb-1 block text-sm font-medium text-gray-700">Present Address (2nd line)</label><input name="present_address_line2" value="{{ old('present_address_line2', $cifData['present_address_line2'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 p-4">
        <h3 class="mb-3 text-sm font-semibold text-gray-900">Contact</h3>
        <div class="grid gap-4 md:grid-cols-2">
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Email Address</label><input type="email" name="email" value="{{ old('email', $cifData['email'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Phone No. / Mobile No.</label><input name="mobile" value="{{ old('mobile', $cifData['mobile'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 p-4">
        <h3 class="mb-3 text-sm font-semibold text-gray-900">Birth / Citizenship</h3>
        <div class="grid gap-4 md:grid-cols-3">
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Date of Birth</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth', $cifData['date_of_birth'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Place of Birth</label><input name="place_of_birth" value="{{ old('place_of_birth', $cifData['place_of_birth'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Citizenship / Nationality</label><input name="citizenship_nationality" value="{{ old('citizenship_nationality', $cifData['citizenship_nationality'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" data-citizenship-nationality-input @if($selectedCitizenshipType === 'filipino') readonly @endif></div>
        </div>
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach (['filipino' => 'Filipino', 'foreigner' => 'Foreigner', 'dual_citizen' => 'Dual Citizen'] as $value => $label)
                <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm"><input type="radio" name="citizenship_type" value="{{ $value }}" @checked(old('citizenship_type', $cifData['citizenship_type'] ?? '') === $value)> {{ $label }}</label>
            @endforeach
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 p-4">
        <h3 class="mb-3 text-sm font-semibold text-gray-900">Gender / Civil Status</h3>
        <div class="mb-3 flex flex-wrap gap-2">
            @foreach (['male' => 'Male', 'female' => 'Female'] as $value => $label)
                <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm"><input type="radio" name="gender" value="{{ $value }}" @checked(old('gender', $cifData['gender'] ?? '') === $value)> {{ $label }}</label>
            @endforeach
        </div>
        <div class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Civil Status</label>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-[repeat(4,minmax(0,max-content))_minmax(260px,1fr)] lg:items-center">
                    @foreach (['single' => 'Single', 'separated' => 'Separated', 'widowed' => 'Widowed', 'married' => 'Married'] as $value => $label)
                        <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm"><input type="radio" name="civil_status" value="{{ $value }}" @checked(old('civil_status', $cifData['civil_status'] ?? '') === $value)> {{ $label }}</label>
                    @endforeach
                    <div class="lg:col-start-5 lg:row-start-1 lg:min-w-0 lg:justify-self-stretch" data-spouse-row @if($selectedCivilStatus !== 'married') style="display:none;" @endif>
                        <div class="flex min-w-0 items-center gap-2">
                            <label for="edit_spouse_name" class="shrink-0 whitespace-nowrap text-sm font-medium text-gray-700">Spouse's Name</label>
                            <input id="edit_spouse_name" name="spouse_name" value="{{ old('spouse_name', $cifData['spouse_name'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 p-4">
        <h3 class="mb-3 text-sm font-semibold text-gray-900">Work / IDs</h3>
        <div class="grid gap-4 md:grid-cols-2">
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Nature of Work / Business</label><input name="nature_of_work_business" value="{{ old('nature_of_work_business', $cifData['nature_of_work_business'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">TIN</label><input name="tin" value="{{ old('tin', $cifData['tin'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Other Government ID</label><input name="other_government_id" value="{{ old('other_government_id', $cifData['other_government_id'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">ID Number</label><input name="id_number" value="{{ old('id_number', $cifData['id_number'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div class="md:col-span-2"><label class="mb-1 block text-sm font-medium text-gray-700">Mother's Maiden Name</label><input name="mothers_maiden_name" value="{{ old('mothers_maiden_name', $cifData['mothers_maiden_name'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 p-4">
        <h3 class="mb-3 text-sm font-semibold text-gray-900">Source of Funds</h3>
        <div class="grid gap-2 sm:grid-cols-2 md:grid-cols-3">
            @foreach (['salary' => 'Salary', 'remittance' => 'Remittance', 'business' => 'Business', 'others' => 'Others', 'commission_fees' => 'Commission / Fees', 'retirement_pension' => 'Retirement / Pension'] as $value => $label)
                <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm"><input type="checkbox" name="source_of_funds[]" value="{{ $value }}" @checked(in_array($value, old('source_of_funds', $cifData['source_of_funds'] ?? []), true))> {{ $label }}</label>
            @endforeach
        </div>
        <div class="mt-3"><label class="mb-1 block text-sm font-medium text-gray-700">Others (Specify)</label><input name="source_of_funds_other_text" value="{{ old('source_of_funds_other_text', $cifData['source_of_funds_other_text'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
    </section>

    <section class="rounded-lg border border-gray-200 p-4" data-foreign-section @if(!in_array($selectedCitizenshipType, ['foreigner', 'dual_citizen'], true)) style="display:none;" @endif>
        <h3 class="mb-3 text-sm font-semibold text-gray-900">Foreigner Details</h3>
        <div class="grid gap-4 md:grid-cols-3">
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Foreigner Passport No.</label><input name="foreigner_passport_no" value="{{ old('foreigner_passport_no', $cifData['foreigner_passport_no'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Expiry Date</label><input type="date" name="foreigner_passport_expiry_date" value="{{ old('foreigner_passport_expiry_date', $cifData['foreigner_passport_expiry_date'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Place of Issue</label><input name="foreigner_passport_place_of_issue" value="{{ old('foreigner_passport_place_of_issue', $cifData['foreigner_passport_place_of_issue'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Foreigner ACR ID No.</label><input name="foreigner_acr_id_no" value="{{ old('foreigner_acr_id_no', $cifData['foreigner_acr_id_no'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Expiry Date</label><input type="date" name="foreigner_acr_expiry_date" value="{{ old('foreigner_acr_expiry_date', $cifData['foreigner_acr_expiry_date'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Place of Issue</label><input name="foreigner_acr_place_of_issue" value="{{ old('foreigner_acr_place_of_issue', $cifData['foreigner_acr_place_of_issue'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div class="md:col-span-3"><label class="mb-1 block text-sm font-medium text-gray-700">Visa Status</label><input name="visa_status" value="{{ old('visa_status', $cifData['visa_status'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 p-4">
        <h3 class="mb-3 text-sm font-semibold text-gray-900">Acknowledgment / Signature Lines</h3>
        <div class="grid gap-4 md:grid-cols-2">
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Signature Name (Left)</label><input name="sig_name_left" value="{{ old('sig_name_left', $cifData['sig_name_left'] ?? $defaultClientSignatureName) }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Signature Name (Right)</label><input name="sig_name_right" value="{{ old('sig_name_right', $cifData['sig_name_right'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Position (Left)</label><input name="sig_position_left" value="{{ old('sig_position_left', $cifData['sig_position_left'] ?? $defaultClientSignaturePosition) }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Position (Right)</label><input name="sig_position_right" value="{{ old('sig_position_right', $cifData['sig_position_right'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 p-4">
        <h3 class="mb-3 text-sm font-semibold text-gray-900">Client Onboarding Requirements</h3>
        <div class="grid gap-2 text-sm">
            <div>1 | 2 Valid Government IDs</div>
            <div>2 | TIN ID (Signatory/Representative/Stockholders/Partners/Others)</div>
            <div>3 | AUTHORIZED SIGNATORY/SIGNATORY (Sole / OPC / Individual) SPECIMEN SIGNATURE CARD</div>
            <div data-foreign-requirement @if(!in_array($selectedCitizenshipType, ['foreigner', 'dual_citizen'], true)) style="display:none;" @endif>4 | If Foreign Signatory/Director/Officer: Passport (Bio Page)</div>
            <div data-foreign-requirement @if(!in_array($selectedCitizenshipType, ['foreigner', 'dual_citizen'], true)) style="display:none;" @endif>5 | If Foreign Signatory/Director/Officer: Valid Visa / ACR I-Card</div>
            <div data-foreign-requirement @if(!in_array($selectedCitizenshipType, ['foreigner', 'dual_citizen'], true)) style="display:none;" @endif>6 | If Foreign Signatory/Director/Officer Alien Employment Permit (AEP)</div>
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 p-4">
        <h3 class="mb-3 text-sm font-semibold text-gray-900">Internal Footer</h3>
        <div class="grid gap-4 md:grid-cols-2">
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Referred By</label><input name="referred_by_footer" value="{{ old('referred_by_footer', $cifData['referred_by_footer'] ?? $defaultReferredBy) }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Date</label><input type="date" name="referred_date" value="{{ old('referred_date', $cifData['referred_date'] ?? $defaultReferredDate) }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Sales &amp; Marketing</label><input name="sales_marketing_footer" value="{{ old('sales_marketing_footer', $cifData['sales_marketing_footer'] ?? $defaultSalesMarketing) }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Finance</label><input name="finance_footer" value="{{ old('finance_footer', $cifData['finance_footer'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div class="md:col-span-2"><label class="mb-1 block text-sm font-medium text-gray-700">President</label><input name="president_footer" value="{{ old('president_footer', $cifData['president_footer'] ?? $defaultPresident) }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 p-4">
        <h3 class="mb-3 text-sm font-semibold text-gray-900">KYC Internal</h3>
        <div class="grid gap-4 md:grid-cols-2">
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Owner Name</label><input name="owner_name" value="{{ old('owner_name', $cifData['owner_name'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">KYC Status</label><input name="kyc_status" value="{{ old('kyc_status', $cifData['kyc_status'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Date Verified</label><input type="date" name="date_verified" value="{{ old('date_verified', $cifData['date_verified'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Verified By</label><input name="verified_by" value="{{ old('verified_by', $cifData['verified_by'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"></div>
            <div class="md:col-span-2"><label class="mb-1 block text-sm font-medium text-gray-700">Remarks</label><textarea name="remarks" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('remarks', $cifData['remarks'] ?? '') }}</textarea></div>
        </div>
    </section>

    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
        <a href="{{ route('contacts.show', ['contact' => $contact->id, 'tab' => 'kyc']) }}" class="h-10 rounded-lg border border-gray-300 px-4 text-sm text-gray-700 hover:bg-gray-50">Cancel</a>
        <button type="submit" class="h-10 rounded-lg bg-blue-600 px-5 text-sm font-medium text-white hover:bg-blue-700">Save CIF</button>
    </div>
</form>

<script>
    (function () {
        const form = document.querySelector('[data-cif-card-form]');
        if (!form) return;

        const spouseRow = form.querySelector('[data-spouse-row]');
        const foreignSection = form.querySelector('[data-foreign-section]');
        const foreignRequirements = form.querySelectorAll('[data-foreign-requirement]');
        const citizenshipNationalityInput = form.querySelector('[data-citizenship-nationality-input]');

        const getSelectedValue = (name) => form.querySelector(`input[name="${name}"]:checked`)?.value || '';

        const syncVisibility = () => {
            const citizenshipType = getSelectedValue('citizenship_type');
            const civilStatus = getSelectedValue('civil_status');
            const showForeign = citizenshipType === 'foreigner' || citizenshipType === 'dual_citizen';

            if (citizenshipNationalityInput) {
                if (citizenshipType === 'filipino') {
                    citizenshipNationalityInput.value = 'FILIPINO';
                    citizenshipNationalityInput.readOnly = true;
                    citizenshipNationalityInput.classList.add('bg-gray-100');
                } else {
                    citizenshipNationalityInput.readOnly = false;
                    citizenshipNationalityInput.classList.remove('bg-gray-100');
                    if (citizenshipNationalityInput.value.trim().toUpperCase() === 'FILIPINO') {
                        citizenshipNationalityInput.value = '';
                    }
                }
            }

            if (spouseRow) {
                spouseRow.style.display = civilStatus === 'married' ? '' : 'none';
            }

            if (foreignSection) {
                foreignSection.style.display = showForeign ? '' : 'none';
            }

            foreignRequirements.forEach((item) => {
                item.style.display = showForeign ? '' : 'none';
            });
        };

        form.querySelectorAll('input[name="citizenship_type"], input[name="civil_status"]').forEach((input) => {
            input.addEventListener('change', syncVisibility);
        });

        syncVisibility();
    })();
</script>
