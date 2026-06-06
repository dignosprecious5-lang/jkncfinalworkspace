<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center px-6 py-10">
    <div class="w-full max-w-lg rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200">
            <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Account Security</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Create a New Password</h1>
            <p class="mt-1 text-sm text-slate-500">Use the secure reset link to create your new password.</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="p-6 space-y-4">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

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
                <label class="block text-sm font-semibold text-slate-700 mb-1">Registered Email</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" required class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">New Password</label>
                <input type="password" name="password" required class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Confirm New Password</label>
                <input type="password" name="password_confirmation" required class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm">
            </div>

            <button type="submit" class="w-full rounded-lg bg-blue-600 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                Reset Password
            </button>
        </form>
    </div>
</body>
</html>
