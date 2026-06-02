<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores finance label overrides and exposes them to the finance frontend bootstrap', function () {
    $admin = User::factory()->create([
        'name' => 'Finance Admin',
        'email' => 'finance.admin@example.com',
        'role' => 'admin',
    ]);

    $response = $this->actingAs($admin)->postJson(route('finance.dropdown-settings.update'), [
        'options' => [
            'supplier' => [
                'supplier_type' => [
                    ['label' => 'Primary Supplier', 'value' => 'Primary Supplier'],
                ],
            ],
        ],
        'attachment_types' => [],
        'label_overrides' => [
            'supplier' => [
                'record_title_label' => 'Vendor Name',
                'record_number_label' => 'Vendor Code',
                'field_labels' => [
                    'business_name' => 'Legal Business Name',
                ],
            ],
        ],
    ]);

    $response->assertOk();
    $response->assertJsonPath('label_overrides.supplier.record_title_label', 'Vendor Name');
    $response->assertJsonPath('label_overrides.supplier.record_number_label', 'Vendor Code');
    $response->assertJsonPath('label_overrides.supplier.field_labels.business_name', 'Legal Business Name');

    $stored = Setting::query()->where('key', 'finance_label_overrides')->value('value');

    expect($stored)->not->toBeNull()
        ->and((string) $stored)->toContain('Vendor Name')
        ->and((string) $stored)->toContain('Vendor Code')
        ->and((string) $stored)->toContain('Legal Business Name');

    $page = $this->actingAs($admin)->get(route('finance'));

    $page->assertOk();
    $page->assertSee('Vendor Name', false);
    $page->assertSee('Vendor Code', false);
    $page->assertSee('Legal Business Name', false);
});
