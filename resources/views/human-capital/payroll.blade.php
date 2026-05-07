@extends('layouts.app')

@section('content')
<div x-data="payrollPage()" class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col">
    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0">
        <div class="flex items-center justify-between px-4 py-3 border-b shrink-0 gap-4">
            <div class="flex items-center flex-1 min-w-0">
                <h1 class="text-lg font-semibold text-gray-900">Payroll</h1>
            </div>

            <button
                type="button"
                @click="openAddSection(activeTab)"
                class="bg-blue-600 text-white px-6 py-2 rounded text-sm shrink-0 hover:bg-blue-700 transition"
            >
                + Add
            </button>
        </div>

        <div class="px-4 py-3 border-b bg-white shrink-0 overflow-x-auto">
            <div class="inline-flex min-w-max rounded-md border border-gray-200 overflow-hidden text-sm">
                <template x-for="tab in tabs" :key="tab.key">
                    <button
                        type="button"
                        @click="activeTab = tab.key"
                        :class="activeTab === tab.key ? 'bg-blue-50 text-blue-700 font-medium' : 'bg-white text-gray-600 hover:bg-gray-50'"
                        class="px-4 py-2 border-r last:border-r-0 whitespace-nowrap"
                        x-text="tab.label"
                    ></button>
                </template>
            </div>
        </div>

        @if(session('success'))
            <div class="mx-4 mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mx-4 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <div class="font-semibold mb-1">Please fix the following:</div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="p-4 flex-grow overflow-hidden">
            <div class="border rounded-md h-full overflow-auto bg-white">
                <div x-show="activeTab === 'salary_grades'" x-cloak class="h-full">
                    <table class="w-full text-sm border-collapse min-w-[1200px]">
                        <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                            <tr>
                                <th class="p-3 text-left font-medium">Code</th>
                                <th class="p-3 text-left font-medium">Name</th>
                                <th class="p-3 text-left font-medium">Payment Type</th>
                                <th class="p-3 text-left font-medium">Monthly Basic</th>
                                <th class="p-3 text-left font-medium">ADR</th>
                                <th class="p-3 text-left font-medium">Hourly</th>
                                <th class="p-3 text-left font-medium">Per Minute</th>
                                <th class="p-3 text-left font-medium">Yearly</th>
                                <th class="p-3 text-left font-medium">Date Created</th>
                                <th class="p-3 text-left font-medium">Policy No.</th>
                                <th class="p-3 text-left font-medium">Basis</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            @forelse($salaryGrades as $item)
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="p-3 text-gray-900 align-top">{{ $item->code }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->name }}</td>
                                    <td class="p-3 text-gray-900 align-top capitalize">{{ $item->payment_type }}</td>
                                    <td class="p-3 text-gray-900 align-top">PHP {{ number_format($item->monthly_basic_pay, 2) }}</td>
                                    <td class="p-3 text-gray-900 align-top">PHP {{ number_format($item->applicable_daily_rate, 2) }}</td>
                                    <td class="p-3 text-gray-900 align-top">PHP {{ number_format($item->hourly_rate, 4) }}</td>
                                    <td class="p-3 text-gray-900 align-top">PHP {{ number_format($item->minute_rate, 6) }}</td>
                                    <td class="p-3 text-gray-900 align-top">PHP {{ number_format($item->yearly_rate, 2) }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ optional($item->date_created)->format('Y-m-d') ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->policy_number ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">
                                        @if($item->basis_file_path)
                                            <a href="{{ asset('storage/'.$item->basis_file_path) }}" target="_blank" class="text-blue-600 hover:underline">View file</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="border-t">
                                    <td colspan="11" class="p-10 text-center text-gray-400 italic">No salary grades yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div x-show="activeTab === 'levels'" x-cloak class="h-full">
                    <table class="w-full text-sm border-collapse min-w-[1100px]">
                        <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                            <tr>
                                <th class="p-3 text-left font-medium">Payroll Level</th>
                                <th class="p-3 text-left font-medium">Salary Grade</th>
                                <th class="p-3 text-left font-medium">Computation Type</th>
                                <th class="p-3 text-left font-medium">Work Schedule</th>
                                <th class="p-3 text-left font-medium">Hours/Day</th>
                                <th class="p-3 text-left font-medium">Date Created</th>
                                <th class="p-3 text-left font-medium">Policy No.</th>
                                <th class="p-3 text-left font-medium">Basis</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            @forelse($payrollLevels as $item)
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="p-3 text-gray-900 align-top">{{ $item->level_name }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->salaryGrade?->name }}</td>
                                    <td class="p-3 text-gray-900 align-top capitalize">{{ $item->computation_type }}</td>
                                    <td class="p-3 text-gray-900 align-top">
                                        {{ match($item->work_schedule_label ?: $item->work_schedule) {
                                            'every_day' => 'Monday to Sunday – 8:00 AM to 5:00 PM',
                                            'no_sunday' => 'Monday to Saturday – 8:00 AM to 5:00 PM',
                                            'no_saturday' => 'Monday to Friday – 8:00 AM to 5:00 PM',
                                            'no_sat_sun' => 'Monday to Friday – 8:00 AM to 5:00 PM',
                                            'no_sat_sun_holidays' => 'Monday to Friday – 8:00 AM to 5:00 PM',
                                            default => $item->work_schedule_label ?: '-',
                                        } }}
                                    </td>
                                    <td class="p-3 text-gray-900 align-top">{{ number_format($item->hours_per_day, 2) }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ optional($item->date_created)->format('Y-m-d') ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->policy_number ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">
                                        @if($item->basis_file_path)
                                            <a href="{{ asset('storage/'.$item->basis_file_path) }}" target="_blank" class="text-blue-600 hover:underline">View file</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="border-t">
                                    <td colspan="8" class="p-10 text-center text-gray-400 italic">No payroll levels yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div x-show="activeTab === 'benefits'" x-cloak class="h-full">
                    <table class="w-full text-sm border-collapse min-w-[1200px]">
                        <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                            <tr>
                                <th class="p-3 text-left font-medium">Salary Grade</th>
                                <th class="p-3 text-left font-medium">Payroll Level</th>
                                <th class="p-3 text-left font-medium">Benefit Name</th>
                                <th class="p-3 text-left font-medium">Type</th>
                                <th class="p-3 text-left font-medium">Rate/Input</th>
                                <th class="p-3 text-left font-medium">Value</th>
                                <th class="p-3 text-left font-medium">Policy No.</th>
                                <th class="p-3 text-left font-medium">Date Created</th>
                                <th class="p-3 text-left font-medium">Basis</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            @forelse($benefits as $item)
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="p-3 text-gray-900 align-top">{{ $item->salaryGrade?->name ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->payrollLevel?->level_name ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->name }}</td>
                                    <td class="p-3 text-gray-900 align-top capitalize">{{ $item->type }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->type === 'percentage' ? number_format($item->rate, 2).'%' : 'PHP '.number_format($item->rate, 2) }}</td>
                                    <td class="p-3 text-gray-900 align-top">PHP {{ number_format($item->value, 2) }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->policy_number ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ optional($item->date_created)->format('Y-m-d') ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">
                                        @if($item->basis_file_path)
                                            <a href="{{ asset('storage/'.$item->basis_file_path) }}" target="_blank" class="text-blue-600 hover:underline">View file</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="border-t">
                                    <td colspan="9" class="p-10 text-center text-gray-400 italic">No benefits yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div x-show="activeTab === 'allowances'" x-cloak class="h-full">
                    <table class="w-full text-sm border-collapse min-w-[1200px]">
                        <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                            <tr>
                                <th class="p-3 text-left font-medium">Salary Grade</th>
                                <th class="p-3 text-left font-medium">Payroll Level</th>
                                <th class="p-3 text-left font-medium">Allowance Name</th>
                                <th class="p-3 text-left font-medium">Type</th>
                                <th class="p-3 text-left font-medium">Rate/Input</th>
                                <th class="p-3 text-left font-medium">Value</th>
                                <th class="p-3 text-left font-medium">Policy No.</th>
                                <th class="p-3 text-left font-medium">Date Created</th>
                                <th class="p-3 text-left font-medium">Basis</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            @forelse($allowances as $item)
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="p-3 text-gray-900 align-top">{{ $item->salaryGrade?->name ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->payrollLevel?->level_name ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->name }}</td>
                                    <td class="p-3 text-gray-900 align-top capitalize">{{ $item->type }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->type === 'percentage' ? number_format($item->rate, 2).'%' : 'PHP '.number_format($item->rate, 2) }}</td>
                                    <td class="p-3 text-gray-900 align-top">PHP {{ number_format($item->value, 2) }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->policy_number ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ optional($item->date_created)->format('Y-m-d') ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">
                                        @if($item->basis_file_path)
                                            <a href="{{ asset('storage/'.$item->basis_file_path) }}" target="_blank" class="text-blue-600 hover:underline">View file</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="border-t">
                                    <td colspan="9" class="p-10 text-center text-gray-400 italic">No allowances yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div x-show="activeTab === 'deductions'" x-cloak class="h-full">
                    <table class="w-full text-sm border-collapse min-w-[1200px]">
                        <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                            <tr>
                                <th class="p-3 text-left font-medium">Salary Grade</th>
                                <th class="p-3 text-left font-medium">Payroll Level</th>
                                <th class="p-3 text-left font-medium">Deduction Name</th>
                                <th class="p-3 text-left font-medium">Type</th>
                                <th class="p-3 text-left font-medium">Rate/Input</th>
                                <th class="p-3 text-left font-medium">Value</th>
                                <th class="p-3 text-left font-medium">Policy No.</th>
                                <th class="p-3 text-left font-medium">Date Created</th>
                                <th class="p-3 text-left font-medium">Basis</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            @forelse($deductions as $item)
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="p-3 text-gray-900 align-top">{{ $item->salaryGrade?->name ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->payrollLevel?->level_name ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->name }}</td>
                                    <td class="p-3 text-gray-900 align-top capitalize">{{ $item->type }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->type === 'percentage' ? number_format($item->rate, 2).'%' : 'PHP '.number_format($item->rate, 2) }}</td>
                                    <td class="p-3 text-gray-900 align-top">PHP {{ number_format($item->value, 2) }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->policy_number ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ optional($item->date_created)->format('Y-m-d') ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">
                                        @if($item->basis_file_path)
                                            <a href="{{ asset('storage/'.$item->basis_file_path) }}" target="_blank" class="text-blue-600 hover:underline">View file</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="border-t">
                                    <td colspan="9" class="p-10 text-center text-gray-400 italic">No deductions yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div x-show="activeTab === 'holidays'" x-cloak class="h-full">
                    <table class="w-full text-sm border-collapse min-w-[1300px]">
                        <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                            <tr>
                                <th class="p-3 text-left font-medium">Salary Grade</th>
                                <th class="p-3 text-left font-medium">Payroll Level</th>
                                <th class="p-3 text-left font-medium">Holiday Name</th>
                                <th class="p-3 text-left font-medium">Holiday Date</th>
                                <th class="p-3 text-left font-medium">Type</th>
                                <th class="p-3 text-left font-medium">Percentage</th>
                                <th class="p-3 text-left font-medium">Holiday Value</th>
                                <th class="p-3 text-left font-medium">Policy No.</th>
                                <th class="p-3 text-left font-medium">Date Created</th>
                                <th class="p-3 text-left font-medium">Basis</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            @forelse($holidays as $item)
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="p-3 text-gray-900 align-top">{{ $item->salaryGrade?->name ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->payrollLevel?->level_name ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->name }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ optional($item->holiday_date)->format('Y-m-d') ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ str($item->holiday_category ?: $item->holiday_type)->replace('_', ' ')->title() }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ number_format($item->percentage, 2) }}%</td>
                                    <td class="p-3 text-gray-900 align-top">PHP {{ number_format($item->holiday_value, 2) }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->policy_number ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ optional($item->date_created)->format('Y-m-d') ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">
                                        @if($item->basis_file_path)
                                            <a href="{{ asset('storage/'.$item->basis_file_path) }}" target="_blank" class="text-blue-600 hover:underline">View file</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="border-t">
                                    <td colspan="10" class="p-10 text-center text-gray-400 italic">No holidays yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div x-show="activeTab === 'periods'" x-cloak class="h-full">
                    <table class="w-full text-sm border-collapse min-w-[1300px]">
                        <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                            <tr>
                                <th class="p-3 text-left font-medium">Name</th>
                                <th class="p-3 text-left font-medium">Period</th>
                                <th class="p-3 text-left font-medium">Payroll Window</th>
                                <th class="p-3 text-left font-medium">Pay Date</th>
                                <th class="p-3 text-left font-medium">Dispute Window</th>
                                <th class="p-3 text-left font-medium">Status</th>
                                <th class="p-3 text-left font-medium">Date Created</th>
                                <th class="p-3 text-left font-medium">Policy No.</th>
                                <th class="p-3 text-left font-medium">Basis</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            @forelse($periods as $item)
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="p-3 text-gray-900 align-top">{{ $item->name }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ optional($item->period_start)->format('Y-m-d') }} to {{ optional($item->period_end)->format('Y-m-d') }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ optional($item->payroll_start)->format('Y-m-d') ?: '-' }} to {{ optional($item->payroll_end)->format('Y-m-d') ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ optional($item->pay_date)->format('Y-m-d') ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ optional($item->dispute_start)->format('Y-m-d') ?: '-' }} to {{ optional($item->dispute_end)->format('Y-m-d') ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top capitalize">{{ $item->status }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ optional($item->date_created)->format('Y-m-d') ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->policy_number ?: '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">
                                        @if($item->basis_file_path)
                                            <a href="{{ asset('storage/'.$item->basis_file_path) }}" target="_blank" class="text-blue-600 hover:underline">View file</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="border-t">
                                    <td colspan="9" class="p-10 text-center text-gray-400 italic">No payroll periods yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div x-show="activeTab === 'profiles'" x-cloak class="h-full">
                    <table class="w-full text-sm table-fixed border-collapse">
                        <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                            <tr>
                                <th class="p-3 text-left font-medium">Employee</th>
                                <th class="p-3 text-left font-medium w-40">Payroll Level</th>
                                <th class="p-3 text-left font-medium w-40">Salary Grade</th>
                                <th class="p-3 text-left font-medium w-32">Override</th>
                                <th class="p-3 text-left font-medium w-28">Night Diff</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            @forelse($profiles as $item)
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="p-3 text-gray-900 align-top">{{ trim(($item->employee->first_name ?? '').' '.($item->employee->last_name ?? '')) }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->payrollLevel?->level_name }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->payrollLevel?->salaryGrade?->name }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->basic_salary_override ? 'PHP '.number_format($item->basic_salary_override, 2) : '-' }}</td>
                                    <td class="p-3 text-gray-900 align-top">{{ $item->night_differential_enabled ? 'Enabled' : 'Disabled' }}</td>
                                </tr>
                            @empty
                                <tr class="border-t">
                                    <td colspan="5" class="p-10 text-center text-gray-400 italic">No employee payroll profiles yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div x-show="activeTab === 'summaries'" x-cloak class="h-full p-4 space-y-4">
                    <form action="{{ route('human-capital.payroll.generate-summary') }}" method="POST" class="flex flex-wrap items-end gap-3">
                        @csrf
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Payroll Period</label>
                            <select name="payroll_period_id" class="w-72 border rounded-md p-2 bg-white text-gray-900">
                                @foreach($periods as $period)
                                    <option value="{{ $period->id }}">
                                        {{ $period->name }} ({{ optional($period->period_start)->format('Y-m-d') }} to {{ optional($period->period_end)->format('Y-m-d') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 transition text-sm">
                            Generate Payroll Summary
                        </button>
                    </form>

                    <div class="border rounded-md overflow-auto bg-white">
                        <table class="w-full text-sm table-fixed border-collapse">
                            <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                                <tr>
                                    <th class="p-3 text-left font-medium">Employee</th>
                                    <th class="p-3 text-left font-medium w-44">Period</th>
                                    <th class="p-3 text-left font-medium w-32">Gross Pay</th>
                                    <th class="p-3 text-left font-medium w-32">Deductions</th>
                                    <th class="p-3 text-left font-medium w-32">Net Pay</th>
                                    <th class="p-3 text-left font-medium w-28">Payslip</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                @forelse($summaries as $item)
                                    <tr class="border-t hover:bg-gray-50">
                                        <td class="p-3 text-gray-900 align-top">{{ trim(($item->employee->first_name ?? '').' '.($item->employee->last_name ?? '')) }}</td>
                                        <td class="p-3 text-gray-900 align-top">{{ $item->period?->name }}</td>
                                        <td class="p-3 text-gray-900 align-top">PHP {{ number_format($item->gross_pay, 2) }}</td>
                                        <td class="p-3 text-gray-900 align-top">PHP {{ number_format($item->total_deductions, 2) }}</td>
                                        <td class="p-3 text-green-700 font-semibold align-top">PHP {{ number_format($item->net_pay, 2) }}</td>
                                        <td class="p-3 text-gray-900 align-top">
                                            <a href="{{ route('human-capital.payroll.payslip.show', $item->id) }}" target="_blank" class="text-blue-600 hover:underline">
                                                View Payslip
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="border-t">
                                        <td colspan="6" class="p-10 text-center text-gray-400 italic">No payroll summaries yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="showSlider" x-cloak class="fixed inset-0 z-50 overflow-hidden" style="display: none;">
            <div class="absolute inset-0 bg-black/40" @click="closeAddSection()"></div>

            <div class="absolute inset-0 flex justify-end">
                <div
                    x-ref="addPanel"
                    class="w-full bg-white shadow-2xl flex h-full transform translate-x-full transition-transform duration-300 ease-in-out"
                >
                    <div class="hidden lg:block flex-1 min-w-0 p-6 bg-[#f5f6f8] border-r border-gray-200">
                        <div class="h-full max-w-[980px] mx-auto">
                            <div class="w-full h-full bg-white border border-gray-200 rounded-xl overflow-hidden p-4">
                                <div class="w-full h-full bg-white rounded-lg border border-gray-200 overflow-hidden flex items-center justify-center">
                                    <div x-show="previewMode === 'empty'" class="text-center px-6 text-gray-500">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Basis File Preview</h3>
                                        <p class="text-sm">Upload a PDF or image file to preview it here.</p>
                                    </div>

                                    <div x-show="previewMode === 'unsupported'" class="text-center px-6 text-gray-500">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Preview Not Available</h3>
                                        <p class="text-sm">This file type can be uploaded, but inline preview is only available for PDF and images.</p>
                                        <p class="mt-2 text-xs text-gray-400" x-text="previewFileName || 'No file selected'"></p>
                                    </div>

                                    <iframe
                                        x-show="previewMode === 'pdf'"
                                        :src="previewFileUrl"
                                        class="w-full h-full bg-white"
                                        frameborder="0"
                                    ></iframe>

                                    <div x-show="previewMode === 'image'" class="w-full h-full overflow-auto bg-white">
                                        <img :src="previewFileUrl" alt="Basis File Preview" class="block max-w-full h-auto mx-auto">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full max-w-[560px] bg-white flex flex-col h-full">
                        <div class="p-6 border-b flex items-center justify-between shrink-0">
                            <h2 class="font-bold text-lg text-gray-900" x-text="sliderTitle"></h2>
                            <button type="button" @click="closeAddSection()" class="text-sm text-gray-500 hover:text-gray-700">Close</button>
                        </div>

                        <div class="px-6 pt-4 shrink-0">
                            <div
                                x-show="sliderError"
                                x-text="sliderError"
                                class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
                            ></div>
                        </div>

                        <div class="flex-1 overflow-y-auto overflow-x-hidden px-6 py-6">
                    <form
                        x-show="activeForm === 'salary_grades'"
                        x-ref="salaryGradeForm"
                        action="{{ route('human-capital.payroll.salary-grades.store') }}"
                        method="POST"
                        enctype="multipart/form-data"
                        class="space-y-4"
                    >
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Code</label>
                                <input name="code" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Name</label>
                                <input name="name" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Computation Type</label>
                            <select name="payment_type" x-model="salaryGradeForm.payment_type" class="w-full border rounded-md p-2 bg-white text-gray-900">
                                <option value="monthly">Monthly Paid</option>
                                <option value="daily">Daily Paid</option>
                            </select>
                        </div>

                        <div x-show="salaryGradeForm.payment_type === 'monthly'">
                            <label class="block text-sm font-medium mb-1">Monthly Basic Pay</label>
                            <input type="number" step="0.01" name="monthly_basic_pay" x-model="salaryGradeForm.monthly_basic_pay" class="w-full border rounded-md p-2 bg-white text-gray-900" placeholder="0.00">
                        </div>

                        <div x-show="salaryGradeForm.payment_type === 'daily'">
                            <label class="block text-sm font-medium mb-1">Applicable Daily Rate</label>
                            <input type="number" step="0.01" name="applicable_daily_rate" x-model="salaryGradeForm.applicable_daily_rate" class="w-full border rounded-md p-2 bg-white text-gray-900" placeholder="0.00">
                        </div>

                        <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
                            <div class="text-sm font-semibold text-blue-900 mb-3">Computed Rate Preview</div>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div class="rounded border bg-white p-3"><div class="text-gray-500">Monthly Basic</div><div class="font-semibold text-gray-900" x-text="formatCurrency(getSalaryGradePreview().monthly_basic_pay)"></div></div>
                                <div class="rounded border bg-white p-3"><div class="text-gray-500">ADR</div><div class="font-semibold text-gray-900" x-text="formatCurrency(getSalaryGradePreview().applicable_daily_rate)"></div></div>
                                <div class="rounded border bg-white p-3"><div class="text-gray-500">Hourly</div><div class="font-semibold text-gray-900" x-text="formatCurrency(getSalaryGradePreview().hourly_rate, 4)"></div></div>
                                <div class="rounded border bg-white p-3"><div class="text-gray-500">Per Minute</div><div class="font-semibold text-gray-900" x-text="formatCurrency(getSalaryGradePreview().minute_rate, 6)"></div></div>
                                <div class="rounded border bg-white p-3"><div class="text-gray-500">Daily</div><div class="font-semibold text-gray-900" x-text="formatCurrency(getSalaryGradePreview().applicable_daily_rate)"></div></div>
                                <div class="rounded border bg-white p-3"><div class="text-gray-500">Yearly</div><div class="font-semibold text-gray-900" x-text="formatCurrency(getSalaryGradePreview().yearly_rate)"></div></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Date Created</label>
                                <input type="date" name="date_created" value="{{ now()->format('Y-m-d') }}" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Policy Number</label>
                                <input name="policy_number" class="w-full border rounded-md p-2 bg-white text-gray-900">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Attach File for Basis</label>
                            <input type="file" name="basis_file" @change="updatePreview($event)" class="w-full border rounded-md p-2 bg-white text-gray-900">
                        </div>
                    </form>

                    <form
                        x-show="activeForm === 'levels'"
                        x-ref="levelFormRef"
                        action="{{ route('human-capital.payroll.levels.store') }}"
                        method="POST"
                        enctype="multipart/form-data"
                        class="space-y-4"
                    >
                        @csrf
                        <div>
                            <label class="block text-sm font-medium mb-1">Salary Grade</label>
                            <select name="salary_grade_id" x-model="levelForm.salary_grade_id" @change="syncLevelComputationType()" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                                <option value="">Select salary grade</option>
                                <template x-for="grade in salaryGrades" :key="grade.id">
                                    <option :value="String(grade.id)" x-text="grade.name + ' (' + grade.code + ')'"></option>
                                </template>
                            </select>
                        </div>

                        <div x-show="selectedSalaryGrade(levelForm.salary_grade_id)" class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm space-y-1">
                            <div><span class="font-medium">Computation Type:</span> <span x-text="titleCase(selectedSalaryGrade(levelForm.salary_grade_id)?.payment_type || '')"></span></div>
                            <div><span class="font-medium">Monthly Basic:</span> <span x-text="formatCurrency(selectedSalaryGrade(levelForm.salary_grade_id)?.monthly_basic_pay || 0)"></span></div>
                            <div><span class="font-medium">ADR:</span> <span x-text="formatCurrency(selectedSalaryGrade(levelForm.salary_grade_id)?.applicable_daily_rate || 0)"></span></div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Payroll Level Name</label>
                            <input name="level_name" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Computation Type</label>
                            <input type="text" x-model="levelForm.computation_type" class="w-full border rounded-md p-2 bg-gray-100 text-gray-700" readonly>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Work Schedule</label>
                            <select name="work_schedule_label" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                                <option value="Monday to Sunday – 8:00 AM to 5:00 PM">Monday to Sunday – 8:00 AM to 5:00 PM</option>
                                <option value="Monday to Saturday – 8:00 AM to 5:00 PM">Monday to Saturday – 8:00 AM to 5:00 PM</option>
                                <option value="Monday to Friday – 8:00 AM to 5:00 PM">Monday to Friday – 8:00 AM to 5:00 PM</option>
                                <option value="Shifting Schedule">Shifting Schedule</option>
                                <option value="Night Shift">Night Shift</option>
                                <option value="Hybrid">Hybrid</option>
                                <option value="Work From Home">Work From Home</option>
                                <option value="Flexible">Flexible</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Hours Per Day</label>
                            <input type="number" step="0.01" name="hours_per_day" value="8" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Date Created</label>
                                <input type="date" name="date_created" value="{{ now()->format('Y-m-d') }}" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Policy Number</label>
                                <input name="policy_number" class="w-full border rounded-md p-2 bg-white text-gray-900">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Attach File for Basis</label>
                            <input type="file" name="basis_file" @change="updatePreview($event)" class="w-full border rounded-md p-2 bg-white text-gray-900">
                        </div>
                    </form>

                    @foreach (['benefits' => 'Benefit', 'allowances' => 'Allowance', 'deductions' => 'Deduction'] as $formKey => $label)
                        <form
                            x-show="activeForm === '{{ $formKey }}'"
                            x-ref="{{ $formKey }}Form"
                            action="{{ route('human-capital.payroll.'.$formKey.'.store') }}"
                            method="POST"
                            enctype="multipart/form-data"
                            class="space-y-4"
                        >
                            @csrf
                            <div>
                                <label class="block text-sm font-medium mb-1">Salary Grade</label>
                                <select name="salary_grade_id" x-model="componentForms.{{ $formKey }}.salary_grade_id" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                                    <option value="">Select salary grade</option>
                                    <template x-for="grade in salaryGrades" :key="grade.id">
                                        <option :value="String(grade.id)" x-text="grade.name + ' (' + grade.code + ')'"></option>
                                    </template>
                                </select>
                            </div>

                            <div x-show="selectedSalaryGrade(componentForms.{{ $formKey }}.salary_grade_id)" class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm space-y-1">
                                <div><span class="font-medium">Payment Type:</span> <span x-text="titleCase(selectedSalaryGrade(componentForms.{{ $formKey }}.salary_grade_id)?.payment_type || '')"></span></div>
                                <div><span class="font-medium">Monthly Basic:</span> <span x-text="formatCurrency(selectedSalaryGrade(componentForms.{{ $formKey }}.salary_grade_id)?.monthly_basic_pay || 0)"></span></div>
                                <div><span class="font-medium">ADR:</span> <span x-text="formatCurrency(selectedSalaryGrade(componentForms.{{ $formKey }}.salary_grade_id)?.applicable_daily_rate || 0)"></span></div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Payroll Level</label>
                                <select name="payroll_level_id" x-model="componentForms.{{ $formKey }}.payroll_level_id" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                                    <option value="">Select payroll level</option>
                                    <template x-for="level in payrollLevelsForGrade(componentForms.{{ $formKey }}.salary_grade_id)" :key="level.id">
                                        <option :value="String(level.id)" x-text="level.level_name"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">{{ $label }} Name</label>
                                <input name="name" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Type</label>
                                <select name="type" x-model="componentForms.{{ $formKey }}.type" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                                    <option value="fixed">Fixed</option>
                                    <option value="percentage">Percentage</option>
                                </select>
                            </div>

                            <div x-show="componentForms.{{ $formKey }}.type === 'percentage'">
                                <label class="block text-sm font-medium mb-1">Percentage</label>
                                <input type="number" step="0.01" name="rate" x-model="componentForms.{{ $formKey }}.rate" class="w-full border rounded-md p-2 bg-white text-gray-900" placeholder="0.00">
                            </div>

                            <div x-show="componentForms.{{ $formKey }}.type === 'fixed'">
                                <label class="block text-sm font-medium mb-1">Fixed Value</label>
                                <input type="number" step="0.01" name="value" x-model="componentForms.{{ $formKey }}.value" class="w-full border rounded-md p-2 bg-white text-gray-900" placeholder="0.00">
                            </div>

                            <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm">
                                <div class="font-semibold text-blue-900 mb-2">Computed {{ $label }} Value</div>
                                <div x-text="formatCurrency(getComponentValue('{{ $formKey }}'))" class="text-lg font-semibold text-gray-900"></div>
                            </div>

                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="is_active" value="1" checked>
                                Active
                            </label>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Date Created</label>
                                    <input type="date" name="date_created" value="{{ now()->format('Y-m-d') }}" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Policy Number</label>
                                    <input name="policy_number" class="w-full border rounded-md p-2 bg-white text-gray-900">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Attach File for Basis</label>
                                <input type="file" name="basis_file" @change="updatePreview($event)" class="w-full border rounded-md p-2 bg-white text-gray-900">
                            </div>
                        </form>
                    @endforeach

                    <form
                        x-show="activeForm === 'holidays'"
                        x-ref="holidaysForm"
                        action="{{ route('human-capital.payroll.holidays.store') }}"
                        method="POST"
                        enctype="multipart/form-data"
                        class="space-y-4"
                    >
                        @csrf
                        <div>
                            <label class="block text-sm font-medium mb-1">Salary Grade</label>
                            <select name="salary_grade_id" x-model="holidayForm.salary_grade_id" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                                <option value="">Select salary grade</option>
                                <template x-for="grade in salaryGrades" :key="grade.id">
                                    <option :value="String(grade.id)" x-text="grade.name + ' (' + grade.code + ')'"></option>
                                </template>
                            </select>
                        </div>

                        <div x-show="selectedSalaryGrade(holidayForm.salary_grade_id)" class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm space-y-1">
                            <div><span class="font-medium">Monthly Basic:</span> <span x-text="formatCurrency(selectedSalaryGrade(holidayForm.salary_grade_id)?.monthly_basic_pay || 0)"></span></div>
                            <div><span class="font-medium">Daily Rate:</span> <span x-text="formatCurrency(selectedSalaryGrade(holidayForm.salary_grade_id)?.applicable_daily_rate || 0)"></span></div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Payroll Level</label>
                            <select name="payroll_level_id" x-model="holidayForm.payroll_level_id" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                                <option value="">Select payroll level</option>
                                <template x-for="level in payrollLevelsForGrade(holidayForm.salary_grade_id)" :key="level.id">
                                    <option :value="String(level.id)" x-text="level.level_name"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Holiday Name</label>
                            <input name="name" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Holiday Date</label>
                            <input type="date" name="holiday_date" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Type of Holiday</label>
                            <select name="holiday_category" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                                <option value="regular">Regular</option>
                                <option value="special">Special</option>
                                <option value="regular_non_working">Regular Non-Working</option>
                                <option value="special_non_working">Special Non-Working</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Percentage</label>
                            <input type="number" step="0.01" name="percentage" x-model="holidayForm.percentage" class="w-full border rounded-md p-2 bg-white text-gray-900" placeholder="0.00" required>
                        </div>

                        <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm">
                            <div class="font-semibold text-blue-900 mb-2">Holiday Value Based on Daily Rate</div>
                            <div x-text="formatCurrency(getHolidayValue())" class="text-lg font-semibold text-gray-900"></div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Date Created</label>
                                <input type="date" name="date_created" value="{{ now()->format('Y-m-d') }}" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Policy Number</label>
                                <input name="policy_number" class="w-full border rounded-md p-2 bg-white text-gray-900">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Attach File for Basis</label>
                            <input type="file" name="basis_file" @change="updatePreview($event)" class="w-full border rounded-md p-2 bg-white text-gray-900">
                        </div>
                    </form>

                    <form
                        x-show="activeForm === 'periods'"
                        x-ref="periodsForm"
                        action="{{ route('human-capital.payroll.periods.store') }}"
                        method="POST"
                        enctype="multipart/form-data"
                        class="space-y-4"
                    >
                        @csrf
                        <div>
                            <label class="block text-sm font-medium mb-1">Name</label>
                            <input name="name" class="w-full border rounded-md p-2 bg-white text-gray-900" placeholder="1st Half Payroll" required>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Period Start</label>
                                <input type="date" name="period_start" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Period End</label>
                                <input type="date" name="period_end" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Payroll Start</label>
                                <input type="date" name="payroll_start" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Payroll End</label>
                                <input type="date" name="payroll_end" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Pay Date</label>
                                <input type="date" name="pay_date" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Dispute Start</label>
                                <input type="date" name="dispute_start" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Dispute End</label>
                                <input type="date" name="dispute_end" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Status</label>
                                <select name="status" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                                    <option value="draft">Draft</option>
                                    <option value="open">Open</option>
                                    <option value="processed">Processed</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Date Created</label>
                                <input type="date" name="date_created" value="{{ now()->format('Y-m-d') }}" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Policy Number</label>
                                <input name="policy_number" class="w-full border rounded-md p-2 bg-white text-gray-900">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Attach File for Basis</label>
                            <input type="file" name="basis_file" @change="updatePreview($event)" class="w-full border rounded-md p-2 bg-white text-gray-900">
                        </div>
                    </form>

                    <form x-show="activeForm === 'profiles'" x-ref="profilesForm" action="{{ route('human-capital.payroll.profiles.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium mb-1">Employee</label>
                            <select name="employee_id" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">
                                        {{ trim(($employee->first_name ?? '').' '.($employee->last_name ?? '')) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Payroll Level</label>
                            <select name="payroll_level_id" class="w-full border rounded-md p-2 bg-white text-gray-900" required>
                                @foreach($payrollLevels as $level)
                                    <option value="{{ $level->id }}">
                                        {{ $level->level_name }} - {{ $level->salaryGrade?->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Basic Salary Override</label>
                            <input type="number" step="0.01" name="basic_salary_override" class="w-full border rounded-md p-2 bg-white text-gray-900" placeholder="Optional">
                        </div>

                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="night_differential_enabled" value="1">
                            Enable Night Differential
                        </label>
                    </form>
                        </div>

                        <div class="p-6 border-t flex gap-2 shrink-0 bg-white">
                            <button type="button" @click="closeAddSection()" class="flex-1 border rounded py-2">Cancel</button>
                            <button type="button" @click="submitActiveForm()" class="flex-1 bg-blue-600 text-white rounded py-2 hover:bg-blue-700 transition">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function payrollPage() {
    return {
        tabs: [
            { key: 'salary_grades', label: 'Salary Grade' },
            { key: 'levels', label: 'Payroll Level' },
            { key: 'benefits', label: 'Benefits' },
            { key: 'allowances', label: 'Allowances' },
            { key: 'deductions', label: 'Deductions' },
            { key: 'holidays', label: 'Holidays' },
            { key: 'periods', label: 'Payroll Date' },
            { key: 'profiles', label: 'Payroll Summary Setup' },
            { key: 'summaries', label: 'Payroll Summary' },
        ],
        salaryGrades: @js($salaryGrades->map(fn ($item) => [
            'id' => $item->id,
            'code' => $item->code,
            'name' => $item->name,
            'payment_type' => $item->payment_type,
            'monthly_basic_pay' => (float) $item->monthly_basic_pay,
            'applicable_daily_rate' => (float) $item->applicable_daily_rate,
            'hourly_rate' => (float) $item->hourly_rate,
            'minute_rate' => (float) $item->minute_rate,
            'yearly_rate' => (float) $item->yearly_rate,
        ])),
        payrollLevels: @js($payrollLevels->map(fn ($item) => [
            'id' => $item->id,
            'salary_grade_id' => $item->salary_grade_id,
            'level_name' => $item->level_name,
            'computation_type' => $item->computation_type,
        ])),
        activeTab: 'salary_grades',
        showSlider: false,
        activeForm: null,
        sliderTitle: 'Add Entry',
        sliderError: '',
        previewFileUrl: '',
        previewFileName: '',
        previewMode: 'empty',
        salaryGradeForm: {
            payment_type: 'monthly',
            monthly_basic_pay: '',
            applicable_daily_rate: '',
        },
        levelForm: {
            salary_grade_id: '',
            computation_type: '',
        },
        componentForms: {
            benefits: { salary_grade_id: '', payroll_level_id: '', type: 'fixed', rate: '', value: '' },
            allowances: { salary_grade_id: '', payroll_level_id: '', type: 'fixed', rate: '', value: '' },
            deductions: { salary_grade_id: '', payroll_level_id: '', type: 'fixed', rate: '', value: '' },
        },
        holidayForm: {
            salary_grade_id: '',
            payroll_level_id: '',
            percentage: '',
        },

        openAddSection(section) {
            this.activeForm = section;
            this.sliderTitle = this.getFormTitle(section);
            this.sliderError = '';

            if (section === 'levels') {
                this.syncLevelComputationType();
            }

            this.showSlider = true;

            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.$refs.addPanel.classList.remove('translate-x-full');
                });
            });
        },

        closeAddSection() {
            if (this.$refs.addPanel) {
                this.$refs.addPanel.classList.add('translate-x-full');
            }

            this.clearPreview();

            setTimeout(() => {
                this.showSlider = false;
                this.activeForm = null;
                this.sliderError = '';
            }, 300);
        },

        getFormTitle(section) {
            const titles = {
                salary_grades: 'Add Salary Grade',
                levels: 'Add Payroll Level',
                benefits: 'Add Benefit',
                allowances: 'Add Allowance',
                deductions: 'Add Deduction',
                holidays: 'Add Holiday',
                periods: 'Add Payroll Date',
                profiles: 'Assign Payroll to Employee',
            };

            return titles[section] || 'Add Entry';
        },

        selectedSalaryGrade(id) {
            return this.salaryGrades.find((grade) => String(grade.id) === String(id)) || null;
        },

        payrollLevelsForGrade(salaryGradeId) {
            return this.payrollLevels.filter((level) => String(level.salary_grade_id) === String(salaryGradeId));
        },

        syncLevelComputationType() {
            this.levelForm.computation_type = this.selectedSalaryGrade(this.levelForm.salary_grade_id)?.payment_type || '';
        },

        getSalaryGradePreview() {
            const paymentType = this.salaryGradeForm.payment_type;
            const monthlyInput = parseFloat(this.salaryGradeForm.monthly_basic_pay || 0);
            const dailyInput = parseFloat(this.salaryGradeForm.applicable_daily_rate || 0);

            let monthlyBasic = 0;
            let dailyRate = 0;

            if (paymentType === 'daily') {
                dailyRate = dailyInput;
                monthlyBasic = (dailyRate * 313) / 12;
            } else {
                monthlyBasic = monthlyInput;
                dailyRate = (monthlyBasic * 12) / 365;
            }

            const hourlyRate = dailyRate / 8;
            const minuteRate = hourlyRate / 60;
            const yearlyRate = monthlyBasic * 12;

            return {
                monthly_basic_pay: monthlyBasic || 0,
                applicable_daily_rate: dailyRate || 0,
                hourly_rate: hourlyRate || 0,
                minute_rate: minuteRate || 0,
                yearly_rate: yearlyRate || 0,
            };
        },

        getComponentValue(formKey) {
            const form = this.componentForms[formKey];
            const grade = this.selectedSalaryGrade(form.salary_grade_id);
            const monthlyBasic = parseFloat(grade?.monthly_basic_pay || 0);

            if (form.type === 'percentage') {
                return monthlyBasic * (parseFloat(form.rate || 0) / 100);
            }

            return parseFloat(form.value || 0);
        },

        getHolidayValue() {
            const grade = this.selectedSalaryGrade(this.holidayForm.salary_grade_id);
            const dailyRate = parseFloat(grade?.applicable_daily_rate || 0);
            const percentage = parseFloat(this.holidayForm.percentage || 0);

            return dailyRate * (percentage / 100);
        },

        formatCurrency(value, decimals = 2) {
            const numericValue = Number(value || 0);
            return 'PHP ' + numericValue.toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            });
        },

        titleCase(value) {
            return String(value || '').replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
        },

        clearPreview() {
            if (this.previewFileUrl && this.previewFileUrl.startsWith('blob:')) {
                URL.revokeObjectURL(this.previewFileUrl);
            }

            this.previewFileUrl = '';
            this.previewFileName = '';
            this.previewMode = 'empty';
        },

        updatePreview(event) {
            const file = event.target.files?.[0];
            this.clearPreview();

            if (!file) {
                return;
            }

            const fileName = file.name.toLowerCase();
            this.previewFileName = file.name;

            if (file.type.includes('pdf') || fileName.endsWith('.pdf')) {
                this.previewFileUrl = URL.createObjectURL(file);
                this.previewMode = 'pdf';
                return;
            }

            if (
                file.type.startsWith('image/') ||
                fileName.endsWith('.jpg') ||
                fileName.endsWith('.jpeg') ||
                fileName.endsWith('.png')
            ) {
                this.previewFileUrl = URL.createObjectURL(file);
                this.previewMode = 'image';
                return;
            }

            this.previewMode = 'unsupported';
        },

        submitActiveForm() {
            this.sliderError = '';

            const refs = {
                salary_grades: 'salaryGradeForm',
                levels: 'levelFormRef',
                benefits: 'benefitsForm',
                allowances: 'allowancesForm',
                deductions: 'deductionsForm',
                holidays: 'holidaysForm',
                periods: 'periodsForm',
                profiles: 'profilesForm',
            };

            const formRef = refs[this.activeForm];
            const form = formRef ? this.$refs[formRef] : null;

            if (form) {
                form.submit();
            } else {
                this.sliderError = 'Unable to submit the form.';
            }
        },
    };
}
</script>
@endsection
