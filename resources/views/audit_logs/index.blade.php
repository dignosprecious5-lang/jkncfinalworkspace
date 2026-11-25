<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail - John Kelly & Company</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased h-full flex overflow-hidden">

    <!-- FIXED & LOCKED LIGHT SIDEBAR -->
    <aside class="w-64 bg-white border-r border-slate-200 flex flex-col shrink-0 h-full select-none">
        
        <!-- BRANDING HEADER -->
        <div class="p-5 border-b border-slate-100">
            <h1 class="font-bold text-slate-900 text-lg leading-tight">John Kelly</h1>
            <p class="text-xs font-semibold text-blue-600">& Company</p>
        </div>

        <!-- NAVIGATION MENU (LOCKED / NO SCROLLBAR) -->
        <div class="p-4 flex-1 overflow-hidden">
            <div class="px-2 mb-3 flex items-center justify-between text-xs font-semibold text-slate-400">
                <span>Enterprise Menu</span>
                <i class="fa-solid fa-angles-right text-[10px]"></i>
            </div>

            <nav class="space-y-1 text-sm font-medium">
                <a href="#" class="flex items-center space-x-3 px-3 py-2 text-slate-600 rounded-lg hover:bg-slate-50 transition">
                    <i class="fa-regular fa-user w-5 text-slate-400"></i>
                    <span>Admin</span>
                </a>
                <a href="#" class="flex items-center space-x-3 px-3 py-2 text-slate-600 rounded-lg hover:bg-slate-50 transition">
                    <i class="fa-solid fa-city w-5 text-slate-400"></i>
                    <span>Town Hall</span>
                </a>
                <a href="#" class="flex items-center space-x-3 px-3 py-2 text-slate-600 rounded-lg hover:bg-slate-50 transition">
                    <i class="fa-regular fa-folder w-5 text-slate-400"></i>
                    <span>Corporate</span>
                </a>

                <!-- MARKETING GROUP -->
                <div>
                    <div class="flex items-center justify-between px-3 py-2 text-blue-600 font-semibold bg-blue-50/60 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid fa-bullhorn w-5"></i>
                            <span>Marketing</span>
                        </div>
                        <i class="fa-solid fa-chevron-down text-xs"></i>
                    </div>
                    <div class="ml-9 mt-1 space-y-1 text-xs font-medium">
                        <a href="{{ route('services.index') }}" class="block py-1.5 text-slate-600 hover:text-blue-600">Services</a>
                        <a href="{{ route('requirements.index') }}" class="flex items-center justify-between py-1.5 text-slate-600 hover:text-blue-600">
                            <span>Requirements</span>
                            <span class="bg-blue-100 text-blue-700 font-semibold text-[10px] px-1.5 py-0.5 rounded-full">New</span>
                        </a>
                    </div>
                </div>

                <!-- PROPOSALS -->
                <a href="{{ route('proposals.index') }}" class="flex items-center justify-between px-3 py-2 text-slate-600 rounded-lg hover:bg-slate-50 transition">
                    <div class="flex items-center space-x-3">
                        <i class="fa-solid fa-file-signature w-5 text-purple-600"></i>
                        <span>Proposals & Contracts</span>
                    </div>
                    <span class="bg-purple-100 text-purple-700 font-semibold text-[10px] px-1.5 py-0.5 rounded-full">New</span>
                </a>

                <!-- OPERATIONS GROUP -->
                <div>
                    <div class="flex items-center justify-between px-3 py-2 text-slate-700 font-semibold">
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid fa-gear w-5 text-slate-400"></i>
                            <span>Operations</span>
                        </div>
                        <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
                    </div>
                    <div class="ml-9 mt-1 space-y-1 text-xs font-medium">
                        <a href="{{ route('engagements.index') }}" class="block py-1.5 text-blue-600 font-semibold">Engagements</a>
                        <a href="{{ route('audit_logs.index') }}" class="block py-1.5 text-blue-600 font-bold">Audit Trail Logs</a>
                    </div>
                </div>
            </nav>
        </div>

        <!-- FOOTER USER PROFILE -->
        <div class="p-4 border-t border-slate-100 text-xs text-slate-500">
            Logged in as: <span class="font-bold text-slate-800">{{ auth()->user()->name ?? 'Manager User' }}</span>
        </div>
    </aside>

    <!-- MAIN SCROLLABLE CONTENT -->
    <main class="flex-1 flex flex-col h-full overflow-hidden">
        
        <!-- HEADER -->
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex justify-between items-center shrink-0">
            <div>
                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-clipboard-list text-blue-600"></i>
                    System Audit Trail & Activity Logs
                </h2>
                <p class="text-xs text-slate-500">ORDO Specification Compliance Audit Trail</p>
            </div>
            <a href="{{ route('services.index') }}" class="text-xs font-semibold text-slate-600 bg-slate-100 px-3 py-2 rounded-lg hover:bg-slate-200 transition">
                &larr; Back to Dashboard
            </a>
        </header>

        <!-- TABLE AREA (ONLY THIS AREA SCROLLS IF NEEDED) -->
        <div class="flex-1 overflow-y-auto p-8">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-left border-collapse text-sm text-slate-600">
                    <thead class="bg-slate-50 border-b border-slate-200 font-semibold text-slate-700 text-xs">
                        <tr>
                            <th class="py-3 px-4">Timestamp</th>
                            <th class="py-3 px-4">User</th>
                            <th class="py-3 px-4">Module</th>
                            <th class="py-3 px-4">Action</th>
                            <th class="py-3 px-4">Description</th>
                            <th class="py-3 px-4">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @forelse($logs as $log)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3 px-4 font-mono text-slate-500 whitespace-nowrap">
                                    {{ $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : 'N/A' }}
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-900">
                                    {{ $log->user_name }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded border border-slate-200 font-medium">
                                        {{ $log->module }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-semibold">
                                    @if(str_contains($log->action, 'EXPORT'))
                                        <span class="inline-flex items-center gap-1 text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded text-[11px] border border-emerald-200">
                                            <i class="fa-solid fa-file-export"></i> {{ $log->action }}
                                        </span>
                                    @elseif(str_contains($log->action, 'IMPORT'))
                                        <span class="inline-flex items-center gap-1 text-blue-700 bg-blue-50 px-2 py-0.5 rounded text-[11px] border border-blue-200">
                                            <i class="fa-solid fa-file-import"></i> {{ $log->action }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-purple-700 bg-purple-50 px-2 py-0.5 rounded text-[11px] border border-purple-200">
                                            <i class="fa-solid fa-bolt"></i> {{ $log->action }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-slate-700">
                                    {{ $log->description }}
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-400">
                                    {{ $log->ip_address ?? '127.0.0.1' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400">
                                    <i class="fa-solid fa-inbox text-2xl mb-2 text-slate-300"></i>
                                    <p>No audit log records found yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if($logs->hasPages())
                    <div class="p-3 border-t border-slate-100 bg-slate-50">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </main>

</body>
</html>