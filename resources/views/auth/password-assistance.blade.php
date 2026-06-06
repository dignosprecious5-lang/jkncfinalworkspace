<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Assistance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center px-6 py-10">
    <div class="w-full max-w-lg rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200">
            <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Account Assistance</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Need help accessing your account?</h1>
            <p class="mt-1 text-sm text-slate-500">Submit a request and an authorized administrator will review it.</p>
        </div>

        <form method="POST" action="{{ route('password.assistance.store') }}" class="p-6 space-y-4">
            @csrf

            @if($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Full Name</label>
                <input name="full_name" value="{{ old('full_name') }}" required class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Registered Email Address</label>
                <input type="email" name="registered_email" value="{{ old('registered_email') }}" required class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Contact Number <span class="font-normal text-slate-400">(Optional)</span></label>
                <input name="contact_number" value="{{ old('contact_number') }}" class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Message / Reason for Request</label>
                <textarea name="message" rows="4" class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{{ old('message') }}</textarea>
            </div>

            <div class="flex gap-3 pt-3 border-t border-slate-100">
                <a href="{{ route('login') }}" class="flex-1 rounded-lg border border-slate-300 bg-white py-3 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Back to Login
                </a>
                <button type="submit" class="flex-1 rounded-lg bg-blue-600 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                    Submit Request
                </button>
            </div>
        </form>
    </div>
</body>
</html>
