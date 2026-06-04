<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addCommonFields('permits', function (Blueprint $table) {
            $this->addColumn($table, 'company_id', fn () => $table->unsignedBigInteger('company_id')->nullable()->after('id')->index());
            $this->addColumn($table, 'company_name', fn () => $table->string('company_name')->nullable()->after('company_id'));
            $this->addColumn($table, 'province', fn () => $table->string('province')->nullable()->after('company_name'));
            $this->addColumn($table, 'city_municipality', fn () => $table->string('city_municipality')->nullable()->after('province'));
            $this->addColumn($table, 'barangay', fn () => $table->string('barangay')->nullable()->after('city_municipality'));
            $this->addColumn($table, 'renewal_date', fn () => $table->date('renewal_date')->nullable()->after('date_of_registration'));
            $this->addColumn($table, 'total_permit_fee', fn () => $table->decimal('total_permit_fee', 12, 2)->nullable()->after('renewal_date'));
        });

        $this->addCommonFields('accountings', function (Blueprint $table) {
            $this->addColumn($table, 'company_id', fn () => $table->unsignedBigInteger('company_id')->nullable()->after('id')->index());
            $this->addColumn($table, 'company_name', fn () => $table->string('company_name')->nullable()->after('company_id'));
            $this->addColumn($table, 'reporting_period_from', fn () => $table->date('reporting_period_from')->nullable()->after('date'));
            $this->addColumn($table, 'reporting_period_to', fn () => $table->date('reporting_period_to')->nullable()->after('reporting_period_from'));
        });

        $this->addCommonFields('bankings', function (Blueprint $table) {
            $this->addColumn($table, 'company_id', fn () => $table->unsignedBigInteger('company_id')->nullable()->after('id')->index());
            $this->addColumn($table, 'company_name', fn () => $table->string('company_name')->nullable()->after('company_id'));
            $this->addColumn($table, 'document_date', fn () => $table->date('document_date')->nullable()->after('bank_doc'));
        });

        $this->addCommonFields('operations', function (Blueprint $table) {
            $this->addColumn($table, 'company_id', fn () => $table->unsignedBigInteger('company_id')->nullable()->after('id')->index());
            $this->addColumn($table, 'company_name', fn () => $table->string('company_name')->nullable()->after('company_id'));
            $this->addColumn($table, 'document_title', fn () => $table->string('document_title')->nullable()->after('document_type'));
            $this->addColumn($table, 'document_date', fn () => $table->date('document_date')->nullable()->after('document_title'));
        });

        $this->addCommonFields('legals', function (Blueprint $table) {
            $this->addColumn($table, 'company_id', fn () => $table->unsignedBigInteger('company_id')->nullable()->after('id')->index());
            $this->addColumn($table, 'company_name', fn () => $table->string('company_name')->nullable()->after('company_id'));
            $this->addColumn($table, 'document_title', fn () => $table->string('document_title')->nullable()->after('document_type'));
            $this->addColumn($table, 'effective_date', fn () => $table->date('effective_date')->nullable()->after('date'));
            $this->addColumn($table, 'expiration_date', fn () => $table->date('expiration_date')->nullable()->after('effective_date'));
            $this->addColumn($table, 'record_status', fn () => $table->string('record_status')->nullable()->after('expiration_date'));
        });
    }

    public function down(): void
    {
        foreach ([
            'permits' => ['company_id', 'company_name', 'province', 'city_municipality', 'barangay', 'renewal_date', 'total_permit_fee'],
            'accountings' => ['company_id', 'company_name', 'reporting_period_from', 'reporting_period_to'],
            'bankings' => ['company_id', 'company_name', 'document_date'],
            'operations' => ['company_id', 'company_name', 'document_title', 'document_date'],
            'legals' => ['company_id', 'company_name', 'document_title', 'effective_date', 'expiration_date', 'record_status'],
        ] as $table => $columns) {
            $this->dropColumns($table, $columns);
        }

        foreach (['permits', 'accountings', 'bankings', 'operations', 'legals'] as $table) {
            $this->dropColumns($table, [
                'draft_documents',
                'approved_documents',
                'uploaded_by',
                'date_uploaded_at',
                'last_updated_by',
                'last_updated_at',
            ]);
        }
    }

    private function addCommonFields(string $tableName, callable $specificFields): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName, $specificFields) {
            $this->tableName = $tableName;
            $specificFields($table);
            $this->addColumn($table, 'draft_documents', fn () => $table->json('draft_documents')->nullable());
            $this->addColumn($table, 'approved_documents', fn () => $table->json('approved_documents')->nullable());
            $this->addColumn($table, 'uploaded_by', fn () => $table->string('uploaded_by')->nullable());
            $this->addColumn($table, 'date_uploaded_at', fn () => $table->timestamp('date_uploaded_at')->nullable());
            $this->addColumn($table, 'last_updated_by', fn () => $table->string('last_updated_by')->nullable());
            $this->addColumn($table, 'last_updated_at', fn () => $table->timestamp('last_updated_at')->nullable());
        });
    }

    private string $tableName = '';

    private function addColumn(Blueprint $table, string $column, callable $definition): void
    {
        if (! Schema::hasColumn($this->tableName, $column)) {
            $definition();
        }
    }

    private function dropColumns(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
            foreach (array_reverse($columns) as $column) {
                if (Schema::hasColumn($table, $column)) {
                    $blueprint->dropColumn($column);
                }
            }
        });
    }
};
