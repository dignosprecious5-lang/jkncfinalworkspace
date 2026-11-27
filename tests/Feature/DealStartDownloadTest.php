<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DealStartDownloadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('projects');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('deals');

        Schema::create('deals', function (Blueprint $table): void {
            $table->id();
            $table->string('deal_name');
            $table->string('deal_title')->nullable();
            $table->string('deal_code')->nullable();
            $table->string('company')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('service_area')->nullable();
            $table->json('service_areas')->nullable();
            $table->string('products_needed')->nullable();
            $table->string('services_needed')->nullable();
            $table->json('services_products')->nullable();
            $table->string('engagement_type')->nullable();
            $table->json('start_batches')->nullable();
            $table->string('stage')->nullable();
            $table->string('pipeline_stage')->nullable();
            $table->timestamps();
        });

        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->string('company_name')->nullable();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('deal_id')->nullable();
            $table->string('engagement_type')->nullable();
            $table->timestamps();
        });
    }

    public function test_deal_start_download_returns_a_pdf_attachment(): void
    {
        $dealId = DB::table('deals')->insertGetId([
            'deal_name' => 'START Download Test',
            'deal_title' => 'START Download Test',
            'deal_code' => 'DEAL-START-001',
            'company' => 'Example Business',
            'engagement_type' => 'Project',
            'services_products' => json_encode([
                ['type' => 'SERVICE', 'name' => 'Business Registration'],
                ['type' => 'PRODUCT', 'name' => 'Compliance Package'],
            ]),
            'start_batches' => json_encode([
                [
                    'id' => 'batch-1',
                    'start_code' => 'START-001',
                    'status' => 'Structuring',
                    'assignments' => [],
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withoutMiddleware()
            ->get(route('deals.start.download', $dealId))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertStringContainsString('start-form-deal-start-001.pdf', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $startTemplate = file_get_contents(resource_path('views/project/pdf/start.blade.php'));
        $consultingTemplate = file_get_contents(resource_path('views/pdf/consulting.blade.php'));
        $this->assertStringContainsString('size: A4 portrait; margin: 8mm 8mm 16mm;', $startTemplate);
        $this->assertStringContainsString('size: A4;', $consultingTemplate);
        $this->assertStringContainsString('width: 188mm;', $startTemplate);
        $this->assertStringContainsString('width: 188mm;', $consultingTemplate);
    }
}
