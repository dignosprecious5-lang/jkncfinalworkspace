<?php

namespace Tests\Feature;

use App\Models\Deal;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DealStartRoleAssignmentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('deals');
        Schema::create('deals', function (Blueprint $table): void {
            $table->id();
            $table->string('deal_name');
            $table->string('stage')->nullable();
            $table->string('deal_code')->nullable();
            $table->string('deal_title')->nullable();
            $table->timestamp('stage_entered_at')->nullable();
            $table->json('start_batches')->nullable();
            $table->timestamps();
        });
    }

    public function test_selected_role_is_saved_to_the_start_batch_assignments(): void
    {
        $deal = $this->createDeal();

        $this->withoutMiddleware()
            ->postJson(route('deals.start.batches.assignments', [
                'deal' => $deal->id,
                'batchId' => 'batch-1',
            ]), [
                'role' => 'Lead Associate Assigned',
                'assignee' => 'Unassigned',
                'is_required' => true,
                'notes' => 'Coordinate assigned associate tasks.',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('batch.assignments.0.role', 'Lead Associate Assigned')
            ->assertJsonPath('batch.assignments.0.notes', 'Coordinate assigned associate tasks.');

        $savedBatch = $deal->fresh()->start_batches[0];
        $this->assertSame('Lead Associate Assigned', $savedBatch['assignments'][0]['role']);
        $this->assertSame('Coordinate assigned associate tasks.', $savedBatch['assignments'][0]['notes']);
    }

    public function test_role_outside_the_dropdown_options_is_rejected(): void
    {
        $deal = $this->createDeal();

        $this->withoutMiddleware()
            ->postJson(route('deals.start.batches.assignments', [
                'deal' => $deal->id,
                'batchId' => 'batch-1',
            ]), [
                'role' => 'Unlisted Role',
                'assignee' => 'Unassigned',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');

        $this->assertSame([], $deal->fresh()->start_batches[0]['assignments']);
    }

    private function createDeal(): Deal
    {
        $id = DB::table('deals')->insertGetId([
            'deal_name' => 'Role Assignment Test',
            'start_batches' => json_encode([
                [
                    'id' => 'batch-1',
                    'start_code' => 'START-001',
                    'assignments' => [],
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Deal::findOrFail($id);
    }
}
