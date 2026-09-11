@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    <!-- Header Navigation & Action Bar -->
    <div class="flex justify-between items-center bg-white p-4 rounded-lg border border-slate-200 shadow-sm w-full">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Proposals &amp; Contracts Integration</h1>
            <p class="text-xs text-slate-500">Create client proposals using configured services and standard pricing.</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ Route::has('services.index') ? route('services.index') : '/services' }}" class="text-xs text-slate-600 hover:text-slate-900 font-semibold border border-slate-300 px-3 py-1.5 rounded bg-white transition">
                &larr; Back to Services
            </a>
            <button onclick="document.getElementById('addProposalModal').classList.remove('hidden')" 
                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-4 py-2 rounded shadow-sm transition cursor-pointer">
                + Create New Proposal
            </button>
        </div>
    </div>

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="bg-emerald-100 border border-emerald-300 text-emerald-800 px-4 py-2.5 rounded text-xs font-semibold flex justify-between items-center shadow-sm w-full">
            <span><i class="fa-solid fa-circle-check mr-2"></i> {{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-rose-100 border border-rose-300 text-rose-800 px-4 py-2.5 rounded text-xs font-semibold flex justify-between items-center shadow-sm w-full">
            <span><i class="fa-solid fa-circle-xmark mr-2"></i> {{ session('error') }}</span>
        </div>
    @endif

    <!-- Table Filter -->
    <div class="bg-white p-3 rounded-lg border border-slate-200 shadow-sm flex items-center justify-between text-xs w-full">
        <form action="{{ Route::has('proposals.index') ? route('proposals.index') : '/proposals' }}" method="GET" class="flex items-center space-x-3 w-full">
            <div class="relative flex-1 max-w-xs">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search code, client, or email..." class="w-full pl-8 pr-3 py-1.5 border border-slate-300 rounded outline-none focus:border-blue-500 text-xs">
                <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-slate-400"></i>
            </div>
            <select name="status" class="border border-slate-300 rounded px-2.5 py-1.5 outline-none text-xs text-slate-700">
                <option value="">All Status</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                <option value="accepted" {{ request('status') == 'accepted' ? 'selected' : '' }}>Accepted</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                <option value="contracted" {{ request('status') == 'contracted' ? 'selected' : '' }}>Contracted</option>
            </select>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-3.5 py-1.5 rounded transition shadow-sm cursor-pointer">Filter</button>
            <a href="{{ Route::has('proposals.index') ? route('proposals.index') : '/proposals' }}" class="border border-slate-300 text-slate-600 px-3 py-1.5 rounded bg-white hover:bg-slate-50 transition">Reset</a>
        </form>
    </div>

    <!-- Proposals Table -->
    <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden w-full">
        <table class="w-full text-left border-collapse text-xs">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-semibold">
                    <th class="py-3 px-4">Proposal Code</th>
                    <th class="py-3 px-4">Client Name &amp; Email</th>
                    <th class="py-3 px-4">Selected Service</th>
                    <th class="py-3 px-4">Proposed Price</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($proposals ?? [] as $prop)
                    <tr class="hover:bg-slate-50 transition">

                        <!-- CLICKABLE PROPOSAL CODE -->
                        <td class="py-3 px-4 font-mono font-bold text-blue-700">
                            <a href="{{ Route::has('proposals.show') ? route('proposals.show', $prop->id) : '/proposals/' . $prop->id }}" 
                               class="hover:underline hover:text-blue-900 flex items-center space-x-1">
                                <span>{{ $prop->proposal_code ?? ('#PRP-' . $prop->id) }}</span>
                            </a>
                        </td>

                        <!-- CLICKABLE CLIENT NAME -->
                        <td class="py-3 px-4 font-semibold text-slate-900">
                            <a href="{{ Route::has('proposals.show') ? route('proposals.show', $prop->id) : '/proposals/' . $prop->id }}" 
                               class="hover:text-blue-600 hover:underline block">
                                {{ $prop->client_name }}
                            </a>
                            <span class="block text-[10px] text-slate-400 font-normal">{{ $prop->client_email }}</span>
                        </td>

                        <td class="py-3 px-4 text-slate-700 font-medium">
                            {{ optional($prop->service)->name ?? $prop->service_name ?? 'General Service' }}
                        </td>
                        <td class="py-3 px-4 font-mono font-bold text-slate-900">
                            ₱{{ number_format($prop->proposed_price ?? $prop->amount ?? 0, 2) }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2.5 py-1 text-[10px] rounded-full font-bold uppercase tracking-wider
                                @if(strtolower($prop->status ?? '') === 'accepted' || strtolower($prop->status ?? '') === 'contracted') bg-emerald-100 text-emerald-700
                                @elseif(strtolower($prop->status ?? '') === 'sent') bg-blue-100 text-blue-700
                                @elseif(strtolower($prop->status ?? '') === 'rejected') bg-rose-100 text-rose-700
                                @else bg-amber-100 text-amber-700 @endif">
                                {{ $prop->status ?? 'DRAFT' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center space-x-2">
                            <a href="{{ Route::has('client.proposal.show') ? route('client.proposal.show', $prop->token ?? $prop->id) : '/p/proposal/' . $prop->id }}" 
                               target="_blank" 
                               class="inline-flex items-center gap-1 text-[11px] font-bold text-purple-700 bg-purple-50 hover:bg-purple-100 px-2.5 py-1 rounded transition border border-purple-200"
                               title="Open Client Portal View">
                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                <span>Client Link</span>
                            </a>

                            <button type="button" onclick="openStatusModal('{{ $prop->id }}', '{{ $prop->status }}')" class="text-purple-600 hover:underline font-semibold cursor-pointer">
                                Status
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">
                            No proposals found. Click <strong>+ Create New Proposal</strong> to add one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- MODAL FOR CREATING PROPOSAL -->
    <div id="addProposalModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden flex justify-center items-center p-4 z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6 text-xs max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h3 class="text-sm font-bold text-slate-900">Create Client Proposal</h3>
                <button type="button" onclick="document.getElementById('addProposalModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-700 text-xl font-bold p-1 cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form action="{{ Route::has('proposals.store') ? route('proposals.store') : '/proposals' }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-600 mb-1">Client Name *</label>
                    <input type="text" name="client_name" required placeholder="e.g. Acme Corp / Juan Dela Cruz" class="w-full border border-slate-300 rounded p-2 outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-600 mb-1">Client Email *</label>
                    <input type="email" name="client_email" required placeholder="client@company.com" class="w-full border border-slate-300 rounded p-2 outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-600 mb-1">Select Service *</label>
                    <select name="service_id" id="serviceSelectModal" onchange="autoFillPrice()" required class="w-full border border-slate-300 rounded p-2 outline-none focus:border-blue-500 text-slate-700">
                        <option value="" data-price="0.00" data-behavior="project">-- Select Service --</option>
                        @if(isset($services) && count($services) > 0)
                            @foreach($services as $svc)
                                @php
                                    $stdPrice = optional($svc->activeVersion)->standard_price ?? 0;
                                    $behavior = $svc->engagement_behavior ?? 'project';
                                @endphp
                                <option value="{{ $svc->id }}" data-price="{{ $stdPrice }}" data-behavior="{{ $behavior }}">
                                    {{ $svc->name }} (₱{{ number_format($stdPrice, 2) }})
                                </option>
                            @endforeach
                        @else
                            <option value="1" data-price="15000.00" data-behavior="project">Project Engagement (One-time) - ₱15,000.00</option>
                            <option value="2" data-price="25000.00" data-behavior="regular">Monthly Retainer Service - ₱25,000.00</option>
                            <option value="3" data-price="50000.00" data-behavior="both">Corporate Tax Audit - ₱50,000.00</option>
                        @endif
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-600 mb-1">Engagement Structure *</label>
                    <select name="engagement_behavior" id="engagementBehaviorModal" required class="w-full border border-slate-300 rounded p-2 outline-none focus:border-blue-500 text-slate-700">
                        <option value="project">Project Engagement (One-time)</option>
                        <option value="regular">Regular (Retainer / Recurring)</option>
                        <option value="both">Hybrid (Project + Retainer)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-600 mb-1">Proposed Price (₱) *</label>
                    <input type="number" step="0.01" name="proposed_price" id="proposedPriceModal" required placeholder="0.00" class="w-full border border-slate-300 rounded p-2 outline-none focus:border-blue-500 font-mono font-semibold">
                </div>

                <div class="flex justify-end space-x-2 pt-4 border-t mt-4">
                    <button type="button" onclick="document.getElementById('addProposalModal').classList.add('hidden')" class="px-3 py-1.5 text-slate-600 hover:bg-slate-100 rounded cursor-pointer">Cancel</button>
                    <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded font-medium shadow-sm cursor-pointer">Save Proposal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL FOR UPDATING STATUS -->
    <div id="statusModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden flex justify-center items-center p-4 z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-sm w-full p-6 text-xs">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h3 class="text-sm font-bold text-slate-900">Update Proposal Status</h3>
                <button type="button" onclick="document.getElementById('statusModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-700 text-xl font-bold p-1 cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="statusForm" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-600 mb-1">New Status *</label>
                    <select name="status" id="modalStatusSelect" required class="w-full border border-slate-300 rounded p-2 outline-none text-slate-700">
                        <option value="draft">Draft</option>
                        <option value="sent">Sent to Client</option>
                        <option value="accepted">Accepted</option>
                        <option value="rejected">Rejected</option>
                        <option value="contracted">Contracted</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-2 pt-4 border-t mt-4">
                    <button type="button" onclick="document.getElementById('statusModal').classList.add('hidden')" class="px-3 py-1.5 text-slate-600 hover:bg-slate-100 rounded cursor-pointer">Cancel</button>
                    <button type="submit" class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded font-medium shadow-sm cursor-pointer">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript Handlers -->
    <script>
        function openStatusModal(id, currentStatus) {
            const form = document.getElementById('statusForm');
            if (form) {
                form.action = '/proposals/' + id + '/status';
                document.getElementById('modalStatusSelect').value = currentStatus;
                document.getElementById('statusModal').classList.remove('hidden');
            }
        }

        function autoFillPrice() {
            const select = document.getElementById('serviceSelectModal');
            if (!select) return;
            const selectedOption = select.options[select.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            const behavior = selectedOption.getAttribute('data-behavior');
            
            if (price) {
                document.getElementById('proposedPriceModal').value = parseFloat(price).toFixed(2);
            }
            if (behavior) {
                document.getElementById('engagementBehaviorModal').value = behavior;
            }
        }
    </script>

</div>
@endsection