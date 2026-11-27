<?php

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DealStartAssignmentResponsesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('deals');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('role')->nullable();
            $table->timestamps();
        });

        Schema::create('deals', function (Blueprint $table): void {
            $table->id();
            $table->string('deal_name');
            $table->string('deal_code')->nullable();
            $table->string('deal_title')->nullable();
            $table->string('stage')->nullable();
            $table->timestamp('stage_entered_at')->nullable();
            $table->json('start_batches')->nullable();
            $table->timestamps();
        });
    }

    public function test_assigned_user_can_accept_assignment_and_status_is_saved(): void
    {
        $user = $this->createAssignee();
        $deal = $this->createDeal($user->name);
        $batches = $deal->start_batches;
        $batches[0]['status'] = 'For Team Confirmation';
        $deal->start_batches = $batches;
        $deal->save();

        $this->actingAs($user)
            ->postJson(route('deals.start.assignments.acknowledge', [
                'deal' => $deal->id,
                'assignId' => 'assignment-1',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('batch.assignments.0.status', 'Acknowledged')
            ->assertJsonPath('batch.assignments.0.response', 'Accepted')
            ->assertJsonPath('batch.status', 'Team Confirmed')
            ->assertJsonPath('is_start_completed', false)
            ->assertJsonPath('start_status', 'Ongoing');

        $this->assertSame('Accepted', $deal->fresh()->start_batches[0]['assignments'][0]['response']);
        $this->assertSame('Assignment Accepted', $deal->fresh()->start_batches[0]['history_logs'][0]['event']);
    }

    public function test_stale_ready_batch_status_resolves_to_team_confirmed_from_accepted_records(): void
    {
        $deal = new Deal();
        $batch = [
            'status' => 'Ready',
            'assignments' => [[
                'assignee' => 'Assigned User',
                'status' => 'Acknowledged',
                'response' => 'Accepted',
                'acknowledged_at' => now()->toIso8601String(),
                'is_required' => true,
            ]],
        ];

        $this->assertSame('Team Confirmed', $deal->resolveStartBatchTeamStatus($batch));
    }

    public function test_engagement_route_stays_locked_until_start_is_complete(): void
    {
        $user = $this->createAssignee();
        $deal = $this->createDeal($user->name);

        $this->actingAs($user)
            ->get(route('deals.project', ['id' => $deal->id]))
            ->assertRedirect(route('deals.show', ['id' => $deal->id]) . '#start');
    }

    public function test_start_completion_requires_each_acceptance_and_a_real_service_memo_pdf(): void
    {
        // This test isolates team/memo completion; financial eligibility is covered separately.
        $eligibility = \Mockery::mock(\App\Services\DealPaymentEligibility::class);
        $eligibility->shouldReceive('batchEligible')->andReturn(true);
        $this->app->instance(\App\Services\DealPaymentEligibility::class, $eligibility);
        Storage::fake('public');
        $deal = $this->createDeal('Assigned User');
        $batch = $deal->start_batches[0];
        $batch['service_memo_ref'] = 'SM-2026-001';
        $batch['service_memo_data'] = ['memo_ref' => 'SM-2026-001', 'issued_at' => now()->toISOString()];
        $batch['service_memo_pdf_path'] = 'service-memos/SM-2026-001.pdf';
        $batch['assignments'][0]['status'] = 'Pending';
        $batch['assignments'][0]['response'] = null;
        $batch['assignments'][0]['acknowledged_at'] = null;
        $batch['assignments'][] = [
            'id' => 'assignment-2',
            'role' => 'Lead Associate',
            'assignee' => 'Another Assignee',
            'status' => 'Pending',
            'response' => null,
            'is_required' => true,
            'acknowledged_at' => null,
        ];
        $deal->start_batches = [$batch];
        Storage::disk('public')->put($batch['service_memo_pdf_path'], '%PDF-1.4 test document');
        $deal->save();

        $this->assertFalse($deal->fresh()->isStartCompleted(), 'A generated memo cannot substitute for explicit assignment acceptance.');
        $this->assertSame('Ongoing', $deal->fresh()->getResolvedStartStatus());

        $batch['assignments'][0]['status'] = 'Acknowledged';
        $batch['assignments'][0]['response'] = 'Accepted';
        $batch['assignments'][0]['acknowledged_at'] = now()->format('M d, Y h:i A');
        $deal->start_batches = [$batch];
        $deal->save();
        $this->assertFalse($deal->fresh()->isStartCompleted(), 'One unaccepted required assignee keeps START ongoing.');

        $batch['assignments'][1]['status'] = 'Acknowledged';
        $batch['assignments'][1]['response'] = 'Accepted';
        $batch['assignments'][1]['acknowledged_at'] = now()->format('M d, Y h:i A');
        $deal->start_batches = [$batch];
        $deal->save();
        Storage::disk('public')->delete($batch['service_memo_pdf_path']);

        $this->assertFalse($deal->fresh()->isStartCompleted(), 'A memo record without an available document cannot complete START.');
        $this->assertSame('Ongoing', $deal->fresh()->getResolvedStartStatus());

        Storage::disk('public')->put($batch['service_memo_pdf_path'], 'not a PDF');
        $this->assertFalse($deal->fresh()->isStartCompleted(), 'A visible memo record with a non-PDF document cannot complete START.');

        Storage::disk('public')->put($batch['service_memo_pdf_path'], '%PDF-1.4 test document');
        $this->assertTrue($deal->fresh()->isStartCompleted());
        $this->assertSame('Complete', $deal->fresh()->getResolvedStartStatus());
    }

    public function test_final_outcome_is_rejected_while_start_requirements_are_outstanding(): void
    {
        $user = $this->createAssignee();
        $deal = $this->createDeal($user->name);

        $this->actingAs($user)
            ->postJson(route('deals.final-outcome', ['id' => $deal->id]), [
                'outcome' => 'Closed Lost',
                'lost_reason' => 'Client selected another provider',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Complete START and reach Activation before recording an outcome.');

        $this->assertNull($deal->fresh()->stage);
    }

    public function test_assigned_user_can_decline_assignment_with_reason_and_status_is_saved(): void
    {
        $user = $this->createAssignee();
        $deal = $this->createDeal($user->name);

        $this->actingAs($user)
            ->postJson(route('deals.start.assignments.decline', [
                'deal' => $deal->id,
                'assignId' => 'assignment-1',
            ]), [
                'decline_reason' => 'Schedule Conflict',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('batch.assignments.0.status', 'Cannot Accept')
            ->assertJsonPath('batch.assignments.0.response', 'Declined')
            ->assertJsonPath('batch.assignments.0.decline_reason', 'Schedule Conflict');

        $savedAssignment = $deal->fresh()->start_batches[0]['assignments'][0];
        $this->assertSame('Declined', $savedAssignment['response']);
        $this->assertSame('Schedule Conflict', $savedAssignment['decline_reason']);
        $this->assertSame('Assignment Declined', $deal->fresh()->start_batches[0]['history_logs'][0]['event']);
    }

    public function test_respond_modal_has_only_the_decline_and_accept_decisions(): void
    {
        $template = file_get_contents(resource_path('views/deals/show.blade.php'));
        $this->assertIsString($template);
        $modalStart = strpos($template, '<div id="startAssignmentActionModal"');
        $modalEnd = strpos($template, '{{--', $modalStart + 1);
        $this->assertNotFalse($modalStart);
        $this->assertNotFalse($modalEnd);

        $modal = substr($template, $modalStart, $modalEnd - $modalStart);
        $this->assertStringContainsString('Decline', $modal);
        $this->assertStringContainsString('Accept Assignment', $modal);
        $this->assertStringNotContainsString('Cannot Accept / Decline', $modal);
        $this->assertStringNotContainsString('Decide Later', $modal);
        $this->assertStringNotContainsString('✕', $modal);
        $this->assertStringNotContainsString('✓', $modal);
    }

    private function createAssignee(): User
    {
        return User::create([
            'name' => 'Assigned User',
            'email' => 'assigned@example.test',
            'password' => bcrypt('password'),
            'role' => 'Employee',
        ]);
    }

    private function createDeal(string $assignee): Deal
    {
        $id = DB::table('deals')->insertGetId([
            'deal_name' => 'START Response Test',
            'deal_code' => 'DEAL-001',
            'start_batches' => json_encode([
                [
                    'id' => 'batch-1',
                    'start_code' => 'START-001',
                    'status' => 'Structuring',
                    'created_by' => $assignee,
                    'assignments' => [
                        [
                            'id' => 'assignment-1',
                            'role' => 'Lead Consultant',
                            'assignee' => $assignee,
                            'status' => 'Pending',
                            'response' => null,
                            'is_required' => true,
                        ],
                    ],
                    'history_logs' => [],
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Deal::findOrFail($id);
    }
}
