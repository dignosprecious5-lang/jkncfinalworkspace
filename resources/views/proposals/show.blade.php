<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal Details - {{ $proposal->proposal_code }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased p-8">

    <div class="max-w-6xl mx-auto space-y-6">

        <!-- Top Header Bar -->
        <div class="flex justify-between items-center bg-white p-6 rounded-lg border border-slate-200 shadow-sm">
            <div>
                <div class="flex items-center space-x-3">
                    <span class="font-mono text-xs px-2.5 py-1 bg-purple-50 text-purple-700 font-bold rounded border border-purple-200">
                        {{ $proposal->proposal_code }}
                    </span>
                    <h1 class="text-2xl font-bold text-slate-900">Proposal Details</h1>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Client: <strong class="text-slate-800">{{ $proposal->client_name }}</strong> ({{ $proposal->client_email }})
                </p>
            </div>

            <div class="flex items-center space-x-3">
                <!-- Status Badge -->
                <span class="px-3 py-1 text-xs font-bold uppercase rounded-full 
                    @if($proposal->status === 'accepted') bg-emerald-100 text-emerald-800
                    @elseif($proposal->status === 'sent') bg-blue-100 text-blue-800
                    @elseif($proposal->status === 'rejected') bg-rose-100 text-rose-800
                    @elseif($proposal->status === 'contracted') bg-purple-100 text-purple-800
                    @else bg-amber-100 text-amber-800 @endif">
                    {{ $proposal->status }}
                </span>

                <!-- Status Update Form (POST method only) -->
                @if(Route::has('proposals.updateStatus'))
                    <form action="{{ route('proposals.updateStatus', $proposal->id) }}" method="POST" class="flex items-center space-x-2">
                        @csrf
                        <select name="status" class="border border-slate-300 rounded p-1.5 text-xs bg-white text-slate-700 outline-none">
                            <option value="draft" {{ $proposal->status == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="sent" {{ $proposal->status == 'sent' ? 'selected' : '' }}>Sent</option>
                            <option value="accepted" {{ $proposal->status == 'accepted' ? 'selected' : '' }}>Accepted</option>
                            <option value="rejected" {{ $proposal->status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            <option value="contracted" {{ $proposal->status == 'contracted' ? 'selected' : '' }}>Contracted</option>
                        </select>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs px-3 py-1.5 rounded shadow-sm transition">
                            Update Status
                        </button>
                    </form>
                @endif

                <a href="{{ Route::has('proposals.index') ? route('proposals.index') : '#' }}" class="text-xs text-slate-600 hover:text-slate-900 border border-slate-300 px-3 py-1.5 rounded bg-slate-50">
                    Back to List
                </a>
            </div>
        </div>

        <!-- Details Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Main Service Info -->
            <div class="md:col-span-2 bg-white p-6 rounded-lg border border-slate-200 shadow-sm space-y-4 text-xs">
                <h2 class="text-sm font-bold text-slate-900 border-b pb-2">Service Master Snapshot</h2>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-slate-400 block uppercase font-semibold text-[10px]">Service Name</span>
                        <span class="font-bold text-slate-800 text-sm">{{ $proposal->service->name ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block uppercase font-semibold text-[10px]">Service Code</span>
                        <span class="font-mono text-slate-700">{{ $proposal->service->service_code ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block uppercase font-semibold text-[10px]">Proposed Commercial Price</span>
                        <span class="font-bold text-emerald-600 text-base font-mono">₱{{ number_format($proposal->proposed_price, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block uppercase font-semibold text-[10px]">Valid Until</span>
                        <span class="font-medium text-slate-700">{{ $proposal->valid_until ?? 'N/A' }}</span>
                    </div>
                </div>

                <!-- Scope & Deliverables -->
                <div class="pt-3 border-t space-y-3">
                    <div>
                        <span class="font-bold text-slate-800 text-xs block">Scope of Work</span>
                        <p class="text-slate-600 leading-relaxed mt-1">
                            {{ $proposal->service->activeVersion->scope_of_work ?? 'No explicit scope defined.' }}
                        </p>
                    </div>
                    <div>
                        <span class="font-bold text-slate-800 text-xs block">Tangible Deliverables</span>
                        <p class="text-slate-600 leading-relaxed mt-1">
                            {{ $proposal->service->activeVersion->deliverables ?? 'No deliverables specified.' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Requirements Checklist -->
            <div class="bg-white p-6 rounded-lg border border-slate-200 shadow-sm space-y-4 text-xs">
                <h2 class="text-sm font-bold text-slate-900 border-b pb-2">Client Requirements Checklist</h2>
                
                <ul class="space-y-2">
                    @forelse($proposal->service->activeVersion->requirements ?? [] as $req)
                        <li class="p-2.5 bg-slate-50 rounded border border-slate-200">
                            <span class="font-bold text-slate-800 block">{{ $req->requirement_name }}</span>
                            <div class="flex justify-between items-center mt-1 text-[10px] text-slate-500">
                                <span>Type: {{ $req->client_type ?? 'All' }}</span>
                                <span class="font-semibold {{ ($req->is_mandatory ?? true) ? 'text-emerald-600' : 'text-slate-400' }}">
                                    {{ ($req->is_mandatory ?? true) ? 'Mandatory' : 'Optional' }}
                                </span>
                            </div>
                        </li>
                    @empty
                        <li class="text-slate-400 italic">No checklist requirements attached.</li>
                    @endforelse
                </ul>
            </div>

        </div>

    </div>

</body>
</html>