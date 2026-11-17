<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('accounts') || Schema::hasColumn('deals', 'account_id')) {
            throw new RuntimeException('Accounts schema differs from the expected pre-migration state.');
        }

        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('account_code', 50)->unique();
            $table->string('account_type', 50);
            $table->string('account_name', 191);
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('individual_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('status', 50)->default('Active');
            $table->timestamps();
            $table->index('account_type');
            $table->index('status');
        });

        Schema::table('deals', function (Blueprint $table): void {
            $table->foreignId('account_id')->nullable()->after('id')->constrained('accounts')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Forward-only migration: Accounts and Deal links must not be dropped.');
    }
};
