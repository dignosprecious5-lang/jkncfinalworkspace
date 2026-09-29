<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use App\Models\UserPosition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeePositionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Departments
        |--------------------------------------------------------------------------
        */

        $departments = [
            'Operations Department',
            'Executive Office',
            'Finance Department',
            'Sales & Marketing Department',
        ];

        $departmentModels = [];

        foreach ($departments as $name) {
            $departmentModels[$name] = Department::firstOrCreate([
                'name' => $name,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Positions
        |--------------------------------------------------------------------------
        */

        $positions = [
            'Consultant',
            'Associate',
            'Compliance Associate',
            'Intern',
            'General Services Personnel',
            'President & Chief Executive Officer',
            'Reviewer',
            'Approver',
            'Sales & Marketing',
            'Lead Consultant',
            'Lead Associate',
            'Finance',
        ];

        $positionModels = [];

        foreach ($positions as $name) {
            $positionModels[$name] = Position::firstOrCreate([
                'name' => $name,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Employees
        |--------------------------------------------------------------------------
        */

        $employees = [
            [
                'name' => 'Carmela Ortiz',
                'employee_id' => '21223',
                'email' => 'carmela.ortiz@example.com',
                'position' => 'Compliance Associate',
                'department' => 'Operations Department',
            ],
            [
                'name' => 'Christian Hingpit',
                'employee_id' => '11037',
                'email' => 'christian.hingpit@example.com',
                'position' => 'Intern',
                'department' => null,
            ],
            [
                'name' => 'CRISTY ESTRELLA',
                'employee_id' => '77812',
                'email' => 'cristy.estrella@example.com',
                'position' => 'Intern',
                'department' => 'Operations Department',
            ],
            [
                'name' => 'Ernesto Tagsip',
                'employee_id' => '97775',
                'email' => 'ernesto.tagsip@example.com',
                'position' => 'General Services Personnel',
                'department' => 'Operations Department',
            ],
            [
                'name' => 'Immaculate Espina',
                'employee_id' => '23508',
                'email' => 'immaculate.espina@example.com',
                'position' => 'Compliance Associate',
                'department' => 'Operations Department',
            ],
            [
                'name' => 'John Kelly Abalde',
                'employee_id' => '27910',
                'email' => 'john.kelly.abalde@example.com',
                'position' => 'President & Chief Executive Officer',
                'department' => 'Executive Office',
            ],
            [
                'name' => 'John Mark Torres',
                'employee_id' => '93772',
                'email' => 'john.mark.torres.93772@example.com',
                'position' => 'Intern',
                'department' => 'Operations Department',
            ],
            [
                'name' => 'John Mark Torres',
                'employee_id' => '39030',
                'email' => 'john.mark.torres.39030@example.com',
                'position' => 'Intern',
                'department' => 'Operations Department',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Create Employees and Position Assignments
        |--------------------------------------------------------------------------
        */

        foreach ($employees as $employee) {
            $user = User::updateOrCreate(
                [
                    'employee_id' => $employee['employee_id'],
                ],
                [
                    'name' => $employee['name'],
                    'email' => $employee['email'],
                    'password' => Hash::make('password'),
                ]
            );

            $position = $positionModels[$employee['position']];

            $department = $employee['department']
                ? $departmentModels[$employee['department']]
                : null;

            UserPosition::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'position_id' => $position->id,
                    'department_id' => $department?->id,
                ]
            );
        }
    }
}