<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeePayrollProfile;
use App\Models\PayrollAllowance;
use App\Models\PayrollBenefit;
use App\Models\PayrollDeduction;
use App\Models\PayrollHoliday;
use App\Models\PayrollLevel;
use App\Models\PayrollPeriod;
use App\Models\PayrollSummary;
use App\Models\PayrollSummaryItem;
use App\Models\SalaryGrade;
use App\Services\PayrollCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PayrollController extends Controller
{
    public function index()
    {
        return view('human-capital.payroll', [
            'salaryGrades' => SalaryGrade::latest()->get(),
            'payrollLevels' => PayrollLevel::with('salaryGrade')->latest()->get(),
            'benefits' => PayrollBenefit::with(['salaryGrade', 'payrollLevel'])->latest()->get(),
            'allowances' => PayrollAllowance::with(['salaryGrade', 'payrollLevel'])->latest()->get(),
            'deductions' => PayrollDeduction::with(['salaryGrade', 'payrollLevel'])->latest()->get(),
            'holidays' => PayrollHoliday::with(['salaryGrade', 'payrollLevel'])->orderByDesc('holiday_date')->get(),
            'periods' => PayrollPeriod::latest()->get(),
            'employees' => Employee::orderBy('last_name')->orderBy('first_name')->get(),
            'profiles' => EmployeePayrollProfile::with(['employee', 'payrollLevel.salaryGrade'])->latest()->get(),
            'summaries' => PayrollSummary::with(['employee', 'period', 'payrollLevel.salaryGrade'])->latest()->get(),
        ]);
    }

    public function storeSalaryGrade(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:100'],
            'payment_type' => ['required', Rule::in(['daily', 'monthly'])],
            'monthly_basic_pay' => ['nullable', 'numeric', 'min:0'],
            'applicable_daily_rate' => ['nullable', 'numeric', 'min:0'],
            'date_created' => ['required', 'date'],
            'policy_number' => ['nullable', 'string', 'max:100'],
            'basis_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        if ($validated['payment_type'] === 'monthly' && ! is_numeric($validated['monthly_basic_pay'] ?? null)) {
            throw ValidationException::withMessages([
                'monthly_basic_pay' => 'Monthly basic pay is required for monthly paid salary grades.',
            ]);
        }

        if ($validated['payment_type'] === 'daily' && ! is_numeric($validated['applicable_daily_rate'] ?? null)) {
            throw ValidationException::withMessages([
                'applicable_daily_rate' => 'Applicable daily rate is required for daily paid salary grades.',
            ]);
        }

        $figures = $this->computeSalaryGradeFigures(
            $validated['payment_type'],
            $validated['monthly_basic_pay'] ?? null,
            $validated['applicable_daily_rate'] ?? null
        );

        SalaryGrade::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'payment_type' => $validated['payment_type'],
            'monthly_basic_pay' => $figures['monthly_basic_pay'],
            'applicable_daily_rate' => $figures['applicable_daily_rate'],
            'hourly_rate' => $figures['hourly_rate'],
            'minute_rate' => $figures['minute_rate'],
            'yearly_rate' => $figures['yearly_rate'],
            'date_created' => $validated['date_created'],
            'policy_number' => $validated['policy_number'] ?? null,
            'basis_file_path' => $this->storeBasisFile($request, 'salary-grades'),
        ]);

        return back()->with('success', 'Salary grade added successfully.');
    }

    public function storePayrollLevel(Request $request)
    {
        $validated = $request->validate([
            'salary_grade_id' => ['required', 'exists:salary_grades,id'],
            'level_name' => ['required', 'string', 'max:100'],
            'work_schedule_label' => ['required', Rule::in([
                'Monday to Sunday – 8:00 AM to 5:00 PM',
                'Monday to Saturday – 8:00 AM to 5:00 PM',
                'Monday to Friday – 8:00 AM to 5:00 PM',
                'Shifting Schedule',
                'Night Shift',
                'Hybrid',
                'Work From Home',
                'Flexible',
            ])],
            'hours_per_day' => ['required', 'numeric', 'min:1'],
            'date_created' => ['required', 'date'],
            'policy_number' => ['nullable', 'string', 'max:100'],
            'basis_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        $salaryGrade = SalaryGrade::findOrFail($validated['salary_grade_id']);

        PayrollLevel::create([
            'salary_grade_id' => $salaryGrade->id,
            'level_name' => $validated['level_name'],
            'computation_type' => $salaryGrade->payment_type,
            'work_schedule' => $this->mapPayrollWorkScheduleCode($validated['work_schedule_label']),
            'work_schedule_label' => $validated['work_schedule_label'],
            'hours_per_day' => $validated['hours_per_day'],
            'date_created' => $validated['date_created'],
            'policy_number' => $validated['policy_number'] ?? null,
            'basis_file_path' => $this->storeBasisFile($request, 'levels'),
        ]);

        return back()->with('success', 'Payroll level added successfully.');
    }

    public function storeBenefit(Request $request)
    {
        return $this->storeLinkedValueItem($request, PayrollBenefit::class, 'Benefit added successfully.');
    }

    public function storeAllowance(Request $request)
    {
        return $this->storeLinkedValueItem($request, PayrollAllowance::class, 'Allowance added successfully.');
    }

    public function storeDeduction(Request $request)
    {
        return $this->storeLinkedValueItem($request, PayrollDeduction::class, 'Deduction added successfully.');
    }

    public function storeHoliday(Request $request)
    {
        $validated = $request->validate([
            'salary_grade_id' => ['required', 'exists:salary_grades,id'],
            'payroll_level_id' => ['required', 'exists:payroll_levels,id'],
            'name' => ['required', 'string', 'max:150'],
            'holiday_date' => ['required', 'date'],
            'holiday_category' => ['required', Rule::in([
                'regular',
                'special',
                'regular_non_working',
                'special_non_working',
            ])],
            'percentage' => ['required', 'numeric', 'min:0'],
            'date_created' => ['required', 'date'],
            'policy_number' => ['nullable', 'string', 'max:100'],
            'basis_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        $salaryGrade = SalaryGrade::findOrFail($validated['salary_grade_id']);
        $this->assertLevelBelongsToGrade($validated['payroll_level_id'], $salaryGrade->id);

        $holidayType = str_starts_with($validated['holiday_category'], 'regular') ? 'regular' : 'special';
        $holidayValue = round((float) $salaryGrade->applicable_daily_rate * ((float) $validated['percentage'] / 100), 2);

        PayrollHoliday::create([
            'salary_grade_id' => $salaryGrade->id,
            'payroll_level_id' => $validated['payroll_level_id'],
            'name' => $validated['name'],
            'holiday_date' => $validated['holiday_date'],
            'holiday_type' => $holidayType,
            'holiday_category' => $validated['holiday_category'],
            'percentage' => $validated['percentage'],
            'holiday_value' => $holidayValue,
            'multiplier' => round((float) $validated['percentage'] / 100, 2),
            'date_created' => $validated['date_created'],
            'policy_number' => $validated['policy_number'] ?? null,
            'basis_file_path' => $this->storeBasisFile($request, 'holidays'),
        ]);

        return back()->with('success', 'Holiday added successfully.');
    }

    public function storePayrollPeriod(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'payroll_start' => ['required', 'date', 'after_or_equal:period_end'],
            'payroll_end' => ['required', 'date', 'after_or_equal:payroll_start'],
            'pay_date' => ['required', 'date', 'after_or_equal:payroll_end'],
            'dispute_start' => ['required', 'date'],
            'dispute_end' => ['required', 'date', 'after_or_equal:dispute_start'],
            'date_created' => ['required', 'date'],
            'policy_number' => ['nullable', 'string', 'max:100'],
            'basis_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'status' => ['required', 'in:draft,open,processed'],
        ]);

        PayrollPeriod::create([
            'name' => $validated['name'],
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'payroll_start' => $validated['payroll_start'],
            'payroll_end' => $validated['payroll_end'],
            'payroll_date' => $validated['payroll_start'],
            'pay_date' => $validated['pay_date'],
            'dispute_start' => $validated['dispute_start'],
            'dispute_end' => $validated['dispute_end'],
            'date_created' => $validated['date_created'],
            'policy_number' => $validated['policy_number'] ?? null,
            'basis_file_path' => $this->storeBasisFile($request, 'periods'),
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Payroll period added successfully.');
    }

    public function storeEmployeeProfile(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'payroll_level_id' => ['required', 'exists:payroll_levels,id'],
            'basic_salary_override' => ['nullable', 'numeric', 'min:0'],
            'night_differential_enabled' => ['nullable', 'boolean'],
        ]);

        $validated['night_differential_enabled'] = $request->boolean('night_differential_enabled');

        EmployeePayrollProfile::updateOrCreate(
            ['employee_id' => $validated['employee_id']],
            $validated
        );

        return back()->with('success', 'Employee payroll profile saved successfully.');
    }

    public function generateSummary(Request $request, PayrollCalculator $calculator)
    {
        $validated = $request->validate([
            'payroll_period_id' => ['required', 'exists:payroll_periods,id'],
        ]);

        $period = PayrollPeriod::findOrFail($validated['payroll_period_id']);

        $profiles = EmployeePayrollProfile::with(['employee', 'payrollLevel.salaryGrade'])->get();

        DB::transaction(function () use ($profiles, $period, $calculator) {
            foreach ($profiles as $profile) {
                if (! $profile->employee || ! $profile->payrollLevel || ! $profile->payrollLevel->salaryGrade) {
                    continue;
                }

                $computed = $calculator->compute($profile, $period);

                $summary = PayrollSummary::updateOrCreate(
                    [
                        'employee_id' => $profile->employee_id,
                        'payroll_period_id' => $period->id,
                    ],
                    [
                        'payroll_level_id' => $profile->payroll_level_id,
                        'computation_type' => $computed['computation_type'],
                        'gross_pay' => $computed['gross_pay'],
                        'total_benefits' => $computed['total_benefits'],
                        'total_allowances' => $computed['total_allowances'],
                        'total_deductions' => $computed['total_deductions'],
                        'night_differential_amount' => $computed['night_differential_amount'],
                        'holiday_pay_amount' => $computed['holiday_pay_amount'],
                        'net_pay' => $computed['net_pay'],
                        'breakdown_json' => $computed['breakdown'],
                        'status' => 'generated',
                    ]
                );

                PayrollSummaryItem::where('payroll_summary_id', $summary->id)->delete();

                foreach ($computed['items'] as $item) {
                    PayrollSummaryItem::create([
                        'payroll_summary_id' => $summary->id,
                        'item_type' => $item['item_type'],
                        'category' => $item['category'],
                        'name' => $item['name'],
                        'amount' => $item['amount'],
                        'meta_json' => $item['meta_json'] ?? null,
                    ]);
                }
            }
        });

        return back()->with('success', 'Payroll summaries generated successfully.');
    }

    public function showPayslip(PayrollSummary $summary)
    {
        $summary->load([
            'employee',
            'period',
            'payrollLevel.salaryGrade',
            'items',
        ]);

        return view('human-capital.payroll-payslip', compact('summary'));
    }

    private function storeLinkedValueItem(Request $request, string $modelClass, string $successMessage)
    {
        $validated = $request->validate([
            'salary_grade_id' => ['required', 'exists:salary_grades,id'],
            'payroll_level_id' => ['required', 'exists:payroll_levels,id'],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:fixed,percentage'],
            'rate' => ['nullable', 'numeric', 'min:0'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'date_created' => ['required', 'date'],
            'policy_number' => ['nullable', 'string', 'max:100'],
            'basis_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        $salaryGrade = SalaryGrade::findOrFail($validated['salary_grade_id']);
        $this->assertLevelBelongsToGrade($validated['payroll_level_id'], $salaryGrade->id);

        $rate = $validated['type'] === 'percentage'
            ? (float) ($validated['rate'] ?? 0)
            : (float) ($validated['value'] ?? 0);

        $value = $validated['type'] === 'percentage'
            ? round((float) $salaryGrade->monthly_basic_pay * ($rate / 100), 2)
            : round((float) ($validated['value'] ?? 0), 2);

        $modelClass::create([
            'salary_grade_id' => $salaryGrade->id,
            'payroll_level_id' => $validated['payroll_level_id'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'rate' => $rate,
            'value' => $value,
            'is_active' => $request->boolean('is_active', true),
            'date_created' => $validated['date_created'],
            'policy_number' => $validated['policy_number'] ?? null,
            'basis_file_path' => $this->storeBasisFile($request, strtolower(class_basename($modelClass)).'s'),
        ]);

        return back()->with('success', $successMessage);
    }

    private function computeSalaryGradeFigures(string $paymentType, ?float $monthlyBasicPay, ?float $dailyRate): array
    {
        if ($paymentType === 'daily') {
            $dailyRate = round((float) $dailyRate, 2);
            $monthlyBasicPay = round(($dailyRate * 313) / 12, 2);
        } else {
            $monthlyBasicPay = round((float) $monthlyBasicPay, 2);
            $dailyRate = round(($monthlyBasicPay * 12) / 365, 2);
        }

        $hourlyRate = round($dailyRate / 8, 4);
        $minuteRate = round($hourlyRate / 60, 6);
        $yearlyRate = round($monthlyBasicPay * 12, 2);

        return [
            'monthly_basic_pay' => $monthlyBasicPay,
            'applicable_daily_rate' => $dailyRate,
            'hourly_rate' => $hourlyRate,
            'minute_rate' => $minuteRate,
            'yearly_rate' => $yearlyRate,
        ];
    }

    private function assertLevelBelongsToGrade(int|string $payrollLevelId, int|string $salaryGradeId): void
    {
        $level = PayrollLevel::findOrFail($payrollLevelId);

        if ((int) $level->salary_grade_id !== (int) $salaryGradeId) {
            throw ValidationException::withMessages([
                'payroll_level_id' => 'The selected payroll level does not belong to the selected salary grade.',
            ]);
        }
    }


private function mapPayrollWorkScheduleCode(?string $label): ?string
{
    return match ($label) {
        'Monday to Sunday – 8:00 AM to 5:00 PM' => 'every_day',
        'Monday to Saturday – 8:00 AM to 5:00 PM' => 'no_sunday',
        'Monday to Friday – 8:00 AM to 5:00 PM' => 'no_sat_sun',
        default => null,
    };
}

    private function storeBasisFile(Request $request, string $directory): ?string
    {
        if (! $request->hasFile('basis_file')) {
            return null;
        }

        return $request->file('basis_file')->store('payroll/'.$directory, 'public');
    }
}
