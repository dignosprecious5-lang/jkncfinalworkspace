@php
    $companyName = old('business_name', $bif->business_name ?: $company->company_name ?: 'Client');
    $selectedOrganization = old('business_organization', $bif->business_organization);
    $showSoleRequirements = $selectedOrganization === 'sole_proprietorship';
    $showJuridicalRequirements = in_array($selectedOrganization, ['partnership', 'corporation', 'cooperative', 'ngo', 'other'], true);
    $showRequirements = $showSoleRequirements || $showJuridicalRequirements;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Information Form</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#eef4ff] text-slate-900">
    <div class="min-h-screen py-8">
        <div class="mx-auto max-w-6xl px-4">
            <div id="clientBifLiveFeedback" class="mb-4 hidden border px-4 py-3 text-sm"></div>
            @if (session('bif_success'))
                <div class="mb-4 border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('bif_success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <p class="font-semibold">Please review the form.</p>
                    <ul class="mt-2 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="overflow-hidden border border-slate-300 bg-white shadow-sm">
                <div class="h-2 bg-[#21409a]"></div>
                <div class="grid gap-6 px-6 py-7 lg:grid-cols-[1.12fr_0.88fr] lg:px-10">
                    <div class="space-y-6">
                        <img src="{{ asset('images/imaglogo.png') }}" alt="John Kelly and Company" class="h-12 w-auto object-contain">
                        <div class="space-y-6 text-slate-900">
                            <p class="text-xl leading-relaxed">Dear <span class="font-semibold" data-company-name-heading>{{ $companyName }}</span>,</p>
                            <p class="text-xl leading-relaxed">Good day.</p>
                            <p class="max-w-2xl text-[2.2rem] font-semibold leading-[1.18]">
                                To get things started smoothly, we kindly ask you to complete your Business Information Form (BIF).
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="border border-slate-300 bg-[#f8fbff] p-5">
                            <p class="text-lg font-semibold text-slate-900">Important</p>
                            <p class="mt-4 text-lg leading-9 text-slate-700">
                                The form is mobile-friendly and can be completed using your phone or computer. You will also be asked to upload the applicable onboarding documents below based on your business organization.
                            </p>
                        </div>
                        <div class="border border-slate-300 bg-slate-50 p-5">
                            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Secure Link</p>
                            <p class="mt-3 text-sm leading-7 text-slate-600">
                                This secure form saves directly to your company KYC record and can be revisited while your link is active.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <form id="clientBifForm" method="POST" action="{{ $clientFormAction }}" enctype="multipart/form-data" class="space-y-5 border-x border-b border-slate-300 bg-white px-4 py-5 md:px-6">
                @csrf

                <section class="border border-slate-300 bg-white p-5">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
                        <div>
                            <h1 class="text-2xl font-semibold text-slate-900">Business Information Form</h1>
                            <p class="mt-1 text-sm text-slate-500">Please complete any missing company details below.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ $clientPreviewUrl }}" target="_blank" rel="noopener noreferrer" class="border border-slate-300 px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50">
                                Preview BIF
                            </a>
                            <a href="{{ $clientDownloadUrl }}" target="_blank" rel="noopener noreferrer" class="bg-[#3153d4] px-3 py-2 text-sm font-medium text-white transition hover:bg-[#2745b3]">
                                Download / Print BIF
                            </a>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Date</label>
                            <input id="bif_date" type="date" name="bif_date" value="{{ old('bif_date', optional($bif->bif_date)->format('Y-m-d') ?: now()->format('Y-m-d')) }}" class="h-11 w-full border border-slate-300 px-3 text-sm" required>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">BIF No.</label>
                            <input type="text" value="{{ $bif->bif_no ?: 'Will be assigned after submission' }}" class="h-11 w-full border border-slate-300 bg-slate-50 px-3 text-sm text-slate-500" readonly>
                        </div>
                    </div>
                    <input type="hidden" name="client_type" value="{{ old('client_type', $bif->client_type ?: 'new_client') }}">
                </section>

                <section class="border border-slate-300 bg-white p-5">
                    <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-slate-900">Business Details</h2>
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div>
                            <label for="business_organization" class="mb-1 block text-sm font-medium text-slate-700">Business Organization</label>
                            <select id="business_organization" name="business_organization" class="h-11 w-full border border-slate-300 px-3 text-sm">
                                <option value="">Select organization</option>
                                @foreach ($organizationOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('business_organization', $bif->business_organization) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="nationality_status" class="mb-1 block text-sm font-medium text-slate-700">Nationality</label>
                            <select id="nationality_status" name="nationality_status" class="h-11 w-full border border-slate-300 px-3 text-sm">
                                @foreach ($nationalityOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('nationality_status', $bif->nationality_status ?: 'filipino') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="office_type" class="mb-1 block text-sm font-medium text-slate-700">Type of Office</label>
                            <select id="office_type" name="office_type" class="h-11 w-full border border-slate-300 px-3 text-sm">
                                <option value="">Select office type</option>
                                @foreach ($officeTypeOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('office_type', $bif->office_type) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2 xl:col-span-3">
                            <label for="business_name" class="mb-1 block text-sm font-medium text-slate-700">Business Name</label>
                            <input id="business_name" name="business_name" value="{{ old('business_name', $bif->business_name ?: $company->company_name) }}" class="h-11 w-full border border-slate-300 px-3 text-sm" required>
                        </div>
                        <div class="md:col-span-2 xl:col-span-3">
                            <label for="alternative_business_name" class="mb-1 block text-sm font-medium text-slate-700">Alternative / Business Name / Style</label>
                            <input id="alternative_business_name" name="alternative_business_name" value="{{ old('alternative_business_name', $bif->alternative_business_name) }}" class="h-11 w-full border border-slate-300 px-3 text-sm">
                        </div>
                        <div class="md:col-span-2 xl:col-span-3">
                            <label for="business_address" class="mb-1 block text-sm font-medium text-slate-700">Business Address</label>
                            <textarea id="business_address" name="business_address" rows="3" class="w-full border border-slate-300 px-3 py-3 text-sm">{{ old('business_address', $bif->business_address) }}</textarea>
                        </div>
                        <div class="md:col-span-2 xl:col-span-3">
                            <label for="business_organization_other" class="mb-1 block text-sm font-medium text-slate-700">If Other, please specify</label>
                            <input id="business_organization_other" name="business_organization_other" value="{{ old('business_organization_other', $bif->business_organization_other) }}" class="h-11 w-full border border-slate-300 px-3 text-sm">
                        </div>
                        <div>
                            <label for="zip_code" class="mb-1 block text-sm font-medium text-slate-700">ZIP Code</label>
                            <input id="zip_code" name="zip_code" value="{{ old('zip_code', $bif->zip_code) }}" class="h-11 w-full border border-slate-300 px-3 text-sm">
                        </div>
                        <div>
                            <label for="business_phone" class="mb-1 block text-sm font-medium text-slate-700">Business Phone</label>
                            <input id="business_phone" name="business_phone" value="{{ old('business_phone', $bif->business_phone) }}" class="h-11 w-full border border-slate-300 px-3 text-sm">
                        </div>
                        <div>
                            <label for="mobile_no" class="mb-1 block text-sm font-medium text-slate-700">Phone No. / Mobile No.</label>
                            <input id="mobile_no" name="mobile_no" value="{{ old('mobile_no', $bif->mobile_no) }}" class="h-11 w-full border border-slate-300 px-3 text-sm">
                        </div>
                        <div>
                            <label for="tin_no" class="mb-1 block text-sm font-medium text-slate-700">TIN Number</label>
                            <input id="tin_no" name="tin_no" value="{{ old('tin_no', $bif->tin_no) }}" class="h-11 w-full border border-slate-300 px-3 text-sm">
                        </div>
                        <div>
                            <label for="place_of_incorporation" class="mb-1 block text-sm font-medium text-slate-700">Place of Incorporation</label>
                            <input id="place_of_incorporation" name="place_of_incorporation" value="{{ old('place_of_incorporation', $bif->place_of_incorporation) }}" class="h-11 w-full border border-slate-300 px-3 text-sm">
                        </div>
                        <div>
                            <label for="date_of_incorporation" class="mb-1 block text-sm font-medium text-slate-700">Date of Incorporation</label>
                            <input id="date_of_incorporation" type="date" name="date_of_incorporation" value="{{ old('date_of_incorporation', optional($bif->date_of_incorporation)->format('Y-m-d')) }}" class="h-11 w-full border border-slate-300 px-3 text-sm">
                        </div>
                    </div>
                </section>

                <section class="border border-slate-300 bg-white p-5">
                    <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-slate-900">Ownership and Signatories</h2>
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div><label for="president_name" class="mb-1 block text-sm font-medium text-slate-700">President / Managing Head</label><input id="president_name" name="president_name" value="{{ old('president_name', $bif->president_name) }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label for="treasurer_name" class="mb-1 block text-sm font-medium text-slate-700">Treasurer</label><input id="treasurer_name" name="treasurer_name" value="{{ old('treasurer_name', $bif->treasurer_name) }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label for="authorized_signatory_position" class="mb-1 block text-sm font-medium text-slate-700">Authorized Signatory Position</label><input id="authorized_signatory_position" name="authorized_signatory_position" value="{{ old('authorized_signatory_position', $bif->authorized_signatory_position) }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div class="md:col-span-2 xl:col-span-3"><label for="authorized_signatory_name" class="mb-1 block text-sm font-medium text-slate-700">Authorized Signatory Name</label><input id="authorized_signatory_name" name="authorized_signatory_name" value="{{ old('authorized_signatory_name', $bif->authorized_signatory_name) }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div class="md:col-span-2 xl:col-span-3"><label for="authorized_signatory_address" class="mb-1 block text-sm font-medium text-slate-700">Authorized Signatory Address</label><input id="authorized_signatory_address" name="authorized_signatory_address" value="{{ old('authorized_signatory_address', $bif->authorized_signatory_address) }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label for="authorized_signatory_nationality" class="mb-1 block text-sm font-medium text-slate-700">Authorized Signatory Nationality</label><input id="authorized_signatory_nationality" name="authorized_signatory_nationality" value="{{ old('authorized_signatory_nationality', $bif->authorized_signatory_nationality) }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label for="authorized_signatory_date_of_birth" class="mb-1 block text-sm font-medium text-slate-700">Authorized Signatory Date of Birth</label><input id="authorized_signatory_date_of_birth" type="date" name="authorized_signatory_date_of_birth" value="{{ old('authorized_signatory_date_of_birth', optional($bif->authorized_signatory_date_of_birth)->format('Y-m-d')) }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label for="authorized_signatory_tin" class="mb-1 block text-sm font-medium text-slate-700">Authorized Signatory TIN</label><input id="authorized_signatory_tin" name="authorized_signatory_tin" value="{{ old('authorized_signatory_tin', $bif->authorized_signatory_tin) }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label for="ubo_name" class="mb-1 block text-sm font-medium text-slate-700">UBO Name</label><input id="ubo_name" name="ubo_name" value="{{ old('ubo_name', $bif->ubo_name) }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div class="md:col-span-2 xl:col-span-3"><label for="ubo_address" class="mb-1 block text-sm font-medium text-slate-700">UBO Address</label><input id="ubo_address" name="ubo_address" value="{{ old('ubo_address', $bif->ubo_address) }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label for="ubo_nationality" class="mb-1 block text-sm font-medium text-slate-700">UBO Nationality</label><input id="ubo_nationality" name="ubo_nationality" value="{{ old('ubo_nationality', $bif->ubo_nationality) }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label for="ubo_date_of_birth" class="mb-1 block text-sm font-medium text-slate-700">UBO Date of Birth</label><input id="ubo_date_of_birth" type="date" name="ubo_date_of_birth" value="{{ old('ubo_date_of_birth', optional($bif->ubo_date_of_birth)->format('Y-m-d')) }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label for="ubo_tin" class="mb-1 block text-sm font-medium text-slate-700">UBO TIN</label><input id="ubo_tin" name="ubo_tin" value="{{ old('ubo_tin', $bif->ubo_tin) }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div class="md:col-span-2 xl:col-span-3"><label for="ubo_position" class="mb-1 block text-sm font-medium text-slate-700">UBO Position / Ownership Role</label><input id="ubo_position" name="ubo_position" value="{{ old('ubo_position', $bif->ubo_position) }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                    </div>
                </section>

                <section class="border border-slate-300 bg-white p-5">
                    <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-slate-900">Authorized Contact Person</h2>
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="md:col-span-2"><label for="authorized_contact_person_name" class="mb-1 block text-sm font-medium text-slate-700">Full Name</label><input id="authorized_contact_person_name" name="authorized_contact_person_name" value="{{ old('authorized_contact_person_name', $bif->authorized_contact_person_name) }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label for="authorized_contact_person_position" class="mb-1 block text-sm font-medium text-slate-700">Position</label><input id="authorized_contact_person_position" name="authorized_contact_person_position" value="{{ old('authorized_contact_person_position', $bif->authorized_contact_person_position) }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label for="authorized_contact_person_phone" class="mb-1 block text-sm font-medium text-slate-700">Phone / Mobile Number</label><input id="authorized_contact_person_phone" name="authorized_contact_person_phone" value="{{ old('authorized_contact_person_phone', $bif->authorized_contact_person_phone) }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div class="md:col-span-2 xl:col-span-4"><label for="authorized_contact_person_email" class="mb-1 block text-sm font-medium text-slate-700">Email Address</label><input id="authorized_contact_person_email" type="email" name="authorized_contact_person_email" value="{{ old('authorized_contact_person_email', $bif->authorized_contact_person_email ?: $bif->client_form_sent_to_email) }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                    </div>
                </section>

                <section class="border border-slate-300 bg-white p-5">
                    <div class="mb-4 border-b border-slate-200 pb-2">
                        <h2 class="text-lg font-semibold text-slate-900">Onboarding Requirements</h2>
                        <p class="mt-1 text-sm text-slate-500">Please upload the supporting documents below. PDF, JPG, and PNG files are accepted up to 5MB each.</p>
                    </div>

                    <div class="border border-slate-300 bg-slate-50 p-4" id="soleRequirementsCard" @if(! $showSoleRequirements) style="display:none;" @endif>
                        <div class="grid gap-2 text-sm text-slate-700">
                            <div>1 | DTI Certificate of Registration (if Sole Prop)</div>
                            <div>2 | BIR Certificate of Registration (COR)</div>
                            <div>3 | Business Permit / Mayor's Permit</div>
                            <div>4 | Proof of Billing (Residential)</div>
                            <div>5 | Proof of Billing (Business Address if different)</div>
                            <div>6 | Special Power of Attorney (if representative)</div>
                            <div>7 | Representative's 2 Valid IDs (if applicable)</div>
                        </div>
                    </div>

                    <div class="border border-slate-300 bg-slate-50 p-4" id="juridicalRequirementsCard" @if(! $showJuridicalRequirements) style="display:none;" @endif>
                        <div class="grid gap-2 text-sm text-slate-700">
                            <div>1 | SEC / CDA Certificate of Registration</div>
                            <div>2 | BIR Certificate of Registration (COR)</div>
                            <div>3 | Business Permit / Mayor's Permit</div>
                            <div>4 | Articles of Incorporation / Partnership</div>
                            <div>5 | By-Laws</div>
                            <div>6 | Latest General Information Sheet (GIS)</div>
                            <div>7 | Appointment of Officers (for OPC, if applicable)</div>
                            <div>8 | Secretary Certificate OR Board Resolution</div>
                            <div>9 | Ultimate Beneficial Owner (UBO) Declaration</div>
                            <div>10 | Proof of Billing (Company Address)</div>
                            <div>11 | Proof of Billing (Authorized Representative, if applicable)</div>
                        </div>
                    </div>

                    <div class="border border-slate-300 bg-white p-4 text-sm text-slate-500" id="requirementsPlaceholder" @if($showRequirements) style="display:none;" @endif>
                        Select the business organization first to show the correct onboarding requirements.
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2" id="soleUploads" @if(! $showSoleRequirements) style="display:none;" @endif>
                        @foreach ($documentFields['sole_proprietorship'] as $document)
                            <div class="border border-slate-300 p-4">
                                <label for="{{ $document['key'] }}" class="mb-2 block text-sm font-semibold text-slate-900">{{ $document['label'] }}</label>
                                <input id="{{ $document['key'] }}" name="{{ $document['key'] }}" type="file" class="block w-full text-sm text-slate-600 file:mr-4 file:border-0 file:bg-[#3153d4] file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[#2745b3]">
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2" id="juridicalUploads" @if(! $showJuridicalRequirements) style="display:none;" @endif>
                        @foreach ($documentFields['juridical_entity'] as $document)
                            <div class="border border-slate-300 p-4">
                                <label for="{{ $document['key'] }}" class="mb-2 block text-sm font-semibold text-slate-900">{{ $document['label'] }}</label>
                                <input id="{{ $document['key'] }}" name="{{ $document['key'] }}" type="file" class="block w-full text-sm text-slate-600 file:mr-4 file:border-0 file:bg-[#3153d4] file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[#2745b3]">
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="border border-slate-300 bg-white p-5">
                    <div class="mb-4 border-b border-slate-200 pb-2">
                        <h2 class="text-lg font-semibold text-slate-900">Acknowledgment</h2>
                        <p class="mt-1 text-sm text-slate-500">Please review and confirm the same acknowledgment used in the BIF before submitting your details.</p>
                    </div>
                    <div class="space-y-4">
                        <p class="text-sm leading-7 text-slate-700">
                            By signing this Business Information Form, I/we certify that all information provided herein is true, correct, and complete to the best of my/our knowledge. I/we agree to comply with the policies, procedures, and service guidelines of JK&amp;C Inc. and authorize JK&amp;C Inc., its officers, employees, consultants, and representatives to collect, verify, record, process, store, and use the information provided for purposes of client registration, due diligence, compliance review, service engagement, documentation, billing, and regulatory requirements.
                        </p>
                        <p class="text-sm leading-7 text-slate-700">
                            In accordance with the Data Privacy Act of 2012, I/we consent to the collection, processing, storage, and lawful use of all personal and business information contained in this form and confirm that the undersigned is duly authorized to provide this information on behalf of the business entity.
                        </p>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Signature over Printed Name</label>
                                <input name="signature_printed_name" value="{{ old('signature_printed_name', $bif->signature_printed_name) }}" class="h-11 w-full border border-slate-300 px-3 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Position</label>
                                <input name="signature_position" value="{{ old('signature_position', $bif->signature_position) }}" class="h-11 w-full border border-slate-300 px-3 text-sm" placeholder="e.g. Authorized Signatory">
                            </div>
                        </div>
                    </div>
                </section>

                <div class="flex items-center justify-end border-t border-slate-200 pt-4">
                    <button type="submit" class="bg-[#3153d4] px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#2745b3]">Submit Business Information Form</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const form = document.getElementById('clientBifForm');
            const organizationSelect = document.getElementById('business_organization');
            const soleRequirementsCard = document.getElementById('soleRequirementsCard');
            const juridicalRequirementsCard = document.getElementById('juridicalRequirementsCard');
            const soleUploads = document.getElementById('soleUploads');
            const juridicalUploads = document.getElementById('juridicalUploads');
            const placeholder = document.getElementById('requirementsPlaceholder');
            const feedback = document.getElementById('clientBifLiveFeedback');
            const companyNameHeading = form?.querySelector('[data-company-name-heading]');

            if (!form || !organizationSelect || !soleRequirementsCard || !juridicalRequirementsCard || !soleUploads || !juridicalUploads || !placeholder) {
                return;
            }

            const showFeedback = (message, tone = 'success') => {
                if (!feedback) return;
                const tones = {
                    success: 'border-green-200 bg-green-50 text-green-700',
                    error: 'border-red-200 bg-red-50 text-red-700',
                };
                feedback.className = `mb-4 border px-4 py-3 text-sm ${tones[tone] || tones.success}`;
                feedback.textContent = message || '';
                feedback.classList.toggle('hidden', !message);
            };

            const juridicalTypes = new Set(['partnership', 'corporation', 'cooperative', 'ngo', 'other']);

            const syncCompanyNameHeading = () => {
                if (!companyNameHeading) return;

                const businessName = form.querySelector('[name="business_name"]')?.value?.trim() || '';
                const fallbackName = @js($companyName);

                companyNameHeading.textContent = businessName || fallbackName || 'Client';
            };

            const syncRequirementCards = () => {
                const value = organizationSelect.value;
                const showSole = value === 'sole_proprietorship';
                const showJuridical = juridicalTypes.has(value);

                soleRequirementsCard.style.display = showSole ? '' : 'none';
                juridicalRequirementsCard.style.display = showJuridical ? '' : 'none';
                soleUploads.style.display = showSole ? '' : 'none';
                juridicalUploads.style.display = showJuridical ? '' : 'none';
                placeholder.style.display = (showSole || showJuridical) ? 'none' : '';
            };

            organizationSelect.addEventListener('change', syncRequirementCards);
            form.querySelector('[name="business_name"]')?.addEventListener('input', syncCompanyNameHeading);
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                showFeedback('');

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new FormData(form),
                        cache: 'no-store',
                    });

                    if (response.status === 422) {
                        const payload = await response.json();
                        const firstError = Object.values(payload.errors || {}).flat()[0] || 'Please review the form.';
                        throw new Error(firstError);
                    }

                    if (!response.ok) {
                        throw new Error('Unable to submit the Business Information Form right now.');
                    }

                    const payload = await response.json();
                    showFeedback(payload.message || 'Your Business Information Form has been submitted successfully.', 'success');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    syncCompanyNameHeading();
                } catch (error) {
                    showFeedback(error.message, 'error');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
            syncRequirementCards();
            syncCompanyNameHeading();
        })();
    </script>
</body>
</html>
