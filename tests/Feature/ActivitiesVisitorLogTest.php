<?php

use App\Models\Call;
use App\Models\Contact;
use App\Models\Task;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->withoutMiddleware();

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password')->nullable();
        $table->string('role')->nullable();
        $table->boolean('must_change_password')->default(false);
        $table->timestamps();
    });

    Schema::create('user_permissions', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->boolean('access_activities')->default(false);
        $table->timestamps();
    });

    Schema::create('contacts', function (Blueprint $table) {
        $table->id();
        $table->string('first_name')->nullable();
        $table->string('middle_initial')->nullable();
        $table->string('middle_name')->nullable();
        $table->string('last_name')->nullable();
        $table->string('name_extension')->nullable();
        $table->string('company_name')->nullable();
        $table->string('email')->nullable();
        $table->string('phone')->nullable();
        $table->string('cif_no')->nullable()->unique();
        $table->timestamps();
    });

    Schema::create('branches', function (Blueprint $table) {
        $table->id();
        $table->string('branch_name');
        $table->unsignedBigInteger('address_id')->nullable();
        $table->string('branch_head')->nullable();
        $table->timestamps();
    });

    Schema::create('tasks', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('events', function (Blueprint $table) {
        $table->id();
        $table->string('title')->nullable();
        $table->timestamps();
    });

    Schema::create('calls', function (Blueprint $table) {
        $table->id();
        $table->string('contact')->nullable();
        $table->string('purpose')->nullable();
        $table->string('audio_path')->nullable();
        $table->timestamps();
    });

    Schema::create('meetings', function (Blueprint $table) {
        $table->id();
        $table->string('title')->nullable();
        $table->string('video_path')->nullable();
        $table->string('audio_path')->nullable();
        $table->timestamps();
    });

    Schema::create('visitors', function (Blueprint $table) {
        $table->id();
        $table->string('visitor_reference')->unique();
        $table->string('name');
        $table->string('mobile')->nullable();
        $table->string('email')->nullable();
        $table->string('company_name')->nullable();
        $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
        $table->timestamps();
    });

    Schema::create('visits', function (Blueprint $table) {
        $table->id();
        $table->string('visit_reference')->unique();
        $table->unsignedBigInteger('visitor_id');
        $table->string('status');
        $table->timestamp('checked_in_at');
        $table->timestamp('checked_out_at')->nullable();
        $table->string('activity_type')->nullable();
        $table->unsignedBigInteger('activity_id')->nullable();
        $table->timestamps();
    });

    Schema::create('visit_movements', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('visit_id');
        $table->string('movement_type');
        $table->timestamp('occurred_at');
        $table->unsignedBigInteger('branch_id')->nullable();
        $table->decimal('latitude', 10, 7)->nullable();
        $table->decimal('longitude', 11, 7)->nullable();
        $table->decimal('accuracy_metres', 10, 2)->nullable();
        $table->timestamp('captured_at')->nullable();
        $table->string('source')->nullable();
        $table->string('location_label')->nullable();
        $table->timestamps();
        $table->unique(['visit_id', 'movement_type']);
    });

    (require database_path('migrations/2026_10_06_000001_add_unique_contact_id_to_visitors_table.php'))->up();
});

test('visitor log endpoints require authentication and retain the Activities permission middleware', function () {
    $this->withMiddleware()
        ->getJson('/api/visitors')
        ->assertUnauthorized();

    foreach (['api/visitors', 'api/visitors/resolve-contact', 'api/visits', 'api/visits/check-in'] as $uri) {
        $route = collect(Route::getRoutes())->first(fn ($route) => $route->uri() === $uri);

        expect($route)->not->toBeNull()
            ->and($route->gatherMiddleware())->toContain('auth')
            ->and($route->gatherMiddleware())->toContain('human-capital.module:access_activities');
    }

    $employee = User::query()->create([
        'name' => 'No Activities Access',
        'email' => 'no-activities@example.test',
        'role' => 'Employee',
    ]);
    Schema::getConnection()->table('user_permissions')->insert([
        'user_id' => $employee->id,
        'access_activities' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($employee)
        ->withMiddleware()
        ->getJson('/api/visitors')
        ->assertForbidden();
});

test('unlinked Contacts are dynamically searchable by full name, phone, email, and CIF number', function () {
    $contact = Contact::query()->create([
        'first_name' => 'Juan',
        'middle_initial' => 'D',
        'last_name' => 'Cruz',
        'company_name' => 'Acme Corp',
        'email' => 'juan@acme.test',
        'phone' => '09171234567',
        'cif_no' => 'CIF-20261006-0001',
    ]);

    foreach (['Juan D Cruz', '09171234567', 'juan@acme.test', 'CIF-20261006-0001'] as $term) {
        $this->getJson('/api/visitors?search='.urlencode($term))
            ->assertOk()
            ->assertJsonCount(1, 'visitors')
            ->assertJsonPath('visitors.0.id', $contact->id)
            ->assertJsonPath('visitors.0.source_type', 'contact')
            ->assertJsonPath('visitors.0.contact_id', $contact->id)
            ->assertJsonPath('visitors.0.visitor_id', null)
            ->assertJsonPath('visitors.0.has_visitor', false);
    }
});

test('existing Visitors remain searchable and are marked as Visitor results', function () {
    $visitor = Visitor::query()->create([
        'name' => 'Ana Reyes',
        'mobile' => '09170000000',
        'email' => 'visitor@acme.test',
    ]);

    foreach ([$visitor->visitor_reference, 'Ana Reyes', '09170000000', 'visitor@acme.test'] as $term) {
        $this->getJson('/api/visitors?search='.urlencode($term))
            ->assertOk()
            ->assertJsonCount(1, 'visitors')
            ->assertJsonPath('visitors.0.id', $visitor->id)
            ->assertJsonPath('visitors.0.source_type', 'visitor')
            ->assertJsonPath('visitors.0.visitor_id', $visitor->id)
            ->assertJsonPath('visitors.0.has_visitor', true);
    }
});

test('linked Contacts resolve to one Visitor result without a duplicate Contact result', function () {
    $contact = Contact::query()->create([
        'first_name' => 'Linked',
        'last_name' => 'Contact',
        'company_name' => 'Linked Corp',
        'email' => 'linked@acme.test',
        'phone' => '09171111111',
        'cif_no' => 'CIF-LINKED-0001',
    ]);
    $visitor = Visitor::query()->create([
        'name' => 'Visitor Snapshot',
        'contact_id' => $contact->id,
    ]);

    $this->getJson('/api/visitors?search=CIF-LINKED-0001')
        ->assertOk()
        ->assertJsonCount(1, 'visitors')
        ->assertJsonPath('visitors.0.id', $visitor->id)
        ->assertJsonPath('visitors.0.source_type', 'visitor')
        ->assertJsonPath('visitors.0.contact_id', $contact->id)
        ->assertJsonPath('visitors.0.cif_no', 'CIF-LINKED-0001');
});

test('resolving a Contact creates one Visitor snapshot and remains idempotent through both endpoints', function () {
    $contact = Contact::query()->create([
        'first_name' => 'Ana',
        'last_name' => 'Reyes',
        'company_name' => 'Acme Corp',
        'email' => 'ana@acme.test',
        'phone' => '09171234567',
        'cif_no' => 'CIF-RESOLVE-0001',
    ]);

    $created = $this->postJson('/api/visitors/resolve-contact', ['contact_id' => $contact->id])
        ->assertCreated()
        ->assertJsonPath('contact_id', $contact->id)
        ->assertJsonPath('name', 'Ana Reyes')
        ->assertJsonPath('mobile', '09171234567')
        ->assertJsonPath('email', 'ana@acme.test')
        ->assertJsonPath('company_name', 'Acme Corp');

    $visitorId = $created->json('id');

    $this->postJson('/api/visitors/resolve-contact', ['contact_id' => $contact->id])
        ->assertOk()
        ->assertJsonPath('id', $visitorId);

    $this->postJson('/api/visitors', ['contact_id' => $contact->id])
        ->assertOk()
        ->assertJsonPath('id', $visitorId);

    $this->assertDatabaseCount('contacts', 1);
    $this->assertDatabaseCount('visitors', 1);
});

test('the database rejects a second linked Visitor for one Contact while allowing unlinked Visitors', function () {
    $contact = Contact::query()->create([
        'first_name' => 'Unique',
        'last_name' => 'Contact',
        'cif_no' => 'CIF-UNIQUE-0001',
    ]);

    $this->postJson('/api/visitors/resolve-contact', ['contact_id' => $contact->id])->assertCreated();

    expect(fn () => Visitor::query()->create([
        'name' => 'Duplicate Linked Visitor',
        'contact_id' => $contact->id,
    ]))->toThrow(QueryException::class);

    Visitor::query()->create(['name' => 'First Unlinked Visitor']);
    Visitor::query()->create(['name' => 'Second Unlinked Visitor']);
    $this->assertDatabaseCount('visitors', 3);
});

test('first-time visitor creation remains unlinked', function () {
    $created = $this->postJson('/api/visitors', [
        'name' => 'First Time Visitor',
        'mobile' => '09990000000',
    ])->assertCreated()
        ->assertJsonPath('source_type', 'visitor')
        ->assertJsonPath('contact_id', null)
        ->assertJsonPath('has_visitor', true);

    expect($created->json('visitor_reference'))->toMatch('/^VIS-\d{4}-\d{6}$/');

    $this->postJson('/api/visitors', ['name' => 'Bad Email', 'email' => 'not-an-email'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

test('deleting a Contact preserves its linked Visitor and nulls the link', function () {
    $contact = Contact::query()->create([
        'first_name' => 'Deleted',
        'last_name' => 'Contact',
        'cif_no' => 'CIF-DELETED-0001',
    ]);
    $visitorId = $this->postJson('/api/visitors/resolve-contact', ['contact_id' => $contact->id])
        ->assertCreated()
        ->json('id');

    $contact->delete();

    $this->assertDatabaseHas('visitors', ['id' => $visitorId, 'contact_id' => null]);
});

test('check-in enforces one active visit and permits completely nullable GPS', function () {
    $visitor = Visitor::query()->create(['name' => 'No GPS Visitor']);

    $created = $this->postJson('/api/visits/check-in', ['visitor_id' => $visitor->id])
        ->assertCreated()
        ->assertJsonPath('status', Visit::STATUS_CHECKED_IN)
        ->assertJsonPath('checked_out_at', null)
        ->assertJsonPath('check_in_movement.latitude', null)
        ->assertJsonPath('check_out_movement', null);

    expect($created->json('visit_reference'))->toMatch('/^VST-\d{8}-\d{4}$/');

    $this->postJson('/api/visits/check-in', ['visitor_id' => $visitor->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('visitor_id');

    $this->assertDatabaseCount('visits', 1);
    $this->assertDatabaseCount('visit_movements', 1);
});

test('checkout completes an active visit and stores its GPS independently from check-in', function () {
    $branchId = Schema::getConnection()->table('branches')->insertGetId([
        'branch_name' => 'Cebu Office',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $visitor = Visitor::query()->create(['name' => 'Moving Visitor']);

    $visitId = $this->postJson('/api/visits/check-in', [
        'visitor_id' => $visitor->id,
        'branch_id' => $branchId,
        'latitude' => 10.3157,
        'longitude' => 123.8854,
        'accuracy_metres' => 12.5,
        'captured_at' => '2026-08-19T09:00:00+08:00',
        'source' => 'browser',
        'location_label' => 'Cebu Lobby',
    ])->assertCreated()->json('id');

    $this->postJson("/api/visits/{$visitId}/check-out", [
        'latitude' => 10.3000,
        'longitude' => 123.9000,
        'accuracy_metres' => 25,
        'source' => 'browser',
        'location_label' => 'Client Site',
    ])->assertOk()
        ->assertJsonPath('status', Visit::STATUS_COMPLETED)
        ->assertJsonPath('check_in_movement.branch.id', $branchId)
        ->assertJsonPath('check_in_movement.location_label', 'Cebu Lobby')
        ->assertJsonPath('check_out_movement.location_label', 'Client Site')
        ->assertJsonPath('check_out_movement.branch', null);

    $this->assertDatabaseHas('visit_movements', [
        'visit_id' => $visitId,
        'movement_type' => 'check_in',
        'location_label' => 'Cebu Lobby',
    ]);
    $this->assertDatabaseHas('visit_movements', [
        'visit_id' => $visitId,
        'movement_type' => 'check_out',
        'location_label' => 'Client Site',
    ]);
});

test('checkout rejects a visit that is not actively checked in and blocks status injection', function () {
    $visitor = Visitor::query()->create(['name' => 'Completed Visitor']);
    $visit = Visit::query()->create([
        'visitor_id' => $visitor->id,
        'status' => Visit::STATUS_COMPLETED,
        'checked_in_at' => now()->subHour(),
        'checked_out_at' => now(),
    ]);

    $this->postJson("/api/visits/{$visit->id}/check-out")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('visit');

    $this->postJson('/api/visits/check-in', [
        'visitor_id' => $visitor->id,
        'status' => Visit::STATUS_COMPLETED,
    ])->assertUnprocessable()->assertJsonValidationErrors('status');
});

test('an optional supported Activity is validated, serialized, and never copied into another activity table', function () {
    $visitor = Visitor::query()->create(['name' => 'Linked Visitor']);
    $task = Task::query()->create(['name' => 'Client Consultation']);

    $this->postJson('/api/visits/check-in', [
        'visitor_id' => $visitor->id,
        'activity_type' => 'Task',
        'activity_id' => $task->id,
    ])->assertCreated()
        ->assertJsonPath('activity.type', 'Task')
        ->assertJsonPath('activity.id', $task->id)
        ->assertJsonPath('activity.label', 'Client Consultation');

    $this->assertDatabaseCount('tasks', 1);
    $this->assertDatabaseCount('events', 0);
    $this->assertDatabaseCount('calls', 0);
    $this->assertDatabaseCount('meetings', 0);

    $otherVisitor = Visitor::query()->create(['name' => 'Invalid Link Visitor']);
    $this->postJson('/api/visits/check-in', [
        'visitor_id' => $otherVisitor->id,
        'activity_type' => 'Email',
        'activity_id' => 1,
    ])->assertUnprocessable()->assertJsonValidationErrors('activity_type');

    $this->postJson('/api/visits/check-in', [
        'visitor_id' => $otherVisitor->id,
        'activity_type' => 'Call',
        'activity_id' => 999,
    ])->assertUnprocessable()->assertJsonValidationErrors('activity_id');
});

test('visit listing supports lifecycle, Pending Link display, and visitor search', function () {
    $visitor = Visitor::query()->create([
        'name' => 'Searchable Visitor',
        'company_name' => 'Search Company',
    ]);

    $visitId = $this->postJson('/api/visits/check-in', ['visitor_id' => $visitor->id])
        ->assertCreated()
        ->assertJsonPath('display_status', 'Pending Link')
        ->json('id');

    $this->getJson('/api/visits?status=Pending%20Link&search=Searchable')
        ->assertOk()
        ->assertJsonCount(1, 'visits')
        ->assertJsonPath('visits.0.id', $visitId)
        ->assertJsonCount(1, 'current_visits');

    $this->postJson("/api/visits/{$visitId}/check-out")->assertOk();

    $this->getJson('/api/visits?status=Completed')
        ->assertOk()
        ->assertJsonCount(1, 'visits')
        ->assertJsonCount(0, 'current_visits');
});
