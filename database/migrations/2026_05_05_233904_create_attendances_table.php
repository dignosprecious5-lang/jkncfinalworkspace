<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('employee_name');

            $table->dateTime('time_in')->nullable();
            $table->dateTime('time_out')->nullable();

            $table->dateTime('break_in')->nullable();
            $table->dateTime('break_out')->nullable();

            $table->dateTime('lunch_in')->nullable();
            $table->dateTime('lunch_out')->nullable();

            $table->integer('late')->default(0);

            $table->decimal('total_working_hours', 8, 2)->nullable();
            $table->decimal('total_lunch', 8, 2)->nullable();
            $table->decimal('total_break', 8, 2)->nullable();

            $table->enum('status', ['pending', 'approved'])->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};