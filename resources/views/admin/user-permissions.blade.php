@extends('layouts.app')
@section('title', 'User Permissions')

@section('content')
<div class="w-full min-h-screen bg-slate-50 px-6 py-5">

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
            <i class="fas fa-circle-check mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            <i class="fas fa-circle-exclamation mr-2"></i>{{ session('error') }}
        </div>
    @endif

    <div class="space-y-5">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="relative px-6 py-6">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-50 via-white to-indigo-50"></div>

                <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-700">
                            <i class="fas fa-user-lock text-[10px]"></i>
                            Admin Controls
                        </div>

                        <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">User Permissions</h1>
                        <p class="mt-1 text-sm text-slate-500">Assign specific system permissions per employee account.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 bg-white/80 px-4 py-3 shadow-sm">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Total Users</p>
                            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $users->total() }}</p>
                        </div>

                        <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-blue-500">Showing</p>
                            <p class="mt-1 text-2xl font-bold text-blue-700">{{ $users->count() }}</p>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white/80 px-4 py-3 shadow-sm col-span-2 sm:col-span-1">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Range</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800">{{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Permission Assignment</h2>
                        <p class="mt-1 text-sm text-slate-500">Open a user to manage their individual access rights.</p>
                    </div>

                    <div class="relative w-full lg:w-96">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                        <input
                            type="text"
                            id="userPermissionSearch"
                            placeholder="Search user, email, or role..."
                            class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-11 pr-4 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                            oninput="filterPermissionUsers(this.value)"
                        >
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[900px] w-full border-collapse text-sm text-slate-700">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50">
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">User</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Email</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Role</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">User ID</th>
                            <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Action</th>
                        </tr>
                    </thead>

                    <tbody id="userPermissionRows" class="divide-y divide-slate-100 bg-white">
                        @forelse($users as $user)
                            @php
                                $roleColor = match(strtolower((string) $user->role)) {
                                    'superadmin', 'super admin', 'system super admin' => 'bg-purple-50 text-purple-700 ring-purple-100',
                                    'admin' => 'bg-blue-50 text-blue-700 ring-blue-100',
                                    'employee' => 'bg-green-50 text-green-700 ring-green-100',
                                    'client' => 'bg-amber-50 text-amber-700 ring-amber-100',
                                    default => 'bg-slate-100 text-slate-700 ring-slate-200',
                                };
                            @endphp

                            <tr class="permission-user-row transition hover:bg-slate-50"
                                data-search="{{ strtolower($user->name.' '.$user->email.' '.$user->role.' '.$user->id) }}">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-sm font-bold text-slate-600">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $user->name }}</p>
                                            <p class="text-xs text-slate-400">Account #{{ $user->id }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-slate-600">{{ $user->email }}</td>

                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $roleColor }}">
                                        {{ $user->role }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 font-mono text-xs text-slate-500">#{{ $user->id }}</td>

                                <td class="px-5 py-4 text-right">
                                    <a
                                        href="{{ route('admin.user-permissions.edit', $user->id) }}"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700"
                                    >
                                        <i class="fas fa-shield-halved text-[11px]"></i>
                                        Manage Permissions
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-14 text-center">
                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                        <i class="fas fa-users-slash text-lg"></i>
                                    </div>
                                    <h3 class="mt-4 text-base font-semibold text-slate-800">No users found</h3>
                                    <p class="mt-1 text-sm text-slate-500">Create users first before assigning permissions.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-5 py-4">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>

<script>
    function filterPermissionUsers(query) {
        const search = String(query || '').toLowerCase().trim();
        document.querySelectorAll('.permission-user-row').forEach(row => {
            row.style.display = row.dataset.search.includes(search) ? '' : 'none';
        });
    }
</script>
@endsection
