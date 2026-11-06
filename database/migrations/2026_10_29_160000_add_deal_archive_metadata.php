<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('archived_by_user_id')->nullable();
            $table->text('archive_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table): void {
            $table->dropColumn(['archived_at', 'archived_by_user_id', 'archive_reason']);
        });
    }
};
