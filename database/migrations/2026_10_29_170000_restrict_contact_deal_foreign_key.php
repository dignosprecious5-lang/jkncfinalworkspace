<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FOREIGN_KEY = 'deals_contact_id_foreign';

    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table): void {
            $table->dropForeign(self::FOREIGN_KEY);
            $table->foreign('contact_id', self::FOREIGN_KEY)
                ->references('id')
                ->on('contacts')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table): void {
            $table->dropForeign(self::FOREIGN_KEY);
            $table->foreign('contact_id', self::FOREIGN_KEY)
                ->references('id')
                ->on('contacts')
                ->cascadeOnDelete();
        });
    }
};
