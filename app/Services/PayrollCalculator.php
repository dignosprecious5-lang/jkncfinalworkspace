<?php

namespace App\Services;

use App\Models\PayrollAllowance;
use App\Models\PayrollBenefit;
use App\Models\PayrollDeduction;
use App\Models\PayrollPeriod;
use App\Models\EmployeePayrollProfile;

class PayrollCalculator
{
    public function compute(EmployeePayrollProfile $profile, PayrollPeriod $period): array
    {
        $level = $profile->payrollLevel;
        $grade = $level->salaryGrade;

        $adr = (float) $grade->applicable_daily_rate;
        $hoursPerDay = (float) $level->hours_per_day;
        $hourlyRate = $hoursPerDay > 0 ? $adr / $hoursPerDay : 0;
        $minuteRate = $hourlyRate / 60;

        if ($profile->basic_salary_override !== null) {
            $monthlyBasic = (float) $profile->basic_salary_override;
        } else {
            $monthlyBasic = $level->computation_type === 'monthly'
                ? ($adr * 365) / 12
                : ($adr * $this->getDailyFactor($level->work_schedule)) / 12;
        }

        $yearlyBasic = $monthlyBasic * 12;
        $dailyEquivalent = $adr;

        $benefits = PayrollBenefit::where('is_active', true)->get();
        $allowances = PayrollAllowance::where('is_active', true)->get();
        $deductions = PayrollDeduction::where('is_active', true)->get();

        $totalBenefits = 0;
        $totalAllowances = 0;
        $totalDeductions = 0;
        $nightDiff = 0;     // placeholder
        $holidayPay = 0;    // placeholder

        $items = [];

        $items[] = [
            'item_type' => 'earning',
            'category' => 'basic_salary',
            'name' => 'Basic Salary',
            'amount' => round($monthlyBasic, 2),
            'meta_json' => [
                'yearly' => round($yearlyBasic, 2),
                'monthly' => round($monthlyBasic, 2),
                'daily' => round($dailyEquivalent, 2),
                'hourly' => round($hourlyRate, 2),
                'minute' => round($minuteRate, 4),
            ],
        ];

        foreach ($benefits as $benefit) {
            $amount = $benefit->type === 'percentage'
                ? ($monthlyBasic * ((float) $benefit->value / 100))
                : (float) $benefit->value;

            $amount = round($amount, 2);
            $totalBenefits += $amount;

            $items[] = [
                'item_type' => 'earning',
                'category' => 'benefit',
                'name' => $benefit->name,
                'amount' => $amount,
                'meta_json' => [
                    'type' => $benefit->type,
                    'value' => $benefit->value,
                ],
            ];
        }

        foreach ($allowances as $allowance) {
            $amount = $allowance->type === 'percentage'
                ? ($monthlyBasic * ((float) $allowance->value / 100))
                : (float) $allowance->value;

            $amount = round($amount, 2);
            $totalAllowances += $amount;

            $items[] = [
                'item_type' => 'earning',
                'category' => 'allowance',
                'name' => $allowance->name,
                'amount' => $amount,
                'meta_json' => [
                    'type' => $allowance->type,
                    'value' => $allowance->value,
                    'yearly' => round($amount * 12, 2),
                    'monthly' => round($amount, 2),
                    'daily' => round($amount / 30, 2),
                    'hourly' => round(($amount / 30) / max($hoursPerDay, 1), 2),
                    'minute' => round((($amount / 30) / max($hoursPerDay, 1)) / 60, 4),
                ],
            ];
        }

        foreach ($deductions as $deduction) {
            $amount = $deduction->type === 'percentage'
                ? ($monthlyBasic * ((float) $deduction->value / 100))
                : (float) $deduction->value;

            $amount = round($amount, 2);
            $totalDeductions += $amount;

            $items[] = [
                'item_type' => 'deduction',
                'category' => 'deduction',
                'name' => $deduction->name,
                'amount' => $amount,
                'meta_json' => [
                    'type' => $deduction->type,
                    'value' => $deduction->value,
                ],
            ];
        }

        if ($profile->night_differential_enabled) {
            $nightDiff = 0;
            $items[] = [
                'item_type' => 'earning',
                'category' => 'night_differential',
                'name' => 'Night Differential',
                'amount' => 0,
                'meta_json' => [
                    'formula' => 'Hourly Rate x 10% x Night Hours Worked',
                ],
            ];
        }

        $grossPay = round($monthlyBasic + $totalBenefits + $totalAllowances + $nightDiff + $holidayPay, 2);
        $netPay = round($grossPay - $totalDeductions, 2);

        return [
            'computation_type' => $level->computation_type,
            'gross_pay' => $grossPay,
            'total_benefits' => round($totalBenefits, 2),
            'total_allowances' => round($totalAllowances, 2),
            'total_deductions' => round($totalDeductions, 2),
            'night_differential_amount' => round($nightDiff, 2),
            'holiday_pay_amount' => round($holidayPay, 2),
            'net_pay' => $netPay,
            'breakdown' => [
                'applicable_daily_rate' => round($adr, 2),
                'monthly_basic_salary' => round($monthlyBasic, 2),
                'yearly_basic_salary' => round($yearlyBasic, 2),
                'hourly_rate' => round($hourlyRate, 2),
                'minute_rate' => round($minuteRate, 4),
                'work_schedule' => $level->work_schedule,
                'hours_per_day' => $hoursPerDay,
            ],
            'items' => $items,
        ];
    }

    protected function getDailyFactor(?string $workSchedule): float
    {
        return match ($workSchedule) {
            'every_day' => 395.0,
            'no_sunday' => 313.0,
            default => 261.0,
        };
    }
}