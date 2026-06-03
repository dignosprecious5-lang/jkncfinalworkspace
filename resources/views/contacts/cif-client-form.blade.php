@php
    $clientName = trim(collect([
        $cifData['first_name'] ?? $contact->first_name ?? '',
        $cifData['middle_name'] ?? '',
        $cifData['last_name'] ?? $contact->last_name ?? '',
        $cifData['name_extension'] ?? '',
    ])->filter(fn ($value) => filled(trim((string) $value)))->implode(' ')) ?: 'Client';
    $clientPrintedName = trim(collect([
        $cifData['first_name'] ?? $contact->first_name ?? '',
        $cifData['middle_name'] ?? '',
        $cifData['last_name'] ?? $contact->last_name ?? '',
    ])->filter(fn ($value) => filled(trim((string) $value)))->implode(' ')) ?: 'Client';
    $selectedCitizenshipType = old('citizenship_type', $cifData['citizenship_type'] ?? '');
    $selectedCivilStatus = old('civil_status', $cifData['civil_status'] ?? '');
    $showForeign = in_array($selectedCitizenshipType, ['foreigner', 'dual_citizen'], true);
    $requirementState = $kycRequirementState ?? [];
    $clientSignatureName = old('sig_name_left', $cifData['sig_name_left'] ?? $clientPrintedName);
    $clientSignaturePosition = old('sig_position_left', $cifData['sig_position_left'] ?? ($contact->position ?: 'Client'));
    $signedCifRequirement = $requirementState['cif_signed_document'] ?? ['file' => null, 'complete' => false];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Information Form</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#eef4ff] text-slate-900">
    <div class="min-h-screen py-8">
        <div class="mx-auto max-w-6xl px-4">
            <div id="clientCifLiveFeedback" class="mb-4 hidden border px-4 py-3 text-sm"></div>
            @if (session('success'))
                <div class="mb-4 border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
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
                            <p class="text-xl leading-relaxed">Dear <span class="font-semibold" data-client-name-heading>{{ $clientName }}</span>,</p>
                            <p class="text-xl leading-relaxed">Good day.</p>
                            <p class="max-w-2xl text-[2.2rem] font-semibold leading-[1.18]">
                                To get things started smoothly, we kindly ask you to complete your Client Information Form (CIF).
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="border border-slate-300 bg-[#f8fbff] p-5">
                            <p class="text-lg font-semibold text-slate-900">Important</p>
                            <p class="mt-4 text-lg leading-9 text-slate-700">
                                The form is mobile-friendly and can be completed using your phone or computer. You will also be asked to upload the required supporting documents below the form. Foreign or dual-citizen clients will also see additional document requirements.
                            </p>
                        </div>
                        <div class="border border-slate-300 bg-slate-50 p-5">
                            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Secure Link</p>
                            <p class="mt-3 text-sm leading-7 text-slate-600">
                                This secure form saves directly to your KYC record and can be revisited while your link is active.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <form id="clientCifForm" method="POST" action="{{ $clientFormAction }}" enctype="multipart/form-data" class="space-y-5 border-x border-b border-slate-300 bg-white px-4 py-5 md:px-6" data-client-cif-form>
                @csrf

                <section class="border border-slate-300 bg-white p-5">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
                        <div>
                            <h1 class="text-2xl font-semibold text-slate-900">Client Information Form</h1>
                            <p class="mt-1 text-sm text-slate-500">Please complete any missing details below.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ $clientPreviewUrl }}" target="_blank" rel="noopener noreferrer" class="border border-slate-300 px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50">
                                Preview CIF
                            </a>
                            <a href="{{ $clientDownloadUrl }}" target="_blank" rel="noopener noreferrer" class="bg-[#3153d4] px-3 py-2 text-sm font-medium text-white transition hover:bg-[#2745b3]">
                                Download / Print CIF
                            </a>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div><label class="mb-1 block text-sm font-medium text-slate-700">Date</label><input type="date" name="cif_date" value="{{ old('cif_date', $cifData['cif_date'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label class="mb-1 block text-sm font-medium text-slate-700">CIF No.</label><input name="cif_no" value="{{ old('cif_no', $cifData['cif_no'] ?? '') }}" class="h-11 w-full border border-slate-300 bg-slate-50 px-3 text-sm text-slate-500" readonly></div>
                    </div>
                </section>

                <section class="border border-slate-300 bg-white p-5">
                    <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-slate-900">Personal Details</h2>
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div><label class="mb-1 block text-sm font-medium text-slate-700">First Name</label><input required name="first_name" value="{{ old('first_name', $cifData['first_name'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label class="mb-1 block text-sm font-medium text-slate-700">Last Name</label><input name="last_name" value="{{ old('last_name', $cifData['last_name'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label class="mb-1 block text-sm font-medium text-slate-700">Middle Name</label><input name="middle_name" value="{{ old('middle_name', $cifData['middle_name'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label class="mb-1 block text-sm font-medium text-slate-700">Name Extension</label><input name="name_extension" value="{{ old('name_extension', $cifData['name_extension'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <label class="flex items-center gap-2 border border-slate-300 px-3 py-2 text-sm"><input type="checkbox" name="no_middle_name" value="1" @checked(old('no_middle_name', $cifData['no_middle_name'] ?? false))> I have no Middle Name</label>
                        <label class="flex items-center gap-2 border border-slate-300 px-3 py-2 text-sm"><input type="checkbox" name="only_first_name" value="1" @checked(old('only_first_name', $cifData['only_first_name'] ?? false))> I only have a First Name</label>
                    </div>
                </section>

                <section class="border border-slate-300 bg-white p-5">
                    <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-slate-900">Contact and Address</h2>
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div><label class="mb-1 block text-sm font-medium text-slate-700">Email Address</label><input type="email" name="email" value="{{ old('email', $cifData['email'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label class="mb-1 block text-sm font-medium text-slate-700">Phone No. / Mobile No.</label><input name="mobile" value="{{ old('mobile', $cifData['mobile'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label class="mb-1 block text-sm font-medium text-slate-700">ZIP Code</label><input name="zip_code" value="{{ old('zip_code', $cifData['zip_code'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div class="md:col-span-2 xl:col-span-3"><label class="mb-1 block text-sm font-medium text-slate-700">Present Address</label><input name="present_address_line1" value="{{ old('present_address_line1', $cifData['present_address_line1'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div class="md:col-span-2 xl:col-span-3"><label class="mb-1 block text-sm font-medium text-slate-700">Present Address (2nd Line)</label><input name="present_address_line2" value="{{ old('present_address_line2', $cifData['present_address_line2'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                    </div>
                </section>

                <section class="border border-slate-300 bg-white p-5">
                    <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-slate-900">Birth, Citizenship, and Status</h2>
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div><label class="mb-1 block text-sm font-medium text-slate-700">Date of Birth</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth', $cifData['date_of_birth'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label class="mb-1 block text-sm font-medium text-slate-700">Place of Birth</label><input name="place_of_birth" value="{{ old('place_of_birth', $cifData['place_of_birth'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label class="mb-1 block text-sm font-medium text-slate-700">Citizenship / Nationality</label><input name="citizenship_nationality" value="{{ old('citizenship_nationality', $cifData['citizenship_nationality'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm" data-citizenship-nationality-input @if($selectedCitizenshipType === 'filipino') readonly @endif></div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2" data-citizenship-radios>
                        @foreach (['filipino' => 'Filipino', 'foreigner' => 'Foreigner', 'dual_citizen' => 'Dual Citizen'] as $value => $label)
                            <label class="flex items-center gap-2 border border-slate-300 px-3 py-2 text-sm"><input type="radio" name="citizenship_type" value="{{ $value }}" @checked(old('citizenship_type', $cifData['citizenship_type'] ?? '') === $value)> {{ $label }}</label>
                        @endforeach
                    </div>
                    <div class="mt-5 space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Gender</label>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach (['male' => 'Male', 'female' => 'Female'] as $value => $label)
                                    <label class="flex items-center gap-2 border border-slate-300 px-3 py-2 text-sm"><input type="radio" name="gender" value="{{ $value }}" @checked(old('gender', $cifData['gender'] ?? '') === $value)> {{ $label }}</label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Civil Status</label>
                            <div class="grid gap-x-2 gap-y-2 sm:grid-cols-2 lg:grid-cols-[repeat(4,minmax(0,max-content))_minmax(220px,1fr)] lg:items-center" data-civil-status-radios>
                                @foreach (['single' => 'Single', 'separated' => 'Separated', 'widowed' => 'Widowed', 'married' => 'Married'] as $value => $label)
                                    <label class="inline-flex min-h-11 items-center gap-2 border border-slate-300 px-3 py-2 text-sm"><input type="radio" name="civil_status" value="{{ $value }}" @checked(old('civil_status', $cifData['civil_status'] ?? '') === $value)> {{ $label }}</label>
                                @endforeach
                                <div class="sm:col-span-2 lg:col-start-5 lg:row-start-1 lg:min-w-0 lg:w-full lg:justify-self-stretch" data-spouse-row @if($selectedCivilStatus !== 'married') style="display:none;" @endif>
                                    <div class="flex w-full min-w-0 items-center gap-2">
                                        <label for="client_spouse_name" class="shrink-0 whitespace-nowrap text-sm font-medium text-slate-700">Spouse's Name</label>
                                        <input id="client_spouse_name" name="spouse_name" value="{{ old('spouse_name', $cifData['spouse_name'] ?? '') }}" class="h-11 w-full min-w-0 border border-slate-300 px-3 text-sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="border border-slate-300 bg-white p-5">
                    <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-slate-900">Work and Identity</h2>
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div><label class="mb-1 block text-sm font-medium text-slate-700">Nature of Work / Business</label><input name="nature_of_work_business" value="{{ old('nature_of_work_business', $cifData['nature_of_work_business'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label class="mb-1 block text-sm font-medium text-slate-700">TIN</label><input name="tin" value="{{ old('tin', $cifData['tin'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label class="mb-1 block text-sm font-medium text-slate-700">Other Government ID</label><input name="other_government_id" value="{{ old('other_government_id', $cifData['other_government_id'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label class="mb-1 block text-sm font-medium text-slate-700">ID Number</label><input name="id_number" value="{{ old('id_number', $cifData['id_number'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div class="md:col-span-2 xl:col-span-4"><label class="mb-1 block text-sm font-medium text-slate-700">Mother's Maiden Name</label><input name="mothers_maiden_name" value="{{ old('mothers_maiden_name', $cifData['mothers_maiden_name'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                    </div>
                </section>

                <section class="border border-slate-300 bg-white p-5">
                    <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-slate-900">Source of Funds</h2>
                    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach (['salary' => 'Salary', 'remittance' => 'Remittance', 'business' => 'Business', 'others' => 'Others', 'commission_fees' => 'Commission / Fees', 'retirement_pension' => 'Retirement / Pension'] as $value => $label)
                            <label class="flex items-center gap-2 border border-slate-300 px-3 py-2 text-sm"><input type="checkbox" name="source_of_funds[]" value="{{ $value }}" @checked(in_array($value, old('source_of_funds', $cifData['source_of_funds'] ?? []), true))> {{ $label }}</label>
                        @endforeach
                    </div>
                    <div class="mt-4"><label class="mb-1 block text-sm font-medium text-slate-700">Others (Specify)</label><input name="source_of_funds_other_text" value="{{ old('source_of_funds_other_text', $cifData['source_of_funds_other_text'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                </section>

                <section class="border border-slate-300 bg-white p-5" data-foreign-section @if(! $showForeign) style="display:none;" @endif>
                    <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-slate-900">Foreigner Information</h2>
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div><label class="mb-1 block text-sm font-medium text-slate-700">Passport No.</label><input name="foreigner_passport_no" value="{{ old('foreigner_passport_no', $cifData['foreigner_passport_no'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label class="mb-1 block text-sm font-medium text-slate-700">Passport Expiry Date</label><input type="date" name="foreigner_passport_expiry_date" value="{{ old('foreigner_passport_expiry_date', $cifData['foreigner_passport_expiry_date'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label class="mb-1 block text-sm font-medium text-slate-700">Passport Place of Issue</label><input name="foreigner_passport_place_of_issue" value="{{ old('foreigner_passport_place_of_issue', $cifData['foreigner_passport_place_of_issue'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label class="mb-1 block text-sm font-medium text-slate-700">ACR ID No.</label><input name="foreigner_acr_id_no" value="{{ old('foreigner_acr_id_no', $cifData['foreigner_acr_id_no'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label class="mb-1 block text-sm font-medium text-slate-700">ACR Expiry Date</label><input type="date" name="foreigner_acr_expiry_date" value="{{ old('foreigner_acr_expiry_date', $cifData['foreigner_acr_expiry_date'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div><label class="mb-1 block text-sm font-medium text-slate-700">ACR Place of Issue</label><input name="foreigner_acr_place_of_issue" value="{{ old('foreigner_acr_place_of_issue', $cifData['foreigner_acr_place_of_issue'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                        <div class="md:col-span-2"><label class="mb-1 block text-sm font-medium text-slate-700">Visa Status</label><input name="visa_status" value="{{ old('visa_status', $cifData['visa_status'] ?? '') }}" class="h-11 w-full border border-slate-300 px-3 text-sm"></div>
                    </div>
                </section>

                <section class="border border-slate-300 bg-white p-5">
                    <div class="mb-4 border-b border-slate-200 pb-2">
                        <h2 class="text-lg font-semibold text-slate-900">Upload Requirements</h2>
                        <p class="mt-1 text-sm text-slate-500">Please upload the supporting documents below. PDF, JPG, and PNG files are accepted up to 5MB each. You may also download, print, sign, and upload your signed CIF form here.</p>
                    </div>

                    <div class="border border-slate-300 bg-slate-50 p-4">
                        <div class="grid gap-2 text-sm text-slate-700">
                            <div>1 | Two (2) Valid Government IDs</div>
                            <div>2 | TIN ID (Signatory/Representative/Stockholders/Partners/Others)</div>
                            <div>3 | Authorized Signatory / Signatory Specimen Signature Card</div>
                            <div data-foreign-requirement @if(! $showForeign) style="display:none;" @endif>4 | If Foreign Signatory/Director/Officer: Passport (Bio Page)</div>
                            <div data-foreign-requirement @if(! $showForeign) style="display:none;" @endif>5 | If Foreign Signatory/Director/Officer: Valid Visa / ACR I-Card</div>
                            <div data-foreign-requirement @if(! $showForeign) style="display:none;" @endif>6 | If Foreign Signatory/Director/Officer: Alien Employment Permit (AEP)</div>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="border border-slate-300 p-4">
                            <label class="mb-2 block text-sm font-semibold text-slate-900">Signed CIF Form</label>
                            <p class="mb-3 text-xs text-slate-500">After printing and signing the CIF, upload the signed copy here.</p>
                            <input type="file" name="cif_signed_document_upload" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm text-slate-600 file:mr-4 file:border-0 file:bg-[#3153d4] file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[#2745b3]" data-file-input>
                            <div class="mt-3 hidden border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700" data-file-preview></div>
                            @if (!empty($signedCifRequirement['file']))
                                <p class="mt-2 text-xs text-slate-500">Current uploaded file: {{ $signedCifRequirement['file']['file_name'] ?? 'Signed CIF uploaded' }}</p>
                            @endif
                        </div>
                        <div class="border border-slate-300 p-4">
                            <label class="mb-2 block text-sm font-semibold text-slate-900">Two Valid Government IDs</label>
                            <p class="mb-3 text-xs text-slate-500">Please upload two separate valid government IDs.</p>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700">Valid Government ID 1</label>
                                    <input type="file" name="two_valid_ids_uploads[]" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm text-slate-600 file:mr-4 file:border-0 file:bg-[#3153d4] file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[#2745b3]" data-file-input>
                                    <div class="mt-3 hidden border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700" data-file-preview></div>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700">Valid Government ID 2</label>
                                    <input type="file" name="two_valid_ids_uploads[]" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm text-slate-600 file:mr-4 file:border-0 file:bg-[#3153d4] file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[#2745b3]" data-file-input>
                                    <div class="mt-3 hidden border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700" data-file-preview></div>
                                </div>
                            </div>
                            @if (!empty($requirementState['two_valid_ids']['files'] ?? []))
                                <p class="mt-2 text-xs text-slate-500">{{ count($requirementState['two_valid_ids']['files']) }} file(s) currently uploaded.</p>
                            @endif
                        </div>
                        <div class="border border-slate-300 p-4">
                            <label class="mb-2 block text-sm font-semibold text-slate-900">TIN ID</label>
                            <input type="file" name="tin_proof_uploads[]" accept=".pdf,.jpg,.jpeg,.png" multiple class="block w-full text-sm text-slate-600 file:mr-4 file:border-0 file:bg-[#3153d4] file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[#2745b3]" data-file-input>
                            <div class="mt-3 hidden border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700" data-file-preview></div>
                        </div>
                        <div class="border border-slate-300 p-4 md:col-span-2" data-foreign-requirement @if(! $showForeign) style="display:none;" @endif>
                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-900">Passport (Bio Page)</label>
                                    <input type="file" name="passport_proof_uploads[]" accept=".pdf,.jpg,.jpeg,.png" multiple class="block w-full text-sm text-slate-600 file:mr-4 file:border-0 file:bg-[#3153d4] file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[#2745b3]" data-file-input>
                                    <div class="mt-3 hidden border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700" data-file-preview></div>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-900">Valid Visa</label>
                                    <input type="file" name="visa_proof_uploads[]" accept=".pdf,.jpg,.jpeg,.png" multiple class="block w-full text-sm text-slate-600 file:mr-4 file:border-0 file:bg-[#3153d4] file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[#2745b3]" data-file-input>
                                    <div class="mt-3 hidden border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700" data-file-preview></div>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-900">ACR I-Card</label>
                                    <input type="file" name="acr_card_proof_uploads[]" accept=".pdf,.jpg,.jpeg,.png" multiple class="block w-full text-sm text-slate-600 file:mr-4 file:border-0 file:bg-[#3153d4] file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[#2745b3]" data-file-input>
                                    <div class="mt-3 hidden border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700" data-file-preview></div>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-900">AEP</label>
                                    <input type="file" name="aaep_proof_uploads[]" accept=".pdf,.jpg,.jpeg,.png" multiple class="block w-full text-sm text-slate-600 file:mr-4 file:border-0 file:bg-[#3153d4] file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[#2745b3]" data-file-input>
                                    <div class="mt-3 hidden border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700" data-file-preview></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="border border-slate-300 bg-white p-5">
                    <div class="mb-4 border-b border-slate-200 pb-2">
                        <h2 class="text-lg font-semibold text-slate-900">Acknowledgment</h2>
                        <p class="mt-1 text-sm text-slate-500">Please review and confirm the same acknowledgment used in the CIF before submitting your details.</p>
                    </div>
                    <div class="space-y-4">
                        <p class="text-sm leading-7 text-slate-700">
                            By signing this Client Information Form, I certify that all personal information provided herein is true, correct, and complete to the best of my knowledge. I agree to comply with the policies, procedures, and service guidelines of JK&amp;C Inc. and authorize JK&amp;C Inc., its officers, employees, consultants, and representatives to collect, verify, record, process, store, and use the information provided for purposes of client registration, due diligence, compliance verification, service engagement, documentation, billing, and regulatory requirements.
                        </p>
                        <p class="text-sm leading-7 text-slate-700">
                            In accordance with the Data Privacy Act of 2012 (Republic Act No. 10173), I voluntarily consent to the collection, processing, storage, and lawful use of my personal information contained in this form. I acknowledge that the information provided shall constitute the official client information on record of JK&amp;C Inc. and may be relied upon in official communications, notices, service documents, billing statements, formal correspondence, and demand letters relating to services rendered or obligations arising from the engagement.
                        </p>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Signature over Printed Name</label>
                                <input name="sig_name_left" value="{{ $clientSignatureName }}" class="h-11 w-full border border-slate-300 px-3 text-sm" data-client-signature-name>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Position</label>
                                <input name="sig_position_left" value="{{ $clientSignaturePosition }}" class="h-11 w-full border border-slate-300 px-3 text-sm" placeholder="e.g. Individual Client / Authorized Signatory">
                            </div>
                        </div>
                        <p class="text-xs text-slate-500">
                            The second signature block on the CIF is reserved for internal JK&amp;C processing.
                        </p>
                        <label class="flex items-start gap-3 border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-800">
                            <input type="checkbox" name="client_acknowledgment" value="1" class="mt-1" @checked(old('client_acknowledgment'))>
                            <span>I have read, understood, and agree to the acknowledgment above.</span>
                        </label>
                    </div>
                </section>

                <div class="flex items-center justify-end border-t border-slate-200 pt-4">
                    <button type="submit" class="bg-[#3153d4] px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#2745b3]">Submit Client Information Form</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const form = document.querySelector('[data-client-cif-form]');
            if (!form) return;

            const feedback = document.getElementById('clientCifLiveFeedback');
            const clientNameHeading = form.querySelector('[data-client-name-heading]');
            const spouseRow = form.querySelector('[data-spouse-row]');
            const foreignSection = form.querySelector('[data-foreign-section]');
            const foreignRequirements = form.querySelectorAll('[data-foreign-requirement]');
            const citizenshipNationalityInput = form.querySelector('[data-citizenship-nationality-input]');
            const signatureNameInput = form.querySelector('[data-client-signature-name]');
            let signatureNameManuallyEdited = false;
            const fileInputs = form.querySelectorAll('[data-file-input]');
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
            const notifyKycUpdated = () => {
                const detail = {
                    module: 'contacts',
                    id: @json((string) $contact->id),
                    source: 'client-cif',
                    at: Date.now(),
                };

                if ('BroadcastChannel' in window) {
                    const channel = new BroadcastChannel('jknc:kyc-sync');
                    channel.postMessage(detail);
                    channel.close();
                }

                try {
                    localStorage.setItem('jknc:kyc-sync', JSON.stringify(detail));
                } catch (error) {
                }
            };

            const getSelectedValue = (name) => form.querySelector(`input[name="${name}"]:checked`)?.value || '';
            const getInputValue = (name) => form.querySelector(`[name="${name}"]`)?.value?.trim() || '';

            const syncClientNameHeading = () => {
                if (!clientNameHeading) return;

                const parts = [
                    getInputValue('first_name'),
                    getInputValue('middle_name'),
                    getInputValue('last_name'),
                    getInputValue('name_extension'),
                ].filter(Boolean);

                clientNameHeading.textContent = parts.join(' ') || 'Client';
            };

            const syncSignatureName = () => {
                if (!signatureNameInput || signatureNameManuallyEdited) return;

                const parts = [
                    getInputValue('first_name'),
                    getInputValue('middle_name'),
                    getInputValue('last_name'),
                ].filter(Boolean);

                signatureNameInput.value = parts.join(' ') || 'Client';
            };

            const syncVisibility = () => {
                const citizenshipType = getSelectedValue('citizenship_type');
                const civilStatus = getSelectedValue('civil_status');
                const showForeign = citizenshipType === 'foreigner' || citizenshipType === 'dual_citizen';

                if (citizenshipNationalityInput) {
                    if (citizenshipType === 'filipino') {
                        citizenshipNationalityInput.value = 'FILIPINO';
                        citizenshipNationalityInput.readOnly = true;
                        citizenshipNationalityInput.classList.add('bg-slate-100');
                    } else {
                        citizenshipNationalityInput.readOnly = false;
                        citizenshipNationalityInput.classList.remove('bg-slate-100');
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

            ['first_name', 'middle_name', 'last_name', 'name_extension'].forEach((name) => {
                const input = form.querySelector(`[name="${name}"]`);
                input?.addEventListener('input', () => {
                    syncClientNameHeading();
                    syncSignatureName();
                });
            });

            signatureNameInput?.addEventListener('input', () => {
                signatureNameManuallyEdited = true;
            });

            const syncFilePreview = (input) => {
                const preview = input.parentElement.querySelector('[data-file-preview]');
                if (!preview) return;

                const files = Array.from(input.files || []);

                if (files.length === 0) {
                    preview.classList.add('hidden');
                    preview.innerHTML = '';
                    return;
                }

                preview.classList.remove('hidden');
                preview.innerHTML = files.map((file, index) => `
                    <div class="flex items-center justify-between gap-3 border-b border-slate-200 py-2 last:border-b-0">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-slate-800">${file.name}</p>
                            <p class="text-xs text-slate-500">${Math.max(1, Math.round(file.size / 1024))} KB</p>
                        </div>
                        <button type="button" class="border border-red-200 px-2 py-1 text-xs text-red-600 hover:bg-red-50" data-remove-file-index="${index}">
                            Remove
                        </button>
                    </div>
                `).join('');

                preview.querySelectorAll('[data-remove-file-index]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const removeIndex = Number(button.getAttribute('data-remove-file-index'));
                        const dt = new DataTransfer();
                        Array.from(input.files || []).forEach((file, idx) => {
                            if (idx !== removeIndex) dt.items.add(file);
                        });
                        input.files = dt.files;
                        syncFilePreview(input);
                    });
                });
            };

            fileInputs.forEach((input) => {
                input.addEventListener('change', () => syncFilePreview(input));
                syncFilePreview(input);
            });

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
                        throw new Error('Unable to submit the Client Information Form right now.');
                    }

                    const payload = await response.json();
                    showFeedback(payload.message || 'Your Client Information Form has been submitted successfully.', 'success');
                    notifyKycUpdated();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } catch (error) {
                    showFeedback(error.message, 'error');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });

            syncVisibility();
            syncClientNameHeading();
            syncSignatureName();
        })();
    </script>
</body>
</html>
