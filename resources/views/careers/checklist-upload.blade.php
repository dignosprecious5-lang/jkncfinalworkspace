<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist Upload | John Kelly & Company</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>body{font-family:'Inter',sans-serif;}</style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="min-h-screen py-10 px-4">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-8">
                <img src="{{ asset('images/imaglogo.png') }}" onerror="this.src='{{ asset('images/imag1logo.jpg') }}'" alt="Logo" class="h-20 w-auto mx-auto mb-5 object-contain">
                <h1 class="text-3xl font-black uppercase tracking-tight">Pre-employment Requirements</h1>
                <p class="text-gray-600 mt-2">Upload your onboarding documents below.</p>
            </div>

            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-700 rounded-2xl p-4 font-semibold">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
                <div class="bg-blue-700 text-white p-6">
                    <p class="text-xs font-black uppercase tracking-widest mb-1">Applicant</p>
                    <h2 class="text-xl font-black">{{ $checklist->employee_name }}</h2>
                    <p class="text-blue-100">{{ $checklist->position }}</p>
                </div>

                <form method="POST" action="{{ route('careers.checklist.submit', $checklist->upload_token) }}" enctype="multipart/form-data" class="p-6 space-y-4">
                    @csrf

                    <div class="grid gap-4">
                        @foreach($documents as $key => $label)
                            @php
                                $existing = collect($checklist->checked_documents ?? [])->firstWhere('key', $key);
                            @endphp

                            <div class="border border-gray-200 rounded-2xl p-4 bg-gray-50">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-black text-gray-900">{{ $label }}</p>
                                        @if(!empty($existing['original_name']))
                                            <p class="text-xs text-blue-600 font-semibold mt-1">Current file: {{ $existing['original_name'] }}</p>
                                        @endif
                                        @if(!empty($existing['remarks']))
                                            <p class="text-xs text-red-600 font-semibold mt-1">HR Remarks: {{ $existing['remarks'] }}</p>
                                        @endif
                                        <p class="text-xs text-gray-500 mt-1">PDF, JPG, PNG, DOC, or DOCX. Max 10MB.</p>
                                    </div>

                                    <span class="text-xs font-bold px-2 py-1 rounded-full
                                        @if(($existing['status'] ?? '') === 'Approved') bg-green-100 text-green-700
                                        @elseif(($existing['status'] ?? '') === 'Needs Re-upload') bg-red-100 text-red-700
                                        @elseif(($existing['status'] ?? '') === 'Submitted') bg-blue-100 text-blue-700
                                        @else bg-yellow-100 text-yellow-700 @endif">
                                        {{ $existing['status'] ?? 'Missing' }}
                                    </span>
                                </div>

                                <input type="file" name="documents[{{ $key }}]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                    class="mt-4 block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                        @endforeach
                    </div>

                    <div class="pt-5 border-t flex justify-end">
                        <button type="submit" class="px-6 py-3 bg-blue-700 hover:bg-blue-800 text-white rounded-xl font-black uppercase tracking-widest text-xs shadow">
                            Submit Documents
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
