@php
    $clientName = trim(collect([
        $specimenForm['left_client_name'] ?? '',
        $contact->first_name ?? '',
        $contact->last_name ?? '',
    ])->filter(fn ($value) => filled(trim((string) $value)))->first() ?? '') ?: 'Client';
    $specimenRequirement = ($kycRequirementState ?? [])['specimen_signature_form'] ?? ['files' => [], 'file' => null, 'complete' => false];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Specimen Signature Form</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#eef4ff] text-slate-900">
    <div class="min-h-screen py-8">
        <div class="mx-auto max-w-6xl px-4">
            <div id="clientSpecimenLiveFeedback" class="mb-4 hidden border px-4 py-3 text-sm"></div>
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
                                We kindly ask you to complete your Specimen Signature Form.
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="border border-slate-300 bg-[#f8fbff] p-5">
                            <p class="text-lg font-semibold text-slate-900">Important</p>
                            <p class="mt-4 text-lg leading-9 text-slate-700">
                                Please complete the specimen signature card carefully. The information provided here will be used to update the contact KYC record and specimen signature details on file.
                            </p>
                        </div>
                        <div class="border border-slate-300 bg-slate-50 p-5">
                            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Secure Link</p>
                            <p class="mt-3 text-sm leading-7 text-slate-600">
                                This secure form saves directly to your specimen signature record and can be revisited while your link is active.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <form id="clientSpecimenForm" method="POST" action="{{ $clientFormAction }}" enctype="multipart/form-data" class="space-y-4 border-x border-b border-slate-300 bg-white px-4 py-5 md:px-6">
                @csrf

                <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
                    <div>
                        <h1 class="text-2xl font-semibold text-slate-900">Specimen Signature Form</h1>
                        <p class="mt-1 text-sm text-slate-500">Please complete the specimen details below.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ $clientPreviewUrl }}" target="_blank" rel="noopener noreferrer" class="border border-slate-300 px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50">
                            Preview PDF
                        </a>
                        <a href="{{ $clientDownloadUrl }}" target="_blank" rel="noopener noreferrer" class="bg-[#3153d4] px-3 py-2 text-sm font-medium text-white transition hover:bg-[#2745b3]">
                            Download / Print PDF
                        </a>
                    </div>
                </div>

                @include('contacts.partials.specimen-signature-card', [
                    'form' => $specimenForm,
                    'readonly' => false,
                    'clientMode' => true,
                    'contact' => $contact,
                ])

                <section class="border border-slate-300 bg-white p-5">
                    <div class="mb-4 border-b border-slate-200 pb-2">
                        <h2 class="text-lg font-semibold text-slate-900">Upload Signed Specimen Form</h2>
                        <p class="mt-1 text-sm text-slate-500">After printing and signing the specimen signature form, upload the signed copy here.</p>
                    </div>
                    <input type="file" name="specimen_signature_signed_upload" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm text-slate-600 file:mr-4 file:border-0 file:bg-[#3153d4] file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[#2745b3]">
                    @if (!empty($specimenRequirement['files']))
                        <p class="mt-3 text-xs text-slate-500">{{ count($specimenRequirement['files']) }} signed specimen file(s) currently uploaded.</p>
                    @endif
                </section>

                <div class="flex items-center justify-end border-t border-slate-200 pt-4">
                    <button type="submit" class="bg-[#3153d4] px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#2745b3]">
                        Submit Specimen Signature Form
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('clientSpecimenForm');
            const feedback = document.getElementById('clientSpecimenLiveFeedback');
            const clientNameHeading = form?.querySelector('[data-client-name-heading]');
            if (!form || !feedback) return;

            const showFeedback = (message, tone = 'success') => {
                const tones = {
                    success: 'border-green-200 bg-green-50 text-green-700',
                    error: 'border-red-200 bg-red-50 text-red-700',
                };
                feedback.className = `mb-4 border px-4 py-3 text-sm ${tones[tone] || tones.success}`;
                feedback.textContent = message || '';
                feedback.classList.toggle('hidden', !message);
            };

            const getInputValue = (name) => form.querySelector(`[name="${name}"]`)?.value?.trim() || '';

            const syncClientNameHeading = () => {
                if (!clientNameHeading) return;

                clientNameHeading.textContent =
                    getInputValue('left_client_name') ||
                    getInputValue('right_client_name') ||
                    getInputValue('signature_over_printed_name') ||
                    '{{ $clientName }}';
            };

            ['left_client_name', 'right_client_name', 'signature_over_printed_name'].forEach((name) => {
                const input = form.querySelector(`[name="${name}"]`);
                input?.addEventListener('input', syncClientNameHeading);
            });

            form.addEventListener('submit', async function (event) {
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
                        throw new Error('Unable to submit the Specimen Signature Form right now.');
                    }

                    const payload = await response.json();
                    showFeedback(payload.message || 'Your Specimen Signature Form has been submitted successfully.', 'success');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    syncClientNameHeading();
                } catch (error) {
                    showFeedback(error.message, 'error');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });

            syncClientNameHeading();
        });
    </script>
</body>
</html>
