<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Test | John Kelly & Company</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="min-h-screen py-10 px-4">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
                <div class="bg-blue-700 px-8 py-8 text-white">
                    <p class="text-xs font-black uppercase tracking-[0.25em] text-blue-100">John Kelly & Company</p>
                    <h1 class="text-3xl font-black mt-2">Assessment Test</h1>
                    <p class="text-blue-100 mt-2">Please answer the questions below honestly and carefully.</p>
                </div>

                <div class="p-8">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                        <div class="bg-gray-50 rounded-2xl border border-gray-100 p-4">
                            <p class="text-[10px] uppercase tracking-widest font-black text-gray-400">Candidate</p>
                            <p class="font-bold text-gray-900 mt-1">{{ $assessment->name }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-2xl border border-gray-100 p-4">
                            <p class="text-[10px] uppercase tracking-widest font-black text-gray-400">Position</p>
                            <p class="font-bold text-gray-900 mt-1">{{ $assessment->position }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-2xl border border-gray-100 p-4">
                            <p class="text-[10px] uppercase tracking-widest font-black text-gray-400">Test Type</p>
                            <p class="font-bold text-gray-900 mt-1">{{ $assessment->test_type }}</p>
                        </div>
                    </div>

                    @if($submitted)
                        <div class="rounded-3xl border p-8 text-center
                            {{ $status === 'Passed' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
                            <p class="text-xs font-black uppercase tracking-[0.25em]
                                {{ $status === 'Passed' ? 'text-green-700' : 'text-red-700' }}">
                                {{ $message ?? 'Assessment submitted.' }}
                            </p>
                            <h2 class="text-4xl font-black mt-3
                                {{ $status === 'Passed' ? 'text-green-700' : 'text-red-700' }}">
                                {{ $status }}
                            </h2>
                            <p class="text-gray-700 mt-3">
                                Your score: <strong>{{ $score }}</strong>
                            </p>
                            <p class="text-sm text-gray-500 mt-6">
                                You may now close this page. HR will review your assessment result.
                            </p>
                        </div>
                    @else
                        <form method="POST" action="{{ route('recruitment.assessment.submit', $assessment->uuid) }}" class="space-y-6">
                            @csrf

                            @foreach($questions as $index => $question)
                                <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                                    <p class="text-xs font-black uppercase tracking-widest text-blue-600 mb-2">
                                        Question {{ $index + 1 }}
                                    </p>
                                    <h3 class="font-bold text-gray-900 text-lg mb-5">
                                        {{ $question['question'] }}
                                    </h3>

                                    <div class="space-y-3">
                                        @foreach($question['choices'] as $choiceIndex => $choice)
                                            <label class="flex items-center gap-3 p-4 rounded-2xl border border-gray-200 hover:bg-blue-50 hover:border-blue-200 cursor-pointer transition">
                                                <input type="radio"
                                                    name="answers[{{ $index }}]"
                                                    value="{{ $choiceIndex }}"
                                                    required
                                                    class="w-4 h-4 text-blue-600 focus:ring-blue-500">
                                                <span class="text-sm font-medium text-gray-700">{{ $choice }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            <div class="flex justify-end pt-4">
                                <button type="submit"
                                    class="px-10 py-4 rounded-2xl bg-blue-700 hover:bg-blue-800 text-white font-black uppercase tracking-widest text-xs shadow-lg shadow-blue-100 transition">
                                    Submit Assessment
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>
