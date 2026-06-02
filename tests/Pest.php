<?php

use App\Models\DirectorOfficer;
use App\Models\Employee;
use App\Models\GisRecord;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function seedFinanceOfficialApprovers(User $owner): array
{
    $president = User::factory()->create([
        'name' => 'Finance President',
        'email' => 'finance-president-' . $owner->id . '@example.com',
        'role' => 'admin',
    ]);
    Employee::query()->create([
        'user_id' => $president->id,
        'first_name' => 'Finance',
        'last_name' => 'President',
        'email' => $president->email,
        'position' => 'President',
        'payroll_type' => 'Monthly Paid',
        'basic_salary' => 0,
        'hourly_rate' => 0,
    ]);

    $treasurer = User::factory()->create([
        'name' => 'Finance Treasurer',
        'email' => 'finance-treasurer-' . $owner->id . '@example.com',
        'role' => 'admin',
    ]);
    Employee::query()->create([
        'user_id' => $treasurer->id,
        'first_name' => 'Finance',
        'last_name' => 'Treasurer',
        'email' => $treasurer->email,
        'position' => 'Treasurer',
        'payroll_type' => 'Monthly Paid',
        'basic_salary' => 0,
        'hourly_rate' => 0,
    ]);

    $gisRecord = GisRecord::query()->create([
        'uploaded_by' => $owner->name,
        'submission_status' => 'Submitted',
        'receive_on' => now()->toDateString(),
        'period_date' => now()->format('Y'),
        'corporation_name' => 'JKNC Holdings, Inc.',
        'trade_name' => 'JKNC',
        'approval_status' => 'Approved',
        'workflow_status' => 'Accepted',
        'submitted_by' => $owner->id,
        'approved_by' => $owner->id,
        'approved_at' => now(),
    ]);

    DirectorOfficer::query()->create([
        'gis_id' => $gisRecord->id,
        'officer_name' => $president->name,
        'address' => 'Test Address',
        'gender' => 'Male',
        'nationality' => 'Filipino',
        'incr' => false,
        'stockholder' => false,
        'board' => 'Board',
        'officer_type' => 'President',
        'committee' => 'N/A',
        'tin' => '000-000-000',
    ]);

    DirectorOfficer::query()->create([
        'gis_id' => $gisRecord->id,
        'officer_name' => $treasurer->name,
        'address' => 'Test Address',
        'gender' => 'Female',
        'nationality' => 'Filipino',
        'incr' => false,
        'stockholder' => false,
        'board' => 'Board',
        'officer_type' => 'Treasurer',
        'committee' => 'N/A',
        'tin' => '111-111-111',
    ]);

    return compact('president', 'treasurer', 'gisRecord');
}
