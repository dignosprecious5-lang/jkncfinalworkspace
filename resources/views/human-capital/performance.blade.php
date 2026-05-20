@extends('layouts.app')

@section('content')
@php
    $criteria = [
        ['key' => 'quality_of_work', 'remarkKey' => 'quality_of_work_remarks', 'label' => 'Quality of Work'],
        ['key' => 'timeliness_compliance', 'remarkKey' => 'timeliness_compliance_remarks', 'label' => 'Timeliness and Deadline Compliance'],
        ['key' => 'productivity_output', 'remarkKey' => 'productivity_output_remarks', 'label' => 'Productivity and Output'],
        ['key' => 'attendance_punctuality', 'remarkKey' => 'attendance_punctuality_remarks', 'label' => 'Attendance and Punctuality'],
        ['key' => 'policy_compliance', 'remarkKey' => 'policy_compliance_remarks', 'label' => 'Compliance with Company Policies'],
        ['key' => 'task_ownership', 'remarkKey' => 'task_ownership_remarks', 'label' => 'Task Ownership and Accountability'],
        ['key' => 'communication_coordination', 'remarkKey' => 'communication_coordination_remarks', 'label' => 'Communication and Coordination'],
        ['key' => 'teamwork_conduct', 'remarkKey' => 'teamwork_conduct_remarks', 'label' => 'Teamwork and Professional Conduct'],
        ['key' => 'initiative_problem_solving', 'remarkKey' => 'initiative_problem_solving_remarks', 'label' => 'Initiative and Problem Solving'],
        ['key' => 'adaptability_learning', 'remarkKey' => 'adaptability_learning_remarks', 'label' => 'Adaptability and Willingness to Learn'],
        ['key' => 'client_support', 'remarkKey' => 'client_support_remarks', 'label' => 'Client / Internal Service Support'],
        ['key' => 'care_of_resources', 'remarkKey' => 'care_of_resources_remarks', 'label' => 'Use and Care of Company Systems, Equipment, and Resources'],
    ];

    $recommendations = [
        'Continue regular employment / engagement',
        'For coaching and monitoring',
        'For Performance Improvement Plan',
        'For promotion or expanded responsibility',
        'For incentive / recognition',
        'For disciplinary review',
    ];
@endphp

<div class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col" x-data="performancePage()">
    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0">
        <div class="flex items-center justify-between px-5 py-4 border-b shrink-0 gap-4">
            <div>
                <p class="text-xs font-bold text-blue-600 uppercase tracking-wider">Human Capital</p>
                <h1 class="text-lg font-semibold text-gray-900">Performance</h1>
                <p class="text-xs text-gray-500">Employee evaluations and performance improvement plans.</p>
            </div>

            <div class="flex items-center gap-3">
                @if($canManagePerformance)
                    <form method="GET" action="{{ route('human-capital.performance') }}">
                        <select name="employee_id" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                            <option value="">All employees</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee['id'] }}" @selected((string) $selectedEmployeeId === (string) $employee['id'])>
                                    {{ $employee['full_name'] }}
                                </option>
                            @endforeach
                        </select>
                    </form>

                    <button type="button" @click="openAdd()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-semibold">
                        + Add New
                    </button>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="mx-5 mt-4 px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm font-semibold">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mx-5 mt-4 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                <p class="font-bold mb-1">Please fix the following:</p>
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(!$canManagePerformance && !$currentEmployee)
            <div class="mx-5 mt-4 px-4 py-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm">
                No employee profile is linked to your user email yet. Please contact Human Capital.
            </div>
        @endif

        <div class="flex gap-6 px-5 py-3 border-b text-sm font-medium">
            <template x-for="tab in tabs" :key="tab.key">
                <button type="button" @click="switchTab(tab.key)" :class="currentTab === tab.key ? 'text-blue-600 border-b-2 border-blue-600 pb-2' : 'text-gray-500 hover:text-gray-700 pb-2'" x-text="tab.label"></button>
            </template>
        </div>

        <div class="p-5 flex-grow overflow-hidden">
            <div class="border rounded-xl h-full overflow-auto bg-white">
                <table class="w-full text-sm min-w-[1100px] border-collapse">
                    <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                        <tr>
                            <th class="p-3 text-left">Reference</th>
                            <th class="p-3 text-left">Employee</th>
                            <th class="p-3 text-left">Department</th>
                            <th class="p-3 text-left" x-text="mainColumnLabel"></th>
                            <th class="p-3 text-left">Date</th>
                            <th class="p-3 text-left">Status / Rating</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template x-if="currentRows.length === 0">
                            <tr>
                                <td colspan="7" class="p-10 text-center text-gray-400">
                                    No records found for this performance module.
                                </td>
                            </tr>
                        </template>

                        <template x-for="row in currentRows" :key="currentTab + '-' + row.id">
                            <tr class="border-t hover:bg-gray-50">
                                <td class="p-3 font-semibold text-blue-700" x-text="displayRef(row)"></td>
                                <td class="p-3">
                                    <div class="font-medium text-gray-900" x-text="row.employee_name || '-'"></div>
                                    <div class="text-xs text-gray-500" x-text="row.employee?.employee_code || '-'"></div>
                                </td>
                                <td class="p-3 text-gray-700" x-text="row.department || '-'"></td>
                                <td class="p-3 text-gray-700" x-text="mainColumnValue(row)"></td>
                                <td class="p-3 text-gray-700" x-text="recordDate(row)"></td>
                                <td class="p-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold" :class="statusClass(row)" x-text="statusValue(row)"></span>
                                </td>
                                <td class="p-3 text-right whitespace-nowrap">
                                    <button type="button" @click="openView(row)" class="text-indigo-600 hover:underline text-xs font-semibold mr-3">View</button>
                                    <button type="button" @click="openEdit(row)" class="text-blue-600 hover:underline text-xs font-semibold mr-3" x-show="canManagePerformance">Edit</button>
                                    <button type="button" @click="openComment(row)" class="text-blue-600 hover:underline text-xs font-semibold mr-3" x-show="!canManagePerformance">Comment</button>
                                    <form :action="deleteAction(row)" method="POST" class="inline" x-show="canManagePerformance" onsubmit="return confirm('Delete this performance record?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:underline text-xs font-semibold">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div x-show="showPanel" x-transition.opacity class="fixed inset-0 z-50 bg-black/40 flex justify-end" style="display:none;" @click.self="closePanel()">
        <div class="w-screen h-full bg-white shadow-xl flex flex-col">
            <div class="px-6 py-4 border-b bg-white flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold uppercase tracking-widest text-gray-900" x-text="panelTitle"></h2>
                    <p class="text-xs text-gray-500" x-text="displayRef(form)"></p>
                </div>
                <button type="button" @click="closePanel()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>

            <div class="flex-1 min-h-0 grid grid-cols-[58%_42%] bg-gray-50">
                <div class="min-h-0 overflow-auto p-5 border-r bg-gray-100">
                    <div class="flex items-center justify-between mb-4 sticky top-0 z-10 bg-gray-100 py-2">
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Live Preview</p>
                        <button type="button" onclick="window.print()" class="px-3 py-2 border rounded-lg text-xs font-semibold text-gray-700 bg-white">Download PDF</button>
                    </div>

                    <div class="bg-white mx-auto border border-gray-300 shadow-lg px-10 py-8 text-[11px] leading-tight w-[820px] min-h-[1123px] print-area">
                        <div class="text-center border-b-2 border-blue-700 pb-4 mb-4">
                            <img src="{{ asset('images/jk-logo-template.png') }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';" class="h-24 mx-auto mb-2 object-contain" alt="John Kelly & Company Logo">
                            <div style="display:none">
                                <div class="text-3xl font-serif font-bold text-gray-900">John Kelly</div>
                                <div class="text-2xl font-serif italic text-gray-800">& Company</div>
                            </div>
                            <p class="mt-2 text-[11px] font-semibold">3F, Cebu Holdings Center, Cebu Business Park, Cebu City, Philippines 6000</p>
                            <p>Email: start@jknc.io | Website: https://jknc.io/ | Phone: 0995-535-8729</p>
                            <p class="mt-1">Form Code: <span x-text="formCode"></span> | Version: 1.0 | Effective Date: {{ now()->format('F j, Y') }} | Issued by: Human Capital</p>
                        </div>

                        <div class="bg-blue-700 text-white px-3 py-2 font-bold uppercase tracking-widest text-sm mb-3 rounded-sm" x-text="activeTitle"></div>

                        <table class="preview-table">
                            <tr><td><b>Employee Name:</b> <span x-text="form.employee_name || preview.full_name || '-'"></span></td><td><b>Position:</b> <span x-text="form.position || preview.position || '-'"></span></td></tr>
                            <tr><td><b>Department / Unit:</b> <span x-text="form.department || preview.department || '-'"></span></td><td><b>Immediate Supervisor:</b> <span x-text="form.supervisor_name || '-'"></span></td></tr>
                            <tr x-show="currentTab === 'evaluation'"><td><b>Evaluation Period:</b> <span x-text="periodText()"></span></td><td><b>Date of Evaluation:</b> <span x-text="form.evaluation_date || '-'"></span></td></tr>
                            <tr x-show="currentTab === 'pip'"><td><b>Start Date:</b> <span x-text="form.start_date || '-'"></span></td><td><b>Target Completion:</b> <span x-text="form.target_completion_date || '-'"></span></td></tr>
                        </table>

                        <template x-if="currentTab === 'evaluation'">
                            <div>
                                <div class="section-title">I. Purpose of Evaluation</div>
                                <div class="border border-gray-300 p-3 mb-3">
                                    This Performance Evaluation Form is used to assess the employee's work performance, conduct, compliance, productivity, and contribution to the company's operations. The result shall be used for performance improvement, coaching, promotion, retention, incentives, or other management decisions.
                                </div>

                                <div class="section-title">II. Rating Scale</div>
                                <table class="preview-table">
                                    <tr><td><b>5</b></td><td>Excellent - Consistently exceeds expectations</td></tr>
                                    <tr><td><b>4</b></td><td>Very Good - Often exceeds expectations</td></tr>
                                    <tr><td><b>3</b></td><td>Satisfactory - Meets expectations</td></tr>
                                    <tr><td><b>2</b></td><td>Needs Improvement - Partially meets expectations</td></tr>
                                    <tr><td><b>1</b></td><td>Unsatisfactory - Does not meet expectations</td></tr>
                                </table>

                                <div class="section-title">III. Performance Criteria</div>
                                <table class="preview-table">
                                    <tr><td><b>Criteria</b></td><td width="65"><b>Rating</b></td><td><b>Remarks</b></td></tr>
                                    <template x-for="criterion in criteria" :key="'preview-' + criterion.key">
                                        <tr>
                                            <td x-text="criterion.label"></td>
                                            <td x-text="form[criterion.key] || '_'"></td>
                                            <td x-text="form[criterion.remarkKey] || ''"></td>
                                        </tr>
                                    </template>
                                </table>
                                <p><b>Total Score:</b> <span x-text="totalScore()"></span> / 60</p>
                                <p><b>Average Rating:</b> <span x-text="averageRating()"></span></p>

                                <div class="section-title">IV. Overall Performance Rating</div>
                                <div class="border border-gray-300 p-3 mb-3" x-text="overallRating()"></div>

                                <div class="section-title">V. Key Strengths</div>
                                <div class="border border-gray-300 p-3 mb-3 whitespace-pre-line" x-text="form.key_strengths || '1.\\n2.\\n3.'"></div>

                                <div class="section-title">VI. Areas for Improvement</div>
                                <div class="border border-gray-300 p-3 mb-3 whitespace-pre-line" x-text="form.areas_for_improvement || '1.\\n2.\\n3.'"></div>

                                <div class="section-title">VII. Performance Improvement / Action Plan</div>
                                <table class="preview-table">
                                    <tr><td><b>Improvement Area</b></td><td><b>Required Action</b></td><td><b>Target Date</b></td><td><b>Responsible Person</b></td></tr>
                                    <template x-for="row in form.action_plan" :key="'ap-preview-' + row._key">
                                        <tr><td x-text="row.improvement_area || ''"></td><td x-text="row.required_action || ''"></td><td x-text="row.target_date || ''"></td><td x-text="row.responsible_person || ''"></td></tr>
                                    </template>
                                </table>

                                <div class="section-title">VIII. Supervisor's Recommendation</div>
                                <div class="border border-gray-300 p-3 mb-3" x-text="recommendationText()"></div>

                                <div class="section-title">IX. Employee Comments</div>
                                <div class="border border-gray-300 p-3 mb-3 min-h-[70px] whitespace-pre-line" x-text="form.employee_comments || ''"></div>

                                <div class="section-title">Suggested Scoring Guide</div>
                                <table class="preview-table">
                                    <tr><td>4.50 - 5.00</td><td>Excellent</td></tr>
                                    <tr><td>3.50 - 4.49</td><td>Very Good</td></tr>
                                    <tr><td>2.50 - 3.49</td><td>Satisfactory</td></tr>
                                    <tr><td>1.50 - 2.49</td><td>Needs Improvement</td></tr>
                                    <tr><td>1.00 - 1.49</td><td>Unsatisfactory</td></tr>
                                </table>
                            </div>
                        </template>

                        <template x-if="currentTab === 'pip'">
                            <div>
                                <div class="section-title">I. Performance Improvement Plan</div>
                                <table class="preview-table">
                                    <tr><td><b>Status:</b> <span x-text="form.status || 'Ongoing'"></span></td><td><b>Supervisor:</b> <span x-text="form.supervisor_name || '-'"></span></td></tr>
                                </table>
                                <div class="section-title">II. Improvement Areas</div>
                                <table class="preview-table">
                                    <tr><td><b>Improvement Area</b></td><td><b>Required Action</b></td><td><b>Target Date</b></td><td><b>Responsible Person</b></td></tr>
                                    <template x-for="row in form.improvement_areas" :key="'pip-preview-' + row._key">
                                        <tr><td x-text="row.area || ''"></td><td x-text="row.required_action || ''"></td><td x-text="row.target_date || ''"></td><td x-text="row.responsible_person || ''"></td></tr>
                                    </template>
                                </table>
                                <div class="section-title">III. Notes</div>
                                <div class="border border-gray-300 p-3 mb-3 min-h-[80px] whitespace-pre-line" x-text="form.notes || ''"></div>
                                <div class="section-title">IV. Employee Comments</div>
                                <div class="border border-gray-300 p-3 mb-3 min-h-[70px] whitespace-pre-line" x-text="form.employee_comments || ''"></div>
                            </div>
                        </template>

                        <div class="grid grid-cols-3 gap-10 mt-16 text-center">
                            <div><div class="border-b border-gray-700 h-8"></div><p class="mt-1 font-bold" x-text="signatureOne"></p></div>
                            <div><div class="border-b border-gray-700 h-8"></div><p class="mt-1 font-bold">Reviewed By</p></div>
                            <div><div class="border-b border-gray-700 h-8"></div><p class="mt-1 font-bold">Employee Acknowledgment</p></div>
                        </div>
                    </div>
                </div>

                <div class="min-h-0 overflow-auto bg-white">
                    <form :action="formAction" method="POST" class="p-6 space-y-4">
                        @csrf
                        <template x-if="usesPut"><input type="hidden" name="_method" value="PUT"></template>

                        <div class="rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-4 py-2 bg-blue-700 text-white text-xs font-bold uppercase tracking-widest">Employee Information</div>
                            <div class="p-4 grid grid-cols-2 gap-3">
                                @if($canManagePerformance)
                                    <div class="col-span-2">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Employee</label>
                                        <select name="employee_id" x-model="form.employee_id" @change="syncEmployee()" :disabled="isView" class="w-full border rounded-lg px-3 py-2 text-sm">
                                            <option value="">Select employee</option>
                                            <template x-for="employee in employees" :key="employee.id">
                                                <option :value="employee.id" x-text="employee.full_name + ' - ' + (employee.employee_code || 'No ID')"></option>
                                            </template>
                                        </select>
                                    </div>
                                @else
                                    <input type="hidden" name="employee_id" value="{{ $currentEmployee['id'] ?? '' }}">
                                @endif
                                <input type="text" x-model="form.position" readonly placeholder="Position" class="border rounded-lg px-3 py-2 text-sm bg-gray-50">
                                <input type="text" x-model="form.department" readonly placeholder="Department / Unit" class="border rounded-lg px-3 py-2 text-sm bg-gray-50">
                                <input type="text" name="supervisor_name" x-model="form.supervisor_name" :readonly="fieldsLocked" placeholder="Immediate Supervisor" class="col-span-2 border rounded-lg px-3 py-2 text-sm">
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 overflow-hidden" x-show="currentTab === 'evaluation'">
                            <div class="px-4 py-2 bg-blue-700 text-white text-xs font-bold uppercase tracking-widest">Employee Evaluation Form</div>
                            <div class="p-4 space-y-3">
                                <div class="grid grid-cols-3 gap-3">
                                    <input type="date" name="evaluation_period_start" x-model="form.evaluation_period_start" :readonly="fieldsLocked" class="border rounded-lg px-3 py-2 text-sm">
                                    <input type="date" name="evaluation_period_end" x-model="form.evaluation_period_end" :readonly="fieldsLocked" class="border rounded-lg px-3 py-2 text-sm">
                                    <input type="date" name="evaluation_date" x-model="form.evaluation_date" :readonly="fieldsLocked" class="border rounded-lg px-3 py-2 text-sm">
                                </div>

                                <template x-for="criterion in criteria" :key="'form-' + criterion.key">
                                    <div class="grid grid-cols-[1fr_90px_1fr] gap-2">
                                        <input type="text" :value="criterion.label" readonly class="border rounded-lg px-3 py-2 text-sm bg-gray-50">
                                        <select :name="criterion.key" x-model="form[criterion.key]" :disabled="fieldsLocked" class="border rounded-lg px-3 py-2 text-sm bg-white">
                                            <option value="">Rating</option>
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                            <option value="5">5</option>
                                        </select>
                                        <input type="text" :name="criterion.remarkKey" x-model="form[criterion.remarkKey]" :readonly="fieldsLocked" placeholder="Remarks" class="border rounded-lg px-3 py-2 text-sm">
                                    </div>
                                </template>

                                <textarea name="key_strengths" x-model="form.key_strengths" :readonly="fieldsLocked" rows="3" placeholder="Key strengths" class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
                                <textarea name="areas_for_improvement" x-model="form.areas_for_improvement" :readonly="fieldsLocked" rows="3" placeholder="Areas for improvement" class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>

                                <div>
                                    <p class="text-xs font-semibold text-gray-600 mb-2">Performance Improvement / Action Plan</p>
                                    <template x-for="(row, index) in form.action_plan" :key="'ap-form-' + row._key">
                                        <div class="grid grid-cols-4 gap-2 mb-2">
                                            <input type="text" :name="`action_plan[${index}][improvement_area]`" x-model="row.improvement_area" :readonly="fieldsLocked" placeholder="Improvement area" class="border rounded-lg px-2 py-2 text-xs">
                                            <input type="text" :name="`action_plan[${index}][required_action]`" x-model="row.required_action" :readonly="fieldsLocked" placeholder="Required action" class="border rounded-lg px-2 py-2 text-xs">
                                            <input type="date" :name="`action_plan[${index}][target_date]`" x-model="row.target_date" :readonly="fieldsLocked" class="border rounded-lg px-2 py-2 text-xs">
                                            <input type="text" :name="`action_plan[${index}][responsible_person]`" x-model="row.responsible_person" :readonly="fieldsLocked" placeholder="Responsible person" class="border rounded-lg px-2 py-2 text-xs">
                                        </div>
                                    </template>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold text-gray-600 mb-2">Supervisor's Recommendation</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <template x-for="item in recommendations" :key="item">
                                            <label class="flex items-center gap-2 text-xs text-gray-700">
                                                <input type="checkbox" name="supervisor_recommendations[]" :value="item" x-model="form.supervisor_recommendations" :disabled="fieldsLocked">
                                                <span x-text="item"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 overflow-hidden" x-show="currentTab === 'pip'">
                            <div class="px-4 py-2 bg-blue-700 text-white text-xs font-bold uppercase tracking-widest">Performance Improvement Plan</div>
                            <div class="p-4 space-y-3">
                                <div class="grid grid-cols-3 gap-3">
                                    <input type="date" name="start_date" x-model="form.start_date" :readonly="fieldsLocked" class="border rounded-lg px-3 py-2 text-sm">
                                    <input type="date" name="target_completion_date" x-model="form.target_completion_date" :readonly="fieldsLocked" class="border rounded-lg px-3 py-2 text-sm">
                                    <select name="status" x-model="form.status" :disabled="fieldsLocked || currentTab !== 'pip'" class="border rounded-lg px-3 py-2 text-sm bg-white">
                                        <option>Ongoing</option>
                                        <option>Completed</option>
                                        <option>Discontinued</option>
                                        <option>Escalated</option>
                                    </select>
                                </div>
                                <template x-for="(row, index) in form.improvement_areas" :key="'pip-form-' + row._key">
                                    <div class="grid grid-cols-4 gap-2">
                                        <input type="text" :name="`improvement_areas[${index}][area]`" x-model="row.area" :readonly="fieldsLocked" placeholder="Improvement area" class="border rounded-lg px-2 py-2 text-xs">
                                        <input type="text" :name="`improvement_areas[${index}][required_action]`" x-model="row.required_action" :readonly="fieldsLocked" placeholder="Required action" class="border rounded-lg px-2 py-2 text-xs">
                                        <input type="date" :name="`improvement_areas[${index}][target_date]`" x-model="row.target_date" :readonly="fieldsLocked" class="border rounded-lg px-2 py-2 text-xs">
                                        <input type="text" :name="`improvement_areas[${index}][responsible_person]`" x-model="row.responsible_person" :readonly="fieldsLocked" placeholder="Responsible person" class="border rounded-lg px-2 py-2 text-xs">
                                    </div>
                                </template>
                                <textarea name="notes" x-model="form.notes" :readonly="fieldsLocked" rows="4" placeholder="Notes" class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-4 py-2 bg-blue-700 text-white text-xs font-bold uppercase tracking-widest">Employee Comments / Acknowledgment</div>
                            <div class="p-4">
                                <textarea name="employee_comments" x-model="form.employee_comments" :readonly="isView || (canManagePerformance && mode !== 'edit')" rows="4" placeholder="Employee comments" class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
                            </div>
                        </div>

                        <div class="pt-2 border-t flex justify-end gap-3 pb-2">
                            <button type="button" @click="closePanel()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                            <button type="submit" x-show="!isView" class="px-5 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold" x-text="canManagePerformance ? 'Save Form' : 'Save Comments'"></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .section-title {
        margin-top: 10px;
        margin-bottom: 4px;
        background: #1d4ed8;
        color: white;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        padding: 5px 8px;
        border-radius: 2px;
    }

    .preview-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
    }

    .preview-table td {
        border: 1px solid #d1d5db;
        padding: 7px;
        vertical-align: top;
    }

    @media print {
        body * { visibility: hidden; }
        .print-area, .print-area * { visibility: visible; }
        .print-area { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none; border: none; }
    }
</style>
@endpush

@push('scripts')
<script>
function performancePage() {
    return {
        currentTab: 'evaluation',
        showPanel: false,
        mode: 'create',
        canManagePerformance: @json($canManagePerformance),
        employees: @json($employees),
        currentEmployee: @json($currentEmployee),
        evaluations: @json($evaluations),
        pips: @json($pips),
        criteria: @json($criteria),
        recommendations: @json($recommendations),
        form: {},
        preview: {},
        tabs: [
            { key: 'evaluation', label: 'Employee Evaluation' },
            { key: 'pip', label: 'Performance Improvement Plan' },
        ],

        get currentRows() {
            if (this.currentTab === 'evaluation') return this.evaluations;
            return this.pips;
        },

        get isView() {
            return this.mode === 'view';
        },

        get usesPut() {
            return this.mode === 'edit' || this.mode === 'comment';
        },

        get fieldsLocked() {
            return this.isView || !this.canManagePerformance;
        },

        get activeTitle() {
            if (this.currentTab === 'evaluation') return 'Performance Evaluation Form';
            return 'Performance Improvement Plan';
        },

        get formCode() {
            if (this.currentTab === 'evaluation') return 'PERF-F001';
            return 'PIP-F001';
        },

        get signatureOne() {
            if (this.currentTab === 'evaluation') return 'Evaluated By';
            return 'Initiated By';
        },

        get panelTitle() {
            const prefix = this.mode === 'view' ? 'View' : (this.mode === 'edit' || this.mode === 'comment' ? 'Edit' : 'New');
            return `${prefix} ${this.activeTitle}`;
        },

        get mainColumnLabel() {
            if (this.currentTab === 'evaluation') return 'Supervisor';
            return 'Improvement Area';
        },

        get formAction() {
            const base = '{{ url('/human-capital/performance') }}';
            if (this.currentTab === 'evaluation') return this.usesPut ? `${base}/evaluation/${this.form.id}` : '{{ route('human-capital.performance.evaluation.store') }}';
            return this.usesPut ? `${base}/pip/${this.form.id}` : '{{ route('human-capital.performance.pip.store') }}';
        },

        switchTab(tab) {
            this.currentTab = tab;
        },

        openAdd() {
            this.mode = 'create';
            this.form = this.defaultForm(this.currentTab);
            this.syncEmployee();
            this.showPanel = true;
        },

        openView(row) {
            this.mode = 'view';
            this.form = this.normalizeForm(this.currentTab, row);
            this.syncEmployee();
            this.showPanel = true;
        },

        openEdit(row) {
            this.mode = 'edit';
            this.form = this.normalizeForm(this.currentTab, row);
            this.syncEmployee();
            this.showPanel = true;
        },

        openComment(row) {
            this.mode = 'comment';
            this.form = this.normalizeForm(this.currentTab, row);
            this.syncEmployee();
            this.showPanel = true;
        },

        closePanel() {
            this.showPanel = false;
        },

        defaultForm(tab) {
            const employee = this.currentEmployee || {};
            const base = {
                id: null,
                employee_id: employee.id || '',
                employee_name: employee.full_name || '',
                position: employee.position || '',
                department: employee.department || '',
                supervisor_name: '',
                employee_comments: '',
            };

            if (tab === 'evaluation') {
                return {
                    ...base,
                    evaluation_period_start: '',
                    evaluation_period_end: '',
                    evaluation_date: new Date().toISOString().slice(0, 10),
                    key_strengths: '',
                    areas_for_improvement: '',
                    supervisor_recommendations: [],
                    action_plan: this.blankRows(['improvement_area', 'required_action', 'target_date', 'responsible_person']),
                    evaluated_by_name: '',
                    reviewed_by_name: '',
                    ...Object.fromEntries(this.criteria.flatMap(c => [[c.key, ''], [c.remarkKey, '']]))
                };
            }

            if (tab === 'pip') {
                return {
                    ...base,
                    start_date: new Date().toISOString().slice(0, 10),
                    target_completion_date: '',
                    status: 'Ongoing',
                    notes: '',
                    improvement_areas: this.blankRows(['area', 'required_action', 'target_date', 'responsible_person']),
                };
            }

            return base;
        },

        normalizeForm(tab, row) {
            const form = { ...this.defaultForm(tab), ...row };
            if (tab === 'evaluation') {
                form.supervisor_recommendations = Array.isArray(form.supervisor_recommendations) ? form.supervisor_recommendations : [];
                form.action_plan = this.normalizeRows(form.action_plan, ['improvement_area', 'required_action', 'target_date', 'responsible_person']);
            }
            if (tab === 'pip') form.improvement_areas = this.normalizeRows(form.improvement_areas, ['area', 'required_action', 'target_date', 'responsible_person']);
            return form;
        },

        blankRows(keys) {
            return [0, 1, 2].map(index => ({ _key: `${Date.now()}-${index}`, ...Object.fromEntries(keys.map(key => [key, ''])) }));
        },

        normalizeRows(rows, keys) {
            const normalized = Array.isArray(rows) && rows.length ? rows : this.blankRows(keys);
            return normalized.map((row, index) => ({ _key: row._key || `${Date.now()}-${index}`, ...Object.fromEntries(keys.map(key => [key, row?.[key] || ''])) }));
        },

        syncEmployee() {
            const employee = this.employees.find(item => String(item.id) === String(this.form.employee_id)) || this.currentEmployee || {};
            this.preview = employee;
            this.form.employee_name = employee.full_name || this.form.employee_name || '';
            this.form.position = employee.position || this.form.position || '';
            this.form.department = employee.department || this.form.department || '';
        },

        displayRef(row) {
            const prefix = this.currentTab === 'evaluation' ? 'PERF' : 'PIP';
            return row?.id ? `${prefix}-${String(row.id).padStart(5, '0')}` : 'Auto-generated';
        },

        mainColumnValue(row) {
            if (this.currentTab === 'evaluation') return row.supervisor_name || '-';
            return (row.improvement_areas || [])[0]?.area || '-';
        },

        recordDate(row) {
            if (this.currentTab === 'evaluation') return row.evaluation_date || '-';
            return row.start_date || '-';
        },

        statusValue(row) {
            if (this.currentTab === 'evaluation') return row.overall_performance_rating || 'Unrated';
            return row.status || '-';
        },

        statusClass(row) {
            const value = this.statusValue(row);
            const map = {
                Excellent: 'bg-green-100 text-green-700',
                'Very Good': 'bg-blue-100 text-blue-700',
                Satisfactory: 'bg-gray-100 text-gray-700',
                'Needs Improvement': 'bg-yellow-100 text-yellow-700',
                Unsatisfactory: 'bg-red-100 text-red-700',
                Ongoing: 'bg-blue-100 text-blue-700',
                Escalated: 'bg-red-100 text-red-700',
                Completed: 'bg-green-100 text-green-700',
                Discontinued: 'bg-gray-100 text-gray-700',
            };
            return map[value] || 'bg-gray-100 text-gray-700';
        },

        deleteAction(row) {
            const base = '{{ url('/human-capital/performance') }}';
            if (this.currentTab === 'evaluation') return `${base}/evaluation/${row.id}`;
            return `${base}/pip/${row.id}`;
        },

        totalScore() {
            return this.criteria.reduce((sum, c) => sum + Number(this.form[c.key] || 0), 0);
        },

        averageRating() {
            return (this.totalScore() / this.criteria.length).toFixed(2);
        },

        overallRating() {
            const avg = Number(this.averageRating());
            if (avg >= 4.5) return 'Excellent';
            if (avg >= 3.5) return 'Very Good';
            if (avg >= 2.5) return 'Satisfactory';
            if (avg >= 1.5) return 'Needs Improvement';
            return 'Unsatisfactory';
        },

        periodText() {
            const start = this.form.evaluation_period_start || '';
            const end = this.form.evaluation_period_end || '';
            return start || end ? `${start} - ${end}` : '-';
        },

        recommendationText() {
            return (this.form.supervisor_recommendations || []).length
                ? this.form.supervisor_recommendations.join(', ')
                : '-';
        },
    };
}
</script>
@endpush
@endsection
