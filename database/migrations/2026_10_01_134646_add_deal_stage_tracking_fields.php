<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Deal stage timer
         */
        if (! Schema::hasColumn('deals', 'stage_entered_at')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->timestamp('stage_entered_at')
                    ->nullable()
                    ->after('stage');
            });
        }

        /*
         * Inquiry workflow fields
         */
        Schema::table('deals', function (Blueprint $table) {
            if (! Schema::hasColumn('deals', 'inquiry_details')) {
                $table->text('inquiry_details')
                    ->nullable()
                    ->after('qualification_notes');
            }

            if (! Schema::hasColumn('deals', 'inquiry_records')) {
                $table->json('inquiry_records')
                    ->nullable()
                    ->after('inquiry_details');
            }
        });

        /*
         * Consultation workflow records
         */
        if (! Schema::hasColumn('deals', 'consultation_records')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->json('consultation_records')
                    ->nullable()
                    ->after('consultant_notes');
            });
        }

        /*
         * Stage history / timer records
         */
        if (! Schema::hasTable('deal_stage_histories')) {
            Schema::create('deal_stage_histories', function (Blueprint $table) {
                $table->id();

                $table->foreignId('deal_id')
                    ->constrained('deals')
                    ->cascadeOnDelete();

                $table->string('stage')->index();

                $table->timestamp('started_at')
                    ->useCurrent()
                    ->index();

                $table->timestamp('ended_at')
                    ->nullable()
                    ->index();

                $table->unsignedInteger('duration_seconds')
                    ->nullable();

                $table->string('duration_formatted')
                    ->nullable();

                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('user_name')
                    ->nullable();

                $table->text('notes')
                    ->nullable();

                $table->timestamps();

                $table->index(['deal_id', 'stage']);
                $table->index(['deal_id', 'started_at']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('deal_stage_histories')) {
            Schema::drop('deal_stage_histories');
        }

        if (Schema::hasColumn('deals', 'consultation_records')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->dropColumn('consultation_records');
            });
        }

        if (Schema::hasColumn('deals', 'inquiry_records')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->dropColumn('inquiry_records');
            });
        }

        if (Schema::hasColumn('deals', 'inquiry_details')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->dropColumn('inquiry_details');
            });
        }

        if (Schema::hasColumn('deals', 'stage_entered_at')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->dropColumn('stage_entered_at');
            });
        }
    }
};