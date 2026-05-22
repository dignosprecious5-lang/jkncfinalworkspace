<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (! Schema::hasColumn('contacts', 'cif_data')) {
                $table->json('cif_data')->nullable()->after('specimen_form_sent_at');
            }

            if (! Schema::hasColumn('contacts', 'cif_documents')) {
                $table->json('cif_documents')->nullable()->after('cif_data');
            }

            if (! Schema::hasColumn('contacts', 'kyc_requirement_documents')) {
                $table->json('kyc_requirement_documents')->nullable()->after('cif_documents');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('contacts', 'cif_data') ? 'cif_data' : null,
                Schema::hasColumn('contacts', 'cif_documents') ? 'cif_documents' : null,
                Schema::hasColumn('contacts', 'kyc_requirement_documents') ? 'kyc_requirement_documents' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
