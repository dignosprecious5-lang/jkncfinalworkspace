<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->string('pipeline_stage')->default('Inquiry')->after('customer_type');
            $table->decimal('amount', 15, 2)->nullable()->after('deal_title');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn(['pipeline_stage', 'amount']);
        });
    }
};
