<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('services')) {
            Schema::create('services', function (Blueprint $table) {
                $table->id();
                $table->string('service_code')->nullable()->unique();
                $table->string('name');
                $table->string('short_name')->nullable();
                $table->string('service_area')->nullable();
                $table->string('category')->nullable();
                $table->string('subcategory')->nullable();
                $table->string('status')->default('incomplete');
                $table->string('engagement_behavior')->default('project');
                $table->unsignedBigInteger('active_version_id')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'service_code')) {
                $table->string('service_code')->nullable()->unique();
            }
            if (! Schema::hasColumn('services', 'name')) {
                $table->string('name')->nullable();
            }
            if (! Schema::hasColumn('services', 'short_name')) {
                $table->string('short_name')->nullable();
            }
            if (! Schema::hasColumn('services', 'subcategory')) {
                $table->string('subcategory')->nullable();
            }
            if (! Schema::hasColumn('services', 'engagement_behavior')) {
                $table->string('engagement_behavior')->default('project');
            }
            if (! Schema::hasColumn('services', 'active_version_id')) {
                $table->unsignedBigInteger('active_version_id')->nullable();
            }
        });

        // Safely backfill V2 columns from existing legacy production rows without overwriting existing values.
        if (Schema::hasColumn('services', 'service_name')) {
            DB::table('services')->orderBy('id')->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $updates = [];
                    if (empty($row->name) && ! empty($row->service_name)) {
                        $updates['name'] = $row->service_name;
                    }
                    if (empty($row->service_code)) {
                        $legacyId = ! empty($row->service_id) ? $row->service_id : $row->id;
                        $updates['service_code'] = 'SRV-' . str_pad((string) $legacyId, 5, '0', STR_PAD_LEFT);
                    }
                    if (empty($row->engagement_behavior)) {
                        $updates['engagement_behavior'] = ! empty($row->is_recurring) ? 'regular' : 'project';
                    }
                    if ($updates !== []) {
                        DB::table('services')->where('id', $row->id)->update($updates);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: preserve pre-existing production services table and data.
    }
};
