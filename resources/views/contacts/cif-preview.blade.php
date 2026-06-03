<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Information Form</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html, body { min-height: 100%; }
        body {
            background: linear-gradient(180deg, #eaf1fb 0%, #f8fafc 100%);
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .document-page {
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
            background: transparent;
        }
    </style>
    @include('partials.a4-fit-to-page')
</head>
<body class="bg-gray-100 text-gray-900">
    @php
        $resolvedBackUrl = $backUrl ?? route('contacts.show', ['contact' => $contact->id, 'tab' => 'kyc']);
    @endphp
    <div class="print-shell a4-fit-shell mx-auto max-w-6xl p-4 md:p-6">
        <div class="no-print mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3">
            <div>
                <h2 class="text-lg font-semibold">Client Information Form</h2>
                <p class="text-sm text-gray-500">Use your browser's print dialog and choose Save as PDF to export this document.</p>
            </div>

            <div class="flex gap-2">
                <button type="button" onclick="window.location.href='{{ $resolvedBackUrl }}'"
                    class="inline-flex h-10 items-center rounded-full border border-gray-200 px-4 text-sm font-medium text-gray-700 hover:bg-gray-100">
                    Back
                </button>

                <button type="button" onclick="window.printA4FitPage()"
                    class="inline-flex h-10 items-center rounded-full bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">
                    Print / Save as PDF
                </button>
            </div>
        </div>

        <div class="document-page a4-fit-page">
            @include('contacts.partials.cif-document', ['cifData' => $cifData])
        </div>
    </div>

    <script>
        const params = new URLSearchParams(window.location.search);
        if (params.get('autoprint') === '1') {
            window.addEventListener('load', () => window.printA4FitPage());
        }
    </script>
</body>
</html>
