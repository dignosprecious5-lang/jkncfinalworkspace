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
            'created_by_name' => 'Finance Approver',
            'requested_by_name' => 'Finance Approver',
            'submitted_by_name' => 'Finance Approver',
            'approved_by_name' => 'Finance Approver',
            'reverted_by_name' => 'Finance Approver',
            'held_by_name' => 'Finance Approver',
            'updated_by_name' => 'Finance Approver',
            'released_by_name' => 'Finance Approver',
            'received_by_name' => 'Finance Approver',
            'liquidated_by_name' => 'Finance Approver',
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
    $response->assertSee('Created By');
    $response->assertSee('Requested By');
    $response->assertSee('Submitted By');
    $response->assertSee('Approved By');
    $response->assertSee('Reverted By');
    $response->assertSee('Held By');
    $response->assertSee('Updated By');
    $response->assertSee('Released By');
    $response->assertSee('Received By');
    $response->assertSee('Liquidated By');
    $response->assertSee('President');
    $response->assertSee('Treasurer');
    $response->assertSee('ca-support.pdf');
    $response->assertSee('receipt.jpg');
});

test('finance reporting uses live system data for dashboards and previews', function () {
    $admin = User::factory()->create([
        'name' => 'Finance Reporting Admin',
        'email' => 'reporting.admin@example.com',
        'role' => 'admin',
    ]);

    $record = FinanceRecord::query()->create([
        'module_key' => 'ca',
        'record_number' => 'CA-REPORT-01',
        'record_title' => 'Live Reporting Cash Advance',
        'record_date' => now()->toDateString(),
        'amount' => 7200,
        'status' => 'Active',
        'workflow_status' => 'Accepted',
        'approval_status' => 'Approved',
        'submitted_by' => $admin->id,
        'submitted_at' => now()->subDay(),
        'approved_by' => $admin->id,
        'approved_at' => now(),
        'data' => [
            'requestor' => 'Finance Reporting Admin',
            'purpose' => 'Live report verification.',
            'relationship_status' => 'Awaiting Liquidation',
            'next_action' => 'Submit Liquidation Report',
            'transaction_progress' => [
                ['label' => 'Request Created', 'completed' => true, 'state' => 'completed'],
                ['label' => 'Approved', 'completed' => true, 'state' => 'completed'],
            ],
            'history' => [
                [
                    'action' => 'Created',
                    'changed_by' => 'Finance Reporting Admin',
                    'changed_at' => now()->subDay()->format('M d, Y h:i A'),
                    'reason' => null,
                    'new_values' => [
                        'record_number' => 'CA-REPORT-01',
                        'record_title' => 'Live Reporting Cash Advance',
                    ],
                ],
            ],
        ],
        'attachments' => [],
        'user' => $admin->name,
    ]);

    $dashboardResponse = $this->actingAs($admin)->get(route('admin.finance.dashboard'));
    $dashboardResponse->assertOk();
    $dashboardResponse->assertSee('CA-REPORT-01');
    $dashboardResponse->assertSee('Live Reporting Cash Advance');
    $dashboardResponse->assertSee('Accepted');

    $previewResponse = $this->actingAs($admin)->get(route('finance.preview.html', $record));
    $previewResponse->assertOk();
    $previewResponse->assertSee('Relationship Status');
    $previewResponse->assertSee('Awaiting Disbursement');
    $previewResponse->assertSee('Live Reporting Cash Advance');
    $previewResponse->assertSee('Created');
});

test('transaction tracker is automatically derived from record state and attachments', function () {
    $admin = User::factory()->create([
        'name' => 'Finance Tracker Admin',
        'email' => 'tracker.admin@example.com',
        'role' => 'admin',
    ]);

    $record = FinanceRecord::query()->create([
        'module_key' => 'po',
        'record_number' => 'PO-TRACK-01',
        'record_title' => 'Auto Tracker Purchase Order',
        'record_date' => now()->toDateString(),
        'amount' => 50000,
        'status' => 'Active',
        'workflow_status' => 'Accepted',
        'approval_status' => 'Approved',
        'submitted_by' => $admin->id,
        'submitted_at' => now()->subDay(),
        'approved_by' => $admin->id,
        'approved_at' => now(),
        'data' => [
            'requestor' => $admin->name,
            'purpose' => 'Tracker regression test.',
            'fund_source' => 'Operating Funds',
            'department' => 'Finance',
            'project' => 'Tracker Project',
            'cost_center' => 'CC-TRACK',
            'line_items' => [
                [
                    'description' => 'Office supplies batch',
                    'account_code' => '6100',
                    'account_name' => 'Office Supplies Expense',
                    'quantity' => 1,
                    'unit_cost' => 50000,
                    'amount' => 50000,
                    'tax' => 0,
                    'net_amount' => 50000,
                ],
            ],
            'relationship_status' => 'Awaiting DV Creation',
            'transaction_progress' => [
                ['label' => 'Source Document Approved', 'completed' => true, 'state' => 'completed'],
                ['label' => 'DV Created', 'completed' => false, 'state' => 'current'],
            ],
        ],
        'attachments' => [
            [
                'name' => 'po-supporting-doc.pdf',
                'path' => 'storage/testing/po-supporting-doc.pdf',
                'mime' => 'application/pdf',
                'size' => 1024,
                'category' => 'Purchase Order',
                'status' => 'uploaded',
                'uploaded_at' => now()->format('Y-m-d H:i:s'),
                'uploaded_by' => $admin->name,
            ],
        ],
        'user' => $admin->name,
    ]);

    $response = $this->actingAs($admin)->getJson(route('finance.show', $record));

    $response->assertOk();
    $response->assertJsonPath('data.transaction_progress.0.label', 'Source Document Approved');
    $response->assertJsonPath('data.transaction_progress.1.label', 'DV Created');
    $response->assertJsonPath('data.transaction_progress.2.label', 'DV Approved');
    $response->assertJsonPath('data.transaction_progress.3.label', 'Funds Released');
    $response->assertJsonPath('data.transaction_progress.4.label', 'Supporting Documents Submitted');
    $response->assertJsonPath('data.transaction_progress.5.label', 'Transaction Completed');
    $response->assertJsonPath('data.transaction_progress.0.completed', true);
    $response->assertJsonPath('data.transaction_progress.1.state', 'current');
});
