<?php

use App\Models\FinanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('finance dashboard shows the shared inventory history board to employees', function () {
    $employee = User::factory()->create([
        'name' => 'Inventory Employee',
        'email' => 'employee@example.com',
        'role' => 'employee',
    ]);

    FinanceRecord::query()->create([
        'module_key' => 'arf',
        'record_number' => 'ARF-00001',
        'record_title' => 'Consumable Inventory - Supplies',
        'record_date' => now()->toDateString(),
        'amount' => 0,
        'status' => 'Active',
        'workflow_status' => 'Accepted',
        'approval_status' => 'Approved',
        'submitted_by' => $employee->id,
        'submitted_at' => now(),
        'approved_by' => $employee->id,
        'approved_at' => now(),
        'data' => [
            'history' => [
                [
                    'action' => 'Stock Transfer',
                    'changed_by' => 'Inventory Employee',
                    'changed_at' => now()->subDay()->format('M d, Y h:i A'),
                    'reason' => 'Moved supplies to Warehouse A',
                    'new_values' => [
                        'current_quantity' => 42,
                        'available_quantity' => 35,
                        'location' => 'Warehouse A',
                        'department' => 'Operations',
                    ],
                ],
            ],
            'item_classification' => 'Consumable Inventory',
            'asset_code' => 'AST-00001',
        ],
        'attachments' => [],
        'user' => $employee->name,
    ]);

    $response = $this->actingAs($employee)->get(route('finance'));

    $response->assertOk();
    $response->assertSee('Inventory History Board');
    $response->assertSee('ARF-00001');
    $response->assertSee('Stock Transfer');
    $response->assertSee('Warehouse A');
});

test('admin finance dashboard shows the shared inventory history board', function () {
    $admin = User::factory()->create([
        'name' => 'Finance Admin',
        'email' => 'admin@example.com',
        'role' => 'admin',
    ]);

    FinanceRecord::query()->create([
        'module_key' => 'arf',
        'record_number' => 'ARF-00002',
        'record_title' => 'Office Consumables',
        'record_date' => now()->toDateString(),
        'amount' => 0,
        'status' => 'Active',
        'workflow_status' => 'Accepted',
        'approval_status' => 'Approved',
        'submitted_by' => $admin->id,
        'submitted_at' => now(),
        'approved_by' => $admin->id,
        'approved_at' => now(),
        'data' => [
            'history' => [
                [
                    'action' => 'Stock Adjustment',
                    'changed_by' => 'Finance Admin',
                    'changed_at' => now()->subHours(4)->format('M d, Y h:i A'),
                    'reason' => 'Adjusted counted stock during inventory audit.',
                    'new_values' => [
                        'current_quantity' => 18,
                        'available_quantity' => 15,
                        'location' => 'Main Storage',
                        'department' => 'Finance',
                    ],
                ],
            ],
            'item_classification' => 'Consumable Inventory',
            'asset_code' => 'AST-00002',
        ],
        'attachments' => [],
        'user' => $admin->name,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.finance.dashboard'));

    $response->assertOk();
    $response->assertSee('Inventory History Board');
    $response->assertSee('ARF-00002');
    $response->assertSee('Stock Adjustment');
    $response->assertSee('Main Storage');
});

test('finance preview html shows approval trail attachment summary and complete data', function () {
    $user = User::factory()->create([
        'name' => 'Finance Approver',
        'email' => 'approver@example.com',
        'role' => 'admin',
    ]);

    $record = FinanceRecord::query()->create([
        'module_key' => 'ca',
        'record_number' => 'CA-00010',
        'record_title' => 'Travel Cash Advance',
        'record_date' => now()->toDateString(),
        'amount' => 5000,
        'status' => 'Active',
        'workflow_status' => 'Accepted',
        'approval_status' => 'Approved',
        'submitted_by' => $user->id,
        'submitted_at' => now()->subDay(),
        'approved_by' => $user->id,
        'approved_at' => now(),
        'data' => [
            'purpose' => 'Field visit expense support',
            'amount_requested' => 5000,
            'approval_steps' => [
                [
                    'step' => 1,
                    'role' => 'President',
                    'user_id' => $user->id,
                    'user_name' => 'Finance Approver',
                ],
                [
                    'step' => 2,
                    'role' => 'Treasurer',
                    'user_id' => $user->id,
                    'user_name' => 'Finance Approver',
                ],
            ],
            'approval_actions' => [
                [
                    'approved_by' => $user->id,
                    'approved_by_name' => 'Finance Approver',
                    'approver_role' => 'President',
                    'approved_at' => now()->subHours(3)->format('Y-m-d H:i:s'),
                ],
                [
                    'approved_by' => $user->id,
                    'approved_by_name' => 'Finance Approver',
                    'approver_role' => 'Treasurer',
                    'approved_at' => now()->subHours(2)->format('Y-m-d H:i:s'),
                ],
            ],
            'transaction_time' => '09:15',
        ],
        'attachments' => [
            [
                'name' => 'ca-support.pdf',
                'path' => 'finance_documents/ca-support.pdf',
                'category' => 'Supporting Document',
                'uploaded_by' => 'Finance Approver',
                'uploaded_at' => now()->subHours(4)->format('Y-m-d H:i:s'),
            ],
            [
                'name' => 'receipt.jpg',
                'path' => 'finance_documents/receipt.jpg',
                'category' => 'Asset Photo',
                'uploaded_by' => 'Finance Approver',
                'uploaded_at' => now()->subHours(4)->format('Y-m-d H:i:s'),
            ],
        ],
        'user' => $user->name,
    ]);

    $response = $this->actingAs($user)->get(route('finance.preview.html', $record));

    $response->assertOk();
    $response->assertSee('Approval Trail');
    $response->assertSee('Attachment Summary');
    $response->assertSee('Complete Record Data');
    $response->assertSee('President');
    $response->assertSee('Treasurer');
    $response->assertSee('ca-support.pdf');
    $response->assertSee('receipt.jpg');
});
