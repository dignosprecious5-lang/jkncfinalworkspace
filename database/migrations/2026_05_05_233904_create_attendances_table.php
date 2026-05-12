<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Best practice
            $table->string('employee_name'); 
            $table->date('date');
            
            $table->dateTime('time_in')->nullable();
            $table->dateTime('time_out')->nullable();

            // Break 1
            $table->dateTime('break_1_start')->nullable();
            $table->dateTime('break_1_end')->nullable();

            // Lunch
            $table->dateTime('lunch_start')->nullable();
            $table->dateTime('lunch_end')->nullable();

            // Break 2
            $table->dateTime('break_2_start')->nullable();
            $table->dateTime('break_2_end')->nullable();

            $table->integer('total_break_mins')->default(0);
            $table->integer('total_lunch_mins')->default(0);
            $table->decimal('total_working_hours', 8, 2)->default(0);

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};