<section class="bg-gray-50 p-4 min-h-[760px]">
    <div id="companyActivitiesApp" class="rounded-md border border-gray-200 bg-white overflow-hidden shadow-sm">
        <div class="border-b border-gray-100 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900">Activities</h2>
                    <p class="mt-1 text-sm text-gray-500">Manage company activities with the same card structure used in Contacts</p>
                </div>
                <button id="openCompanyActivityModal" type="button" class="h-10 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">+ Add Activity</button>
            </div>
        </div>
        <div class="p-4">
            <div id="companyActivitiesList" class="space-y-3"></div>
        </div>
        <div id="companyActivityModal" class="fixed inset-0 z-[70] hidden" aria-hidden="true">
            <button type="button" data-slideover-overlay class="absolute inset-0 bg-slate-900/45 opacity-0 transition-opacity duration-300"></button>
            <div class="absolute inset-y-0 right-0 flex w-full justify-end overflow-hidden pointer-events-none">
                <div data-slideover-panel class="pointer-events-auto flex h-full w-full max-w-[620px] translate-x-full flex-col border-l border-gray-200 bg-white shadow-2xl transition-transform duration-300 ease-out">
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5 sm:px-8">
                        <h3 id="companyActivityModalTitle" class="text-xl font-semibold text-gray-900">Add Activity</h3>
                        <button id="closeCompanyActivityModal" type="button" class="text-2xl leading-none text-gray-500 hover:text-gray-900">&times;</button>
                    </div>
                    <form id="companyActivityForm" class="flex min-h-0 flex-1 flex-col">
                        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-6 sm:px-8">
                            <div><label for="companyActivityType" class="mb-1 block text-sm font-medium text-gray-700">Activity Type</label><select id="companyActivityType" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"><option>Task</option><option>Call</option><option>Meeting</option><option>Email</option></select></div>
                            <div><label for="companyActivityDescription" class="mb-1 block text-sm font-medium text-gray-700">Activity Notes</label><textarea id="companyActivityDescription" rows="4" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></textarea></div>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div><label for="companyActivityAssignedUser" class="mb-1 block text-sm font-medium text-gray-700">Assigned User</label><input id="companyActivityAssignedUser" type="text" value="{{ $company->owner_name ?: 'John Admin' }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div><label for="companyActivityStatus" class="mb-1 block text-sm font-medium text-gray-700">Status</label><select id="companyActivityStatus" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"><option>Pending</option><option>In Progress</option><option>Completed</option><option>Sent</option></select></div>
                            </div>
                            <div><label for="companyActivityDueAt" class="mb-1 block text-sm font-medium text-gray-700">Due Date</label><input id="companyActivityDueAt" type="datetime-local" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                            <div><label for="companyActivityNotes" class="mb-1 block text-sm font-medium text-gray-700">Internal Notes</label><textarea id="companyActivityNotes" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></textarea></div>
                        </div>
                        <div class="flex justify-end gap-3 border-t border-gray-100 bg-white px-6 py-4 sm:px-8">
                            <button id="deleteCompanyActivity" type="button" class="mr-auto hidden h-10 rounded-lg border border-red-200 bg-red-50 px-4 text-sm text-red-700 hover:bg-red-100">Delete</button>
                            <button id="cancelCompanyActivity" type="button" class="h-10 rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                            <button type="submit" class="h-10 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">Save Activity</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const app = document.getElementById('companyActivitiesApp');
    if (!app) return;
    const csrfToken = @json(csrf_token());
    const storeUrl = @json(route('company.activities.store', $company->id));
    const updateTemplate = @json(route('company.activities.update', [$company->id, '__ACTIVITY__']));
    const completeTemplate = @json(route('company.activities.complete', [$company->id, '__ACTIVITY__']));
    const deleteTemplate = @json(route('company.activities.destroy', [$company->id, '__ACTIVITY__']));
    let activities = @json($activities);
    let editActivityId = null;
    const list = document.getElementById('companyActivitiesList');
    const modal = document.getElementById('companyActivityModal');
    const form = document.getElementById('companyActivityForm');
    const deleteButton = document.getElementById('deleteCompanyActivity');
    const fields = {
        type: document.getElementById('companyActivityType'),
        description: document.getElementById('companyActivityDescription'),
        assignedUser: document.getElementById('companyActivityAssignedUser'),
        status: document.getElementById('companyActivityStatus'),
        dueAt: document.getElementById('companyActivityDueAt'),
        notes: document.getElementById('companyActivityNotes'),
    };
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));
    const statusClass = (status) => status === 'Completed' ? 'bg-green-100 text-green-700' : status === 'Sent' ? 'bg-blue-100 text-blue-700' : status === 'In Progress' ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-700';
    const sendJson = async (url, method, payload) => {
        const response = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: payload ? JSON.stringify(payload) : null });
        if (!response.ok) throw new Error('Request failed');
        return response.json();
    };
    const showModal = () => { modal.classList.remove('hidden'); requestAnimationFrame(() => { modal.querySelector('[data-slideover-overlay]').classList.remove('opacity-0'); modal.querySelector('[data-slideover-panel]').classList.remove('translate-x-full'); }); };
    const hideModal = () => { modal.querySelector('[data-slideover-overlay]').classList.add('opacity-0'); modal.querySelector('[data-slideover-panel]').classList.add('translate-x-full'); setTimeout(() => modal.classList.add('hidden'), 300); };
    const resetForm = () => { editActivityId = null; fields.type.value = 'Task'; fields.description.value = ''; fields.assignedUser.value = @json($company->owner_name ?: 'John Admin'); fields.status.value = 'Pending'; fields.dueAt.value = ''; fields.notes.value = ''; deleteButton.classList.add('hidden'); document.getElementById('companyActivityModalTitle').textContent = 'Add Activity'; };
    const render = () => {
        if (!activities.length) { list.innerHTML = '<div class="rounded-xl border border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-500">No activities yet.</div>'; return; }
        list.innerHTML = activities.map((activity) => {
            const isLinkedMainActivity = activity.readonly || activity.source === 'main';
            const sourceBadge = isLinkedMainActivity ? '<span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">Main Activities</span>' : '';
            const actions = isLinkedMainActivity ? '' : `<button type="button" class="activity-edit text-gray-500 hover:text-blue-600" data-activity-id="${activity.id}"><i class="far fa-pen-to-square"></i></button>${activity.status !== 'Completed' ? `<button type="button" class="activity-complete text-gray-500 hover:text-green-600" data-activity-id="${activity.id}"><i class="fas fa-check"></i></button>` : ''}`;
            return `<article class="rounded-xl border border-gray-200 p-4"><div class="flex items-start justify-between gap-3"><div class="flex items-start gap-3"><span class="mt-1 flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-blue-600"><i class="fas ${escapeHtml(activity.icon || 'fa-square-check')} text-xs"></i></span><div><div class="flex flex-wrap items-center gap-2"><h3 class="text-lg font-semibold text-gray-900">${escapeHtml(activity.type)}</h3>${sourceBadge}</div><p class="text-sm text-gray-600">${escapeHtml(activity.description)}</p><p class="mt-2 text-xs text-gray-500">${escapeHtml(activity.when || '-')} | ${escapeHtml(activity.owner || '-')}</p>${activity.notes ? `<p class="mt-2 text-xs text-gray-500">${escapeHtml(activity.notes)}</p>` : ''}</div></div><div class="flex items-center gap-2"><span class="rounded-full px-2 py-0.5 text-xs font-medium ${statusClass(activity.status)}">${escapeHtml(activity.status)}</span>${actions}</div></div></article>`;
        }).join('');
    };
    const openEdit = (activityId) => {
        const activity = activities.find((item) => Number(item.id) === Number(activityId));
        if (!activity) return;
        editActivityId = Number(activity.id);
        fields.type.value = activity.type || 'Task';
        fields.description.value = activity.description || '';
        fields.assignedUser.value = activity.owner || '';
        fields.status.value = activity.status || 'Pending';
        fields.dueAt.value = activity.dueAt || '';
        fields.notes.value = activity.notes || '';
        deleteButton.classList.remove('hidden');
        document.getElementById('companyActivityModalTitle').textContent = 'Edit Activity';
        showModal();
    };
    document.getElementById('openCompanyActivityModal').addEventListener('click', function () { resetForm(); showModal(); });
    document.getElementById('closeCompanyActivityModal').addEventListener('click', hideModal);
    document.getElementById('cancelCompanyActivity').addEventListener('click', hideModal);
    modal.querySelector('[data-slideover-overlay]').addEventListener('click', hideModal);
    list.addEventListener('click', async function (event) {
        const editButton = event.target.closest('.activity-edit');
        const completeButton = event.target.closest('.activity-complete');
        if (editButton) openEdit(editButton.dataset.activityId);
        if (completeButton) {
            const payload = await sendJson(completeTemplate.replace('__ACTIVITY__', completeButton.dataset.activityId), 'PATCH');
            activities = activities.map((item) => Number(item.id) === Number(payload.id) ? payload : item);
            render();
        }
    });
    deleteButton.addEventListener('click', async function () { if (editActivityId === null || !confirm('Delete this activity?')) return; await sendJson(deleteTemplate.replace('__ACTIVITY__', editActivityId), 'DELETE'); activities = activities.filter((item) => Number(item.id) !== editActivityId); render(); hideModal(); });
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        const payload = { type: fields.type.value, description: fields.description.value, assigned_user: fields.assignedUser.value, status: fields.status.value, due_at: fields.dueAt.value ? fields.dueAt.value.replace('T', ' ') : null, notes: fields.notes.value };
        const activity = editActivityId === null ? await sendJson(storeUrl, 'POST', payload) : await sendJson(updateTemplate.replace('__ACTIVITY__', editActivityId), 'PUT', payload);
        activities = editActivityId === null ? [activity, ...activities] : activities.map((item) => Number(item.id) === editActivityId ? activity : item);
        render();
        hideModal();
    });
    render();
});
</script>
