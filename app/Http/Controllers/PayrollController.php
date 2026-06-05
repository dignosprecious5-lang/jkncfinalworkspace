<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RequestsHumanCapitalApproval;
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
use App\Support\HumanCapitalLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PayrollController extends Controller
{
    use RequestsHumanCapitalApproval;
    private const WORK_SCHEDULE_LABELS = [
        'Monday to Sunday - 8:00 AM to 5:00 PM',
        'Monday to Saturday - 8:00 AM to 5:00 PM',
        'Monday to Friday - 8:00 AM to 5:00 PM',
        'Monday to Sunday â€“ 8:00 AM to 5:00 PM',
        'Monday to Saturday â€“ 8:00 AM to 5:00 PM',
        'Monday to Friday â€“ 8:00 AM to 5:00 PM',
        'Shifting Schedule',
        'Night Shift',
        'Hybrid',
        'Work From Home',
        'Flexible',
    ];

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
        $salaryGrade = SalaryGrade::create($this->salaryGradePayload($request));
        $this->logPayrollCreated($request, 'Salary Grade', $salaryGrade);

        return back()->with('success', 'Salary grade added successfully.');
    }

    public function updateSalaryGrade(Request $request, SalaryGrade $salaryGrade)
    {
        $this->requestHumanCapitalChange($request, 'Payroll', 'update', $salaryGrade, $this->salaryGradePayload($request, $salaryGrade->basis_file_path));

        return back()->with('success', 'Salary grade update submitted for admin approval.');
    }

    public function destroySalaryGrade(SalaryGrade $salaryGrade)
    {
        $this->requestHumanCapitalChange(request(), 'Payroll', 'delete', $salaryGrade, null, $this->modelLabel($salaryGrade));

        return back()->with('success', 'Salary grade deletion submitted for admin approval.');
    }

    public function storePayrollLevel(Request $request)
    {
        $level = PayrollLevel::create($this->payrollLevelPayload($request));
        $this->logPayrollCreated($request, 'Payroll Level', $level);

        return back()->with('success', 'Payroll level added successfully.');
    }

    public function updatePayrollLevel(Request $request, PayrollLevel $level)
    {
        $this->requestHumanCapitalChange($request, 'Payroll', 'update', $level, $this->payrollLevelPayload($request, $level->basis_file_path));

        return back()->with('success', 'Payroll level update submitted for admin approval.');
    }

    public function destroyPayrollLevel(PayrollLevel $level)
    {
        $this->requestHumanCapitalChange(request(), 'Payroll', 'delete', $level, null, $this->modelLabel($level));

        return back()->with('success', 'Payroll level deletion submitted for admin approval.');
    }

    public function storeBenefit(Request $request)
    {
        return $this->storeLinkedValueItem($request, PayrollBenefit::class, 'Benefit', 'Benefit added successfully.');
    }

    public function updateBenefit(Request $request, PayrollBenefit $benefit)
    {
        $this->requestHumanCapitalChange($request, 'Payroll', 'update', $benefit, $this->linkedValuePayload($request, PayrollBenefit::class, $benefit->basis_file_path));

        return back()->with('success', 'Benefit update submitted for admin approval.');
    }

    public function destroyBenefit(PayrollBenefit $benefit)
    {
        $this->requestHumanCapitalChange(request(), 'Payroll', 'delete', $benefit, null, $this->modelLabel($benefit));

        return back()->with('success', 'Benefit deletion submitted for admin approval.');
    }

    public function storeAllowance(Request $request)
    {
        return $this->storeLinkedValueItem($request, PayrollAllowance::class, 'Allowance', 'Allowance added successfully.');
    }

    public function updateAllowance(Request $request, PayrollAllowance $allowance)
    {
        $this->requestHumanCapitalChange($request, 'Payroll', 'update', $allowance, $this->linkedValuePayload($request, PayrollAllowance::class, $allowance->basis_file_path));

        return back()->with('success', 'Allowance update submitted for admin approval.');
    }

    public function destroyAllowance(PayrollAllowance $allowance)
    {
        $this->requestHumanCapitalChange(request(), 'Payroll', 'delete', $allowance, null, $this->modelLabel($allowance));

        return back()->with('success', 'Allowance deletion submitted for admin approval.');
    }

    public function storeDeduction(Request $request)
    {
        return $this->storeLinkedValueItem($request, PayrollDeduction::class, 'Deduction', 'Deduction added successfully.');
    }

    public function updateDeduction(Request $request, PayrollDeduction $deduction)
    {
        $this->requestHumanCapitalChange($request, 'Payroll', 'update', $deduction, $this->linkedValuePayload($request, PayrollDeduction::class, $deduction->basis_file_path));

        return back()->with('success', 'Deduction update submitted for admin approval.');
    }

    public function destroyDeduction(PayrollDeduction $deduction)
    {
        $this->requestHumanCapitalChange(request(), 'Payroll', 'delete', $deduction, null, $this->modelLabel($deduction));

        return back()->with('success', 'Deduction deletion submitted for admin approval.');
    }

    public function storeHoliday(Request $request)
    {
        $holiday = PayrollHoliday::create($this->holidayPayload($request));
        $this->logPayrollCreated($request, 'Holiday', $holiday);

        return back()->with('success', 'Holiday added successfully.');
    }

    public function updateHoliday(Request $request, PayrollHoliday $holiday)
    {
        $this->requestHumanCapitalChange($request, 'Payroll', 'update', $holiday, $this->holidayPayload($request, $holiday->basis_file_path));

        return back()->with('success', 'Holiday update submitted for admin approval.');
    }

    public function destroyHoliday(PayrollHoliday $holiday)
    {
        $this->requestHumanCapitalChange(request(), 'Payroll', 'delete', $holiday, null, $this->modelLabel($holiday));

        return back()->with('success', 'Holiday deletion submitted for admin approval.');
    }

    public function storePayrollPeriod(Request $request)
    {
        $period = PayrollPeriod::create($this->periodPayload($request));
        $this->logPayrollCreated($request, 'Payroll Period', $period);

        return back()->with('success', 'Payroll period added successfully.');
    }

    public function updatePayrollPeriod(Request $request, PayrollPeriod $period)
    {
        $this->requestHumanCapitalChange($request, 'Payroll', 'update', $period, $this->periodPayload($request, $period->basis_file_path));

        return back()->with('success', 'Payroll period update submitted for admin approval.');
    }

    public function destroyPayrollPeriod(PayrollPeriod $period)
    {
        $this->requestHumanCapitalChange(request(), 'Payroll', 'delete', $period, null, $this->modelLabel($period));

        return back()->with('success', 'Payroll period deletion submitted for admin approval.');
    }

    public function storeEmployeeProfile(Request $request)
    {
        $payload = $this->employeeProfilePayload($request);

        EmployeePayrollProfile::updateOrCreate(
            ['employee_id' => $payload['employee_id']],
            $payload
        );
        $profile = EmployeePayrollProfile::where('employee_id', $payload['employee_id'])->first();
        if ($profile) {
            $this->logPayrollCreated($request, 'Employee Payroll Profile', $profile, 'Employee payroll profile saved');
        }

        return back()->with('success', 'Employee payroll profile saved successfully.');
    }

    public function updateEmployeeProfile(Request $request, EmployeePayrollProfile $profile)
    {
        $this->requestHumanCapitalChange($request, 'Payroll', 'update', $profile, $this->employeeProfilePayload($request), $this->modelLabel($profile));

        return back()->with('success', 'Employee payroll profile update submitted for admin approval.');
    }

    public function destroyEmployeeProfile(EmployeePayrollProfile $profile)
    {
        $this->requestHumanCapitalChange(request(), 'Payroll', 'delete', $profile, null, $this->modelLabel($profile));

        return back()->with('success', 'Employee payroll profile deletion submitted for admin approval.');
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

        HumanCapitalLogger::log($request, [
            'module' => 'Payroll',
            'action' => 'generated_summary',
            'subject_type' => PayrollPeriod::class,
            'subject_id' => $period->id,
            'subject_name' => $period->name,
            'description' => 'Generated payroll summaries for '.$period->name.'.',
            'new_values' => ['payroll_period_id' => $period->id, 'period_name' => $period->name],
        ]);

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

    public function updateSummary(Request $request, PayrollSummary $summary)
    {
        $validated = $request->validate([
            'payroll_level_id' => ['required', 'exists:payroll_levels,id'],
            'gross_pay' => ['required', 'numeric', 'min:0'],
            'total_benefits' => ['required', 'numeric', 'min:0'],
            'total_allowances' => ['required', 'numeric', 'min:0'],
            'total_deductions' => ['required', 'numeric', 'min:0'],
            'night_differential_amount' => ['required', 'numeric', 'min:0'],
            'holiday_pay_amount' => ['required', 'numeric', 'min:0'],
            'net_pay' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['generated', 'approved', 'released'])],
        ]);

        $level = PayrollLevel::findOrFail($validated['payroll_level_id']);
        $validated['computation_type'] = $level->computation_type;

        $this->requestHumanCapitalChange($request, 'Payroll', 'update', $summary, $validated, $this->modelLabel($summary));

        return back()->with('success', 'Payroll summary update submitted for admin approval.');
    }

    public function destroySummary(PayrollSummary $summary)
    {
        $this->requestHumanCapitalChange(request(), 'Payroll', 'delete', $summary, null, $this->modelLabel($summary));

        return back()->with('success', 'Payroll summary deletion submitted for admin approval.');
    }

    private function storeLinkedValueItem(Request $request, string $modelClass, string $moduleLabel, string $successMessage)
    {
        $model = $modelClass::create($this->linkedValuePayload($request, $modelClass));
        $this->logPayrollCreated($request, $moduleLabel, $model);

        return back()->with('success', $successMessage);
    }

    private function salaryGradePayload(Request $request, ?string $existingBasisFile = null): array
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

        return [
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
            'basis_file_path' => $this->storeBasisFile($request, 'salary-grades') ?: $existingBasisFile,
        ];
    }

    private function payrollLevelPayload(Request $request, ?string $existingBasisFile = null): array
    {
        $validated = $request->validate([
            'salary_grade_id' => ['required', 'exists:salary_grades,id'],
            'level_name' => ['required', 'string', 'max:100'],
            'work_schedule_label' => ['required', Rule::in(self::WORK_SCHEDULE_LABELS)],
            'hours_per_day' => ['required', 'numeric', 'min:1'],
            'date_created' => ['required', 'date'],
            'policy_number' => ['nullable', 'string', 'max:100'],
            'basis_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        $salaryGrade = SalaryGrade::findOrFail($validated['salary_grade_id']);

        return [
            'salary_grade_id' => $salaryGrade->id,
            'level_name' => $validated['level_name'],
            'computation_type' => $salaryGrade->payment_type,
            'work_schedule' => $this->mapPayrollWorkScheduleCode($validated['work_schedule_label']),
            'work_schedule_label' => $validated['work_schedule_label'],
            'hours_per_day' => $validated['hours_per_day'],
            'date_created' => $validated['date_created'],
            'policy_number' => $validated['policy_number'] ?? null,
            'basis_file_path' => $this->storeBasisFile($request, 'levels') ?: $existingBasisFile,
        ];
    }

    private function linkedValuePayload(Request $request, string $modelClass, ?string $existingBasisFile = null): array
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

        return [
            'salary_grade_id' => $salaryGrade->id,
            'payroll_level_id' => $validated['payroll_level_id'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'rate' => $rate,
            'value' => $value,
            'is_active' => $request->boolean('is_active', true),
            'date_created' => $validated['date_created'],
            'policy_number' => $validated['policy_number'] ?? null,
            'basis_file_path' => $this->storeBasisFile($request, strtolower(class_basename($modelClass)).'s') ?: $existingBasisFile,
        ];
    }

    private function holidayPayload(Request $request, ?string $existingBasisFile = null): array
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

        return [
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
            'basis_file_path' => $this->storeBasisFile($request, 'holidays') ?: $existingBasisFile,
        ];
    }

    private function periodPayload(Request $request, ?string $existingBasisFile = null): array
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

        return [
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
            'basis_file_path' => $this->storeBasisFile($request, 'periods') ?: $existingBasisFile,
            'status' => $validated['status'],
        ];
    }

    private function employeeProfilePayload(Request $request): array
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'payroll_level_id' => ['required', 'exists:payroll_levels,id'],
            'basic_salary_override' => ['nullable', 'numeric', 'min:0'],
            'night_differential_enabled' => ['nullable', 'boolean'],
        ]);

        $validated['night_differential_enabled'] = $request->boolean('night_differential_enabled');

        return $validated;
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
            'Monday to Sunday - 8:00 AM to 5:00 PM', 'Monday to Sunday â€“ 8:00 AM to 5:00 PM' => 'every_day',
            'Monday to Saturday - 8:00 AM to 5:00 PM', 'Monday to Saturday â€“ 8:00 AM to 5:00 PM' => 'no_sunday',
            'Monday to Friday - 8:00 AM to 5:00 PM', 'Monday to Friday â€“ 8:00 AM to 5:00 PM' => 'no_sat_sun',
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

    private function logPayrollCreated(Request $request, string $recordType, Model $model, ?string $description = null): void
    {
        HumanCapitalLogger::logModelChange(
            $request,
            'Payroll',
            'created',
            $model,
            null,
            $this->snapshot($model),
            $this->modelLabel($model),
            $description ?: "{$recordType} created."
        );
    }

    private function logPayrollUpdated(Request $request, string $recordType, Model $model, array $oldValues): void
    {
        HumanCapitalLogger::logModelChange(
            $request,
            'Payroll',
            'updated',
            $model,
            $oldValues,
            $this->snapshot($model),
            $this->modelLabel($model),
            "{$recordType} updated."
        );
    }

    private function logPayrollDeleted(Request $request, string $recordType, Model $model, array $oldValues, string $subjectName): void
    {
        HumanCapitalLogger::logModelChange(
            $request,
            'Payroll',
            'deleted',
            $model,
            $oldValues,
            null,
            $subjectName,
            "{$recordType} deleted."
        );
    }

    private function snapshot(Model $model): array
    {
        return collect($model->getAttributes())
            ->except(['created_at', 'updated_at'])
            ->all();
    }

    private function modelLabel(Model $model): string
    {
        foreach (['name', 'level_name', 'employee_code', 'code'] as $field) {
            if (filled($model->{$field} ?? null)) {
                return (string) $model->{$field};
            }
        }

        if ($model instanceof PayrollSummary) {
            return trim(($model->employee?->full_name ?? 'Employee').' - '.($model->period?->name ?? 'Payroll Summary'));
        }

        if ($model instanceof EmployeePayrollProfile) {
            return trim(($model->employee?->full_name ?? 'Employee').' Payroll Profile');
        }

        return class_basename($model).' #'.$model->getKey();
    }
}
