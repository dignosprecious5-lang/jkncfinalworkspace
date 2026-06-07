@extends('layouts.app')

@section('content')
@php
    $companyHeader = $companyHeader ?? [
        'logo_url' => asset('images/jk-logo.png'),
        'company_name' => 'JOHN KELLY & COMPANY (JK&C INC)',
        'company_address' => '3F Cebu Holdings Center Cebu Business Park, Cebu City, Philippines, 6000',
    ];
@endphp
<div
    class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col"
    x-data="obfPage()"
>
    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0">
        <div class="flex items-center justify-between px-5 py-4 border-b shrink-0 gap-4">
            <div>
                <p class="text-xs font-bold text-blue-600 uppercase tracking-wider">Human Capital</p>
                <h1 class="text-lg font-semibold text-gray-900">Official Business Trip Form</h1>
                <p class="text-xs text-gray-500">OBF records, employee fill-up form, and HR approval.</p>
            </div>

            <button
                type="button"
                @click="openAdd()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-semibold"
            >
                + Add New
            </button>
        </div>

        @if (session('success'))
            <div class="mx-5 mt-4 px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm font-semibold">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mx-5 mt-4 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                <p class="font-bold mb-1">Please fix the following:</p>
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="p-5 flex-grow overflow-hidden">
            <div class="border rounded-xl h-full overflow-auto bg-white">
                <table class="w-full text-sm min-w-[1200px] border-collapse">
                    <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                        <tr>
                            <th class="p-3 text-left">Reference No.</th>
                            <th class="p-3 text-left">Employee</th>
                            <th class="p-3 text-left">Position</th>
                            <th class="p-3 text-left">Destination</th>
                            <th class="p-3 text-left">Date From</th>
                            <th class="p-3 text-left">Date To</th>
                            <th class="p-3 text-left">Trip Type</th>
                            <th class="p-3 text-left">Status</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template x-if="trips.length === 0">
                            <tr>
                                <td colspan="9" class="p-10 text-center text-gray-400">
                                    No OBF records yet. Click + Add New to create one.
                                </td>
                            </tr>
                        </template>

                        <template x-for="trip in trips" :key="trip.id">
                            <tr class="border-t hover:bg-gray-50">
                                <td class="p-3 font-semibold text-blue-700" x-text="trip.ob_reference_no || '-'"></td>
                                <td class="p-3">
                                    <div class="font-medium text-gray-900" x-text="trip.employee_name || '-'"></div>
                                    <div class="text-xs text-gray-500" x-text="trip.employee_code || '-'"></div>
                                </td>
                                <td class="p-3 text-gray-700" x-text="trip.position || '-'"></td>
                                <td class="p-3 text-gray-700" x-text="trip.destination || '-'"></td>
                                <td class="p-3 text-gray-700" x-text="trip.date_from || '-'"></td>
                                <td class="p-3 text-gray-700" x-text="trip.date_to || '-'"></td>
                                <td class="p-3 text-gray-700" x-text="trip.trip_type || '-'"></td>
                                <td class="p-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold" :class="statusClass(trip.status)" x-text="trip.status || 'Pending'"></span>
                                </td>
                                <td class="p-3 text-right whitespace-nowrap">
                                    <button type="button" @click="openView(trip)" class="text-indigo-600 hover:underline text-xs font-semibold mr-3">View</button>
                                    <button type="button" @click="openEdit(trip)" class="text-blue-600 hover:underline text-xs font-semibold mr-3">Edit</button>

                                    <form :action="`{{ url('/human-capital/obf') }}/${trip.id}/approve`" method="POST" class="inline" x-show="canManageObf && trip.status === 'Pending'">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:underline text-xs font-semibold mr-3">Approve</button>
                                    </form>

                                    <form :action="`{{ url('/human-capital/obf') }}/${trip.id}/reject`" method="POST" class="inline" x-show="canManageObf && trip.status === 'Pending'">
                                        @csrf
                                        <button type="submit" class="text-orange-600 hover:underline text-xs font-semibold mr-3">Reject</button>
                                    </form>

                                    <form
                                        :action="`{{ url('/human-capital/obf') }}/${trip.id}`"
                                        method="POST"
                                        class="inline"
                                        onsubmit="return confirm('Delete this OBF record?')"
                                    >
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

    {{-- PDS / MRF STYLE SLIDING PANEL: LEFT PREVIEW + RIGHT FILL-UP FORM --}}
    <div
        x-show="showPanel"
        x-transition.opacity
        class="fixed inset-0 z-50 bg-black/40 flex justify-end"
        style="display: none;"
        @click.self="closePanel()"
    >
        <div
            x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="w-screen max-w-none h-full bg-white shadow-xl flex flex-col"
        >
            <div class="px-6 py-4 border-b bg-white flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold uppercase tracking-widest text-gray-900" x-text="panelTitle"></h2>
                    <p class="text-xs text-gray-500" x-text="form.ob_reference_no || 'Official Business Travel Form'"></p>
                </div>

                <button type="button" @click="closePanel()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>

            <div class="flex-1 min-h-0 grid grid-cols-[58%_42%] gap-0 bg-gray-50">
                {{-- LEFT LIVE PREVIEW --}}
                <div class="min-h-0 flex flex-col border-r bg-gray-100">
                    <div class="shrink-0 flex items-center justify-between border-b border-gray-200 bg-gray-100 px-5 py-4">
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Live Preview</p>
                        <button type="button" onclick="window.print()" class="px-3 py-2 border rounded-lg text-xs font-semibold text-gray-700 bg-white">
                            Download PDF
                        </button>
                    </div>

                    {{-- A4 PAPER PREVIEW --}}
                    <div class="flex-1 min-h-0 overflow-auto p-5">
                    <div class="obf-paper print-area">
                        <div class="obf-letterhead">
                            <img src="{{ $companyHeader['logo_url'] }}" onerror="this.style.display='none';" class="obf-logo" alt="Company Logo">
                            <p class="obf-company-name">{{ $companyHeader['company_name'] }}</p>
                            <p>{{ $companyHeader['company_address'] }}</p>
                        </div>

                        <div class="obf-title">
                            Official Business Travel Form
                        </div>

                        <div class="obf-reference">
                            <div><b>OB Reference No.:</b><strong x-text="form.ob_reference_no || 'Auto-generated'"></strong></div>
                            <div><b>Date & Time:</b><strong>{{ now()->format('n/j/Y h:i:s A') }}</strong></div>
                            <div class="obf-config">Config loaded</div>
                        </div>

                        <div class="section-title">A. Travel Information</div>
                        <table class="preview-table">
                            <tr>
                                <td class="w-1/2"><b>Primary Destination:</b> <span x-text="form.destination || '-'"></span></td>
                                <td><b>Additional Stops:</b> <span x-text="form.additional_stops || 'None'"></span></td>
                            </tr>
                            <tr>
                                <td colspan="2"><b>Maps:</b> <span>Open in Google Maps    Tip: type a place/address and click Maps.</span></td>
                            </tr>
                            <tr>
                                <td><b>Travel Purpose:</b> <span x-text="purposeText()"></span></td>
                                <td><b>If Others, specify:</b> <span x-text="form.purpose_other || '-'"></span></td>
                            </tr>
                            <tr>
                                <td><b>Departure Date & Time:</b> <span x-text="dateTimeText(form.date_from, form.departure_time)"></span></td>
                                <td><b>Return Date & Time:</b> <span x-text="dateTimeText(form.date_to, form.return_time)"></span></td>
                            </tr>
                            <tr>
                                <td colspan="2"><b>Purpose Details:</b> <span x-text="form.purpose || '-'"></span></td>
                            </tr>
                        </table>

                        <div class="section-title">B. Personnel Details</div>
                        <table class="preview-table">
                            <tr>
                                <td><b>Employee Name:</b> <span x-text="preview.full_name || form.employee_name || '-'"></span></td>
                                <td><b>Email Address:</b> <span x-text="preview.email || '-'"></span></td>
                            </tr>
                            <tr>
                                <td><b>Employee ID:</b> <span x-text="preview.employee_code || form.employee_code || '-'"></span></td>
                                <td><b>Contact Number:</b> <span x-text="preview.phone_number || '-'"></span></td>
                            </tr>
                            <tr>
                                <td><b>Position:</b> <span x-text="preview.position || form.position || '-'"></span></td>
                                <td><b>Department / Unit:</b> <span x-text="preview.department || form.department || '-'"></span></td>
                            </tr>
                            <tr>
                                <td><b>Immediate Superior:</b> <span x-text="form.immediate_superior || '-'"></span></td>
                                <td><b>Superior Email:</b> <span x-text="form.superior_email || '-'"></span></td>
                            </tr>
                            <tr>
                                <td><b>Nature of Travel:</b> <span x-text="form.nature_of_travel || '-'"></span></td>
                                <td><b>Team Members:</b> <span x-text="teamText()"></span></td>
                            </tr>
                        </table>

                        <div class="section-title">C. Client & Billability Section</div>
                        <table class="preview-table">
                            <tr>
                                <td><b>Is this travel for a client?</b> <span x-text="form.is_client_travel == '1' ? 'Yes' : 'No'"></span></td>
                                <td><b>Client Type:</b> <span x-text="form.client_type || '-'"></span></td>
                            </tr>
                            <tr>
                                <td><b>Client ID:</b> <span x-text="form.client_id_no || '-'"></span></td>
                                <td><b>Client Name:</b> <span x-text="form.client_name || '-'"></span></td>
                            </tr>
                            <tr>
                                <td><b>Client Email:</b> <span x-text="form.client_email || '-'"></span></td>
                                <td><b>Client Contract Number:</b> <span x-text="form.client_contract_number || '-'"></span></td>
                            </tr>
                            <tr>
                                <td><b>Contract Type:</b> <span x-text="form.contract_type || '-'"></span></td>
                                <td><b>Travel Billability:</b> <span x-text="form.travel_billability || '-'"></span></td>
                            </tr>
                        </table>

                        <div class="section-title">D. Client Payment Details</div>
                        <table class="preview-table">
                            <tr>
                                <td><b>Will client pay?</b> <span x-text="form.client_payment_status || '-'"></span></td>
                                <td><b>What will client pay for?</b> <span x-text="clientPaymentText()"></span></td>
                            </tr>
                            <tr>
                                <td><b>If Others, specify:</b> <span x-text="form.client_payment_other || '-'"></span></td>
                                <td><b>Amount Client Will Pay:</b> ₱<span x-text="form.amount_client_will_pay || '0.00'"></span></td>
                            </tr>
                        </table>

                        <div class="section-title">E. Travel Credits</div>
                        <table class="preview-table">
                            <tr>
                                <td><b>Monthly Limit:</b> ₱<span x-text="form.travel_credit_monthly_limit || '-'"></span></td>
                                <td><b>Used This Month:</b> ₱<span x-text="form.travel_credit_used || '-'"></span></td>
                            </tr>
                            <tr>
                                <td><b>Needed For Trip:</b> ₱<span x-text="form.travel_credit_needed || '-'"></span></td>
                                <td><b>Remaining After Trip:</b> ₱<span x-text="form.travel_credit_remaining || '-'"></span></td>
                            </tr>
                        </table>

                        <div class="section-title">F. Mode of Transportation</div>
                        <table class="preview-table">
                            <tr>
                                <td><b>Transportation Mode:</b> <span x-text="form.transportation_mode || '-'"></span></td>
                                <td><b>Estimated Expenses:</b> ₱<span x-text="form.estimated_expenses || '0.00'"></span></td>
                            </tr>
                            <tr>
                                <td><b>Company Vehicle:</b> <span x-text="form.company_vehicle || '-'"></span></td>
                                <td><b>Assigned Driver:</b> <span x-text="form.assigned_driver || '-'"></span></td>
                            </tr>
                            <tr>
                                <td><b>Vehicle Details:</b> <span x-text="form.vehicle_details || '-'"></span></td>
                                <td><b>Plate Number:</b> <span x-text="form.plate_number || '-'"></span></td>
                            </tr>
                            <tr>
                                <td><b>Odometer:</b> <span x-text="form.odometer || '-'"></span></td>
                                <td><b>Fuel Level:</b> <span x-text="form.fuel_level || '-'"></span></td>
                            </tr>
                            <tr>
                                <td><b>Private Vehicle:</b> <span x-text="form.private_vehicle_details || '-'"></span></td>
                                <td><b>Public Transport:</b> <span x-text="form.public_transport_details || '-'"></span></td>
                            </tr>
                            <tr>
                                <td><b>Air Travel:</b> <span x-text="form.air_travel_details || '-'"></span></td>
                                <td><b>Sea Travel:</b> <span x-text="form.sea_travel_details || '-'"></span></td>
                            </tr>
                        </table>

                        <div class="section-title">G. Attachments</div>
                        <table class="preview-table">
                            <tr>
                                <td><b>Attachment Types:</b> <span x-text="attachmentText()"></span></td>
                                <td><b>If Others, specify:</b> <span x-text="form.attachment_other || '-'"></span></td>
                            </tr>
                            <tr>
                                <td colspan="2"><b>Remarks:</b> <span x-text="form.remarks || '-'"></span></td>
                            </tr>
                        </table>

                        <div class="grid grid-cols-3 gap-8 mt-10 text-center">
                            <div>
                                <div class="border-t border-gray-700 pt-1">Requester</div>
                            </div>
                            <div>
                                <div class="border-t border-gray-700 pt-1">Immediate Superior</div>
                            </div>
                            <div>
                                <div class="border-t border-gray-700 pt-1">Authorized Approver</div>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>

                {{-- RIGHT FILL-UP FORM --}}
                <div class="min-h-0 overflow-y-auto bg-white">
                    <div class="sticky top-0 z-20 bg-blue-700 text-white px-5 py-4">
                        <p class="text-xs font-bold uppercase tracking-widest">Fill Up Form</p>
                    </div>

                    <form
                        method="POST"
                        enctype="multipart/form-data"
                        :action="isEdit ? `{{ url('/human-capital/obf') }}/${form.id}` : `{{ url('/human-capital/obf') }}`"
                        class="p-5 space-y-6"
                    >
                        @csrf
                        <template x-if="isEdit">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <template x-if="isView">
                            <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
                                <p class="text-xs font-bold text-blue-600 uppercase tracking-wider">Viewing Mode</p>
                                <p class="text-sm text-blue-900">Status: <span class="font-bold" x-text="form.status"></span></p>
                            </div>
                        </template>

                        {{-- A --}}
                        <section class="form-section">
                            <h3 class="form-title">A. Travel Information</h3>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="label">Primary Destination <span class="text-red-500">*</span></label>
                                    <input name="destination" x-model="form.destination" :readonly="isView" required class="input">
                                </div>

                                <div>
                                    <label class="label">Trip Type</label>
                                    <select name="trip_type" x-model="form.trip_type" :disabled="isView" class="input">
                                        <option value="">Select trip type</option>
                                        <option>One-way</option>
                                        <option>Roundtrip</option>
                                        <option>Multi-stop</option>
                                    </select>
                                </div>

                                <div class="col-span-2">
                                    <label class="label">Additional Stops</label>
                                    <textarea name="additional_stops" x-model="form.additional_stops" :readonly="isView" rows="2" class="input"></textarea>
                                </div>

                                <div class="col-span-2">
                                    <label class="label">Travel Purpose Options</label>
                                    <div class="grid grid-cols-3 gap-2 text-sm">
                                        <template x-for="option in purposeOptionList" :key="option">
                                            <label class="check-card">
                                                <input type="checkbox" name="purpose_options[]" :value="option" x-model="form.purpose_options" :disabled="isView">
                                                <span x-text="option"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>

                                <div class="col-span-2">
                                    <label class="label">Purpose / Details <span class="text-red-500">*</span></label>
                                    <textarea name="purpose" x-model="form.purpose" :readonly="isView" required rows="4" class="input"></textarea>
                                </div>

                                <div class="col-span-2">
                                    <label class="label">If Others, specify</label>
                                    <input name="purpose_other" x-model="form.purpose_other" :readonly="isView" class="input">
                                </div>

                                <div>
                                    <label class="label">Departure Date <span class="text-red-500">*</span></label>
                                    <input type="date" name="date_from" x-model="form.date_from" :readonly="isView" required class="input">
                                </div>

                                <div>
                                    <label class="label">Return Date</label>
                                    <input type="date" name="date_to" x-model="form.date_to" :readonly="isView" class="input">
                                </div>

                                <div>
                                    <label class="label">Departure Time</label>
                                    <input type="time" name="departure_time" x-model="form.departure_time" :readonly="isView" class="input">
                                </div>

                                <div>
                                    <label class="label">Return Time</label>
                                    <input type="time" name="return_time" x-model="form.return_time" :readonly="isView" class="input">
                                </div>
                            </div>
                        </section>

                        {{-- B --}}
                        <section class="form-section">
                            <h3 class="form-title">B. Personnel Details</h3>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <label class="label">Employee <span class="text-red-500">*</span></label>
                                    <select name="employee_id" x-model="form.employee_id" @change="selectEmployee()" :disabled="isView || isEdit" required class="input bg-white disabled:bg-gray-100">
                                        <option value="">Select employee</option>
                                        <template x-for="employee in employees" :key="employee.id">
                                            <option :value="employee.id" x-text="`${employee.employee_code} - ${employee.full_name}`"></option>
                                        </template>
                                    </select>
                                </div>

                                <div><label class="label">Employee ID</label><input x-model="preview.employee_code" readonly class="input bg-gray-100"></div>
                                <div><label class="label">Email</label><input x-model="preview.email" readonly class="input bg-gray-100"></div>
                                <div><label class="label">Contact Number</label><input x-model="preview.phone_number" readonly class="input bg-gray-100"></div>
                                <div><label class="label">Position</label><input x-model="preview.position" readonly class="input bg-gray-100"></div>
                                <div><label class="label">Department / Unit</label><input x-model="preview.department" readonly class="input bg-gray-100"></div>

                                <div>
                                    <label class="label">Nature of Travel</label>
                                    <select name="nature_of_travel" x-model="form.nature_of_travel" :disabled="isView" class="input">
                                        <option value="">Select nature</option>
                                        <option>Individual</option>
                                        <option>Team</option>
                                    </select>
                                </div>

                                <div><label class="label">Immediate Superior</label><input name="immediate_superior" x-model="form.immediate_superior" :readonly="isView" class="input"></div>
                                <div><label class="label">Superior Email</label><input type="email" name="superior_email" x-model="form.superior_email" :readonly="isView" class="input"></div>

                                <div class="col-span-2">
                                    <label class="label">Team Members</label>
                                    <div class="grid grid-cols-3 gap-3">
                                        <template x-for="(member, index) in form.team_members" :key="index">
                                            <input name="team_members[]" x-model="form.team_members[index]" :readonly="isView" :placeholder="`Team member ${index + 1}`" class="input">
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </section>

                        {{-- C --}}
                        <section class="form-section">
                            <h3 class="form-title">C. Client & Billability Section</h3>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="label">Is this travel for a client?</label>
                                    <select name="is_client_travel" x-model="form.is_client_travel" @change="handleClientTravelChange()" :disabled="isView" class="input">
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
                                    </select>
                                </div>

                                <div x-show="form.is_client_travel == '1'" x-cloak>
                                    <label class="label">Select Client Contact</label>
                                    <select x-model="form.client_contact_id" @change="selectClientContact()" :disabled="isView" class="input">
                                        <option value="">Select contact from Contacts</option>
                                        <template x-for="contact in contacts" :key="contact.id">
                                            <option :value="contact.id" x-text="contact.label"></option>
                                        </template>
                                    </select>
                                    <p class="mt-1 text-[11px] text-gray-400">This auto-fills the client details below from the Contacts module.</p>
                                </div>

                                <div>
                                    <label class="label">Client Type</label>
                                    <select name="client_type" x-model="form.client_type" :disabled="isView" class="input">
                                        <option value="">Select client type</option>
                                        <option>Local</option>
                                        <option>International</option>
                                    </select>
                                </div>

                                <div><label class="label">Client ID / CIF No.</label><input name="client_id_no" x-model="form.client_id_no" :readonly="isView" class="input"></div>
                                <div><label class="label">Client Name</label><input name="client_name" x-model="form.client_name" :readonly="isView" class="input"></div>
                                <div><label class="label">Client Email</label><input type="email" name="client_email" x-model="form.client_email" :readonly="isView" class="input"></div>
                                <div><label class="label">Client Contact Number</label><input x-model="selectedClientPhone" readonly class="input bg-gray-100"></div>
                                <div><label class="label">Company / Business Name</label><input x-model="selectedClientCompany" readonly class="input bg-gray-100"></div>
                                <div><label class="label">Contract Number</label><input name="client_contract_number" x-model="form.client_contract_number" :readonly="isView" class="input"></div>
                                <div><label class="label">Contract Type</label><input name="contract_type" x-model="form.contract_type" :readonly="isView" class="input"></div>

                                <div>
                                    <label class="label">Travel Billability</label>
                                    <select name="travel_billability" x-model="form.travel_billability" :disabled="isView" class="input">
                                        <option value="">Select billability</option>
                                        <option>Billable</option>
                                        <option>Non-billable</option>
                                        <option>Partially Billable</option>
                                    </select>
                                </div>
                            </div>
                        </section>

                        {{-- D --}}
                        <section class="form-section">
                            <h3 class="form-title">D. Client Payment Details</h3>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="label">Will client pay for this travel?</label>
                                    <select name="client_payment_status" x-model="form.client_payment_status" :disabled="isView" class="input">
                                        <option value="">Select</option>
                                        <option>Yes</option>
                                        <option>No</option>
                                        <option>Partial</option>
                                    </select>
                                </div>

                                <div><label class="label">Amount Client Will Pay</label><input type="number" step="0.01" name="amount_client_will_pay" x-model="form.amount_client_will_pay" :readonly="isView" class="input"></div>

                                <div class="col-span-2">
                                    <label class="label">What will client pay for?</label>
                                    <div class="grid grid-cols-4 gap-2 text-sm">
                                        <template x-for="option in clientPaymentOptionList" :key="option">
                                            <label class="check-card">
                                                <input type="checkbox" name="client_payment_items[]" :value="option" x-model="form.client_payment_items" :disabled="isView">
                                                <span x-text="option"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>

                                <div class="col-span-2"><label class="label">If Others, specify</label><input name="client_payment_other" x-model="form.client_payment_other" :readonly="isView" class="input"></div>
                            </div>
                        </section>

                        {{-- E --}}
                        <section class="form-section">
                            <h3 class="form-title">E. Travel Credits</h3>

                            <div class="grid grid-cols-4 gap-4">
                                <div><label class="label">Monthly Limit</label><input type="number" step="0.01" name="travel_credit_monthly_limit" x-model="form.travel_credit_monthly_limit" :readonly="isView" class="input"></div>
                                <div><label class="label">Used This Month</label><input type="number" step="0.01" name="travel_credit_used" x-model="form.travel_credit_used" :readonly="isView" class="input"></div>
                                <div><label class="label">Needed For Trip</label><input type="number" step="0.01" name="travel_credit_needed" x-model="form.travel_credit_needed" :readonly="isView" class="input"></div>
                                <div><label class="label">Remaining After Trip</label><input type="number" step="0.01" name="travel_credit_remaining" x-model="form.travel_credit_remaining" :readonly="isView" class="input"></div>
                            </div>
                        </section>

                        {{-- F --}}
                        <section class="form-section">
                            <h3 class="form-title">F. Mode of Transportation</h3>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="label">Transportation Mode</label>
                                    <select name="transportation_mode" x-model="form.transportation_mode" :disabled="isView" class="input">
                                        <option value="">Select mode</option>
                                        <option>Company Vehicle</option>
                                        <option>Private Vehicle</option>
                                        <option>Public Transport</option>
                                        <option>Air Travel</option>
                                        <option>Sea Travel</option>
                                    </select>
                                </div>

                                <div><label class="label">Estimated Expenses</label><input type="number" step="0.01" name="estimated_expenses" x-model="form.estimated_expenses" :readonly="isView" class="input"></div>
                                <div><label class="label">Company Vehicle</label><input name="company_vehicle" x-model="form.company_vehicle" :readonly="isView" class="input"></div>
                                <div><label class="label">Assigned Driver</label><input name="assigned_driver" x-model="form.assigned_driver" :readonly="isView" class="input"></div>
                                <div><label class="label">Vehicle Details</label><input name="vehicle_details" x-model="form.vehicle_details" :readonly="isView" class="input"></div>
                                <div><label class="label">Plate Number</label><input name="plate_number" x-model="form.plate_number" :readonly="isView" class="input"></div>
                                <div><label class="label">Odometer</label><input name="odometer" x-model="form.odometer" :readonly="isView" class="input"></div>
                                <div><label class="label">Fuel Level</label><input name="fuel_level" x-model="form.fuel_level" :readonly="isView" class="input"></div>
                                <div><label class="label">Private Vehicle Details</label><input name="private_vehicle_details" x-model="form.private_vehicle_details" :readonly="isView" class="input"></div>
                                <div><label class="label">Public Transport Details</label><input name="public_transport_details" x-model="form.public_transport_details" :readonly="isView" class="input"></div>
                                <div><label class="label">Air Travel Details</label><input name="air_travel_details" x-model="form.air_travel_details" :readonly="isView" class="input"></div>
                                <div><label class="label">Sea Travel Details</label><input name="sea_travel_details" x-model="form.sea_travel_details" :readonly="isView" class="input"></div>
                            </div>
                        </section>

                        {{-- G --}}
                        <section class="form-section">
                            <h3 class="form-title">G. Attachments</h3>

                            <div class="space-y-4">
                                <div>
                                    <label class="label">Attachment Type</label>
                                    <div class="grid grid-cols-4 gap-2 text-sm">
                                        <template x-for="option in attachmentTypeList" :key="option">
                                            <label class="check-card">
                                                <input type="checkbox" name="attachment_types[]" :value="option" x-model="form.attachment_types" :disabled="isView">
                                                <span x-text="option"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>

                                <div><label class="label">If Others, specify</label><input name="attachment_other" x-model="form.attachment_other" :readonly="isView" class="input"></div>

                                <template x-if="!isView">
                                    <div>
                                        <label class="label">Upload Attachments</label>
                                        <input type="file" name="attachments[]" multiple class="input">
                                        <p class="text-[11px] text-gray-500 mt-1">Accepted: PDF, JPG, PNG, DOC, DOCX. Max 10MB each.</p>
                                    </div>
                                </template>

                                <template x-if="form.attachment_paths && form.attachment_paths.length">
                                    <div>
                                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Uploaded Files</p>
                                        <template x-for="attachment in form.attachment_paths" :key="attachment.path">
                                            <a :href="attachment.url" target="_blank" class="block px-3 py-2 rounded-lg border border-gray-200 text-sm text-blue-700 hover:bg-blue-50 mb-2" x-text="attachment.original_name || 'Attachment'"></a>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </section>

                        <section class="form-section">
                            <h3 class="form-title">Remarks</h3>
                            <textarea name="remarks" x-model="form.remarks" :readonly="isView" rows="4" class="input"></textarea>
                        </section>

                        <div class="sticky bottom-0 bg-white border-t py-4 flex justify-end gap-3">
                            <button type="button" @click="closePanel()" class="px-5 py-2 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>

                            <template x-if="isView">
                                <button type="button" @click="openEdit(form)" class="px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">
                                    Edit OBF
                                </button>
                            </template>

                            <template x-if="!isView">
                                <button type="submit" class="px-6 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">
                                    <span x-text="isEdit ? 'Update OBF' : 'Submit OBF'"></span>
                                </button>
                            </template>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.input {
    width: 100%;
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid rgb(209 213 219);
    border-radius: 0.5rem;
    outline: none;
}
.input:focus {
    border-color: rgb(59 130 246);
    box-shadow: 0 0 0 2px rgb(191 219 254);
}
.label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: rgb(75 85 99);
    margin-bottom: 0.25rem;
}
.form-section {
    border: 1px solid rgb(229 231 235);
    border-radius: 0.75rem;
    padding: 1rem;
}
.form-title {
    font-size: 0.75rem;
    font-weight: 800;
    color: rgb(37 99 235);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    border-bottom: 1px solid rgb(229 231 235);
    padding-bottom: 0.6rem;
    margin-bottom: 1rem;
}
.check-card {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid rgb(229 231 235);
    border-radius: 0.5rem;
    padding: 0.55rem 0.7rem;
}
.obf-paper {
    background: white;
    border-left: 3px solid #1d4ed8;
    box-shadow: 0 10px 22px rgb(15 23 42 / 0.16);
    color: #001b5f;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    line-height: 1.35;
    margin: 0 auto;
    min-height: 1123px;
    padding: 28px 28px 40px;
    width: 794px;
}
.obf-letterhead {
    border-bottom: 4px solid #2654e8;
    color: #0b1b3f;
    margin-bottom: 30px;
    padding-bottom: 18px;
    text-align: center;
}
.obf-logo {
    height: 76px;
    margin: 0 auto 8px;
    object-fit: contain;
}
.obf-company-name {
    color: #000;
    font-size: 13px;
    font-weight: 800;
    text-transform: uppercase;
}
.obf-partners {
    color: #000;
    font-size: 12px;
    font-weight: 800;
}
.obf-letterhead p {
    margin: 4px 0;
}
.obf-title {
    background: #2654e8;
    border-radius: 7px;
    color: white;
    font-size: 15px;
    font-weight: 800;
    letter-spacing: 0.08em;
    margin-bottom: 18px;
    padding: 10px 18px;
    text-transform: uppercase;
}
.obf-reference {
    display: grid;
    gap: 16px 26px;
    grid-template-columns: max-content 1fr;
    margin-bottom: 28px;
}
.obf-reference b {
    color: #06236d;
    display: inline-block;
    min-width: 132px;
}
.obf-reference strong {
    color: #000;
    font-size: 13px;
}
.obf-config {
    align-self: center;
    border: 1px solid #22c55e;
    border-radius: 999px;
    color: #047857;
    font-size: 11px;
    font-weight: 700;
    justify-self: end;
    padding: 4px 12px;
}
.section-title {
    background: #173684;
    color: white;
    border: 0;
    border-radius: 5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    padding: 0.5rem 0.9rem;
    margin: 1.25rem 0 0.75rem;
}
.preview-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 0.6rem;
    margin-bottom: 0;
}
.preview-table td {
    border: 0;
    padding: 0 0.75rem 0 0;
    vertical-align: top;
}
.preview-table b {
    color: #001b5f;
    display: block;
    font-size: 11px;
    margin-bottom: 0.3rem;
}
.preview-table td > span {
    background: #f8fafc;
    border: 1px solid #1d4ed8;
    border-radius: 5px;
    color: #111827;
    display: block;
    min-height: 32px;
    padding: 8px 10px;
    white-space: pre-line;
}
.preview-table td[colspan="2"] > span {
    min-height: 44px;
}
@media print {
    body * {
        visibility: hidden;
    }
    .print-area, .print-area * {
        visibility: visible;
    }
    @page {
        size: A4;
        margin: 0;
    }
    .print-area {
        position: fixed;
        inset: 0;
        width: 794px;
        min-height: 1123px;
        box-shadow: none;
        border: none;
        padding: 28px;
    }
}
</style>

<script>
function obfPage() {
    return {
        employees: @json($employees),
        contacts: @json($contacts ?? []),
        trips: @json($trips),
        canManageObf: @json($canManageObf ?? false),

        showPanel: false,
        isEdit: false,
        isView: false,

        purposeOptionList: ['Official Business Travel', 'Site Inspection', 'Training / Seminar', 'Operations Support', 'Field Work', 'Audit', 'Client Meeting', 'Delivery / Pickup', 'Others'],
        clientPaymentOptionList: ['Transportation', 'Meals Per Diem', 'Miscellaneous', 'Vehicle Rental', 'Fuel', 'Airline Boat Tickets', 'Driver Fee', 'Toll Parking', 'Accommodation', 'Others'],
        attachmentTypeList: ['Rental Contract', 'Flight Ticket', 'Transmital form', 'Memo', 'Client Contract', 'Clients Approval', 'Picture of Meter', 'Others'],

        form: {},
        preview: {},

        get panelTitle() {
            if (this.isView) return 'View Official Business Travel Form';
            if (this.isEdit) return 'Edit Official Business Travel Form';
            return 'New Official Business Travel Form';
        },

        blankForm() {
            return {
                id: null,
                ob_reference_no: '',
                employee_id: '',
                employee_name: '',
                employee_code: '',
                position: '',
                department: '',
                destination: '',
                additional_stops: '',
                purpose: '',
                purpose_options: [],
                purpose_other: '',
                trip_type: '',
                date_from: '',
                date_to: '',
                departure_time: '',
                return_time: '',
                nature_of_travel: '',
                team_members: ['', '', ''],

                immediate_superior: '',
                superior_email: '',

                is_client_travel: '0',
                client_contact_id: '',
                client_type: '',
                client_id_no: '',
                client_name: '',
                client_email: '',
                client_contract_number: '',
                contract_type: '',
                travel_billability: '',

                client_payment_status: '',
                client_payment_items: [],
                client_payment_other: '',
                amount_client_will_pay: 0,

                travel_credit_monthly_limit: '',
                travel_credit_used: '',
                travel_credit_needed: '',
                travel_credit_remaining: '',

                transportation_mode: '',
                company_vehicle: '',
                assigned_driver: '',
                vehicle_details: '',
                plate_number: '',
                odometer: '',
                fuel_level: '',
                private_vehicle_details: '',
                public_transport_details: '',
                air_travel_details: '',
                sea_travel_details: '',
                estimated_expenses: 0,

                attachment_paths: [],
                attachment_types: [],
                attachment_other: '',

                remarks: '',
                status: 'Pending',
            };
        },

        blankPreview() {
            return {
                full_name: '',
                employee_code: '',
                email: '',
                phone_number: '',
                position: '',
                department: '',
            };
        },

        openAdd() {
            this.isView = false;
            this.isEdit = false;
            this.form = this.blankForm();
            this.preview = this.blankPreview();
            if (!this.canManageObf && this.employees.length === 1) {
                this.form.employee_id = this.employees[0].id;
                this.selectEmployee();
            }
            this.showPanel = true;
        },

        openView(trip) {
            this.isView = true;
            this.isEdit = false;
            this.form = this.hydrateTrip(trip);
            this.setPreviewFromTrip(trip);
            this.showPanel = true;
        },

        openEdit(trip) {
            this.isView = false;
            this.isEdit = true;
            this.form = this.hydrateTrip(trip);
            this.setPreviewFromTrip(trip);
            this.showPanel = true;
        },

        closePanel() {
            this.showPanel = false;
            this.isView = false;
            this.isEdit = false;
            this.form = {};
            this.preview = {};
        },

        hydrateTrip(trip) {
            const credits = trip.travel_credit_details || {};
            const transpo = trip.transportation_details || {};

            return {
                ...this.blankForm(),
                ...trip,
                purpose_options: trip.purpose_options || [],
                team_members: trip.team_members && trip.team_members.length ? [...trip.team_members, '', '', ''].slice(0, 3) : ['', '', ''],
                is_client_travel: trip.is_client_travel ? '1' : '0',
                client_contact_id: trip.client_contact_id || '',
                client_payment_items: trip.client_payment_items || [],
                attachment_paths: trip.attachment_paths || [],
                attachment_types: trip.attachment_types || [],
                travel_credit_monthly_limit: credits.monthly_limit || '',
                travel_credit_used: credits.used_this_month || '',
                travel_credit_needed: credits.needed_for_trip || '',
                travel_credit_remaining: credits.remaining_after_trip || '',
                company_vehicle: transpo.company_vehicle || '',
                assigned_driver: transpo.assigned_driver || '',
                vehicle_details: transpo.vehicle_details || '',
                plate_number: transpo.plate_number || '',
                odometer: transpo.odometer || '',
                fuel_level: transpo.fuel_level || '',
                private_vehicle_details: transpo.private_vehicle_details || '',
                public_transport_details: transpo.public_transport_details || '',
                air_travel_details: transpo.air_travel_details || '',
                sea_travel_details: transpo.sea_travel_details || '',
            };
        },

        selectEmployee() {
            const employee = this.employees.find(item => String(item.id) === String(this.form.employee_id));

            if (!employee) {
                this.preview = this.blankPreview();
                return;
            }

            this.preview = {
                full_name: employee.full_name || '',
                employee_code: employee.employee_code || '',
                email: employee.email || '',
                phone_number: employee.phone_number || '',
                position: employee.position || '',
                department: employee.department || '',
            };
        },

        setPreviewFromTrip(trip) {
            const employee = this.employees.find(item => String(item.id) === String(trip.employee_id));

            this.preview = {
                full_name: trip.employee_name || employee?.full_name || '',
                employee_code: trip.employee_code || employee?.employee_code || '',
                email: employee?.email || '',
                phone_number: employee?.phone_number || '',
                position: trip.position || employee?.position || '',
                department: trip.department || employee?.department || '',
            };
        },


        selectedClientContact() {
            return this.contacts.find(item => String(item.id) === String(this.form.client_contact_id));
        },

        get selectedClientPhone() {
            return this.selectedClientContact()?.phone || '';
        },

        get selectedClientCompany() {
            return this.selectedClientContact()?.company_name || '';
        },

        handleClientTravelChange() {
            if (this.form.is_client_travel == '1') {
                return;
            }

            this.form.client_contact_id = '';
            this.form.client_type = '';
            this.form.client_id_no = '';
            this.form.client_name = '';
            this.form.client_email = '';
            this.form.client_contract_number = '';
            this.form.contract_type = '';
            this.form.travel_billability = '';
        },

        selectClientContact() {
            const contact = this.selectedClientContact();

            if (!contact) {
                return;
            }

            this.form.is_client_travel = '1';
            this.form.client_type = contact.client_type || this.form.client_type || 'Local';
            this.form.client_id_no = contact.cif_no || contact.tin || contact.id || '';
            this.form.client_name = contact.full_name || '';
            this.form.client_email = contact.email || '';
            this.form.client_contract_number = contact.cif_no || this.form.client_contract_number || '';
            this.form.contract_type = contact.contract_type || this.form.contract_type || '';
        },

        purposeText() {
            const values = this.form.purpose_options || [];
            return values.length ? values.join(', ') : '-';
        },

        teamText() {
            const values = (this.form.team_members || []).filter(Boolean);
            return values.length ? values.join(', ') : '-';
        },

        clientPaymentText() {
            const values = this.form.client_payment_items || [];
            return values.length ? values.join(', ') : '-';
        },

        attachmentText() {
            const values = this.form.attachment_types || [];
            return values.length ? values.join(', ') : '-';
        },

        dateTimeText(date, time) {
            if (!date && !time) return '-';
            return `${date || ''} ${time || ''}`.trim();
        },

        statusClass(status) {
            if (status === 'Approved') return 'bg-green-100 text-green-700';
            if (status === 'Rejected') return 'bg-red-100 text-red-700';
            if (status === 'Cancelled') return 'bg-gray-100 text-gray-700';
            return 'bg-yellow-100 text-yellow-700';
        },
    }
}
</script>
@endsection
