@php
    $reports = $project->sowReports()->latest('date_prepared')->latest()->get();
    $statusClasses = [
        'Approved' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
        'Pending' => 'bg-amber-50 text-amber-700 border border-amber-200',
    ];
    $projectLocked = $projectLocked ?? false;
@endphp

<div class="space-y-5">
    @if (! $projectLocked)
    <div id="projectReportSelectionBar" class="hidden rounded-xl border border-blue-100 bg-blue-50 px-4 py-3">
        <div class="flex items-center gap-2 text-sm">
            <span class="font-medium text-slate-800"><span id="projectReportSelectedCount">0</span> selected</span>
            <button id="projectReportOpenDeleteModal" type="button" class="h-8 rounded-md border border-red-200 bg-white px-3 text-red-600 hover:bg-red-50">Delete Selected</button>
            <button id="projectReportClearSelection" type="button" class="ml-auto text-slate-700 hover:underline">Clear</button>
        </div>
    </div>
    @endif

    <section class="project-top-card rounded-2xl px-6 py-5">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Report Registry</p>
                <h2 class="mt-2 text-3xl font-semibold text-gray-900">SOW Reports</h2>
                <p class="mt-2 text-sm text-slate-500">Generated scope of work reports are recorded here from the Scope of Work tab.</p>
            </div>
            <div class="flex flex-wrap gap-3 text-sm">
                <span class="project-pill"><span class="text-slate-400">Total Reports</span> {{ $reports->count() }}</span>
                <span class="project-pill"><span class="text-slate-400">Latest Report</span> {{ $reports->first()?->report_number ?: '-' }}</span>
            </div>
        </div>
    </section>

    <section class="project-linked-card overflow-hidden rounded-2xl">
        <div class="border-b border-slate-200 px-6 py-4">
            <div class="relative max-w-md">
                <i class="fas fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                <input
                    id="projectReportSearch"
                    type="text"
                    placeholder="Search report number or status..."
                    autocomplete="off"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-3 text-sm text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                >
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                    <tr>
                        @if (! $projectLocked)<th class="w-10 px-3 py-4 text-left"><input id="projectReportSelectAll" type="checkbox" class="h-4 w-4 rounded border-slate-300"></th>@endif
                        <th class="px-6 py-4 text-left">Report No.</th>
                        <th class="px-6 py-4 text-left">Date of Reporting</th>
                        <th class="px-6 py-4 text-left">Date Sent to Client</th>
                        <th class="px-6 py-4 text-left">Date Approved</th>
                        <th class="px-6 py-4 text-left">Status</th>
                    </tr>
                </thead>
                <tbody id="projectReportTableBody" class="divide-y divide-slate-100 bg-white">
                    @forelse ($reports as $item)
                        @php
                            $isApproved = $item->client_response_status === 'approved' && $item->client_approved_at;
                            $statusLabel = $isApproved ? 'Approved' : 'Pending';
                            $previewUrl = route('project.report.preview', ['project' => $project->id, 'report' => $item->id]);
                        @endphp
                        <tr
                            class="cursor-pointer text-slate-700 transition hover:bg-slate-50"
                            data-report-search="{{ \Illuminate\Support\Str::lower(implode(' ', array_filter([$item->report_number, $statusLabel, optional($item->date_prepared)->format('M d, Y'), optional($item->client_approved_at)->format('M d, Y')])) ) }}"
                            onclick="window.location='{{ $previewUrl }}'"
                        >
                            @if (! $projectLocked)<td class="px-3 py-4" onclick="event.stopPropagation()">
                                <input type="checkbox" value="{{ $item->id }}" class="project-report-row-checkbox h-4 w-4 rounded border-slate-300">
                            </td>@endif
                            <td class="px-6 py-4">
                                <span class="font-semibold text-blue-700 hover:text-blue-800">{{ $item->report_number ?: 'Report-'.$item->id }}</span>
                            </td>
                            <td class="px-6 py-4">{{ optional($item->date_prepared)->format('M d, Y') ?: '-' }}</td>
                            <td class="px-6 py-4">{{ optional($item->created_at)->format('M d, Y') ?: '-' }}</td>
                            <td class="px-6 py-4">{{ optional($item->client_approved_at)->format('M d, Y') ?: '-' }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClasses[$statusLabel] ?? 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $projectLocked ? 5 : 6 }}" class="px-6 py-12 text-center text-sm text-slate-500">
                                No generated SOW reports yet. Use <span class="font-semibold text-slate-700">Generate SOW Report</span> in the Scope of Work tab.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

@if (! $projectLocked)
<div id="projectReportDeleteModal" class="fixed inset-0 z-[70] hidden" aria-hidden="true">
    <button id="projectReportDeleteOverlay" type="button" aria-label="Close delete reports modal" class="absolute inset-0 bg-slate-900/45"></button>
    <div class="absolute inset-0 flex items-center justify-center px-4">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
            <div class="border-b border-slate-100 px-6 py-5">
                <h2 class="text-xl font-semibold text-slate-900">Delete Selected SOW Reports</h2>
                <p class="mt-1 text-sm text-slate-500">This action will permanently delete the selected report records.</p>
            </div>
            <form id="projectReportBulkDeleteForm" method="POST" action="{{ route('project.report.bulk-delete', $project) }}">
                @csrf
                @method('DELETE')
                <div id="projectReportDeleteSelectedInputs"></div>
                <div class="px-6 py-5 text-sm text-slate-700">
                    Are you sure you want to delete <span id="projectReportDeleteCountText" class="font-semibold text-slate-900">0 reports</span>?
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-slate-100 px-6 py-4">
                    <button id="projectReportCancelDeleteModal" type="button" class="h-10 rounded-lg border border-slate-300 px-4 text-sm text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="h-10 rounded-lg bg-red-600 px-5 text-sm font-medium text-white hover:bg-red-700">Delete Selected</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
    (() => {
        const searchInput = document.getElementById('projectReportSearch');
        const rows = Array.from(document.querySelectorAll('#projectReportTableBody tr[data-report-search]'));
        const selectAll = document.getElementById('projectReportSelectAll');
        const rowChecks = Array.from(document.querySelectorAll('.project-report-row-checkbox'));
        const selectionBar = document.getElementById('projectReportSelectionBar');
        const selectedCount = document.getElementById('projectReportSelectedCount');
        const clearSelection = document.getElementById('projectReportClearSelection');
        const openDeleteModalButton = document.getElementById('projectReportOpenDeleteModal');
        const deleteModal = document.getElementById('projectReportDeleteModal');
        const deleteOverlay = document.getElementById('projectReportDeleteOverlay');
        const cancelDeleteModalButton = document.getElementById('projectReportCancelDeleteModal');
        const deleteSelectedInputs = document.getElementById('projectReportDeleteSelectedInputs');
        const deleteCountText = document.getElementById('projectReportDeleteCountText');

        if (searchInput && rows.length > 0) {
            searchInput.addEventListener('input', () => {
                const keyword = String(searchInput.value || '').trim().toLowerCase();

                rows.forEach((row) => {
                    const blob = String(row.dataset.reportSearch || '').toLowerCase();
                    row.classList.toggle('hidden', keyword !== '' && !blob.includes(keyword));
                });
            });
        }

        const syncSelectionUi = () => {
            const selected = rowChecks.filter((item) => item.checked);

            if (selectionBar) {
                selectionBar.classList.toggle('hidden', selected.length === 0);
            }

            if (selectedCount) {
                selectedCount.textContent = String(selected.length);
            }

            if (selectAll) {
                selectAll.checked = rowChecks.length > 0 && selected.length === rowChecks.length;
                selectAll.indeterminate = selected.length > 0 && selected.length < rowChecks.length;
            }
        };

        const closeDeleteModal = () => {
            if (!deleteModal) {
                return;
            }

            deleteModal.classList.add('hidden');
            deleteModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
        };

        const openDeleteModal = () => {
            const selected = rowChecks.filter((item) => item.checked);

            if (selected.length === 0 || !deleteModal) {
                return;
            }

            if (deleteSelectedInputs) {
                deleteSelectedInputs.innerHTML = selected
                    .map((item) => `<input type="hidden" name="selected_reports[]" value="${item.value}">`)
                    .join('');
            }

            if (deleteCountText) {
                deleteCountText.textContent = `${selected.length} ${selected.length === 1 ? 'report' : 'reports'}`;
            }

            deleteModal.classList.remove('hidden');
            deleteModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
        };

        selectAll?.addEventListener('change', () => {
            rowChecks.forEach((item) => {
                item.checked = selectAll.checked;
            });
            syncSelectionUi();
        });

        rowChecks.forEach((item) => {
            item.addEventListener('change', syncSelectionUi);
        });

        clearSelection?.addEventListener('click', () => {
            rowChecks.forEach((item) => {
                item.checked = false;
            });
            if (selectAll) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            }
            syncSelectionUi();
        });

        openDeleteModalButton?.addEventListener('click', openDeleteModal);
        deleteOverlay?.addEventListener('click', closeDeleteModal);
        cancelDeleteModalButton?.addEventListener('click', closeDeleteModal);

        syncSelectionUi();
    })();
</script>
