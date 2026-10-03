<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('exam_type', 20);
            $table->unsignedSmallInteger('attempt_number');
            $table->string('booking_status', 20)->index();
            $table->date('exam_date')->nullable()->index();
            $table->unsignedTinyInteger('score')->nullable();
            $table->unsignedTinyInteger('target_score')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'exam_type', 'attempt_number']);
            $table->index(['student_id', 'exam_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
