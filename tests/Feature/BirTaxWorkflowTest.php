<?php

use App\Models\BirTax;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function birTaxPayload(array $overrides = []): array
{
    return array_merge([
        'tin' => '123-456-789-000',
        'tax_payer' => 'JKNC Holdings, Inc.',
        'rdo' => 'RDO 001',
        'registered_address' => 'Cebu City',
        'tax_types' => 'Income Tax, VAT',
        'form_type' => '1702-RT, 2550Q',
        'tax_due' => 12500.50,
        'filing_frequency' => 'Monthly',
        'due_date' => now()->addDays(15)->toDateString(),
        'status' => 'Pending',
    ], $overrides);
}

test('bir tax records follow the lgu-style workflow lifecycle', function () {
    $requester = User::factory()->create([
        'name' => 'BIR Requester',
        'email' => 'bir.requester@example.com',
        'role' => 'Employee',
    ]);
    UserPermission::query()->create([
        'user_id' => $requester->id,
    ]);

    $approver = User::factory()->create([
        'name' => 'Corporate Approver',
        'email' => 'corporate.approver@example.com',
        'role' => 'Admin',
    ]);
    UserPermission::query()->create([
        'user_id' => $approver->id,
        'approve_corporate' => true,
    ]);

    $storeResponse = $this->actingAs($requester)->postJson(route('bir-tax.store'), birTaxPayload());
    $storeResponse->assertCreated();

    $record = BirTax::query()->findOrFail($storeResponse->json('data.id'));
    expect($record->workflow_status)->toBe('Uploaded');
    expect($record->approval_status)->toBe('Pending');

    $this->actingAs($requester)
        ->postJson(route('bir-tax.submit', $record))
        ->assertOk();

    $record->refresh();
    expect($record->workflow_status)->toBe('Submitted');
    expect($record->approval_status)->toBe('Pending');

    $this->actingAs($requester)
        ->putJson(route('bir-tax.update', $record), birTaxPayload([
            'tax_due' => 15000,
        ]))
        ->assertForbidden();

    $this->actingAs($approver)
        ->from(route('admin.corporate.dashboard'))
        ->post(route('corporate.approvals.revise', ['module' => 'bir-tax', 'id' => $record->id]), [
            'review_note' => 'Please correct the tax due amount.',
        ])
        ->assertRedirect(route('admin.corporate.dashboard'));

    $record->refresh();
    expect($record->workflow_status)->toBe('Reverted');
    expect($record->approval_status)->toBe('Needs Revision');
    expect($record->review_note)->toBe('Please correct the tax due amount.');

    $this->actingAs($requester)
        ->putJson(route('bir-tax.update', $record), birTaxPayload([
            'tax_due' => 15000,
        ]))
        ->assertOk();

    $record->refresh();
    expect($record->tax_due)->toBe('15000.00');
    expect($record->approval_status)->toBe('Pending');
    expect($record->review_note)->toBeNull();

    $this->actingAs($requester)
        ->postJson(route('bir-tax.submit', $record))
        ->assertOk();

    $this->actingAs($approver)
        ->from(route('admin.corporate.dashboard'))
        ->post(route('corporate.approvals.approve', ['module' => 'bir-tax', 'id' => $record->id]))
        ->assertRedirect(route('admin.corporate.dashboard'));

    $record->refresh();
    expect($record->workflow_status)->toBe('Accepted');
    expect($record->approval_status)->toBe('Approved');

    $this->actingAs($approver)
        ->get(route('admin.corporate.dashboard'))
        ->assertOk()
        ->assertSee('BIR & Tax')
        ->assertSee('Accepted');
});
