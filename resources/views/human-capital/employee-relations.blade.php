@extends('layouts.app')

@section('content')
@php
    $companyHeader = $companyHeader ?? [
        'logo_url' => asset('images/jk-logo.png'),
        'company_name' => 'JOHN KELLY & COMPANY (JK&C INC)',
        'company_address' => '3F Cebu Holdings Center Cebu Business Park, Cebu City, Philippines, 6000',
    ];
@endphp
<div class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col" x-data="employeeRelationsPage()">
    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0">
        <div class="flex items-center justify-between px-5 py-4 border-b shrink-0 gap-4">
            <div>
                <p class="text-xs font-bold text-blue-600 uppercase tracking-wider">Human Capital</p>
                <h1 class="text-lg font-semibold text-gray-900">Employee Relations</h1>
                <p class="text-xs text-gray-500">Incident reports, employee grievances, and mediation / resolution records.</p>
            </div>

            <button
                type="button"
                @click="openAdd()"
                @disabled(!$canManageRelations && !$currentEmployee)
                class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white px-6 py-2 rounded-lg text-sm font-semibold"
            >
                + Add New
            </button>
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

        @if(!$canManageRelations && !$currentEmployee)
            <div class="mx-5 mt-4 px-4 py-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm">
                No employee profile is linked to your user email yet. Please contact Human Capital before filing a relation form.
            </div>
        @endif

        <div class="p-5 flex-grow overflow-hidden">
            <div class="border rounded-xl h-full overflow-auto bg-white">
                <table class="w-full text-sm min-w-[1100px] border-collapse">
                    <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                        <tr>
                            <th class="p-3 text-left">Reference No.</th>
                            <th class="p-3 text-left">Form</th>
                            <th class="p-3 text-left">Employee</th>
                            <th class="p-3 text-left">Subject</th>
                            <th class="p-3 text-left">Filed Date</th>
                            <th class="p-3 text-left">Status</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="relations.length === 0">
                            <tr>
                                <td colspan="7" class="p-10 text-center text-gray-400">
                                    No employee relations records yet. Click + Add New to create one.
                                </td>
                            </tr>
                        </template>

                        <template x-for="relation in relations" :key="relation.id">
                            <tr class="border-t hover:bg-gray-50">
                                <td class="p-3 font-semibold text-blue-700" x-text="relation.reference_no || '-'"></td>
                                <td class="p-3 text-gray-700" x-text="relation.form_type_label"></td>
                                <td class="p-3">
                                    <div class="font-medium text-gray-900" x-text="relation.employee_name || '-'"></div>
                                    <div class="text-xs text-gray-500" x-text="relation.employee_code || '-'"></div>
                                </td>
                                <td class="p-3 text-gray-700" x-text="relation.subject || '-'"></td>
                                <td class="p-3 text-gray-700" x-text="relation.filed_at || '-'"></td>
                                <td class="p-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold" :class="statusClass(relation.status)" x-text="relation.status || 'Pending'"></span>
                                </td>
                                <td class="p-3 text-right whitespace-nowrap">
                                    <button type="button" @click="openView(relation)" class="text-indigo-600 hover:underline text-xs font-semibold mr-3">View</button>
                                    <button type="button" @click="openEdit(relation)" class="text-blue-600 hover:underline text-xs font-semibold mr-3" x-show="canManageRelations || relation.status === 'Pending'">Edit</button>

                                    <form :action="`{{ url('/human-capital/employee-relations') }}/${relation.id}/approve`" method="POST" class="inline" x-show="canManageRelations && relation.status !== 'Resolved'">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:underline text-xs font-semibold mr-3">Resolve</button>
                                    </form>

                                    <form :action="`{{ url('/human-capital/employee-relations') }}/${relation.id}/reject`" method="POST" class="inline" x-show="canManageRelations && relation.status !== 'Closed'">
                                        @csrf
                                        <button type="submit" class="text-orange-600 hover:underline text-xs font-semibold mr-3">Close</button>
                                    </form>

                                    <form :action="`{{ url('/human-capital/employee-relations') }}/${relation.id}`" method="POST" class="inline" x-show="canManageRelations" onsubmit="return confirm('Delete this employee relations record?')">
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
                    <p class="text-xs text-gray-500" x-text="form.reference_no || formTypeLabel(form.form_type)"></p>
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
                            <img src="{{ $companyHeader['logo_url'] }}" onerror="this.style.display='none';" class="h-24 mx-auto mb-2 object-contain" alt="Company Logo">
                            <p class="mt-2 text-[12px] font-bold uppercase">{{ $companyHeader['company_name'] }}</p>
                            <p class="mt-1 text-[11px] font-semibold">{{ $companyHeader['company_address'] }}</p>
                            <p class="mt-1">Form Code: ERF-F001 | Version: 1.0 | Effective Date: {{ now()->format('F j, Y') }} | Issued by: Human Capital</p>
                        </div>

                        <div class="bg-blue-700 text-white px-3 py-2 font-bold uppercase tracking-widest text-sm mb-3 rounded-sm" x-text="formTypeLabel(form.form_type)"></div>

                        <div class="grid grid-cols-2 gap-2 mb-3">
                            <div><span class="font-bold">Reference No:</span> <span x-text="form.reference_no || 'Auto-generated'"></span></div>
                            <div><span class="font-bold">Status:</span> <span x-text="form.status || 'Pending'"></span></div>
                            <div><span class="font-bold">Filed Date:</span> <span x-text="form.filed_at || '{{ now()->format('Y-m-d') }}'"></span></div>
                            <div><span class="font-bold">Subject:</span> <span x-text="form.subject || '-'"></span></div>
                        </div>

                        <div class="section-title">A. Employee Information</div>
                        <table class="preview-table">
                            <tr>
                                <td><b>Employee Name:</b> <span x-text="preview.full_name || form.employee_name || '-'"></span></td>
                                <td><b>Employee ID:</b> <span x-text="preview.employee_code || form.employee_code || '-'"></span></td>
                            </tr>
                            <tr>
                                <td><b>Position:</b> <span x-text="preview.position || form.position || '-'"></span></td>
                                <td><b>Department:</b> <span x-text="preview.department || form.department || '-'"></span></td>
                            </tr>
                        </table>

                        <template x-if="form.form_type === 'incident_report'">
                            <div>
                                <div class="section-title">B. Incident Details</div>
                                <table class="preview-table">
                                    <tr><td><b>Date / Time:</b> <span x-text="form.incident_date || '-'"></span> <span x-text="form.incident_time || ''"></span></td><td><b>Location:</b> <span x-text="form.incident_location || '-'"></span></td></tr>
                                    <tr><td><b>Incident Type:</b> <span x-text="form.incident_type || '-'"></span></td><td><b>Persons Involved:</b> <span x-text="form.persons_involved || '-'"></span></td></tr>
                                    <tr><td colspan="2"><b>Witnesses:</b> <span x-text="form.witnesses || '-'"></span></td></tr>
                                    <tr><td colspan="2"><b>Description:</b><br><span x-text="form.incident_description || '-'"></span></td></tr>
                                    <tr><td colspan="2"><b>Immediate Action Taken:</b><br><span x-text="form.immediate_action || '-'"></span></td></tr>
                                    <tr><td colspan="2"><b>Injury / Damage:</b><br><span x-text="form.injury_or_damage || '-'"></span></td></tr>
                                </table>
                            </div>
                        </template>

                        <template x-if="form.form_type === 'employee_grievance'">
                            <div>
                                <div class="section-title">B. Grievance Details</div>
                                <table class="preview-table">
                                    <tr><td><b>Grievance Date:</b> <span x-text="form.grievance_date || '-'"></span></td><td><b>Category:</b> <span x-text="form.grievance_category || '-'"></span></td></tr>
                                    <tr><td><b>Filed Against / Area:</b> <span x-text="form.grievance_against || '-'"></span></td><td><b>Confidential:</b> <span x-text="form.is_confidential ? 'Yes' : 'No'"></span></td></tr>
                                    <tr><td colspan="2"><b>Summary:</b><br><span x-text="form.grievance_summary || '-'"></span></td></tr>
                                    <tr><td colspan="2"><b>Prior Steps Taken:</b><br><span x-text="form.prior_steps_taken || '-'"></span></td></tr>
                                    <tr><td colspan="2"><b>Desired Resolution:</b><br><span x-text="form.desired_resolution || '-'"></span></td></tr>
                                </table>
                            </div>
                        </template>

                        <template x-if="form.form_type === 'mediation_resolution'">
                            <div>
                                <div class="section-title">B. Mediation / Resolution Details</div>
                                <table class="preview-table">
                                    <tr><td><b>Mediation Date:</b> <span x-text="form.mediation_date || '-'"></span></td><td><b>Mediator:</b> <span x-text="form.mediator || '-'"></span></td></tr>
                                    <tr><td colspan="2"><b>Parties Involved:</b> <span x-text="form.parties_involved || '-'"></span></td></tr>
                                    <tr><td colspan="2"><b>Issue Summary:</b><br><span x-text="form.issue_summary || '-'"></span></td></tr>
                                    <tr><td colspan="2"><b>Employee Position:</b><br><span x-text="form.employee_position_statement || '-'"></span></td></tr>
                                    <tr><td colspan="2"><b>Management Position:</b><br><span x-text="form.management_position_statement || '-'"></span></td></tr>
                                    <tr><td colspan="2"><b>Resolution Agreement:</b><br><span x-text="form.resolution_agreement || '-'"></span></td></tr>
                                    <tr><td colspan="2"><b>Action Items:</b><br><span x-text="form.action_items || '-'"></span></td></tr>
                                    <tr><td colspan="2"><b>Follow-up Date:</b> <span x-text="form.follow_up_date || '-'"></span></td></tr>
                                </table>
                            </div>
                        </template>

                        <div class="section-title">C. HR Review</div>
                        <table class="preview-table">
                            <tr><td><b>HR Remarks:</b><br><span x-text="form.hr_remarks || '-'"></span></td></tr>
                            <tr><td><b>Attachments:</b> <span x-text="(form.attachment_paths || []).length ? (form.attachment_paths || []).map(a => a.original_name).join(', ') : 'None'"></span></td></tr>
                        </table>

                        <div class="grid grid-cols-2 gap-10 mt-12 text-center">
                            <div><div class="border-b border-gray-700 h-8"></div><p class="mt-1 font-bold">Employee Signature</p></div>
                            <div><div class="border-b border-gray-700 h-8"></div><p class="mt-1 font-bold">HR / Authorized Reviewer</p></div>
                        </div>
                    </div>
                </div>

                <div class="min-h-0 overflow-auto bg-white">
                    <form :action="formAction" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                        @csrf
                        <template x-if="isEdit"><input type="hidden" name="_method" value="PUT"></template>

                        <div class="rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-4 py-2 bg-blue-700 text-white text-xs font-bold uppercase tracking-widest">Form Type</div>
                            <div class="p-4 grid grid-cols-1 gap-3">
                                <select name="form_type" x-model="form.form_type" :disabled="isView" required class="w-full border rounded-lg px-3 py-2 text-sm">
                                    <option value="incident_report">Incident Report Form</option>
                                    <option value="employee_grievance">Employee Grievance Form</option>
                                    <option value="mediation_resolution">Mediation / Resolution Form</option>
                                </select>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-4 py-2 bg-blue-700 text-white text-xs font-bold uppercase tracking-widest">Employee Information</div>
                            <div class="p-4 grid grid-cols-2 gap-3">
                                @if($canManageRelations)
                                    <div class="col-span-2">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Employee</label>
                                        <select name="employee_id" x-model="form.employee_id" @change="syncEmployee()" :disabled="isView" required class="w-full border rounded-lg px-3 py-2 text-sm">
                                            <option value="">Select employee</option>
                                            <template x-for="employee in employees" :key="employee.id">
                                                <option :value="employee.id" x-text="employee.full_name + ' - ' + (employee.employee_code || 'No ID')"></option>
                                            </template>
                                        </select>
                                    </div>
                                @else
                                    <input type="hidden" name="employee_id" value="{{ $currentEmployee['id'] ?? '' }}">
                                @endif
                                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Filed Date</label><input type="date" name="filed_at" x-model="form.filed_at" :readonly="isView" class="w-full border rounded-lg px-3 py-2 text-sm"></div>
                                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Subject</label><input type="text" name="subject" x-model="form.subject" :readonly="isView" required class="w-full border rounded-lg px-3 py-2 text-sm"></div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 overflow-hidden" x-show="form.form_type === 'incident_report'">
                            <div class="px-4 py-2 bg-blue-700 text-white text-xs font-bold uppercase tracking-widest">Incident Report Fields</div>
                            <div class="p-4 grid grid-cols-2 gap-3">
                                <input type="date" name="incident_date" x-model="form.incident_date" :readonly="isView" class="border rounded-lg px-3 py-2 text-sm">
                                <input type="time" name="incident_time" x-model="form.incident_time" :readonly="isView" class="border rounded-lg px-3 py-2 text-sm">
                                <input type="text" name="incident_location" x-model="form.incident_location" :readonly="isView" placeholder="Location" class="border rounded-lg px-3 py-2 text-sm">
                                <input type="text" name="incident_type" x-model="form.incident_type" :readonly="isView" placeholder="Incident type" class="border rounded-lg px-3 py-2 text-sm">
                                <textarea name="persons_involved" x-model="form.persons_involved" :readonly="isView" rows="2" placeholder="Persons involved" class="col-span-2 border rounded-lg px-3 py-2 text-sm"></textarea>
                                <textarea name="witnesses" x-model="form.witnesses" :readonly="isView" rows="2" placeholder="Witnesses" class="col-span-2 border rounded-lg px-3 py-2 text-sm"></textarea>
                                <textarea name="incident_description" x-model="form.incident_description" :readonly="isView" rows="4" placeholder="Detailed description of incident" class="col-span-2 border rounded-lg px-3 py-2 text-sm"></textarea>
                                <textarea name="immediate_action" x-model="form.immediate_action" :readonly="isView" rows="3" placeholder="Immediate action taken" class="col-span-2 border rounded-lg px-3 py-2 text-sm"></textarea>
                                <textarea name="injury_or_damage" x-model="form.injury_or_damage" :readonly="isView" rows="3" placeholder="Injury, damage, or impact" class="col-span-2 border rounded-lg px-3 py-2 text-sm"></textarea>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 overflow-hidden" x-show="form.form_type === 'employee_grievance'">
                            <div class="px-4 py-2 bg-blue-700 text-white text-xs font-bold uppercase tracking-widest">Employee Grievance Fields</div>
                            <div class="p-4 grid grid-cols-2 gap-3">
                                <input type="date" name="grievance_date" x-model="form.grievance_date" :readonly="isView" class="border rounded-lg px-3 py-2 text-sm">
                                <input type="text" name="grievance_category" x-model="form.grievance_category" :readonly="isView" placeholder="Category" class="border rounded-lg px-3 py-2 text-sm">
                                <input type="text" name="grievance_against" x-model="form.grievance_against" :readonly="isView" placeholder="Against / department / area" class="col-span-2 border rounded-lg px-3 py-2 text-sm">
                                <label class="col-span-2 flex items-center gap-2 text-sm"><input type="checkbox" name="is_confidential" value="1" x-model="form.is_confidential" :disabled="isView"> Mark as confidential</label>
                                <textarea name="grievance_summary" x-model="form.grievance_summary" :readonly="isView" rows="4" placeholder="Grievance summary" class="col-span-2 border rounded-lg px-3 py-2 text-sm"></textarea>
                                <textarea name="prior_steps_taken" x-model="form.prior_steps_taken" :readonly="isView" rows="3" placeholder="Prior steps taken" class="col-span-2 border rounded-lg px-3 py-2 text-sm"></textarea>
                                <textarea name="desired_resolution" x-model="form.desired_resolution" :readonly="isView" rows="3" placeholder="Desired resolution" class="col-span-2 border rounded-lg px-3 py-2 text-sm"></textarea>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 overflow-hidden" x-show="form.form_type === 'mediation_resolution'">
                            <div class="px-4 py-2 bg-blue-700 text-white text-xs font-bold uppercase tracking-widest">Mediation / Resolution Fields</div>
                            <div class="p-4 grid grid-cols-2 gap-3">
                                <input type="date" name="mediation_date" x-model="form.mediation_date" :readonly="isView" class="border rounded-lg px-3 py-2 text-sm">
                                <input type="text" name="mediator" x-model="form.mediator" :readonly="isView" placeholder="Mediator" class="border rounded-lg px-3 py-2 text-sm">
                                <textarea name="parties_involved" x-model="form.parties_involved" :readonly="isView" rows="2" placeholder="Parties involved" class="col-span-2 border rounded-lg px-3 py-2 text-sm"></textarea>
                                <textarea name="issue_summary" x-model="form.issue_summary" :readonly="isView" rows="3" placeholder="Issue summary" class="col-span-2 border rounded-lg px-3 py-2 text-sm"></textarea>
                                <textarea name="employee_position_statement" x-model="form.employee_position_statement" :readonly="isView" rows="3" placeholder="Employee position statement" class="col-span-2 border rounded-lg px-3 py-2 text-sm"></textarea>
                                <textarea name="management_position_statement" x-model="form.management_position_statement" :readonly="isView" rows="3" placeholder="Management position statement" class="col-span-2 border rounded-lg px-3 py-2 text-sm"></textarea>
                                <textarea name="resolution_agreement" x-model="form.resolution_agreement" :readonly="isView" rows="3" placeholder="Resolution / agreement" class="col-span-2 border rounded-lg px-3 py-2 text-sm"></textarea>
                                <textarea name="action_items" x-model="form.action_items" :readonly="isView" rows="3" placeholder="Action items / responsibilities" class="col-span-2 border rounded-lg px-3 py-2 text-sm"></textarea>
                                <input type="date" name="follow_up_date" x-model="form.follow_up_date" :readonly="isView" class="border rounded-lg px-3 py-2 text-sm">
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-4 py-2 bg-blue-700 text-white text-xs font-bold uppercase tracking-widest">Attachments & HR Review</div>
                            <div class="p-4 grid grid-cols-1 gap-3">
                                <input type="file" name="attachments[]" multiple :disabled="isView" class="border rounded-lg px-3 py-2 text-sm">
                                <textarea name="hr_remarks" x-model="form.hr_remarks" :readonly="isView || !canManageRelations" rows="3" placeholder="HR remarks" class="border rounded-lg px-3 py-2 text-sm"></textarea>
                                <select name="status" x-model="form.status" x-show="canManageRelations" :disabled="isView" class="border rounded-lg px-3 py-2 text-sm">
                                    <option>Pending</option>
                                    <option>Under Review</option>
                                    <option>For Mediation</option>
                                    <option>Resolved</option>
                                    <option>Closed</option>
                                </select>
                            </div>
                        </div>

                        <div class="pt-2 border-t flex justify-end gap-3 pb-2">
                            <button type="button" @click="closePanel()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                            <button type="submit" x-show="!isView" class="px-5 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold">Save Form</button>
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
function employeeRelationsPage() {
    return {
        employees: @json($employees),
        relations: @json($relations),
        canManageRelations: @json($canManageRelations),
        currentEmployee: @json($currentEmployee),
        showPanel: false,
        mode: 'create',
        form: {},
        preview: {},

        get isView() {
            return this.mode === 'view';
        },

        get isEdit() {
            return this.mode === 'edit';
        },

        get panelTitle() {
            if (this.isView) return 'View ' + this.formTypeLabel(this.form.form_type);
            if (this.isEdit) return 'Edit ' + this.formTypeLabel(this.form.form_type);
            return 'New Employee Relations Form';
        },

        get formAction() {
            if (this.isEdit && this.form.id) {
                return `{{ url('/human-capital/employee-relations') }}/${this.form.id}`;
            }

            return `{{ route('human-capital.employee-relations.store') }}`;
        },

        defaultForm() {
            return {
                form_type: 'incident_report',
                reference_no: '',
                employee_id: this.currentEmployee?.id || '',
                employee_code: this.currentEmployee?.employee_code || '',
                employee_name: this.currentEmployee?.full_name || '',
                position: this.currentEmployee?.position || '',
                department: this.currentEmployee?.department || '',
                filed_at: new Date().toISOString().slice(0, 10),
                subject: '',
                status: 'Pending',
                attachment_paths: [],
                hr_remarks: '',
                is_confidential: false,
            };
        },

        openAdd() {
            this.mode = 'create';
            this.form = this.defaultForm();
            this.syncEmployee();
            this.showPanel = true;
        },

        openView(relation) {
            this.mode = 'view';
            this.form = this.flattenRelation(relation);
            this.syncEmployee();
            this.showPanel = true;
        },

        openEdit(relation) {
            this.mode = 'edit';
            this.form = this.flattenRelation(relation);
            this.syncEmployee();
            this.showPanel = true;
        },

        closePanel() {
            this.showPanel = false;
        },

        flattenRelation(relation) {
            return {
                ...this.defaultForm(),
                ...relation,
                ...(relation.details || {}),
            };
        },

        syncEmployee() {
            const employee = this.employees.find(item => String(item.id) === String(this.form.employee_id)) || this.currentEmployee;
            this.preview = employee || {};

            if (employee) {
                this.form.employee_code = employee.employee_code || '';
                this.form.employee_name = employee.full_name || '';
                this.form.position = employee.position || '';
                this.form.department = employee.department || '';
            }
        },

        formTypeLabel(type) {
            const labels = {
                incident_report: 'Incident Report Form',
                employee_grievance: 'Employee Grievance Form',
                mediation_resolution: 'Mediation / Resolution Form',
            };

            return labels[type] || 'Employee Relations Form';
        },

        statusClass(status) {
            const map = {
                Pending: 'bg-yellow-100 text-yellow-700',
                'Under Review': 'bg-blue-100 text-blue-700',
                'For Mediation': 'bg-indigo-100 text-indigo-700',
                Resolved: 'bg-green-100 text-green-700',
                Closed: 'bg-gray-100 text-gray-700',
            };

            return map[status] || 'bg-gray-100 text-gray-700';
        },
    };
}
</script>
@endpush
@endsection
