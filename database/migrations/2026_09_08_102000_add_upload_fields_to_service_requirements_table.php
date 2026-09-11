<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requirements', function (Blueprint $table) {
            if (! Schema::hasColumn('service_requirements', 'document_name')) {
                $table->string('document_name')->nullable();
            }
            if (! Schema::hasColumn('service_requirements', 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn('service_requirements', 'status')) {
                $table->string('status')->default('pending');
            }
            if (! Schema::hasColumn('service_requirements', 'file_path')) {
                $table->string('file_path')->nullable();
            }
            if (! Schema::hasColumn('service_requirements', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_requirements', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('service_requirements', 'document_name') ? 'document_name' : null,
                Schema::hasColumn('service_requirements', 'description') ? 'description' : null,
                Schema::hasColumn('service_requirements', 'status') ? 'status' : null,
                Schema::hasColumn('service_requirements', 'file_path') ? 'file_path' : null,
                Schema::hasColumn('service_requirements', 'rejection_reason') ? 'rejection_reason' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
