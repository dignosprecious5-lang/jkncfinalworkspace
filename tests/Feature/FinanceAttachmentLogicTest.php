<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('exposes the standard finance attachment document types', function () {
    $user = User::factory()->create([
        'name' => 'Finance Viewer',
        'email' => 'finance.viewer@example.com',
        'role' => 'admin',
    ]);

    $response = $this->actingAs($user)->get(route('finance'));

    $response->assertOk();
    $response->assertViewHas('financeAttachmentTypes', function (array $types): bool {
        $values = collect($types)
            ->pluck('value')
            ->filter()
            ->map(fn ($value) => strtolower((string) $value))
            ->all();

        foreach ([
            'Purchase Order',
            'Invoice',
            'Official Receipt',
            'Billing Statement',
            'Contract',
            'Quotation',
            'Payroll Register',
            'Liquidation Report',
            'Deposit Slip',
            'Bank Confirmation',
            'Proof of Transfer',
        ] as $expectedType) {
            if (! in_array(strtolower($expectedType), $values, true)) {
                return false;
            }
        }

        return true;
    });
});
