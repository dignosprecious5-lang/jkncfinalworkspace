<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_consultation_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('title');
            $table->date('consultation_date');
            $table->string('author')->nullable();
            $table->text('summary')->nullable();
            $table->longText('details')->nullable();
            $table->string('category')->nullable();
            $table->string('linked_deal')->nullable();
            $table->string('linked_activity')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->index('contact_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_consultation_notes');
    }
};
