@extends('layouts.app')

@section('content')
<div
    x-data="employeePage({
        employees: @js($employees),
        officeOptions: @js($officeOptions),
        branchOptions: @js($branchOptions),
        departmentOptions: @js($departmentOptions),
        divisionOptions: @js($divisionOptions),
        unitOptions: @js($unitOptions),
        storeUrl: '{{ route('human-capital.employee-profile.store') }}',
        updateBaseUrl: '{{ url('/human-capital/employee-profile') }}'
    })"
    class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col"
>
    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0 overflow-hidden">

        <div class="px-5 py-4 border-b shrink-0">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold text-blue-600 uppercase tracking-wider">Human Capital</p>
                    <h1 class="text-xl font-bold text-gray-900">Employee Profile</h1>
                    <p class="text-xs text-gray-500 mt-1">
                        Employee profiles are created after completed onboarding and employee registration. Use On Boarding for new applicants and existing personnel.
                    </p>
                </div>

                <a
                    href="{{ url('/human-capital/onboarding') }}"
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm shrink-0 hover:bg-blue-700 transition font-semibold"
                >
                    + Create from Onboarding
                </a>
            </div>

            @if (session('success'))
                <div class="mt-4 px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm font-semibold">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-4 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                    <p class="font-bold mb-1">Please fix the following:</p>
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="px-5 py-4 border-b bg-gray-50 shrink-0">
            <div class="grid grid-cols-4 gap-4">
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Personnel</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1" x-text="employees.length"></p>
                    <p class="text-xs text-gray-500 mt-1">Official employee records</p>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Monthly Paid</p>
                    <p class="text-2xl font-bold text-blue-700 mt-1" x-text="monthlyPaidCount"></p>
                    <p class="text-xs text-gray-500 mt-1">Employees on monthly payroll</p>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Daily Paid</p>
                    <p class="text-2xl font-bold text-indigo-700 mt-1" x-text="dailyPaidCount"></p>
                    <p class="text-xs text-gray-500 mt-1">Employees on daily payroll</p>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Departments</p>
                    <p class="text-2xl font-bold text-green-700 mt-1" x-text="uniqueDepartments.length"></p>
                    <p class="text-xs text-gray-500 mt-1">Departments with personnel records</p>
                </div>
            </div>
        </div>

        <div class="px-5 py-4 border-b shrink-0">
            <div class="grid grid-cols-5 gap-3">
                <div class="col-span-2">
                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Search</label>
                    <input
                        type="text"
                        x-model="search"
                        placeholder="Search employee name, ID, email, position..."
                        class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none"
                    >
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Department</label>
                    <select
                        x-model="filterDepartment"
                        class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none"
                    >
                        <option value="">All Departments</option>
                        <template x-for="department in uniqueDepartments" :key="department">
                            <option :value="department" x-text="department"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Branch</label>
                    <select
                        x-model="filterBranch"
                        class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none"
                    >
                        <option value="">All Branches</option>
                        <template x-for="branch in uniqueBranches" :key="branch">
                            <option :value="branch" x-text="branch"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Payroll Type</label>
                    <select
                        x-model="filterPayroll"
                        class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none"
                    >
                        <option value="">All Types</option>
                        <option value="Monthly Paid">Monthly Paid</option>
                        <option value="Daily Paid">Daily Paid</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex-1 min-h-0 overflow-hidden p-5">
            <div class="h-full border border-gray-200 rounded-xl overflow-auto bg-white">
                <table class="w-full text-sm min-w-[1200px]">
                    <thead class="bg-gray-50 border-b border-gray-200 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Employee ID</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Full Name</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Work Email</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Account</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Phone</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Position</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Department</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Office</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Branch</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Schedule</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Payroll Type</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Basic Salary</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template x-if="filteredEmployees.length === 0">
                            <tr>
                                <td colspan="13" class="px-4 py-10 text-center text-gray-400">
                                    No employees found.
                                </td>
                            </tr>
                        </template>

                        <template x-for="employee in filteredEmployees" :key="employee.id">
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap font-semibold text-blue-700" x-text="employee.employee_code ?? '-'"></td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <template x-if="employee.profile_photo_url">
                                            <img :src="employee.profile_photo_url" class="h-10 w-10 rounded-full object-cover border border-gray-200" alt="Employee photo">
                                        </template>

                                        <template x-if="!employee.profile_photo_url">
                                            <div class="h-10 w-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold" x-text="initials(employee)"></div>
                                        </template>

                                        <div>
                                            <div class="font-semibold text-gray-900" x-text="employee.full_name ?? '-'"></div>
                                            <div class="text-[11px] text-gray-400" x-text="employee.position ?? '-'"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-700" x-text="employee.work_email || employee.email || '-'"></td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <template x-if="employee.has_user_account">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700">
                                            Has Account
                                        </span>
                                    </template>

                                    <template x-if="!employee.has_user_account">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                            No Account
                                        </span>
                                    </template>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-700" x-text="employee.phone_number ?? '-'"></td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-700" x-text="employee.position ?? '-'"></td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-700" x-text="employee.department_name ?? '-'"></td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-700" x-text="employee.office_name ?? '-'"></td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-700" x-text="employee.branch_name ?? '-'"></td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-700" x-text="scheduleLabel(employee)"></td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700" x-text="employee.payroll_type ?? '-'"></span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-700" x-text="formatMoney(employee.basic_salary)"></td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="openView(employee)"
                                        class="text-indigo-600 hover:text-indigo-800 text-sm font-semibold mr-3"
                                    >
                                        View
                                    </button>
                                    <button
                                        type="button"
                                        @click="openEdit(employee)"
                                        class="text-blue-600 hover:text-blue-800 text-sm font-semibold"
                                    >
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- VIEW EMPLOYEE PROFILE DRAWER --}}
    <div
        x-show="showDetails"
        x-transition.opacity
        class="fixed inset-0 z-50"
        style="display: none;"
    >
        <div class="absolute inset-0 bg-black/40" @click="closeDetails()"></div>

        <div
            x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="absolute right-0 top-0 h-full w-full max-w-[900px] bg-white shadow-xl flex flex-col"
        >
            <template x-if="selectedEmployee">
                <div class="h-full flex flex-col">
                    <div class="px-6 py-5 bg-indigo-700 text-white">
                        <div class="flex items-center gap-5">
                            <template x-if="selectedEmployee.profile_photo_url">
                                <img :src="selectedEmployee.profile_photo_url" class="h-24 w-24 rounded-2xl object-cover border-4 border-white/30 shadow" alt="Employee photo">
                            </template>

                            <template x-if="!selectedEmployee.profile_photo_url">
                                <div class="h-24 w-24 rounded-2xl bg-white/20 text-white border-4 border-white/30 flex items-center justify-center text-3xl font-bold" x-text="initials(selectedEmployee)"></div>
                            </template>

                            <div class="flex-1">
                                <p class="text-xs font-bold text-indigo-100 uppercase tracking-widest">Employee Profile Details</p>
                                <h2 class="text-2xl font-bold mt-1" x-text="selectedEmployee.full_name ?? '-'"></h2>
                                <p class="text-sm text-indigo-100 mt-1">
                                    <span x-text="selectedEmployee.employee_code ?? '-'"></span>
                                    <span class="mx-2">•</span>
                                    <span x-text="selectedEmployee.position ?? '-'"></span>
                                </p>
                                <p class="text-sm text-indigo-100 mt-1" x-text="selectedEmployee.company_email || selectedEmployee.work_email || selectedEmployee.email || '-'"></p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-white/15 border border-white/20" x-text="selectedEmployee.department_name || 'No Department'"></span>
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-white text-indigo-700" x-text="selectedEmployee.employment_status || 'Active'"></span>
                                </div>
                            </div>

                            <button type="button" @click="closeDetails()" class="text-indigo-100 hover:text-white text-2xl leading-none">&times;</button>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-b bg-white">
                        <div class="grid grid-cols-4 lg:grid-cols-7 gap-2">
                            <button type="button" @click="profileTab = 'overview'" :class="tabClass('overview')">Overview</button>
                            <button type="button" @click="profileTab = 'personal'" :class="tabClass('personal')">Personal</button>
                            <button type="button" @click="profileTab = 'organization'" :class="tabClass('organization')">Organization</button>
                            <button type="button" @click="profileTab = 'payroll'" :class="tabClass('payroll')">Payroll</button>
                            <button type="button" @click="profileTab = 'government'" :class="tabClass('government')">Government</button>
                            <button type="button" @click="profileTab = 'history'" :class="tabClass('history')">History</button>
                            <button type="button" @click="profileTab = 'documents'" :class="tabClass('documents')">Documents</button>
                            <button type="button" @click="profileTab = 'access'" :class="tabClass('access')">Access</button>
                            <button type="button" @click="profileTab = 'digital-id'" :class="tabClass('digital-id')">Digital ID</button>
                            <button type="button" @click="profileTab = 'audit'" :class="tabClass('audit')">Audit</button>
                            <button type="button" @click="profileTab = 'records'" :class="tabClass('records')">Related Records</button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-auto p-6 bg-gray-50">
                        <div x-show="profileTab === 'overview'" class="space-y-5">
                            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                                <div class="flex items-start gap-4">
                                    <template x-if="selectedEmployee.profile_photo_url">
                                        <img :src="selectedEmployee.profile_photo_url" class="h-16 w-16 rounded-2xl object-cover border border-gray-200" alt="Employee photo">
                                    </template>

                                    <template x-if="!selectedEmployee.profile_photo_url">
                                        <div class="h-16 w-16 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-2xl font-bold" x-text="initials(selectedEmployee)"></div>
                                    </template>

                                    <div class="flex-1">
                                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Personnel Profile</p>
                                        <h3 class="text-xl font-bold text-gray-900 mt-1" x-text="selectedEmployee.full_name ?? '-'"></h3>
                                        <p class="text-sm text-gray-500 mt-1" x-text="selectedEmployee.email ?? '-'"></p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-50 text-green-700">Active Record</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-4 gap-4">
                                <div class="profile-card"><p class="profile-label">Department</p><p class="profile-value" x-text="selectedEmployee.department_name ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Branch</p><p class="profile-value" x-text="selectedEmployee.branch_name ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Office</p><p class="profile-value" x-text="selectedEmployee.office_name ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Employment Status</p><p class="profile-value" x-text="selectedEmployee.employment_status ?? 'Active'"></p></div>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div class="profile-card"><p class="profile-label">Basic Salary</p><p class="profile-value text-green-700" x-text="formatMoney(selectedEmployee.basic_salary)"></p></div>
                                <div class="profile-card"><p class="profile-label">Hourly Rate</p><p class="profile-value" x-text="formatMoney(selectedEmployee.hourly_rate)"></p></div>
                                <div class="profile-card"><p class="profile-label">Work Schedule</p><p class="profile-value" x-text="scheduleLabel(selectedEmployee)"></p></div>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="profile-card"><p class="profile-label">Date Hired</p><p class="profile-value" x-text="selectedEmployee.date_hired || selectedEmployee.start_date || '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Immediate Supervisor</p><p class="profile-value" x-text="selectedEmployee.immediate_supervisor || '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Reporting To</p><p class="profile-value" x-text="selectedEmployee.reporting_to || '-'"></p></div>
                            </div>
                        </div>

                        <div x-show="profileTab === 'personal'" class="space-y-5">
                            <h3 class="section-heading">Personal Information</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="profile-card"><p class="profile-label">Employee ID</p><p class="profile-value" x-text="selectedEmployee.employee_code ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Full Name</p><p class="profile-value" x-text="selectedEmployee.full_name ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">First Name</p><p class="profile-value" x-text="selectedEmployee.first_name ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Middle Name</p><p class="profile-value" x-text="selectedEmployee.middle_name ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Last Name</p><p class="profile-value" x-text="selectedEmployee.last_name ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Suffix</p><p class="profile-value" x-text="selectedEmployee.suffix ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Nickname</p><p class="profile-value" x-text="selectedEmployee.nickname ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Gender</p><p class="profile-value" x-text="selectedEmployee.gender ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Civil Status</p><p class="profile-value" x-text="selectedEmployee.civil_status ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Date of Birth</p><p class="profile-value" x-text="selectedEmployee.date_of_birth ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Age</p><p class="profile-value" x-text="selectedEmployee.age ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Blood Type</p><p class="profile-value" x-text="selectedEmployee.blood_type ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Phone Number</p><p class="profile-value" x-text="selectedEmployee.phone_number ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Alternate Mobile</p><p class="profile-value" x-text="selectedEmployee.alternate_phone_number ?? '-'"></p></div>
                                <div class="profile-card col-span-2"><p class="profile-label">Email Address</p><p class="profile-value" x-text="selectedEmployee.personal_email ?? selectedEmployee.email ?? '-'"></p></div>
                                <div class="profile-card col-span-2"><p class="profile-label">Company Email</p><p class="profile-value" x-text="selectedEmployee.company_email ?? selectedEmployee.work_email ?? '-'"></p></div>
                                <div class="profile-card col-span-2"><p class="profile-label">Current Address</p><p class="profile-value" x-text="selectedEmployee.current_address ?? selectedEmployee.address ?? '-'"></p></div>
                                <div class="profile-card col-span-2"><p class="profile-label">Permanent Address</p><p class="profile-value" x-text="selectedEmployee.permanent_address ?? '-'"></p></div>
                                <div class="profile-card col-span-2"><p class="profile-label">Emergency Contact</p><p class="profile-value" x-text="[selectedEmployee.emergency_contact_name, selectedEmployee.emergency_contact_relationship, selectedEmployee.emergency_contact_number].filter(Boolean).join(' | ') || '-'"></p></div>
                            </div>
                        </div>

                        <div x-show="profileTab === 'organization'" class="space-y-5">
                            <h3 class="section-heading">Organizational Assignment</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="profile-card"><p class="profile-label">Branch</p><p class="profile-value" x-text="selectedEmployee.branch_name ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Office</p><p class="profile-value" x-text="selectedEmployee.office_name ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Department</p><p class="profile-value" x-text="selectedEmployee.department_name ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Division</p><p class="profile-value" x-text="selectedEmployee.division_name ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Unit</p><p class="profile-value" x-text="selectedEmployee.unit_name ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Position</p><p class="profile-value" x-text="selectedEmployee.position ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Job Level / Rank</p><p class="profile-value" x-text="selectedEmployee.job_level_rank ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Employment Type</p><p class="profile-value" x-text="selectedEmployee.employment_type ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Work Classification</p><p class="profile-value" x-text="selectedEmployee.work_classification ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Work Arrangement</p><p class="profile-value" x-text="selectedEmployee.work_arrangement ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Work Location</p><p class="profile-value" x-text="selectedEmployee.work_location ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Company Assigned To</p><p class="profile-value" x-text="selectedEmployee.company_assigned_to ?? '-'"></p></div>
                            </div>
                        </div>

                        <div x-show="profileTab === 'payroll'" class="space-y-5">
                            <h3 class="section-heading">Payroll Details</h3>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="profile-card"><p class="profile-label">Payroll Type</p><p class="profile-value" x-text="selectedEmployee.payroll_type ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Salary Grade</p><p class="profile-value" x-text="selectedEmployee.salary_grade ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Basic Salary</p><p class="profile-value text-green-700" x-text="formatMoney(selectedEmployee.basic_salary)"></p></div>
                                <div class="profile-card"><p class="profile-label">Hourly Rate</p><p class="profile-value" x-text="formatMoney(selectedEmployee.hourly_rate)"></p></div>
                            </div>
                            <div class="grid grid-cols-1 gap-4">
                                <div class="profile-card"><p class="profile-label">Work Schedule</p><p class="profile-value" x-text="scheduleLabel(selectedEmployee)"></p></div>
                            </div>
                            <div class="profile-card"><p class="profile-label">Benefits Checklist</p><p class="profile-value whitespace-pre-line" x-text="bulletList(selectedEmployee.benefits_checklist)"></p></div>
                        </div>

                        <div x-show="profileTab === 'government'" class="space-y-5">
                            <h3 class="section-heading">Government Information</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="profile-card"><p class="profile-label">TIN</p><p class="profile-value" x-text="selectedEmployee.tin_number ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">SSS</p><p class="profile-value" x-text="selectedEmployee.sss_number ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">PhilHealth</p><p class="profile-value" x-text="selectedEmployee.philhealth_number ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Pag-IBIG</p><p class="profile-value" x-text="selectedEmployee.pagibig_number ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Passport</p><p class="profile-value" x-text="selectedEmployee.passport_number ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Driver's License</p><p class="profile-value" x-text="selectedEmployee.drivers_license_number ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">PRC License</p><p class="profile-value" x-text="selectedEmployee.prc_license_number ?? '-'"></p></div>
                            </div>
                        </div>

                        <div x-show="profileTab === 'history'" class="space-y-5">
                            <h3 class="section-heading">Education, Employment, Certifications, Skills</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="profile-card"><p class="profile-label">Educational Background</p><p class="profile-value whitespace-pre-line" x-text="bulletList(selectedEmployee.educational_background)"></p></div>
                                <div class="profile-card"><p class="profile-label">Employment History</p><p class="profile-value whitespace-pre-line" x-text="bulletList(selectedEmployee.employment_history)"></p></div>
                                <div class="profile-card"><p class="profile-label">Certifications & Trainings</p><p class="profile-value whitespace-pre-line" x-text="bulletList(selectedEmployee.certifications_trainings)"></p></div>
                                <div class="profile-card"><p class="profile-label">Skills & Competencies</p><p class="profile-value whitespace-pre-line" x-text="bulletList(selectedEmployee.skills_competencies)"></p></div>
                            </div>
                        </div>

                        <div x-show="profileTab === 'documents'" class="space-y-5">
                            <h3 class="section-heading">Attachments</h3>
                            <template x-if="!(selectedEmployee.employee_attachments || []).length">
                                <div class="profile-card text-sm text-gray-500">No attachments uploaded yet.</div>
                            </template>
                            <div class="grid grid-cols-2 gap-4">
                                <template x-for="file in selectedEmployee.employee_attachments || []" :key="file.path">
                                    <div class="profile-card">
                                        <p class="profile-label" x-text="file.category || 'Attachment'"></p>
                                        <p class="profile-value" x-text="file.file_name"></p>
                                        <p class="text-xs text-gray-500 mt-1" x-text="[file.file_type, formatFileSize(file.file_size), file.uploaded_at].filter(Boolean).join(' | ')"></p>
                                        <a :href="file.url" target="_blank" class="inline-flex mt-3 text-xs font-bold text-blue-600">Preview / Download</a>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div x-show="profileTab === 'access'" class="space-y-5">
                            <h3 class="section-heading">System Access & Assigned Platforms</h3>
                            <div class="profile-card"><p class="profile-value whitespace-pre-line" x-text="bulletList(selectedEmployee.system_access)"></p></div>
                        </div>

                        <div x-show="profileTab === 'digital-id'" class="space-y-5">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h3 class="section-heading">Digital Employee ID</h3>
                                    <p class="text-xs text-gray-500 mt-1">CR80 / PVC standard: 85.6mm x 54mm, landscape, front and back.</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" @click="digitalIdSide = 'front'" :class="digitalIdSide === 'front' ? 'bg-blue-700 text-white' : 'bg-white text-blue-700 border border-blue-200'" class="px-3 py-2 rounded-lg text-xs font-black uppercase tracking-widest">Front</button>
                                    <button type="button" @click="digitalIdSide = 'back'" :class="digitalIdSide === 'back' ? 'bg-blue-700 text-white' : 'bg-white text-blue-700 border border-blue-200'" class="px-3 py-2 rounded-lg text-xs font-black uppercase tracking-widest">Back</button>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-blue-100 bg-slate-50 p-5">
                                <div class="digital-id-stage">
                                    <div id="digital-id-export" x-ref="digitalIdExport" class="digital-id-export">
                                        <div class="digital-id-card digital-id-front" x-show="digitalIdSide === 'front'">
                                            <div class="id-brand-row">
                                                <img src="{{ asset('images/FINAL_LOGO.jpg') }}" onerror="this.src='{{ asset('images/imaglogo.png') }}'" alt="John Kelly & Company" class="id-logo">
                                                <div class="id-company-block">
                                                    <p class="id-company">John Kelly &amp; Company</p>
                                                    <p class="id-subcompany">JK&amp;C Inc.</p>
                                                </div>
                                            </div>

                                            <div class="id-front-body">
                                                <div class="id-photo-frame">
                                                    <template x-if="selectedEmployee.profile_photo_url">
                                                        <img :src="selectedEmployee.profile_photo_url" class="id-photo" alt="Employee photo">
                                                    </template>
                                                    <template x-if="!selectedEmployee.profile_photo_url">
                                                        <div class="id-photo-fallback" x-text="initials(selectedEmployee)"></div>
                                                    </template>
                                                </div>

                                                <div class="id-person-block">
                                                    <p class="id-name" x-text="selectedEmployee.full_name || '-'"></p>
                                                    <p class="id-position" x-text="selectedEmployee.position || '-'"></p>
                                                    <div class="id-chip-row">
                                                        <span class="id-chip">Employee ID</span>
                                                        <span class="id-code" x-text="selectedEmployee.employee_code || '-'"></span>
                                                    </div>
                                                    <p class="id-department" x-text="[selectedEmployee.department_name, selectedEmployee.employment_status || 'Active'].filter(Boolean).join(' | ')"></p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="digital-id-card digital-id-back" x-show="digitalIdSide === 'back'">
                                            <div class="id-back-grid">
                                                <div>
                                                    <p class="id-back-company">John Kelly &amp; Company</p>
                                                    <p class="id-back-label">Employee ID Number</p>
                                                    <p class="id-back-code" x-text="selectedEmployee.employee_code || '-'"></p>

                                                    <div class="id-back-section">
                                                        <p class="id-back-label">Emergency Contact Person</p>
                                                        <p class="id-back-value" x-text="selectedEmployee.emergency_contact_name || 'Not provided'"></p>
                                                        <p class="id-back-label mt-2">Emergency Contact Number</p>
                                                        <p class="id-back-value" x-text="selectedEmployee.emergency_contact_number || 'Not provided'"></p>
                                                    </div>
                                                </div>

                                                <div class="id-qr-panel">
                                                    <img :src="qrUrl(selectedEmployee.verification_url)" class="id-qr" alt="Verification QR">
                                                    <p class="id-qr-note">Scan to verify this employee ID.</p>
                                                </div>
                                            </div>

                                            <div class="id-back-footer">
                                                <div>
                                                    <p class="id-back-label">Company Address</p>
                                                    <p class="id-back-value" x-text="companyAddress(selectedEmployee)"></p>
                                                    <p class="id-back-label mt-2">Company Contact Details</p>
                                                    <p class="id-back-value" x-text="companyContactDetails(selectedEmployee)"></p>
                                                    <p class="id-return-note">If found, please return this ID to John Kelly &amp; Company / JK&amp;C Inc. This ID remains the property of the Company.</p>
                                                </div>
                                                <div class="id-signature-box">
                                                    <div class="id-signature-line"></div>
                                                    <p>Authorized Signature</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                                    <a :href="selectedEmployee.verification_url" target="_blank" class="text-xs font-bold text-blue-700 break-all" x-text="selectedEmployee.verification_url"></a>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" @click="showDigitalIdFullscreen = true" class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-xs font-black uppercase tracking-widest text-slate-700">Fullscreen</button>
                                        <button type="button" @click="previewDigitalIdPdf()" class="px-4 py-2 rounded-lg border border-blue-200 bg-white text-xs font-black uppercase tracking-widest text-blue-700">PDF Preview</button>
                                        <button type="button" @click="downloadDigitalId()" class="px-4 py-2 rounded-lg bg-blue-700 text-white text-xs font-black uppercase tracking-widest">Download</button>
                                        <button type="button" @click="printDigitalId()" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-xs font-black uppercase tracking-widest">Print</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-show="profileTab === 'audit'" class="space-y-5">
                            <h3 class="section-heading">Audit Trail & Salary / Employment History</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="profile-card"><p class="profile-label">Activity Audit</p><p class="profile-value whitespace-pre-line" x-text="historyText(selectedEmployee.activity_audit)"></p></div>
                                <div class="profile-card"><p class="profile-label">Salary & Employment History</p><p class="profile-value whitespace-pre-line" x-text="historyText(selectedEmployee.salary_employment_history)"></p></div>
                            </div>
                        </div>

                        <div x-show="profileTab === 'records'" class="space-y-5">
                            <h3 class="section-heading">Related Records</h3>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="related-card"><p class="text-sm font-bold text-gray-900">OBF Requests</p><p class="text-xs text-gray-500 mt-1">Official business trip forms linked to this employee.</p><p class="text-2xl font-bold text-blue-700 mt-4">—</p></div>
                                <div class="related-card"><p class="text-sm font-bold text-gray-900">Attendance</p><p class="text-xs text-gray-500 mt-1">Attendance logs and time records.</p><p class="text-2xl font-bold text-blue-700 mt-4">—</p></div>
                                <div class="related-card"><p class="text-sm font-bold text-gray-900">Training</p><p class="text-xs text-gray-500 mt-1">Training records and certificates.</p><p class="text-2xl font-bold text-blue-700 mt-4">—</p></div>
                            </div>
                            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                                This section is ready for real OBF, attendance, and training counts once we update the controller to load related records.
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t bg-white flex justify-end gap-3">
                        <button type="button" @click="closeDetails()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Close</button>
                        <button type="button" @click="openEdit(selectedEmployee); closeDetails()" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">Edit Employee</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div x-show="showDigitalIdFullscreen" x-transition.opacity class="fixed inset-0 z-[70] bg-slate-950/90 p-6" style="display:none;">
        <div class="mx-auto flex h-full max-w-6xl flex-col">
            <div class="mb-5 flex items-center justify-between text-white">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-blue-200">Digital Employee ID</p>
                    <h2 class="text-xl font-black" x-text="selectedEmployee?.full_name || 'Employee ID'"></h2>
                </div>
                <button type="button" @click="showDigitalIdFullscreen = false" class="rounded-full bg-white/10 px-4 py-2 text-sm font-bold hover:bg-white/20">Close</button>
            </div>

            <div class="flex-1 overflow-auto rounded-2xl bg-slate-100 p-8">
                <div class="grid gap-8 xl:grid-cols-2">
                    <div>
                        <p class="mb-3 text-xs font-black uppercase tracking-widest text-slate-500">Front Side</p>
                        <div class="digital-id-card digital-id-front">
                            <div class="id-brand-row">
                                <img src="{{ asset('images/FINAL_LOGO.jpg') }}" onerror="this.src='{{ asset('images/imaglogo.png') }}'" alt="John Kelly & Company" class="id-logo">
                                <div class="id-company-block">
                                    <p class="id-company">John Kelly &amp; Company</p>
                                    <p class="id-subcompany">JK&amp;C Inc.</p>
                                </div>
                            </div>
                            <div class="id-front-body">
                                <div class="id-photo-frame">
                                    <template x-if="selectedEmployee?.profile_photo_url"><img :src="selectedEmployee.profile_photo_url" class="id-photo" alt="Employee photo"></template>
                                    <template x-if="!selectedEmployee?.profile_photo_url"><div class="id-photo-fallback" x-text="initials(selectedEmployee)"></div></template>
                                </div>
                                <div class="id-person-block">
                                    <p class="id-name" x-text="selectedEmployee?.full_name || '-'"></p>
                                    <p class="id-position" x-text="selectedEmployee?.position || '-'"></p>
                                    <div class="id-chip-row"><span class="id-chip">Employee ID</span><span class="id-code" x-text="selectedEmployee?.employee_code || '-'"></span></div>
                                    <p class="id-department" x-text="[selectedEmployee?.department_name, selectedEmployee?.employment_status || 'Active'].filter(Boolean).join(' | ')"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="mb-3 text-xs font-black uppercase tracking-widest text-slate-500">Back Side</p>
                        <div class="digital-id-card digital-id-back">
                            <div class="id-back-grid">
                                <div>
                                    <p class="id-back-company">John Kelly &amp; Company</p>
                                    <p class="id-back-label">Employee ID Number</p>
                                    <p class="id-back-code" x-text="selectedEmployee?.employee_code || '-'"></p>
                                    <div class="id-back-section">
                                        <p class="id-back-label">Emergency Contact Person</p>
                                        <p class="id-back-value" x-text="selectedEmployee?.emergency_contact_name || 'Not provided'"></p>
                                        <p class="id-back-label mt-2">Emergency Contact Number</p>
                                        <p class="id-back-value" x-text="selectedEmployee?.emergency_contact_number || 'Not provided'"></p>
                                    </div>
                                </div>
                                <div class="id-qr-panel">
                                    <img :src="qrUrl(selectedEmployee?.verification_url)" class="id-qr" alt="Verification QR">
                                    <p class="id-qr-note">Scan to verify this employee ID.</p>
                                </div>
                            </div>
                            <div class="id-back-footer">
                                <div>
                                    <p class="id-back-label">Company Address</p>
                                    <p class="id-back-value" x-text="companyAddress(selectedEmployee)"></p>
                                    <p class="id-back-label mt-2">Company Contact Details</p>
                                    <p class="id-back-value" x-text="companyContactDetails(selectedEmployee)"></p>
                                    <p class="id-return-note">If found, please return this ID to John Kelly &amp; Company / JK&amp;C Inc. This ID remains the property of the Company.</p>
                                </div>
                                <div class="id-signature-box"><div class="id-signature-line"></div><p>Authorized Signature</p></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ADD / EDIT SLIDER --}}
    <div
        x-show="showSlider"
        x-transition.opacity
        class="fixed inset-0 z-50"
        style="display: none;"
    >
        <div class="absolute inset-0 bg-black/40" @click="closeSlider()"></div>

        <div
            x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="absolute right-0 top-0 h-full w-full max-w-[650px] bg-white shadow-xl flex flex-col"
        >
            <div class="px-5 py-4 border-b flex items-center justify-between bg-blue-700">
                <div>
                    <p class="text-xs font-bold text-blue-100 uppercase tracking-widest">Employee Profile</p>
                    <h2 class="text-base font-bold text-white" x-text="isEdit ? 'Edit Employee' : 'Employee Profile'"></h2>
                </div>
                <button type="button" @click="closeSlider()" class="text-blue-100 hover:text-white text-xl leading-none">&times;</button>
            </div>

            <form method="POST" :action="formAction" enctype="multipart/form-data" class="flex-1 overflow-auto px-5 py-5 space-y-5">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="rounded-xl border border-gray-200 p-4">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">Profile Photo</h3>

                    <div class="flex items-center gap-4">
                        <template x-if="form.profile_photo_url">
                            <img :src="form.profile_photo_url" class="h-20 w-20 rounded-2xl object-cover border border-gray-200" alt="Employee photo">
                        </template>

                        <template x-if="!form.profile_photo_url">
                            <div class="h-20 w-20 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-2xl font-bold" x-text="formInitials"></div>
                        </template>

                        <div class="flex-1">
                            <label class="form-label">Upload Profile Photo</label>
                            <input type="file" name="profile_photo" accept="image/*" class="form-input" @change="previewUpload($event)">
                            <input type="hidden" name="captured_photo" x-model="capturedPhoto">
                            <p class="text-[11px] text-gray-500 mt-1">
                                Accepted: JPG, PNG, WEBP. Max 4MB. Leave empty if you do not want to change the current photo.
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" @click="startCamera()" class="px-3 py-1.5 rounded-lg bg-gray-900 text-white text-xs font-bold">Take Photo</button>
                                <button type="button" x-show="cameraActive" @click="capturePhoto()" class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-bold">Capture</button>
                                <button type="button" x-show="cameraActive" @click="stopCamera()" class="px-3 py-1.5 rounded-lg border text-xs font-bold">Close Camera</button>
                            </div>
                        </div>
                    </div>
                    <video x-ref="camera" x-show="cameraActive" autoplay playsinline class="mt-4 w-full rounded-xl border border-gray-200 bg-black"></video>
                </div>

                <div class="rounded-xl border border-gray-200 p-4">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">Personal Details</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="form-label">First Name <span class="text-red-500">*</span></label><input type="text" name="first_name" x-model="form.first_name" required class="form-input"></div>
                        <div><label class="form-label">Middle Name</label><input type="text" name="middle_name" x-model="form.middle_name" class="form-input"></div>
                        <div><label class="form-label">Last Name <span class="text-red-500">*</span></label><input type="text" name="last_name" x-model="form.last_name" required class="form-input"></div>
                        <div><label class="form-label">Suffix</label><input type="text" name="suffix" x-model="form.suffix" class="form-input"></div>
                        <div><label class="form-label">Nickname / Preferred Name</label><input type="text" name="nickname" x-model="form.nickname" class="form-input"></div>
                        <div><label class="form-label">Gender</label><select name="gender" x-model="form.gender" class="form-input"><option value="">Select</option><option>Male</option><option>Female</option><option>Prefer not to say</option></select></div>
                        <div><label class="form-label">Civil Status</label><select name="civil_status" x-model="form.civil_status" class="form-input"><option value="">Select</option><option>Single</option><option>Married</option><option>Widowed</option><option>Separated</option><option>Others</option></select></div>
                        <div><label class="form-label">Date of Birth</label><input type="date" name="date_of_birth" x-model="form.date_of_birth" class="form-input"></div>
                        <div><label class="form-label">Age</label><input type="number" name="age" :value="computedAge" readonly class="form-input bg-gray-50"></div>
                        <div><label class="form-label">Nationality</label><input type="text" name="nationality" x-model="form.nationality" class="form-input"></div>
                        <div><label class="form-label">Religion</label><input type="text" name="religion" x-model="form.religion" class="form-input"></div>
                        <div><label class="form-label">Place of Birth</label><input type="text" name="place_of_birth" x-model="form.place_of_birth" class="form-input"></div>
                        <div><label class="form-label">Blood Type</label><input type="text" name="blood_type" x-model="form.blood_type" class="form-input"></div>
                        <div><label class="form-label">Height</label><input type="text" name="height" x-model="form.height" class="form-input"></div>
                        <div><label class="form-label">Weight</label><input type="text" name="weight" x-model="form.weight" class="form-input"></div>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_pwd" value="1" x-model="form.is_pwd"> PWD</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_solo_parent" value="1" x-model="form.is_solo_parent"> Solo Parent</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_senior_citizen" value="1" x-model="form.is_senior_citizen"> Senior Citizen</label>
                        <div><label class="form-label">Phone Number</label><input type="text" name="phone_number" x-model="form.phone_number" class="form-input"></div>
                        <div><label class="form-label">Alternate Mobile Number</label><input type="text" name="alternate_phone_number" x-model="form.alternate_phone_number" class="form-input"></div>
                        <div class="col-span-2">
                            <label class="form-label">Personal Email</label>
                            <input type="email" name="personal_email" x-model="form.personal_email" class="form-input" placeholder="Personal/applicant email">
                        </div>
                        <div class="col-span-2">
                            <label class="form-label">Work Email <span class="text-red-500">*</span></label>
                            <input type="email" name="work_email" x-model="form.work_email" required class="form-input" placeholder="Official company email">
                            <input type="hidden" name="email" :value="form.work_email || form.personal_email || form.email">
                            <input type="hidden" name="company_email" :value="form.work_email || form.company_email">
                        </div>
                        <div class="col-span-2"><label class="form-label">Current Address</label><textarea name="current_address" x-model="form.current_address" rows="2" class="form-input"></textarea></div>
                        <div class="col-span-2"><label class="form-label">Permanent Address</label><textarea name="permanent_address" x-model="form.permanent_address" rows="2" class="form-input"></textarea></div>
                        <input type="hidden" name="address" :value="form.current_address || form.address">
                        <div><label class="form-label">Emergency Contact Name</label><input type="text" name="emergency_contact_name" x-model="form.emergency_contact_name" class="form-input"></div>
                        <div><label class="form-label">Relationship</label><input type="text" name="emergency_contact_relationship" x-model="form.emergency_contact_relationship" class="form-input"></div>
                        <div><label class="form-label">Emergency Contact Number</label><input type="text" name="emergency_contact_number" x-model="form.emergency_contact_number" class="form-input"></div>
                        <div><label class="form-label">Emergency Contact Address</label><input type="text" name="emergency_contact_address" x-model="form.emergency_contact_address" class="form-input"></div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 p-4">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">Organizational Assignment</h3>
                    <div class="space-y-3">
                        <div><label class="form-label">Office</label><select name="office_id" x-model="form.office_id" @change="onOfficeChange()" class="form-input"><option value="">Select Office</option><template x-for="office in officeOptions" :key="office.id"><option :value="office.id" x-text="office.office_name"></option></template></select></div>
                        <div><label class="form-label">Branch</label><select name="branch_id" x-model="form.branch_id" @change="onBranchChange()" class="form-input"><option value="">Select Branch</option><template x-for="branch in filteredBranches" :key="branch.id"><option :value="branch.id" x-text="branch.branch_name"></option></template></select></div>
                        <div><label class="form-label">Department</label><select name="department_id" x-model="form.department_id" @change="onDepartmentChange()" class="form-input"><option value="">Select Department</option><template x-for="department in filteredDepartments" :key="department.id"><option :value="department.id" x-text="department.department_name"></option></template></select></div>
                        <div><label class="form-label">Division</label><select name="division_id" x-model="form.division_id" @change="onDivisionChange()" class="form-input"><option value="">Select Division</option><template x-for="division in filteredDivisions" :key="division.id"><option :value="division.id" x-text="division.division_name"></option></template></select></div>
                        <div><label class="form-label">Unit</label><select name="unit_id" x-model="form.unit_id" class="form-input"><option value="">Select Unit</option><template x-for="unit in filteredUnits" :key="unit.id"><option :value="unit.id" x-text="unit.unit_name"></option></template></select></div>
                        <div><label class="form-label">Applicant ID</label><input type="text" name="applicant_id" x-model="form.applicant_id" class="form-input"></div>
                        <div><label class="form-label">Job ID</label><input type="text" name="job_id" x-model="form.job_id" class="form-input"></div>
                        <div><label class="form-label">MRF Reference Number</label><input type="text" name="mrf_reference" x-model="form.mrf_reference" class="form-input"></div>
                        <div><label class="form-label">JPF Reference Number</label><input type="text" name="jpf_reference" x-model="form.jpf_reference" class="form-input"></div>
                        <div><label class="form-label">Position / Title</label><input type="text" name="position" x-model="form.position" class="form-input"></div>
                        <div><label class="form-label">Job Level / Rank</label><input type="text" name="job_level_rank" x-model="form.job_level_rank" class="form-input"></div>
                        <div><label class="form-label">Employment Type</label><select name="employment_type" x-model="form.employment_type" class="form-input"><option value="">Select</option><template x-for="type in employmentTypeOptions" :key="type"><option :value="type" x-text="type"></option></template></select></div>
                        <div><label class="form-label">Employment Status</label><select name="employment_status" x-model="form.employment_status" class="form-input"><template x-for="status in employmentStatusOptions" :key="status"><option :value="status" x-text="status"></option></template></select></div>
                        <div x-show="form.employment_status === 'Others'"><label class="form-label">Other Employment Status</label><input type="text" name="employment_status_other" x-model="form.employment_status_other" class="form-input"></div>
                        <div><label class="form-label">Work Classification</label><input type="text" name="work_classification" x-model="form.work_classification" class="form-input"></div>
                        <div><label class="form-label">Work Arrangement</label><input type="text" name="work_arrangement" x-model="form.work_arrangement" class="form-input"></div>
                        <div><label class="form-label">Date Hired</label><input type="date" name="date_hired" x-model="form.date_hired" class="form-input"></div>
                        <div><label class="form-label">Start Date</label><input type="date" name="start_date" x-model="form.start_date" class="form-input"></div>
                        <div><label class="form-label">Probationary End Date</label><input type="date" name="probationary_end_date" x-model="form.probationary_end_date" class="form-input"></div>
                        <div><label class="form-label">Regularization Date</label><input type="date" name="regularization_date" x-model="form.regularization_date" class="form-input"></div>
                        <div><label class="form-label">Contract Duration</label><input type="text" name="contract_duration" x-model="form.contract_duration" class="form-input"></div>
                        <div><label class="form-label">Immediate Supervisor</label><input type="text" name="immediate_supervisor" x-model="form.immediate_supervisor" class="form-input"></div>
                        <div><label class="form-label">Reporting To</label><input type="text" name="reporting_to" x-model="form.reporting_to" class="form-input"></div>
                        <div><label class="form-label">Payroll Group</label><input type="text" name="payroll_group" x-model="form.payroll_group" class="form-input"></div>
                        <div><label class="form-label">Work Location</label><input type="text" name="work_location" x-model="form.work_location" class="form-input"></div>
                        <div><label class="form-label">Company Assigned To</label><input type="text" name="company_assigned_to" x-model="form.company_assigned_to" class="form-input"></div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 p-4">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">Compensation Details</h3>
                    <div class="space-y-3">
                        <div><label class="form-label">Salary Grade</label><select name="salary_grade" x-model="form.salary_grade" class="form-input"><option value="">Select</option><option>SG-01</option><option>SG-02</option><option>SG-03</option><option>SG-04</option><option>SG-05</option><option>Others</option></select></div>
                        <div x-show="form.salary_grade === 'Others'"><label class="form-label">Other Salary Grade</label><input type="text" name="salary_grade_other" x-model="form.salary_grade_other" class="form-input"></div>
                        <div><label class="form-label">Payroll Type <span class="text-red-500">*</span></label><select name="payroll_type" x-model="form.payroll_type" required class="form-input"><option value="">Select Payroll Type</option><option value="Monthly Paid">Monthly Paid</option><option value="Daily Paid">Daily Paid</option></select></div>
                        <div><label class="form-label">Payroll Frequency</label><input type="text" name="payroll_frequency" x-model="form.payroll_frequency" class="form-input"></div>
                        <div><label class="form-label">Basic Salary <span class="text-red-500">*</span></label><input type="number" step="0.01" name="basic_salary" x-model="form.basic_salary" required class="form-input"></div>
                        <div><label class="form-label">Computed Hourly Rate</label><input type="text" :value="hourlyRate" readonly class="form-input bg-gray-50 text-gray-700"></div>
                        <div><label class="form-label">Allowances</label><textarea name="allowances" x-model="form.allowances_text" rows="2" class="form-input" placeholder="One per line"></textarea></div>
                        <div><label class="form-label">Incentives</label><textarea name="incentives" x-model="form.incentives_text" rows="2" class="form-input" placeholder="One per line"></textarea></div>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="bonus_eligibility" value="1" x-model="form.bonus_eligibility"> Bonus</label>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="overtime_eligibility" value="1" x-model="form.overtime_eligibility"> Overtime</label>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="night_differential_eligibility" value="1" x-model="form.night_differential_eligibility"> Night Differential</label>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="holiday_pay_eligibility" value="1" x-model="form.holiday_pay_eligibility"> Holiday Pay</label>
                        </div>
                        <div>
                            <label class="form-label">Benefits Checklist</label>
                            <div class="max-h-40 overflow-auto border rounded-lg p-2 space-y-1">
                                <template x-for="benefit in benefitsOptions" :key="benefit">
                                    <label class="flex items-start gap-2 text-xs"><input type="checkbox" name="benefits_checklist[]" :value="benefit" x-model="form.benefits_checklist"> <span x-text="benefit"></span></label>
                                </template>
                            </div>
                            <input x-show="form.benefits_checklist.includes('Others')" type="text" name="benefits_other" x-model="form.benefits_other" class="form-input mt-2" placeholder="Specify other benefit">
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 p-4">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">Work Schedule</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="form-label">Shift Start</label><input type="time" name="schedule_start_time" x-model="form.schedule_start_time" class="form-input"></div>
                        <div><label class="form-label">Shift End</label><input type="time" name="schedule_end_time" x-model="form.schedule_end_time" class="form-input"></div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Employees can clock in starting 10 minutes before shift start. Active shifts auto close 4 hours after shift end.</p>
                </div>

                <div class="rounded-xl border border-gray-200 p-4">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">Government, History, Access & Documents</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="form-label">TIN Number</label><input type="text" name="tin_number" x-model="form.tin_number" class="form-input"></div>
                        <div><label class="form-label">SSS Number</label><input type="text" name="sss_number" x-model="form.sss_number" class="form-input"></div>
                        <div><label class="form-label">PhilHealth Number</label><input type="text" name="philhealth_number" x-model="form.philhealth_number" class="form-input"></div>
                        <div><label class="form-label">Pag-IBIG Number</label><input type="text" name="pagibig_number" x-model="form.pagibig_number" class="form-input"></div>
                        <div><label class="form-label">Passport Number</label><input type="text" name="passport_number" x-model="form.passport_number" class="form-input"></div>
                        <div><label class="form-label">Driver's License Number</label><input type="text" name="drivers_license_number" x-model="form.drivers_license_number" class="form-input"></div>
                        <div><label class="form-label">PRC License Number</label><input type="text" name="prc_license_number" x-model="form.prc_license_number" class="form-input"></div>
                        <div><label class="form-label">PRC Expiry Date</label><input type="date" name="prc_license_expiry_date" x-model="form.prc_license_expiry_date" class="form-input"></div>
                        <div class="col-span-2"><label class="form-label">Educational Background</label><textarea name="educational_background" x-model="form.educational_background_text" rows="3" class="form-input" placeholder="One entry per line or JSON"></textarea></div>
                        <div class="col-span-2"><label class="form-label">Employment History</label><textarea name="employment_history" x-model="form.employment_history_text" rows="3" class="form-input" placeholder="One entry per line or JSON"></textarea></div>
                        <div class="col-span-2"><label class="form-label">Certifications & Trainings</label><textarea name="certifications_trainings" x-model="form.certifications_trainings_text" rows="3" class="form-input" placeholder="One entry per line or JSON"></textarea></div>
                        <div class="col-span-2"><label class="form-label">Skills & Competencies</label><textarea name="skills_competencies" x-model="form.skills_competencies_text" rows="3" class="form-input" placeholder="One skill per line"></textarea></div>
                        <div class="col-span-2"><label class="form-label">System Access & Platforms</label><textarea name="system_access" x-model="form.system_access_text" rows="3" class="form-input" placeholder="ORDO - Admin - Active"></textarea></div>
                        <div><label class="form-label">Attachment Category</label><select name="attachment_category" class="form-input"><option>Resume / CV</option><option>Portfolio</option><option>Government IDs</option><option>Educational Documents</option><option>Employment Documents</option><option>Clearances</option><option>Medical Records</option><option>Contracts & Agreements</option><option>Certifications</option><option>Other Attachments</option></select></div>
                        <div><label class="form-label">Upload Attachments</label><input type="file" name="attachments[]" multiple class="form-input"></div>
                        <div class="col-span-2"><label class="form-label">Attachment Remarks</label><input type="text" name="attachment_remarks" class="form-input"></div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 p-4" x-show="['Resigned','Terminated','End of Contract','Retired','Deceased','Inactive'].includes(form.employment_status)">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">Inactive / Separation Requirements</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="form-label">Effective Date</label><input type="date" name="status_effective_date" x-model="form.status_effective_date" class="form-input"></div>
                        <div><label class="form-label">Approved By</label><input type="text" name="status_approved_by" x-model="form.status_approved_by" class="form-input"></div>
                        <div><label class="form-label">Reason</label><input type="text" name="status_reason" x-model="form.status_reason" class="form-input"></div>
                        <div><label class="form-label">Supporting Attachment</label><input type="file" name="status_attachment" class="form-input"></div>
                        <div class="col-span-2"><label class="form-label">Remarks</label><textarea name="status_remarks" x-model="form.status_remarks" rows="2" class="form-input"></textarea></div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 p-4">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">Legal, Compliance & Consent</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="consent in consentOptions" :key="consent">
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="compliance_consents[]" :value="consent" x-model="form.compliance_consents_selected"> <span x-text="consent"></span></label>
                        </template>
                    </div>
                </div>

                <div class="sticky bottom-0 bg-white border-t py-4">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-4 py-2.5 text-sm font-semibold">
                        Save Employee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.profile-card { border: 1px solid rgb(229 231 235); background: white; border-radius: 0.75rem; padding: 1rem; }
.profile-label { font-size: 0.6875rem; font-weight: 800; color: rgb(156 163 175); text-transform: uppercase; letter-spacing: 0.08em; }
.profile-value { margin-top: 0.35rem; font-size: 0.95rem; font-weight: 700; color: rgb(17 24 39); word-break: break-word; }
.section-heading { font-size: 0.9rem; font-weight: 800; color: rgb(17 24 39); }
.related-card { border: 1px solid rgb(229 231 235); background: white; border-radius: 0.75rem; padding: 1rem; }
.digital-id-stage { display: flex; justify-content: center; align-items: center; min-height: 340px; overflow-x: auto; }
.digital-id-export { display: inline-block; }
.digital-id-card { width: 85.6mm; height: 54mm; border-radius: 3mm; overflow: hidden; position: relative; background: #ffffff; color: #061533; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; box-shadow: 0 22px 48px rgba(15, 23, 42, 0.18); border: 1px solid #d7deea; }
.digital-id-front { padding: 5mm; background: linear-gradient(135deg, #ffffff 0%, #f7faff 58%, #eaf1ff 100%); }
.digital-id-front::after { content: ""; position: absolute; right: -18mm; bottom: -24mm; width: 58mm; height: 58mm; border-radius: 999px; background: rgba(30, 58, 138, 0.12); }
.digital-id-back { padding: 4.5mm; background: #ffffff; }
.id-brand-row { display: flex; align-items: center; gap: 3mm; position: relative; z-index: 1; }
.id-logo { height: 10mm; width: auto; max-width: 28mm; object-fit: contain; }
.id-company { color: #12398f; font-weight: 900; font-size: 3.6mm; line-height: 1.05; text-transform: uppercase; letter-spacing: .2mm; }
.id-subcompany { margin-top: .6mm; color: #64748b; font-weight: 800; font-size: 2.2mm; text-transform: uppercase; letter-spacing: .55mm; }
.id-front-body { display: grid; grid-template-columns: 23mm 1fr; gap: 4mm; align-items: center; margin-top: 5mm; position: relative; z-index: 1; }
.id-photo-frame { width: 23mm; height: 27mm; border-radius: 2.5mm; border: 1px solid #cbd5e1; background: #f8fafc; overflow: hidden; display: flex; align-items: center; justify-content: center; }
.id-photo { width: 100%; height: 100%; object-fit: cover; }
.id-photo-fallback { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #dbeafe; color: #12398f; font-size: 8mm; font-weight: 900; }
.id-person-block { min-width: 0; }
.id-name { color: #061533; font-size: 5.3mm; font-weight: 900; line-height: 1.02; text-transform: uppercase; overflow-wrap: anywhere; }
.id-position { margin-top: 1.4mm; color: #334155; font-size: 3mm; font-weight: 800; line-height: 1.15; }
.id-chip-row { display: inline-flex; align-items: center; margin-top: 4mm; border: 1px solid #12398f; border-radius: 999px; overflow: hidden; background: #ffffff; }
.id-chip { background: #12398f; color: #ffffff; font-size: 2.1mm; font-weight: 900; text-transform: uppercase; letter-spacing: .35mm; padding: 1.3mm 2.2mm; }
.id-code { color: #12398f; font-size: 3.3mm; font-weight: 900; padding: 1.1mm 2.5mm; letter-spacing: .45mm; }
.id-department { margin-top: 2.2mm; color: #64748b; font-size: 2.4mm; font-weight: 700; line-height: 1.2; }
.id-back-grid { display: grid; grid-template-columns: 1fr 23mm; gap: 4mm; }
.id-back-company { color: #12398f; font-size: 3.4mm; font-weight: 900; text-transform: uppercase; letter-spacing: .18mm; }
.id-back-label { color: #64748b; font-size: 2mm; font-weight: 900; text-transform: uppercase; letter-spacing: .35mm; }
.id-back-code { color: #061533; font-size: 4.4mm; font-weight: 900; letter-spacing: .7mm; margin: .8mm 0 3mm; }
.id-back-section { border-top: 1px solid #d7deea; padding-top: 2.4mm; margin-top: 1mm; }
.id-back-value { color: #061533; font-size: 2.7mm; font-weight: 800; line-height: 1.2; overflow-wrap: anywhere; }
.id-qr-panel { text-align: center; border: 1px solid #d7deea; border-radius: 2mm; padding: 2mm; background: #f8fafc; }
.id-qr { width: 18mm; height: 18mm; object-fit: contain; margin: 0 auto; }
.id-qr-note { color: #334155; font-size: 1.9mm; font-weight: 800; line-height: 1.15; margin-top: 1mm; }
.id-back-footer { display: grid; grid-template-columns: 1fr 25mm; gap: 4mm; align-items: end; border-top: 1px solid #d7deea; margin-top: 3.2mm; padding-top: 2.8mm; }
.id-return-note { color: #334155; font-size: 2mm; line-height: 1.25; margin-top: 1.5mm; }
.id-signature-box { text-align: center; color: #64748b; font-size: 1.9mm; font-weight: 800; text-transform: uppercase; }
.id-signature-line { border-top: 1px solid #061533; margin-bottom: 1.5mm; }
.form-label { display: block; font-size: 0.82rem; font-weight: 600; color: rgb(55 65 81); margin-bottom: 0.25rem; }
.form-input { width: 100%; border: 1px solid rgb(209 213 219); border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem; outline: none; }
.form-input:focus { border-color: rgb(59 130 246); box-shadow: 0 0 0 2px rgb(191 219 254); }
@media print {
    body * { visibility: hidden !important; }
    .digital-id-print-page, .digital-id-print-page * { visibility: visible !important; }
    .digital-id-print-page { position: fixed; inset: 0; display: flex !important; align-items: center; justify-content: center; gap: 12mm; background: #ffffff; }
    .digital-id-card { box-shadow: none; }
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function employeePage(config) {
    return {
        employees: config.employees ?? [],
        officeOptions: config.officeOptions ?? [],
        branchOptions: config.branchOptions ?? [],
        departmentOptions: config.departmentOptions ?? [],
        divisionOptions: config.divisionOptions ?? [],
        unitOptions: config.unitOptions ?? [],
        storeUrl: config.storeUrl,
        updateBaseUrl: config.updateBaseUrl,
        employmentTypeOptions: ['Intern / OJT', 'Probationary', 'Regular', 'Project-Based', 'Fixed-Term', 'Part-Time', 'Casual / Temporary', 'Consultant / Independent Contractor', 'Others'],
        employmentStatusOptions: ['Active', 'Probationary', 'Regular', 'Project-Based', 'Fixed-Term', 'Part-Time', 'Casual / Temporary', 'Consultant / Independent Contractor', 'Resigned', 'Terminated', 'End of Contract', 'Retired', 'Deceased', 'Inactive', 'Others'],
        benefitsOptions: ['Social Security System (SSS)', 'PhilHealth', 'Pag-IBIG Fund (HDMF)', 'Bonus', 'Overtime Pay', 'Night Differential Pay', 'Rest Day / Special Holiday Premium Pay', 'Maternity Benefits', 'Paternity Benefits', 'Solo Parent Benefits', 'Retirement Benefits', 'Other statutory labor benefits', 'Performance Incentive Schemes', 'Merit-Based Rewards', 'Healthcare / Insurance', 'Investment Benefit Plans', 'Leave Benefits', 'Day Shift + Weekends Off', 'No Work on Philippine Holidays', 'Structured Professional Work Environment', 'Exposure to Corporate Advisory and Governance Practice', 'Opportunity for Long-Term Growth', 'Others'],
        consentOptions: ['Data Privacy Consent', 'NDA Acknowledgment', 'Policy Acceptance', 'Handbook Acknowledgment', 'Code of Conduct Acceptance'],

        search: '',
        filterDepartment: '',
        filterBranch: '',
        filterPayroll: '',

        showSlider: false,
        showDetails: false,
        cameraActive: false,
        cameraStream: null,
        capturedPhoto: '',
        selectedEmployee: null,
        profileTab: 'overview',
        digitalIdSide: 'front',
        showDigitalIdFullscreen: false,
        isEdit: false,
        formAction: config.storeUrl,

        form: {
            id: null,
            first_name: '',
            last_name: '',
            age: '',
            address: '',
            phone_number: '',
            email: '',
            personal_email: '',
            work_email: '',
            profile_photo_url: '',
            office_id: '',
            branch_id: '',
            department_id: '',
            division_id: '',
            unit_id: '',
            position: '',
            payroll_type: 'Monthly Paid',
            basic_salary: 0,
            employment_status: 'Active',
            benefits_checklist: [],
            compliance_consents_selected: [],
            schedule_start_time: '',
            schedule_end_time: ''
        },

        get filteredEmployees() {
            const keyword = (this.search || '').toLowerCase().trim();

            return this.employees.filter(employee => {
                const haystack = [
                    employee.employee_code,
                    employee.full_name,
                    employee.email,
                    employee.personal_email,
                    employee.work_email,
                    employee.user_email,
                    employee.phone_number,
                    employee.position,
                    employee.department_name,
                    employee.office_name,
                    employee.branch_name,
                    this.scheduleLabel(employee),
                    employee.payroll_type
                ].join(' ').toLowerCase();

                const matchesSearch = !keyword || haystack.includes(keyword);
                const matchesDepartment = !this.filterDepartment || employee.department_name === this.filterDepartment;
                const matchesBranch = !this.filterBranch || employee.branch_name === this.filterBranch;
                const matchesPayroll = !this.filterPayroll || employee.payroll_type === this.filterPayroll;

                return matchesSearch && matchesDepartment && matchesBranch && matchesPayroll;
            });
        },

        get uniqueDepartments() {
            return [...new Set(this.employees.map(item => item.department_name).filter(Boolean))].sort();
        },

        get uniqueBranches() {
            return [...new Set(this.employees.map(item => item.branch_name).filter(Boolean))].sort();
        },

        get monthlyPaidCount() {
            return this.employees.filter(item => item.payroll_type === 'Monthly Paid').length;
        },

        get dailyPaidCount() {
            return this.employees.filter(item => item.payroll_type === 'Daily Paid').length;
        },


        get filteredBranches() {
            if (!this.form.office_id) return [];

            const selectedOffice = this.officeOptions.find(item => String(item.id) === String(this.form.office_id));
            if (!selectedOffice) return [];

            return this.branchOptions.filter(item => String(item.id) === String(selectedOffice.branch_id));
        },

        get filteredDepartments() {
            if (!this.form.office_id) return [];
            return this.departmentOptions.filter(item => String(item.office_id) === String(this.form.office_id));
        },

        get filteredDivisions() {
            if (!this.form.department_id) return [];
            return this.divisionOptions.filter(item => String(item.department_id) === String(this.form.department_id));
        },

        get filteredUnits() {
            if (!this.form.division_id) return [];
            return this.unitOptions.filter(item => String(item.division_id) === String(this.form.division_id));
        },

        get hourlyRate() {
            const salary = parseFloat(this.form.basic_salary || 0);
            if (!salary || !this.form.payroll_type) return '0.00';
            if (this.form.payroll_type === 'Monthly Paid') return (salary / 22 / 8).toFixed(2);
            return (salary / 8).toFixed(2);
        },

        get computedAge() {
            if (!this.form.date_of_birth) return this.form.age || '';
            const birth = new Date(this.form.date_of_birth);
            if (Number.isNaN(birth.getTime())) return this.form.age || '';
            const today = new Date();
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) age--;
            return age;
        },

        get formInitials() {
            const first = this.form.first_name?.charAt(0) ?? '';
            const last = this.form.last_name?.charAt(0) ?? '';
            return (first + last).toUpperCase() || 'EP';
        },

        openView(employee) {
            this.selectedEmployee = employee;
            this.profileTab = 'overview';
            this.digitalIdSide = 'front';
            this.showDetails = true;
        },

        closeDetails() {
            this.showDetails = false;
            this.showDigitalIdFullscreen = false;
            this.selectedEmployee = null;
            this.profileTab = 'overview';
            this.digitalIdSide = 'front';
        },

        openAdd() {
            this.isEdit = false;
            this.formAction = this.storeUrl;
            this.resetForm();
            this.showSlider = true;
        },

        openEdit(employee) {
            this.isEdit = true;
            this.formAction = `${this.updateBaseUrl}/${employee.id}`;

            this.form = {
                id: employee.id,
                first_name: employee.first_name ?? '',
                last_name: employee.last_name ?? '',
                age: employee.age ?? '',
                address: employee.address ?? '',
                phone_number: employee.phone_number ?? '',
                email: employee.email ?? '',
                personal_email: employee.personal_email ?? '',
                work_email: employee.work_email ?? employee.email ?? '',
                profile_photo_url: employee.profile_photo_url ?? '',
                office_id: employee.office_id ?? '',
                branch_id: employee.branch_id ?? '',
                department_id: employee.department_id ?? '',
                division_id: employee.division_id ?? '',
                unit_id: employee.unit_id ?? '',
                position: employee.position ?? '',
                payroll_type: employee.payroll_type ?? 'Monthly Paid',
                basic_salary: employee.basic_salary ?? 0,
                schedule_start_time: employee.schedule_start_time ?? '',
                schedule_end_time: employee.schedule_end_time ?? ''
            };
            this.form = {
                ...this.defaultForm(),
                ...this.form,
                ...employee,
                current_address: employee.current_address ?? employee.address ?? '',
                company_email: employee.company_email ?? employee.work_email ?? '',
                allowances_text: this.arrayToLines(employee.allowances),
                incentives_text: this.arrayToLines(employee.incentives),
                benefits_checklist: employee.benefits_checklist ?? [],
                educational_background_text: this.arrayToLines(employee.educational_background),
                employment_history_text: this.arrayToLines(employee.employment_history),
                certifications_trainings_text: this.arrayToLines(employee.certifications_trainings),
                skills_competencies_text: this.arrayToLines(employee.skills_competencies),
                system_access_text: this.arrayToLines(employee.system_access),
                compliance_consents_selected: (employee.compliance_consents || []).map(item => item.label || item),
            };
            this.capturedPhoto = '';

            this.showSlider = true;
        },

        closeSlider() {
            this.showSlider = false;
        },

        resetForm() {
            this.form = this.defaultForm();
            this.capturedPhoto = '';
        },

        defaultForm() {
            return {
                id: null,
                first_name: '',
                middle_name: '',
                last_name: '',
                suffix: '',
                nickname: '',
                gender: '',
                civil_status: '',
                nationality: 'Filipino',
                religion: '',
                date_of_birth: '',
                age: '',
                place_of_birth: '',
                blood_type: '',
                height: '',
                weight: '',
                is_pwd: false,
                is_solo_parent: false,
                is_senior_citizen: false,
                address: '',
                current_address: '',
                permanent_address: '',
                phone_number: '',
                alternate_phone_number: '',
                email: '',
                personal_email: '',
                work_email: '',
                company_email: '',
                emergency_contact_name: '',
                emergency_contact_relationship: '',
                emergency_contact_number: '',
                emergency_contact_address: '',
                profile_photo_url: '',
                office_id: '',
                branch_id: '',
                department_id: '',
                division_id: '',
                unit_id: '',
                applicant_id: '',
                job_id: '',
                mrf_reference: '',
                jpf_reference: '',
                position: '',
                job_level_rank: '',
                employment_type: '',
                employment_status: 'Active',
                employment_status_other: '',
                work_classification: '',
                work_arrangement: '',
                date_hired: '',
                start_date: '',
                probationary_end_date: '',
                regularization_date: '',
                contract_duration: '',
                immediate_supervisor: '',
                reporting_to: '',
                payroll_group: '',
                work_location: '',
                company_assigned_to: '',
                recruitment_status: '',
                onboarding_status: '',
                payroll_type: 'Monthly Paid',
                payroll_frequency: '',
                salary_grade: '',
                salary_grade_other: '',
                basic_salary: 0,
                allowances_text: '',
                incentives_text: '',
                benefits_checklist: [],
                benefits_other: '',
                bonus_eligibility: false,
                overtime_eligibility: false,
                night_differential_eligibility: false,
                holiday_pay_eligibility: false,
                tin_number: '',
                sss_number: '',
                philhealth_number: '',
                pagibig_number: '',
                passport_number: '',
                drivers_license_number: '',
                prc_license_number: '',
                prc_license_expiry_date: '',
                educational_background_text: '',
                employment_history_text: '',
                certifications_trainings_text: '',
                skills_competencies_text: '',
                system_access_text: '',
                compliance_consents_selected: [],
                status_effective_date: '',
                status_reason: '',
                status_remarks: '',
                status_approved_by: '',
                schedule_start_time: '',
                schedule_end_time: ''
            };
        },

        onOfficeChange() {
            const selectedOffice = this.officeOptions.find(item => String(item.id) === String(this.form.office_id));
            this.form.branch_id = selectedOffice?.branch_id ?? '';
            this.form.department_id = '';
            this.form.division_id = '';
            this.form.unit_id = '';
        },

        onBranchChange() {
            this.form.department_id = '';
            this.form.division_id = '';
            this.form.unit_id = '';
        },

        onDepartmentChange() {
            this.form.division_id = '';
            this.form.unit_id = '';
        },

        onDivisionChange() {
            this.form.unit_id = '';
        },

        tabClass(tab) {
            if (this.profileTab === tab) {
                return 'px-3 py-2 rounded-lg text-xs font-bold bg-indigo-700 text-white';
            }
            return 'px-3 py-2 rounded-lg text-xs font-bold bg-gray-100 text-gray-600 hover:bg-gray-200';
        },

        initials(employee) {
            const first = employee?.first_name?.charAt(0) ?? '';
            const last = employee?.last_name?.charAt(0) ?? '';
            return (first + last).toUpperCase() || 'EP';
        },

        formatTime(value) {
            if (!value) return '';
            const [hours, minutes] = String(value).slice(0, 5).split(':').map(Number);
            if (!Number.isFinite(hours) || !Number.isFinite(minutes)) return '';

            const date = new Date();
            date.setHours(hours, minutes, 0, 0);

            return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        },

        scheduleLabel(employee) {
            const start = this.formatTime(employee?.schedule_start_time);
            const end = this.formatTime(employee?.schedule_end_time);

            return start && end ? `${start} - ${end}` : 'Not set';
        },

        formatMoney(value) {
            const number = parseFloat(value || 0);
            return 'PHP ' + number.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        arrayToLines(value) {
            if (!value) return '';
            if (Array.isArray(value)) {
                return value.map(item => typeof item === 'string' ? item.replace(/^•\s*/, '') : Object.values(item).filter(Boolean).join(' - ')).join('\n');
            }
            return String(value);
        },

        bulletList(value) {
            if (!value || (Array.isArray(value) && value.length === 0)) return '-';
            const items = Array.isArray(value) ? value : String(value).split(/\r?\n/);
            return items.filter(Boolean).map(item => String(item).startsWith('•') ? item : `• ${item}`).join('\n');
        },

        historyText(value) {
            if (!Array.isArray(value) || !value.length) return 'No history recorded yet.';
            return value.slice().reverse().map(item => `${item.timestamp || ''} | ${item.field || 'Update'}: ${item.previous_value ?? '-'} -> ${item.new_value ?? '-'}${item.updated_by ? ' | ' + item.updated_by : ''}`).join('\n');
        },

        formatFileSize(size) {
            const number = Number(size || 0);
            if (!number) return '';
            if (number < 1024 * 1024) return `${Math.round(number / 1024)} KB`;
            return `${(number / 1024 / 1024).toFixed(2)} MB`;
        },

        qrUrl(url) {
            return `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(url || '')}`;
        },

        companyAddress(employee) {
            return employee?.company_address
                || employee?.work_location
                || employee?.office_name
                || 'John Kelly & Company / JK&C Inc.';
        },

        companyContactDetails(employee) {
            return employee?.office_contact
                || employee?.branch_contact
                || 'Human Capital Department';
        },

        cloneDigitalIdCard(side) {
            const previousSide = this.digitalIdSide;
            this.digitalIdSide = side;

            return new Promise(resolve => {
                this.$nextTick(() => {
                    const source = this.$refs.digitalIdExport?.querySelector('.digital-id-card:not([style*="display: none"])');
                    const clone = source ? source.cloneNode(true) : null;
                    this.digitalIdSide = previousSide;
                    this.$nextTick(() => resolve(clone));
                });
            });
        },

        async buildDigitalIdSheet() {
            const sheet = document.createElement('div');
            sheet.className = 'digital-id-print-page';
            sheet.style.display = 'flex';
            sheet.style.gap = '12mm';
            sheet.style.alignItems = 'center';
            sheet.style.justifyContent = 'center';
            sheet.style.padding = '12mm';
            sheet.style.background = '#ffffff';

            const front = await this.cloneDigitalIdCard('front');
            const back = await this.cloneDigitalIdCard('back');

            if (front) sheet.appendChild(front);
            if (back) sheet.appendChild(back);

            return sheet;
        },

        async previewDigitalIdPdf() {
            if (typeof html2pdf === 'undefined') {
                alert('PDF generator is still loading. Please try again in a moment.');
                return;
            }

            const sheet = await this.buildDigitalIdSheet();
            sheet.style.position = 'fixed';
            sheet.style.left = '0';
            sheet.style.top = '0';
            sheet.style.zIndex = '-1';
            document.body.appendChild(sheet);

            try {
                const worker = html2pdf().set({
                    margin: 0,
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 3, useCORS: true, scrollY: 0 },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
                }).from(sheet);

                const blob = await worker.outputPdf('blob');
                window.open(URL.createObjectURL(blob), '_blank');
            } finally {
                sheet.remove();
            }
        },

        async downloadDigitalId() {
            if (typeof html2pdf === 'undefined') {
                alert('PDF generator is still loading. Please try again in a moment.');
                return;
            }

            const sheet = await this.buildDigitalIdSheet();
            const code = this.selectedEmployee?.employee_code || 'employee';
            sheet.style.position = 'fixed';
            sheet.style.left = '0';
            sheet.style.top = '0';
            sheet.style.zIndex = '-1';
            document.body.appendChild(sheet);

            try {
                await html2pdf().set({
                    margin: 0,
                    filename: `digital-employee-id-${code}.pdf`,
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 3, useCORS: true, scrollY: 0 },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
                }).from(sheet).save();
            } finally {
                sheet.remove();
            }
        },

        async printDigitalId() {
            const sheet = await this.buildDigitalIdSheet();
            document.body.appendChild(sheet);
            window.print();
            setTimeout(() => sheet.remove(), 500);
        },

        previewUpload(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            this.capturedPhoto = '';
            this.form.profile_photo_url = URL.createObjectURL(file);
        },

        async startCamera() {
            try {
                this.cameraStream = await navigator.mediaDevices.getUserMedia({ video: true });
                this.cameraActive = true;
                this.$nextTick(() => {
                    this.$refs.camera.srcObject = this.cameraStream;
                });
            } catch (error) {
                alert('Camera permission was not granted or no camera is available.');
            }
        },

        capturePhoto() {
            const video = this.$refs.camera;
            if (!video) return;
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
            this.capturedPhoto = canvas.toDataURL('image/png');
            this.form.profile_photo_url = this.capturedPhoto;
            this.stopCamera();
        },

        stopCamera() {
            if (this.cameraStream) {
                this.cameraStream.getTracks().forEach(track => track.stop());
            }
            this.cameraStream = null;
            this.cameraActive = false;
        }
    }
}
</script>
@endsection
