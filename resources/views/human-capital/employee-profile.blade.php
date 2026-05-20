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
                                <p class="text-sm text-indigo-100 mt-1" x-text="selectedEmployee.email ?? '-'"></p>
                            </div>

                            <button type="button" @click="closeDetails()" class="text-indigo-100 hover:text-white text-2xl leading-none">&times;</button>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-b bg-white">
                        <div class="grid grid-cols-5 gap-3">
                            <button type="button" @click="profileTab = 'overview'" :class="tabClass('overview')">Overview</button>
                            <button type="button" @click="profileTab = 'personal'" :class="tabClass('personal')">Personal</button>
                            <button type="button" @click="profileTab = 'organization'" :class="tabClass('organization')">Organization</button>
                            <button type="button" @click="profileTab = 'payroll'" :class="tabClass('payroll')">Payroll</button>
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
                                <div class="profile-card"><p class="profile-label">Payroll Type</p><p class="profile-value" x-text="selectedEmployee.payroll_type ?? '-'"></p></div>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div class="profile-card"><p class="profile-label">Basic Salary</p><p class="profile-value text-green-700" x-text="formatMoney(selectedEmployee.basic_salary)"></p></div>
                                <div class="profile-card"><p class="profile-label">Hourly Rate</p><p class="profile-value" x-text="formatMoney(selectedEmployee.hourly_rate)"></p></div>
                                <div class="profile-card"><p class="profile-label">Work Schedule</p><p class="profile-value" x-text="scheduleLabel(selectedEmployee)"></p></div>
                            </div>
                        </div>

                        <div x-show="profileTab === 'personal'" class="space-y-5">
                            <h3 class="section-heading">Personal Information</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="profile-card"><p class="profile-label">Employee ID</p><p class="profile-value" x-text="selectedEmployee.employee_code ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Full Name</p><p class="profile-value" x-text="selectedEmployee.full_name ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">First Name</p><p class="profile-value" x-text="selectedEmployee.first_name ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Last Name</p><p class="profile-value" x-text="selectedEmployee.last_name ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Age</p><p class="profile-value" x-text="selectedEmployee.age ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Phone Number</p><p class="profile-value" x-text="selectedEmployee.phone_number ?? '-'"></p></div>
                                <div class="profile-card col-span-2"><p class="profile-label">Email Address</p><p class="profile-value" x-text="selectedEmployee.email ?? '-'"></p></div>
                                <div class="profile-card col-span-2"><p class="profile-label">Address</p><p class="profile-value" x-text="selectedEmployee.address ?? '-'"></p></div>
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
                            </div>
                        </div>

                        <div x-show="profileTab === 'payroll'" class="space-y-5">
                            <h3 class="section-heading">Payroll Details</h3>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="profile-card"><p class="profile-label">Payroll Type</p><p class="profile-value" x-text="selectedEmployee.payroll_type ?? '-'"></p></div>
                                <div class="profile-card"><p class="profile-label">Basic Salary</p><p class="profile-value text-green-700" x-text="formatMoney(selectedEmployee.basic_salary)"></p></div>
                                <div class="profile-card"><p class="profile-label">Hourly Rate</p><p class="profile-value" x-text="formatMoney(selectedEmployee.hourly_rate)"></p></div>
                            </div>
                            <div class="grid grid-cols-1 gap-4">
                                <div class="profile-card"><p class="profile-label">Work Schedule</p><p class="profile-value" x-text="scheduleLabel(selectedEmployee)"></p></div>
                            </div>
                            <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
                                Payroll profile, payslip records, deductions, and payroll summaries can be connected here later.
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
                            <input type="file" name="profile_photo" accept="image/*" class="form-input">
                            <p class="text-[11px] text-gray-500 mt-1">
                                Accepted: JPG, PNG, WEBP. Max 4MB. Leave empty if you do not want to change the current photo.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 p-4">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">Personal Details</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="form-label">First Name <span class="text-red-500">*</span></label><input type="text" name="first_name" x-model="form.first_name" required class="form-input"></div>
                        <div><label class="form-label">Last Name <span class="text-red-500">*</span></label><input type="text" name="last_name" x-model="form.last_name" required class="form-input"></div>
                        <div><label class="form-label">Age</label><input type="number" name="age" x-model="form.age" class="form-input"></div>
                        <div><label class="form-label">Phone Number</label><input type="text" name="phone_number" x-model="form.phone_number" class="form-input"></div>
                        <div class="col-span-2">
                            <label class="form-label">Personal Email</label>
                            <input type="email" name="personal_email" x-model="form.personal_email" class="form-input" placeholder="Personal/applicant email">
                        </div>
                        <div class="col-span-2">
                            <label class="form-label">Work Email <span class="text-red-500">*</span></label>
                            <input type="email" name="work_email" x-model="form.work_email" required class="form-input" placeholder="Official company email">
                            <input type="hidden" name="email" :value="form.work_email || form.personal_email || form.email">
                        </div>
                        <div class="col-span-2"><label class="form-label">Address</label><textarea name="address" x-model="form.address" rows="3" class="form-input"></textarea></div>
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
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 p-4">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">Compensation Details</h3>
                    <div class="space-y-3">
                        <div><label class="form-label">Position</label><input type="text" name="position" x-model="form.position" class="form-input"></div>
                        <div><label class="form-label">Payroll Type <span class="text-red-500">*</span></label><select name="payroll_type" x-model="form.payroll_type" required class="form-input"><option value="">Select Payroll Type</option><option value="Monthly Paid">Monthly Paid</option><option value="Daily Paid">Daily Paid</option></select></div>
                        <div><label class="form-label">Basic Salary <span class="text-red-500">*</span></label><input type="number" step="0.01" name="basic_salary" x-model="form.basic_salary" required class="form-input"></div>
                        <div><label class="form-label">Computed Hourly Rate</label><input type="text" :value="hourlyRate" readonly class="form-input bg-gray-50 text-gray-700"></div>
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
.form-label { display: block; font-size: 0.82rem; font-weight: 600; color: rgb(55 65 81); margin-bottom: 0.25rem; }
.form-input { width: 100%; border: 1px solid rgb(209 213 219); border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem; outline: none; }
.form-input:focus { border-color: rgb(59 130 246); box-shadow: 0 0 0 2px rgb(191 219 254); }
</style>

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

        search: '',
        filterDepartment: '',
        filterBranch: '',
        filterPayroll: '',

        showSlider: false,
        showDetails: false,
        selectedEmployee: null,
        profileTab: 'overview',
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

        get formInitials() {
            const first = this.form.first_name?.charAt(0) ?? '';
            const last = this.form.last_name?.charAt(0) ?? '';
            return (first + last).toUpperCase() || 'EP';
        },

        openView(employee) {
            this.selectedEmployee = employee;
            this.profileTab = 'overview';
            this.showDetails = true;
        },

        closeDetails() {
            this.showDetails = false;
            this.selectedEmployee = null;
            this.profileTab = 'overview';
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

            this.showSlider = true;
        },

        closeSlider() {
            this.showSlider = false;
        },

        resetForm() {
            this.form = {
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
            return '₱' + number.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    }
}
</script>
@endsection
