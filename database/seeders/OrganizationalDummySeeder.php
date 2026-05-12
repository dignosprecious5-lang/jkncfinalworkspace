<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationalDummySeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Dummy Organizational Data for Testing Recruitment
        |--------------------------------------------------------------------------
        | Creates:
        | Address → Branch → Office → Department → Division → Unit → Position
        */

        $addressId = DB::table('organizational_addresses')->insertGetId([
            'country' => 'Philippines',
            'region_code' => '07',
            'region_name' => 'Central Visayas',
            'province_code' => '0722',
            'province_name' => 'Cebu',
            'province_type' => 'Province',
            'city_code' => '072230',
            'city_name' => 'Cebu City',
            'barangay_code' => '072230001',
            'barangay_name' => 'Capitol Site',
            'street_address' => 'Dummy Street, Capitol Site',
            'subdivision_building' => 'JK&C Main Building',
            'unit_no' => 'Unit 101',
            'postal_code' => '6000',
            'full_address' => 'Unit 101, JK&C Main Building, Dummy Street, Capitol Site, Cebu City, Cebu, Philippines 6000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $branchId = DB::table('branches')->insertGetId([
            'branch_name' => 'Cebu Main Branch',
            'address_id' => $addressId,
            'branch_head' => 'John Kelly Abalde',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $officeId = DB::table('offices')->insertGetId([
            'office_name' => 'Main Office',
            'branch_id' => $branchId,
            'address_id' => $addressId,
            'office_head' => 'Maria Santos',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $departmentId = DB::table('departments')->insertGetId([
            'department_name' => 'Human Capital Department',
            'office_id' => $officeId,
            'address_id' => $addressId,
            'department_head' => 'Angela Reyes',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $divisionId = DB::table('divisions')->insertGetId([
            'division_name' => 'Recruitment Division',
            'department_id' => $departmentId,
            'address_id' => $addressId,
            'division_head' => 'Michael Cruz',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $unitId = DB::table('units')->insertGetId([
            'unit_name' => 'Talent Acquisition Unit',
            'division_id' => $divisionId,
            'address_id' => $addressId,
            'unit_head' => 'Sarah Lim',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('positions')->insert([
            [
                'position_name' => 'HR Assistant',
                'unit_id' => $unitId,
                'address_id' => $addressId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'position_name' => 'Recruitment Staff',
                'unit_id' => $unitId,
                'address_id' => $addressId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'position_name' => 'Administrative Assistant',
                'unit_id' => $unitId,
                'address_id' => $addressId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'position_name' => 'OJT Intern',
                'unit_id' => $unitId,
                'address_id' => $addressId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}