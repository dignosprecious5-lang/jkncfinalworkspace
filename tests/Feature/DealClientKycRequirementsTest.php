<?php

namespace Tests\Feature;

use App\Http\Controllers\DealStartController;
use App\Models\Deal;
use App\Services\ProjectProvisioner;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DealClientKycRequirementsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['deals', 'contacts', 'companies'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->string('customer_type')->nullable();
            $table->string('organization_type')->nullable();
            $table->string('business_type_organization')->nullable();
            $table->string('cif_status')->nullable();
            $table->string('ownership_flag')->nullable();
            $table->string('foreign_business_nature')->nullable();
            $table->json('cif_data')->nullable();
            $table->json('kyc_requirement_documents')->nullable();
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('primary_contact_id')->nullable();
            $table->timestamps();
        });

        Schema::create('deals', function (Blueprint $table): void {
            $table->id();
            $table->string('deal_name')->nullable();
            $table->string('stage')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('customer_type')->nullable();
            $table->json('client_kyc_requirements')->nullable();
            $table->timestamps();
        });
    }

    public function test_kyc_assignments_are_client_specific_and_custom_requirements_start_pending(): void
    {
        $contactOne = DB::table('contacts')->insertGetId(['customer_type' => 'Individual']);
        $contactTwo = DB::table('contacts')->insertGetId(['customer_type' => 'Individual']);
        $dealOne = $this->createDeal($contactOne, 'Client One');
        $dealTwo = $this->createDeal($contactTwo, 'Client Two');

        $this->saveRequirements($dealOne, ['Client Contact Form'], ['Additional notarized affidavit']);
        $this->saveRequirements($dealTwo, ['TIN ID'], []);

        $kycOne = app(ProjectProvisioner::class)->resolveDealStartKyc($dealOne->fresh());
        $kycTwo = app(ProjectProvisioner::class)->resolveDealStartKyc($dealTwo->fresh());
        $clientOneRows = collect($kycOne['individual']);
        $clientTwoRows = collect($kycTwo['individual']);

        $this->assertSame('individual', $kycOne['organization_type']);
        $this->assertTrue($clientOneRows->firstWhere('label', 'Client Contact Form')['required']);
        $this->assertFalse($clientOneRows->firstWhere('label', 'TIN ID')['required']);
        $customRow = $clientOneRows->firstWhere('label', 'Additional notarized affidavit');
        $this->assertTrue($customRow['required']);
        $this->assertSame('pending', $customRow['status']);
        $this->assertTrue($clientTwoRows->firstWhere('label', 'TIN ID')['required']);
        $this->assertFalse($clientTwoRows->firstWhere('label', 'Client Contact Form')['required']);
        $this->assertNull($clientOneRows->firstWhere('label', 'SEC / CDA Certificate of Registration'));
        $this->assertSame(17, $dealOne->fresh()->client_kyc_requirements['assignment_history'][0]['updated_by_id']);
    }

    private function saveRequirements(Deal $deal, array $requirements, array $custom): void
    {
        $user = new class
        {
            public int $id = 17;
            public string $name = 'KYC Reviewer';

            public function hasPermission(string $permission): bool
            {
                return $permission === 'approve_townhall';
            }
        };

        $request = Request::create('/deals/'.$deal->id.'/start/kyc-requirements', 'POST', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'organization_type' => 'individual',
            'requirements' => $requirements,
            'custom_requirements' => $custom,
        ], JSON_THROW_ON_ERROR));
        $request->setUserResolver(fn () => $user);

        $response = app(DealStartController::class)->updateKycRequirements($request, $deal->id);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);
    }

    private function createDeal(int $contactId, string $name): Deal
    {
        $id = DB::table('deals')->insertGetId([
            'deal_name' => $name,
            'contact_id' => $contactId,
            'customer_type' => 'Individual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Deal::findOrFail($id);
    }
}
