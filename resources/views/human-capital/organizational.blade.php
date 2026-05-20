@extends('layouts.app')

@section('content')
<div class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col">
    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0">

        {{-- TOP BAR --}}
        <div class="flex items-center justify-between px-4 py-3 border-b shrink-0 gap-4">
            <div class="flex items-center flex-1 min-w-0">
                <h1 class="text-lg font-semibold text-gray-900">Organizational</h1>
            </div>

            <button
                type="button"
                onclick="openAddSection()"
                class="bg-blue-600 text-white px-6 py-2 rounded text-sm shrink-0 hover:bg-blue-700 transition"
            >
                + Add
            </button>
        </div>

        {{-- TABS --}}
        <div class="px-4 py-3 border-b bg-white shrink-0 overflow-x-auto">
            <div class="inline-flex min-w-max rounded-md border border-gray-200 overflow-hidden">
                <button type="button" class="org-tab-btn px-4 py-2 text-sm border-r border-gray-200 bg-blue-50 text-blue-700 font-medium" data-tab="address" onclick="changeTab('address', this)">Address</button>
                <button type="button" class="org-tab-btn px-4 py-2 text-sm border-r border-gray-200 bg-white text-gray-600 hover:bg-gray-50" data-tab="branch" onclick="changeTab('branch', this)">Branch</button>
                <button type="button" class="org-tab-btn px-4 py-2 text-sm border-r border-gray-200 bg-white text-gray-600 hover:bg-gray-50" data-tab="office" onclick="changeTab('office', this)">Office</button>
                <button type="button" class="org-tab-btn px-4 py-2 text-sm border-r border-gray-200 bg-white text-gray-600 hover:bg-gray-50" data-tab="department" onclick="changeTab('department', this)">Department</button>
                <button type="button" class="org-tab-btn px-4 py-2 text-sm border-r border-gray-200 bg-white text-gray-600 hover:bg-gray-50" data-tab="division" onclick="changeTab('division', this)">Division</button>
                <button type="button" class="org-tab-btn px-4 py-2 text-sm border-r border-gray-200 bg-white text-gray-600 hover:bg-gray-50" data-tab="unit" onclick="changeTab('unit', this)">Unit</button>
                <button type="button" class="org-tab-btn px-4 py-2 text-sm bg-white text-gray-600 hover:bg-gray-50" data-tab="position" onclick="changeTab('position', this)">Position</button>
            </div>
        </div>

        {{-- TABLE VIEW --}}
        <div class="p-4 flex-grow overflow-hidden">
            <div class="border rounded-md h-full overflow-auto bg-white">
                <table class="w-full text-sm table-fixed border-collapse">
                    <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                        <tr id="tableHeadRow"></tr>
                    </thead>
                    <tbody id="tableBody" class="bg-white"></tbody>
                </table>
            </div>
        </div>

        {{-- ADD SLIDE OVER --}}
        <div id="addSection" class="hidden fixed inset-0 z-50">
            <div class="absolute inset-0 bg-black/40" onclick="closeAddSection()"></div>

            <div
                id="addPanel"
                class="absolute top-0 right-0 h-full w-full max-w-[760px] bg-white shadow-2xl flex flex-col overflow-hidden transform translate-x-full transition-transform duration-300 ease-in-out"
            >
                <div class="p-6 border-b flex items-center justify-between shrink-0">
                    <h2 id="formTitle" class="font-bold text-lg text-gray-900">Add Address</h2>
                    <button type="button" onclick="closeAddSection()" class="text-sm text-gray-500 hover:text-gray-700">Close</button>
                </div>

                <div class="px-6 pt-4 shrink-0">
                    <div id="formError" class="hidden rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></div>
                </div>

                <div class="flex-1 overflow-y-auto overflow-x-hidden px-6 py-6">
                    {{-- ADDRESS --}}
                    <div id="form-address" class="org-form-section w-full">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                            <div class="w-full">
                                <label class="block text-sm font-medium mb-1">Country</label>
                                <input id="address_country" class="w-full border rounded-md p-2 bg-gray-50" value="Philippines" readonly>
                            </div>

                            <div class="w-full">
                                <label class="block text-sm font-medium mb-1">Region</label>
                                <select id="address_region_code" class="w-full border rounded-md p-2" onchange="onRegionChange()"></select>
                            </div>

                            <div class="w-full">
                                <label id="provinceLabel" class="block text-sm font-medium mb-1">Province / District</label>
                                <select id="address_province_code" class="w-full border rounded-md p-2" onchange="onProvinceChange()"></select>
                            </div>

                            <div class="w-full">
                                <label class="block text-sm font-medium mb-1">City / Municipality</label>
                                <select id="address_city_code" class="w-full border rounded-md p-2" onchange="onCityChange()"></select>
                            </div>

                            <div class="w-full">
                                <label class="block text-sm font-medium mb-1">Barangay</label>
                                <select id="address_barangay_code" class="w-full border rounded-md p-2"></select>
                            </div>

                            <div class="w-full">
                                <label class="block text-sm font-medium mb-1">Postal Code</label>
                                <input id="address_postal_code" class="w-full border rounded-md p-2" placeholder="Postal code">
                            </div>

                            <div class="md:col-span-2 w-full">
                                <label class="block text-sm font-medium mb-1">Street Address</label>
                                <input id="address_street_address" class="w-full border rounded-md p-2" placeholder="House no., street, purok, etc.">
                            </div>

                            <div class="w-full">
                                <label class="block text-sm font-medium mb-1">Subdivision / Building</label>
                                <input id="address_subdivision_building" class="w-full border rounded-md p-2" placeholder="Subdivision / Building">
                            </div>

                            <div class="w-full">
                                <label class="block text-sm font-medium mb-1">Unit No.</label>
                                <input id="address_unit_no" class="w-full border rounded-md p-2" placeholder="Unit no.">
                            </div>
                        </div>
                    </div>

                    {{-- BRANCH --}}
                    <div id="form-branch" class="org-form-section hidden w-full space-y-4">
                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Branch Name</label>
                            <input id="branch_name" class="w-full border rounded-md p-2" placeholder="Branch name">
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Address</label>
                            <select id="branch_address_id" class="w-full border rounded-md p-2"></select>
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Branch Head</label>
                            <input id="branch_head" class="w-full border rounded-md p-2" placeholder="Branch head">
                        </div>
                    </div>

                    {{-- OFFICE --}}
                    <div id="form-office" class="org-form-section hidden w-full space-y-4">
                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Office Name</label>
                            <input id="office_name" class="w-full border rounded-md p-2" placeholder="Office of the President, Office of the Treasurer, etc.">
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Head of Office</label>
                            <input id="office_head" class="w-full border rounded-md p-2" placeholder="Head of office">
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Branch</label>
                            <select id="office_branch_id" class="w-full border rounded-md p-2" onchange="syncOfficeReadonlyFields()"></select>
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Address</label>
                            <textarea id="office_address_preview" rows="4" class="w-full border rounded-md p-2 bg-gray-50 resize-none" readonly></textarea>
                        </div>
                    </div>

                    {{-- DEPARTMENT --}}
                    <div id="form-department" class="org-form-section hidden w-full space-y-4">
                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Department Name</label>
                            <input id="department_name" class="w-full border rounded-md p-2" placeholder="Department name">
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Department Head</label>
                            <input id="department_head" class="w-full border rounded-md p-2" placeholder="Department head">
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Office</label>
                            <select id="department_office_id" class="w-full border rounded-md p-2" onchange="syncDepartmentReadonlyFields()"></select>
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Branch</label>
                            <input id="department_branch_preview" class="w-full border rounded-md p-2 bg-gray-50" readonly>
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Address</label>
                            <textarea id="department_address_preview" rows="4" class="w-full border rounded-md p-2 bg-gray-50 resize-none" readonly></textarea>
                        </div>
                    </div>

                    {{-- DIVISION --}}
                    <div id="form-division" class="org-form-section hidden w-full space-y-4">
                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Division Name</label>
                            <input id="division_name" class="w-full border rounded-md p-2" placeholder="Division name">
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Division Head</label>
                            <input id="division_head" class="w-full border rounded-md p-2" placeholder="Division head">
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Department</label>
                            <select id="division_department_id" class="w-full border rounded-md p-2" onchange="syncDivisionReadonlyFields()"></select>
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Office</label>
                            <input id="division_office_preview" class="w-full border rounded-md p-2 bg-gray-50" readonly>
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Branch</label>
                            <input id="division_branch_preview" class="w-full border rounded-md p-2 bg-gray-50" readonly>
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Address</label>
                            <textarea id="division_address_preview" rows="4" class="w-full border rounded-md p-2 bg-gray-50 resize-none" readonly></textarea>
                        </div>
                    </div>

                    {{-- UNIT --}}
                    <div id="form-unit" class="org-form-section hidden w-full space-y-4">
                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Unit Name</label>
                            <input id="unit_name" class="w-full border rounded-md p-2" placeholder="Unit name">
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Unit Head</label>
                            <input id="unit_head" class="w-full border rounded-md p-2" placeholder="Unit head">
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Division</label>
                            <select id="unit_division_id" class="w-full border rounded-md p-2" onchange="syncUnitReadonlyFields()"></select>
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Department</label>
                            <input id="unit_department_preview" class="w-full border rounded-md p-2 bg-gray-50" readonly>
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Office</label>
                            <input id="unit_office_preview" class="w-full border rounded-md p-2 bg-gray-50" readonly>
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Branch</label>
                            <input id="unit_branch_preview" class="w-full border rounded-md p-2 bg-gray-50" readonly>
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Address</label>
                            <textarea id="unit_address_preview" rows="4" class="w-full border rounded-md p-2 bg-gray-50 resize-none" readonly></textarea>
                        </div>
                    </div>

                    {{-- POSITION --}}
                    <div id="form-position" class="org-form-section hidden w-full space-y-4">
                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Position Name</label>
                            <input id="position_name" class="w-full border rounded-md p-2" placeholder="Position name">
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Unit</label>
                            <select id="position_unit_id" class="w-full border rounded-md p-2" onchange="syncPositionReadonlyFields()"></select>
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Division</label>
                            <input id="position_division_preview" class="w-full border rounded-md p-2 bg-gray-50" readonly>
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Department</label>
                            <input id="position_department_preview" class="w-full border rounded-md p-2 bg-gray-50" readonly>
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Office</label>
                            <input id="position_office_preview" class="w-full border rounded-md p-2 bg-gray-50" readonly>
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Branch</label>
                            <input id="position_branch_preview" class="w-full border rounded-md p-2 bg-gray-50" readonly>
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-1">Address</label>
                            <textarea id="position_address_preview" rows="4" class="w-full border rounded-md p-2 bg-gray-50 resize-none" readonly></textarea>
                        </div>
                    </div>
                </div>

                <div class="p-6 border-t flex gap-2 shrink-0 bg-white">
                    <button type="button" onclick="closeAddSection()" class="flex-1 border rounded py-2">Cancel</button>
                    <button
                        type="button"
                        id="saveButton"
                        onclick="saveOrganizationalEntry()"
                        class="flex-1 bg-blue-600 text-white rounded py-2 hover:bg-blue-700 transition"
                    >
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let activeTab = 'address';

let organizationalRows = {
    address: @json($addresses),
    branch: @json($branches),
    office: @json($offices),
    department: @json($departments),
    division: @json($divisions),
    unit: @json($units),
    position: @json($positions),
};

let addressOptions = @json($addressOptions);
let branchOptions = @json($branchOptions);
let officeOptions = @json($officeOptions);
let departmentOptions = @json($departmentOptions);
let divisionOptions = @json($divisionOptions);
let unitOptions = @json($unitOptions);

const storeUrl = @json(route('human-capital.organizational.store'));
const updateUrlTemplate = @json(route('human-capital.organizational.update', ['type' => '__TYPE__', 'id' => '__ID__']));
const deleteUrlTemplate = @json(route('human-capital.organizational.destroy', ['type' => '__TYPE__', 'id' => '__ID__']));
const csrfToken = @json(csrf_token());

const regionsUrl = @json(route('human-capital.organizational.locations.regions'));
const provincesOrDistrictsUrlTemplate = @json(route('human-capital.organizational.locations.provinces-or-districts', ['regionCode' => '__REGION__']));
const citiesUrlTemplate = @json(route('human-capital.organizational.locations.cities-municipalities', ['type' => '__TYPE__', 'code' => '__CODE__']));
const barangaysUrlTemplate = @json(route('human-capital.organizational.locations.barangays', ['cityCode' => '__CITY__']));

let currentProvinceType = 'province';
let isEditMode = false;
let editingRecordId = null;

const tableColumns = {
    address: [
        { key: 'region_name', label: 'Region', width: 'w-40' },
        { key: 'province_name', label: 'Province / District', width: 'w-40' },
        { key: 'city_name', label: 'City / Municipality', width: 'w-44' },
        { key: 'barangay_name', label: 'Barangay', width: 'w-40' },
        { key: 'full_address', label: 'Full Address', width: '' },
    ],
    branch: [
        { key: 'branch_name', label: 'Branch Name', width: 'w-48' },
        { key: 'address', label: 'Address', width: '' },
        { key: 'branch_head', label: 'Branch Head', width: 'w-48' },
    ],
    office: [
        { key: 'office_name', label: 'Office', width: 'w-52' },
        { key: 'branch_name', label: 'Branch', width: 'w-44' },
        { key: 'address', label: 'Address', width: '' },
        { key: 'office_head', label: 'Head of Office', width: 'w-44' },
    ],
    department: [
        { key: 'department_name', label: 'Department', width: 'w-44' },
        { key: 'office_name', label: 'Office', width: 'w-44' },
        { key: 'branch_name', label: 'Branch', width: 'w-44' },
        { key: 'address', label: 'Address', width: '' },
        { key: 'department_head', label: 'Department Head', width: 'w-44' },
    ],
    division: [
        { key: 'division_name', label: 'Division', width: 'w-44' },
        { key: 'department_name', label: 'Department', width: 'w-44' },
        { key: 'office_name', label: 'Office', width: 'w-44' },
        { key: 'branch_name', label: 'Branch', width: 'w-44' },
        { key: 'address', label: 'Address', width: '' },
        { key: 'division_head', label: 'Division Head', width: 'w-44' },
    ],
    unit: [
        { key: 'unit_name', label: 'Unit', width: 'w-40' },
        { key: 'division_name', label: 'Division', width: 'w-40' },
        { key: 'department_name', label: 'Department', width: 'w-40' },
        { key: 'office_name', label: 'Office', width: 'w-40' },
        { key: 'branch_name', label: 'Branch', width: 'w-40' },
        { key: 'address', label: 'Address', width: '' },
        { key: 'unit_head', label: 'Unit Head', width: 'w-40' },
    ],
    position: [
        { key: 'position_name', label: 'Position', width: 'w-40' },
        { key: 'unit_name', label: 'Unit', width: 'w-40' },
        { key: 'division_name', label: 'Division', width: 'w-40' },
        { key: 'department_name', label: 'Department', width: 'w-40' },
        { key: 'office_name', label: 'Office', width: 'w-40' },
        { key: 'branch_name', label: 'Branch', width: 'w-40' },
        { key: 'address', label: 'Address', width: '' },
    ],
};

const formTitles = {
    address: 'Add Address',
    branch: 'Add Branch',
    office: 'Add Office',
    department: 'Add Department',
    division: 'Add Division',
    unit: 'Add Unit',
    position: 'Add Position',
};

const editFormTitles = {
    address: 'Edit Address',
    branch: 'Edit Branch',
    office: 'Edit Office',
    department: 'Edit Department',
    division: 'Edit Division',
    unit: 'Edit Unit',
    position: 'Edit Position',
};

function openAddSection() {
    isEditMode = false;
    editingRecordId = null;
    resetFormDefaults();
    populateAllSelects();
    document.getElementById('formTitle').textContent = isEditMode ? (editFormTitles[activeTab] || 'Edit Entry') : (formTitles[activeTab] || 'Add Entry');
    document.getElementById('saveButton').textContent = 'Save';
    showFormSection(activeTab);
    showPanel();

    if (activeTab === 'address') {
        loadRegions();
    }

    syncOfficeReadonlyFields();
    syncDepartmentReadonlyFields();
    syncDivisionReadonlyFields();
    syncUnitReadonlyFields();
    syncPositionReadonlyFields();
}

function showPanel() {
    const addSection = document.getElementById('addSection');
    const addPanel = document.getElementById('addPanel');

    addSection.classList.remove('hidden');

    requestAnimationFrame(() => {
        addPanel.classList.remove('translate-x-full');
    });
}

function closeAddSection() {
    resetFormDefaults();
    isEditMode = false;
    editingRecordId = null;
    document.getElementById('saveButton').textContent = 'Save';

    const addSection = document.getElementById('addSection');
    const addPanel = document.getElementById('addPanel');

    addPanel.classList.add('translate-x-full');

    setTimeout(() => {
        addSection.classList.add('hidden');
    }, 300);
}

function changeTab(tab, button) {
    activeTab = tab;
    updateTabStyles(button);
    drawTableRows();
    document.getElementById('formTitle').textContent = isEditMode ? (editFormTitles[activeTab] || 'Edit Entry') : (formTitles[activeTab] || 'Add Entry');
    showFormSection(activeTab);
    hideFormError();
}

function updateTabStyles(activeButton) {
    document.querySelectorAll('.org-tab-btn').forEach(btn => {
        btn.classList.remove('bg-blue-50', 'text-blue-700', 'font-medium');
        btn.classList.add('bg-white', 'text-gray-600');
    });

    if (activeButton) {
        activeButton.classList.remove('bg-white', 'text-gray-600');
        activeButton.classList.add('bg-blue-50', 'text-blue-700', 'font-medium');
    } else {
        const btn = document.querySelector(`.org-tab-btn[data-tab="${activeTab}"]`);
        if (btn) {
            btn.classList.remove('bg-white', 'text-gray-600');
            btn.classList.add('bg-blue-50', 'text-blue-700', 'font-medium');
        }
    }
}

function showFormSection(tab) {
    document.querySelectorAll('.org-form-section').forEach(section => {
        section.classList.add('hidden');
    });

    const activeSection = document.getElementById(`form-${tab}`);
    if (activeSection) {
        activeSection.classList.remove('hidden');
    }
}

function drawTableHead() {
    const head = document.getElementById('tableHeadRow');
    const columns = tableColumns[activeTab] || [];

    head.innerHTML = columns.map(column => {
        return `<th class="p-3 text-left font-medium ${column.width || ''}">${column.label}</th>`;
    }).join('') + `<th class="p-3 text-left font-medium w-36">Actions</th>`;
}

function drawTableRows() {
    drawTableHead();

    const tableBody = document.getElementById('tableBody');
    const rows = organizationalRows[activeTab] || [];
    const columns = tableColumns[activeTab] || [];

    tableBody.innerHTML = '';

    if (!rows.length) {
        tableBody.innerHTML = `<tr class="border-t"><td colspan="${columns.length + 1}" class="p-10 text-center text-gray-400 italic">No data found</td></tr>`;
        return;
    }

    rows.forEach(row => {
        const tr = document.createElement('tr');
        tr.className = 'border-t hover:bg-gray-50';

        tr.innerHTML = columns.map(column => {
            return `<td class="p-3 text-gray-900 align-top break-words">${escapeHtml(row[column.key] ?? '')}</td>`;
        }).join('') + `
            <td class="p-3 align-top">
                <div class="flex gap-2">
                    <button type="button"
                            onclick='openEditSection(${JSON.stringify(activeTab)}, ${JSON.stringify(row).replaceAll("'", "&#039;")})'
                            class="px-2.5 py-1 rounded bg-amber-50 text-amber-700 text-xs font-medium hover:bg-amber-100">
                        Edit
                    </button>
                    <button type="button"
                            onclick="deleteOrganizationalEntry('${activeTab}', ${row.id})"
                            class="px-2.5 py-1 rounded bg-red-50 text-red-700 text-xs font-medium hover:bg-red-100">
                        Delete
                    </button>
                </div>
            </td>
        `;

        tableBody.appendChild(tr);
    });
}


async function openEditSection(tab, row) {
    activeTab = tab;
    isEditMode = true;
    editingRecordId = row.id;
    hideFormError();
    populateAllSelects();
    updateTabStyles();
    showFormSection(activeTab);
    document.getElementById('formTitle').textContent = editFormTitles[activeTab] || 'Edit Entry';
    document.getElementById('saveButton').textContent = 'Update';
    showPanel();

    await fillFormForEdit(tab, row);
}

async function fillFormForEdit(tab, row) {
    resetFormDefaults();
    populateAllSelects();

    if (tab === 'address') {
        document.getElementById('address_country').value = row.country || 'Philippines';
        document.getElementById('address_street_address').value = row.street_address || '';
        document.getElementById('address_subdivision_building').value = row.subdivision_building || '';
        document.getElementById('address_unit_no').value = row.unit_no || '';
        document.getElementById('address_postal_code').value = row.postal_code || '';

        await loadRegions();
        document.getElementById('address_region_code').value = row.region_code || '';
        await onRegionChange();
        document.getElementById('address_province_code').value = row.province_code || '';
        await onProvinceChange();
        document.getElementById('address_city_code').value = row.city_code || '';
        await onCityChange();
        document.getElementById('address_barangay_code').value = row.barangay_code || '';
        return;
    }

    if (tab === 'branch') {
        document.getElementById('branch_name').value = row.branch_name || '';
        document.getElementById('branch_address_id').value = row.address_id || '';
        document.getElementById('branch_head').value = row.branch_head || '';
        return;
    }

    if (tab === 'office') {
        document.getElementById('office_name').value = row.office_name || '';
        document.getElementById('office_head').value = row.office_head || '';
        document.getElementById('office_branch_id').value = row.branch_id || '';
        syncOfficeReadonlyFields();
        return;
    }

    if (tab === 'department') {
        document.getElementById('department_name').value = row.department_name || '';
        document.getElementById('department_head').value = row.department_head || '';
        document.getElementById('department_office_id').value = row.office_id || '';
        syncDepartmentReadonlyFields();
        return;
    }

    if (tab === 'division') {
        document.getElementById('division_name').value = row.division_name || '';
        document.getElementById('division_head').value = row.division_head || '';
        document.getElementById('division_department_id').value = row.department_id || '';
        syncDivisionReadonlyFields();
        return;
    }

    if (tab === 'unit') {
        document.getElementById('unit_name').value = row.unit_name || '';
        document.getElementById('unit_head').value = row.unit_head || '';
        document.getElementById('unit_division_id').value = row.division_id || '';
        syncUnitReadonlyFields();
        return;
    }

    if (tab === 'position') {
        document.getElementById('position_name').value = row.position_name || '';
        document.getElementById('position_unit_id').value = row.unit_id || '';
        syncPositionReadonlyFields();
    }
}

function populateSelect(selectId, items, placeholder, labelKey) {
    const select = document.getElementById(selectId);
    if (!select) return;

    let html = `<option value="">${placeholder}</option>`;

    items.forEach(item => {
        html += `<option value="${item.id}">${escapeHtml(item[labelKey] ?? '')}</option>`;
    });

    select.innerHTML = html;
}

function populateAllSelects() {
    populateSelect('branch_address_id', addressOptions, 'Select address', 'full_address');
    populateSelect('office_branch_id', branchOptions, 'Select branch', 'branch_name');
    populateSelect('department_office_id', officeOptions, 'Select office', 'office_name');
    populateSelect('division_department_id', departmentOptions, 'Select department', 'department_name');
    populateSelect('unit_division_id', divisionOptions, 'Select division', 'division_name');
    populateSelect('position_unit_id', unitOptions, 'Select unit', 'unit_name');
}

function resetFormDefaults() {
    hideFormError();

    const fields = [
        'address_country',
        'address_region_code',
        'address_province_code',
        'address_city_code',
        'address_barangay_code',
        'address_street_address',
        'address_subdivision_building',
        'address_unit_no',
        'address_postal_code',

        'branch_name',
        'branch_address_id',
        'branch_head',

        'office_name',
        'office_head',
        'office_branch_id',
        'office_address_preview',

        'department_name',
        'department_head',
        'department_office_id',
        'department_branch_preview',
        'department_address_preview',

        'division_name',
        'division_head',
        'division_department_id',
        'division_office_preview',
        'division_branch_preview',
        'division_address_preview',

        'unit_name',
        'unit_head',
        'unit_division_id',
        'unit_department_preview',
        'unit_office_preview',
        'unit_branch_preview',
        'unit_address_preview',

        'position_name',
        'position_unit_id',
        'position_division_preview',
        'position_department_preview',
        'position_office_preview',
        'position_branch_preview',
        'position_address_preview',
    ];

    fields.forEach(id => {
        const element = document.getElementById(id);
        if (!element) return;

        if (id === 'address_country') {
            element.value = 'Philippines';
            return;
        }

        element.value = '';
    });

    document.getElementById('provinceLabel').textContent = 'Province / District';
    currentProvinceType = 'province';
    clearSelect('address_region_code', 'Select region');
    clearSelect('address_province_code', 'Select province / district');
    clearSelect('address_city_code', 'Select city / municipality');
    clearSelect('address_barangay_code', 'Select barangay');
}

function clearSelect(selectId, placeholder) {
    const select = document.getElementById(selectId);
    if (!select) return;
    select.innerHTML = `<option value="">${placeholder}</option>`;
}

function showFormError(message) {
    const errorBox = document.getElementById('formError');
    errorBox.textContent = message;
    errorBox.classList.remove('hidden');
}

function hideFormError() {
    const errorBox = document.getElementById('formError');
    errorBox.textContent = '';
    errorBox.classList.add('hidden');
}

async function loadRegions() {
    clearSelect('address_region_code', 'Loading regions...');
    clearSelect('address_province_code', 'Select province / district');
    clearSelect('address_city_code', 'Select city / municipality');
    clearSelect('address_barangay_code', 'Select barangay');

    const response = await fetch(regionsUrl, {
        headers: { 'Accept': 'application/json' }
    });

    const data = await response.json();

    let html = `<option value="">Select region</option>`;
    data.forEach(item => {
        html += `<option value="${item.code}">${escapeHtml(item.name)}</option>`;
    });

    document.getElementById('address_region_code').innerHTML = html;
}

async function onRegionChange() {
    const regionCode = document.getElementById('address_region_code').value;

    clearSelect('address_city_code', 'Select city / municipality');
    clearSelect('address_barangay_code', 'Select barangay');

    if (!regionCode) {
        document.getElementById('provinceLabel').textContent = 'Province / District';
        clearSelect('address_province_code', 'Select province / district');
        return;
    }

    const url = provincesOrDistrictsUrlTemplate.replace('__REGION__', encodeURIComponent(regionCode));
    const response = await fetch(url, {
        headers: { 'Accept': 'application/json' }
    });
    const data = await response.json();

    currentProvinceType = data.type || 'province';
    document.getElementById('provinceLabel').textContent = data.label === 'District' ? 'District' : 'Province';

    let html = `<option value="">Select ${data.label === 'District' ? 'district' : 'province'}</option>`;
    (data.items || []).forEach(item => {
        html += `<option value="${item.code}" data-type="${item.type}">${escapeHtml(item.name)}</option>`;
    });

    document.getElementById('address_province_code').innerHTML = html;
}

async function onProvinceChange() {
    const provinceSelect = document.getElementById('address_province_code');
    const code = provinceSelect.value;

    clearSelect('address_barangay_code', 'Select barangay');

    if (!code) {
        clearSelect('address_city_code', 'Select city / municipality');
        return;
    }

    const selectedOption = provinceSelect.options[provinceSelect.selectedIndex];
    const type = selectedOption?.dataset?.type || currentProvinceType || 'province';

    const url = citiesUrlTemplate
        .replace('__TYPE__', encodeURIComponent(type))
        .replace('__CODE__', encodeURIComponent(code));

    const response = await fetch(url, {
        headers: { 'Accept': 'application/json' }
    });
    const data = await response.json();

    let html = `<option value="">Select city / municipality</option>`;
    data.forEach(item => {
        html += `<option value="${item.code}">${escapeHtml(item.name)}</option>`;
    });

    document.getElementById('address_city_code').innerHTML = html;
}

async function onCityChange() {
    const cityCode = document.getElementById('address_city_code').value;

    if (!cityCode) {
        clearSelect('address_barangay_code', 'Select barangay');
        return;
    }

    const url = barangaysUrlTemplate.replace('__CITY__', encodeURIComponent(cityCode));
    const response = await fetch(url, {
        headers: { 'Accept': 'application/json' }
    });
    const data = await response.json();

    let html = `<option value="">Select barangay</option>`;
    data.forEach(item => {
        html += `<option value="${item.code}">${escapeHtml(item.name)}</option>`;
    });

    document.getElementById('address_barangay_code').innerHTML = html;
}

function getSelectedText(selectId) {
    const select = document.getElementById(selectId);
    if (!select || !select.value) return '';
    return select.options[select.selectedIndex]?.text || '';
}

function getBranchById(id) {
    return (organizationalRows.branch || []).find(item => String(item.id) === String(id)) || null;
}

function getOfficeById(id) {
    return (organizationalRows.office || []).find(item => String(item.id) === String(id)) || null;
}

function getDepartmentById(id) {
    return (organizationalRows.department || []).find(item => String(item.id) === String(id)) || null;
}

function getDivisionById(id) {
    return (organizationalRows.division || []).find(item => String(item.id) === String(id)) || null;
}

function getUnitById(id) {
    return (organizationalRows.unit || []).find(item => String(item.id) === String(id)) || null;
}

function syncOfficeReadonlyFields() {
    const branchId = document.getElementById('office_branch_id')?.value;
    const branch = getBranchById(branchId);
    document.getElementById('office_address_preview').value = branch?.address || '';
}

function syncDepartmentReadonlyFields() {
    const officeId = document.getElementById('department_office_id')?.value;
    const office = getOfficeById(officeId);
    document.getElementById('department_branch_preview').value = office?.branch_name || '';
    document.getElementById('department_address_preview').value = office?.address || '';
}

function syncDivisionReadonlyFields() {
    const departmentId = document.getElementById('division_department_id')?.value;
    const department = getDepartmentById(departmentId);
    document.getElementById('division_office_preview').value = department?.office_name || '';
    document.getElementById('division_branch_preview').value = department?.branch_name || '';
    document.getElementById('division_address_preview').value = department?.address || '';
}

function syncUnitReadonlyFields() {
    const divisionId = document.getElementById('unit_division_id')?.value;
    const division = getDivisionById(divisionId);
    document.getElementById('unit_department_preview').value = division?.department_name || '';
    document.getElementById('unit_office_preview').value = division?.office_name || '';
    document.getElementById('unit_branch_preview').value = division?.branch_name || '';
    document.getElementById('unit_address_preview').value = division?.address || '';
}

function syncPositionReadonlyFields() {
    const unitId = document.getElementById('position_unit_id')?.value;
    const unit = getUnitById(unitId);
    document.getElementById('position_division_preview').value = unit?.division_name || '';
    document.getElementById('position_department_preview').value = unit?.department_name || '';
    document.getElementById('position_office_preview').value = unit?.office_name || '';
    document.getElementById('position_branch_preview').value = unit?.branch_name || '';
    document.getElementById('position_address_preview').value = unit?.address || '';
}

function getPayload() {
    switch (activeTab) {
        case 'address': {
            const provinceSelect = document.getElementById('address_province_code');
            const selectedProvinceOption = provinceSelect.options[provinceSelect.selectedIndex];

            return {
                type: 'address',
                country: document.getElementById('address_country').value.trim(),
                region_code: document.getElementById('address_region_code').value,
                region_name: getSelectedText('address_region_code'),
                province_code: document.getElementById('address_province_code').value || null,
                province_name: getSelectedText('address_province_code') || null,
                province_type: selectedProvinceOption?.dataset?.type || currentProvinceType || null,
                city_code: document.getElementById('address_city_code').value,
                city_name: getSelectedText('address_city_code'),
                barangay_code: document.getElementById('address_barangay_code').value,
                barangay_name: getSelectedText('address_barangay_code'),
                street_address: document.getElementById('address_street_address').value.trim(),
                subdivision_building: document.getElementById('address_subdivision_building').value.trim(),
                unit_no: document.getElementById('address_unit_no').value.trim(),
                postal_code: document.getElementById('address_postal_code').value.trim(),
            };
        }

        case 'branch':
            return {
                type: 'branch',
                branch_name: document.getElementById('branch_name').value.trim(),
                address_id: document.getElementById('branch_address_id').value,
                branch_head: document.getElementById('branch_head').value.trim(),
            };

        case 'office':
            return {
                type: 'office',
                office_name: document.getElementById('office_name').value.trim(),
                branch_id: document.getElementById('office_branch_id').value,
                office_head: document.getElementById('office_head').value.trim(),
            };

        case 'department':
            return {
                type: 'department',
                department_name: document.getElementById('department_name').value.trim(),
                office_id: document.getElementById('department_office_id').value,
                department_head: document.getElementById('department_head').value.trim(),
            };

        case 'division':
            return {
                type: 'division',
                division_name: document.getElementById('division_name').value.trim(),
                department_id: document.getElementById('division_department_id').value,
                division_head: document.getElementById('division_head').value.trim(),
            };

        case 'unit':
            return {
                type: 'unit',
                unit_name: document.getElementById('unit_name').value.trim(),
                division_id: document.getElementById('unit_division_id').value,
                unit_head: document.getElementById('unit_head').value.trim(),
            };

        case 'position':
            return {
                type: 'position',
                position_name: document.getElementById('position_name').value.trim(),
                unit_id: document.getElementById('position_unit_id').value,
            };

        default:
            return {};
    }
}

function refreshOptions(recordType, record) {
    if (recordType === 'address') {
        addressOptions.unshift({
            id: record.id,
            full_address: record.full_address,
        });
    }

    if (recordType === 'branch') {
        branchOptions.unshift({
            id: record.id,
            branch_name: record.branch_name,
            address_id: record.address_id,
        });
    }

    if (recordType === 'office') {
        officeOptions.unshift({
            id: record.id,
            office_name: record.office_name,
            branch_id: record.branch_id,
            address_id: record.address_id,
        });
    }

    if (recordType === 'department') {
        departmentOptions.unshift({
            id: record.id,
            department_name: record.department_name,
            office_id: record.office_id,
            address_id: record.address_id,
        });
    }

    if (recordType === 'division') {
        divisionOptions.unshift({
            id: record.id,
            division_name: record.division_name,
            department_id: record.department_id,
            address_id: record.address_id,
        });
    }

    if (recordType === 'unit') {
        unitOptions.unshift({
            id: record.id,
            unit_name: record.unit_name,
            division_id: record.division_id,
            address_id: record.address_id,
        });
    }
}


function upsertOption(recordType, record) {
    const optionMaps = {
        address: { list: addressOptions, labelKey: 'full_address' },
        branch: { list: branchOptions, labelKey: 'branch_name' },
        office: { list: officeOptions, labelKey: 'office_name' },
        department: { list: departmentOptions, labelKey: 'department_name' },
        division: { list: divisionOptions, labelKey: 'division_name' },
        unit: { list: unitOptions, labelKey: 'unit_name' },
    };

    const config = optionMaps[recordType];
    if (!config) return;

    const index = config.list.findIndex(item => String(item.id) === String(record.id));
    const option = { id: record.id };
    option[config.labelKey] = record[config.labelKey];

    if (record.address_id !== undefined) option.address_id = record.address_id;
    if (record.branch_id !== undefined) option.branch_id = record.branch_id;
    if (record.office_id !== undefined) option.office_id = record.office_id;
    if (record.department_id !== undefined) option.department_id = record.department_id;
    if (record.division_id !== undefined) option.division_id = record.division_id;

    if (index >= 0) {
        config.list[index] = option;
    } else {
        config.list.unshift(option);
    }
}

function removeOption(recordType, id) {
    const optionLists = {
        address: addressOptions,
        branch: branchOptions,
        office: officeOptions,
        department: departmentOptions,
        division: divisionOptions,
        unit: unitOptions,
    };

    const list = optionLists[recordType];
    if (!list) return;

    const index = list.findIndex(item => String(item.id) === String(id));
    if (index >= 0) {
        list.splice(index, 1);
    }
}

function replaceRow(recordType, record) {
    const rows = organizationalRows[recordType] || [];
    const index = rows.findIndex(item => String(item.id) === String(record.id));

    if (index >= 0) {
        rows[index] = record;
    } else {
        rows.unshift(record);
    }
}

function removeRow(recordType, id) {
    const rows = organizationalRows[recordType] || [];
    const index = rows.findIndex(item => String(item.id) === String(id));

    if (index >= 0) {
        rows.splice(index, 1);
    }
}

async function saveOrganizationalEntry() {
    hideFormError();

    const saveButton = document.getElementById('saveButton');
    const originalText = saveButton.textContent;

    saveButton.disabled = true;
    saveButton.textContent = isEditMode ? 'Updating...' : 'Saving...';

    try {
        const url = isEditMode
            ? updateUrlTemplate
                .replace('__TYPE__', encodeURIComponent(activeTab))
                .replace('__ID__', encodeURIComponent(editingRecordId))
            : storeUrl;

        const response = await fetch(url, {
            method: isEditMode ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(getPayload()),
        });

        const contentType = response.headers.get('content-type') || '';
        let data = {};

        if (contentType.includes('application/json')) {
            data = await response.json();
        } else {
            throw new Error('Server did not return JSON.');
        }

        if (!response.ok) {
            if (data.errors) {
                const firstErrorGroup = Object.values(data.errors)[0];
                showFormError(Array.isArray(firstErrorGroup) ? firstErrorGroup[0] : 'Validation error.');
            } else {
                showFormError(data.message || 'Unable to save record.');
            }
            return false;
        }

        if (isEditMode) {
            replaceRow(activeTab, data.record);
            upsertOption(activeTab, data.record);
        } else {
            organizationalRows[activeTab].unshift(data.record);
            refreshOptions(activeTab, data.record);
        }

        populateAllSelects();
        drawTableRows();
        closeAddSection();
        return true;
    } catch (error) {
        console.error(error);
        showFormError(isEditMode ? 'Something went wrong while updating.' : 'Something went wrong while saving.');
        return false;
    } finally {
        saveButton.disabled = false;
        saveButton.textContent = isEditMode ? 'Update' : originalText;
    }
}

async function deleteOrganizationalEntry(type, id) {
    if (!confirm('Delete this record? This cannot be undone.')) {
        return;
    }

    try {
        const url = deleteUrlTemplate
            .replace('__TYPE__', encodeURIComponent(type))
            .replace('__ID__', encodeURIComponent(id));

        const response = await fetch(url, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            alert(data.message || 'Unable to delete record.');
            return;
        }

        removeRow(type, id);
        removeOption(type, id);
        populateAllSelects();
        drawTableRows();
    } catch (error) {
        console.error(error);
        alert('Something went wrong while deleting.');
    }
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

populateAllSelects();
updateTabStyles();
showFormSection(activeTab);
drawTableRows();
</script>
@endsection