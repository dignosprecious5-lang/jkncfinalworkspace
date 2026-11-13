<?php

namespace Tests\Support;

use App\Models\Transmittal;
use App\Models\TransmittalReceipt;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;

/** Legacy contract fixtures, not a production-schema or migration rehearsal. */
trait TransmittalV1Fixture
{
    protected function setUpTransmittalV1Fixture(): void
    {
        $connection = config('database.connections.'.config('database.default'), []);
        if (! app()->environment('testing') || ($connection['driver'] ?? null) !== 'sqlite'
            || ($connection['database'] ?? null) !== ':memory:' || ! empty($connection['url'])) {
            throw new RuntimeException('V1 fixtures require testing and SQLite :memory: without a database URL. Isolate the runner before Laravel boots.');
        }
        config([
            'database.default' => 'transmittal_v1_regression',
            'database.connections.transmittal_v1_regression' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
            ],
            'cache.default' => 'array', 'session.driver' => 'array', 'mail.default' => 'array',
            'queue.default' => 'sync', 'broadcasting.default' => 'null', 'logging.default' => 'null',
        ]);
        DB::purge('transmittal_v1_regression');
        $this->withoutVite();
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-05 09:30:00'));
        Mail::fake();
        Notification::fake();
        Bus::fake();
        Http::preventStrayRequests();
        Storage::fake('public');
        Storage::fake('local');

        // Explicit schemas follow the existing Pest tests; no migrations run.
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('role');
            $table->boolean('must_change_password')->default(false);
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::connection('transmittal_v1_regression')->create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        foreach (['user_permissions', 'role_permissions'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name): void {
                $table->id();
                if ($name === 'user_permissions') {
                    $table->foreignId('user_id')->constrained('users');
                } else {
                    $table->string('role');
                }
                $table->boolean('approve_corporate')->default(false);
                $table->timestamps();
            });
        }
        Schema::create('transmittals', function (Blueprint $table): void {
            $table->id();
            $table->string('workflow_version', 8)->nullable();
            $table->string('transmittal_no')->nullable()->unique();
            $table->date('transmittal_date')->nullable();
            $table->string('mode')->default('SEND');
            foreach (['party_name', 'office_name', 'delivery_type', 'by_person_who', 'registered_mail_provider',
                'electronic_method', 'recipient_email', 'prepared_by_name', 'approved_by_name', 'approved_position',
                'document_custodian', 'delivered_by', 'received_by', 'receiver_affiliation'] as $column) {
                $table->string($column)->nullable();
            }
            $table->text('address')->nullable();
            foreach (['action_delivery', 'action_pick_up', 'action_drop_off', 'action_email'] as $column) {
                $table->boolean($column)->default(false);
            }
            foreach (['prepared_at', 'received_at', 'approved_at'] as $column) {
                $table->dateTime($column)->nullable();
            }
            $table->string('workflow_status')->default('Uploaded');
            $table->string('approval_status')->default('Pending');
            $table->text('review_note')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
        });
        Schema::create('transmittal_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transmittal_id')->constrained('transmittals')->cascadeOnDelete();
            $table->unsignedInteger('item_no')->default(1);
            $table->string('particular')->nullable();
            $table->string('unique_id')->nullable();
            $table->unsignedInteger('qty')->nullable();
            $table->text('description')->nullable();
            $table->text('remarks')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamps();
        });
        Schema::create('transmittal_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transmittal_id')->constrained('transmittals')->cascadeOnDelete();
            $table->string('receipt_no')->unique();
            $table->date('receipt_date')->nullable();
            foreach (['mode', 'from_name', 'to_name', 'office_name', 'delivery_type', 'delivery_detail',
                'recipient_email', 'prepared_by_name', 'approved_by_name', 'approved_position',
                'document_custodian', 'delivered_by', 'received_by'] as $column) {
                $table->string($column)->nullable();
            }
            $table->text('actions_summary')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('transmittal_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transmittal_id')->constrained('transmittals')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    protected function v1User(bool $canApprove = false): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => $canApprove ? 'Fixture System Approver' : 'Fixture Submitter',
            'email' => 'v1-'.(DB::table('users')->count() + 1).'@example.test', 'role' => 'Employee',
            'must_change_password' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('user_permissions')->insert([
            'user_id' => $id, 'approve_corporate' => $canApprove, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return User::query()->findOrFail($id);
    }

    protected function v1Record(User $owner, array $overrides = []): Transmittal
    {
        $id = DB::table('transmittals')->insertGetId(array_replace([
            'transmittal_no' => 'LEGACY-'.(DB::table('transmittals')->count() + 1),
            'transmittal_date' => '2026-10-21', 'mode' => 'SEND', 'party_name' => 'Fixture Counterparty',
            'office_name' => 'Fixture Origin Office', 'address' => 'Fixture Address', 'delivery_type' => 'By Person',
            'by_person_who' => 'Fixture Courier', 'recipient_email' => 'recipient@example.test',
            'action_delivery' => true, 'action_pick_up' => false, 'action_drop_off' => true, 'action_email' => false,
            'prepared_by_name' => $owner->name, 'prepared_at' => '2026-10-21 08:15:00',
            'approved_by_name' => 'Fixture Operations Manager', 'approved_position' => 'Operations Manager',
            'document_custodian' => 'Fixture Custodian', 'delivered_by' => 'Fixture Courier',
            'received_by' => 'Fixture Receiver', 'receiver_affiliation' => 'Fixture Receiving Office',
            'received_at' => '2026-10-22 10:45:00', 'workflow_status' => 'Uploaded', 'approval_status' => 'Pending',
            'submitted_by' => $owner->id, 'created_at' => '2026-10-21 08:15:00', 'updated_at' => '2026-10-21 08:15:00',
        ], $overrides));
        $itemPath = 'transmittal/item-attachments/fixture-'.$id.'.pdf';
        $attachmentPath = 'transmittal/attachments/fixture-'.$id.'.txt';
        Storage::disk('public')->put($itemPath, 'isolated legacy item fixture bytes');
        Storage::disk('public')->put($attachmentPath, 'isolated legacy supporting attachment');
        DB::table('transmittal_items')->insert([
            'transmittal_id' => $id, 'item_no' => 7, 'particular' => 'Fixture Document', 'unique_id' => 'LEGACY-DOC-7',
            'qty' => 3, 'description' => 'Original description', 'remarks' => 'Original remarks',
            'attachment_path' => $itemPath, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('transmittal_attachments')->insert([
            'transmittal_id' => $id, 'file_path' => $attachmentPath, 'original_name' => 'supporting-fixture.txt',
            'mime_type' => 'text/plain', 'size' => Storage::disk('public')->size($attachmentPath),
            'uploaded_by' => $owner->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return Transmittal::query()->findOrFail($id);
    }

    protected function v1Receipt(Transmittal $record, array $overrides = []): TransmittalReceipt
    {
        $id = DB::table('transmittal_receipts')->insertGetId(array_replace([
            'transmittal_id' => $record->id, 'receipt_no' => 'LEGACY-TRR-'.(DB::table('transmittal_receipts')->count() + 1),
            'receipt_date' => '2026-10-22', 'mode' => $record->mode, 'from_name' => $record->from_value,
            'to_name' => $record->to_value, 'office_name' => $record->office_name, 'delivery_type' => $record->delivery_type,
            'prepared_by_name' => $record->prepared_by_name, 'approved_by_name' => $record->approved_by_name,
            'received_by' => $record->received_by, 'received_at' => $record->received_at,
            'created_at' => '2026-10-22 10:45:00', 'updated_at' => '2026-10-22 10:45:00',
        ], $overrides));
        return TransmittalReceipt::query()->findOrFail($id);
    }

    protected function v1BusinessSnapshot(Transmittal $record): array
    {
        $business = (array) DB::table('transmittals')->find($record->id);
        foreach (['workflow_status', 'approval_status', 'review_note', 'submitted_by', 'approved_by', 'approved_at', 'updated_at'] as $column) {
            unset($business[$column]);
        }
        $items = DB::table('transmittal_items')->where('transmittal_id', $record->id)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $attachments = DB::table('transmittal_attachments')->where('transmittal_id', $record->id)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $hashes = [];
        foreach (array_merge(array_column($items, 'attachment_path'), array_column($attachments, 'file_path')) as $file) {
            $hashes[$file] = hash('sha256', Storage::disk('public')->get($file));
        }
        return compact('business', 'items', 'attachments', 'hashes');
    }

    /** Assert the controller contract without running Dompdf or fetching assets. */
    protected function expectV1Pdf(string $view, Closure $inspectData, array|string $paper, ?string $orientation, ?array $options, string $filename): void
    {
        $renderer = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        Pdf::shouldReceive('loadView')->once()->with($view, Mockery::on($inspectData))->andReturn($renderer);
        Pdf::shouldNotReceive('loadHTML');
        if ($orientation === null) {
            $renderer->shouldReceive('setPaper')->once()->with($paper)->andReturnSelf();
        } else {
            $renderer->shouldReceive('setPaper')->once()->with($paper, $orientation)->andReturnSelf();
        }
        if ($options !== null) {
            $renderer->shouldReceive('setOptions')->once()->with($options)->andReturnSelf();
        }
        $renderer->shouldReceive('stream')->once()->with($filename)->andReturn(
            response('isolated PDF renderer response', 200, ['Content-Type' => 'application/pdf'])
        );
    }
}
