<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->date('attendance_date');
            $table->boolean('present')->default(true);
            $table->text('remarks')->nullable();
            $table->string('marked_by', 100)->nullable();
            $table->timestamps();
            
            // Unique constraint to prevent duplicate attendance
            $table->unique(['student_id', 'subject_id', 'attendance_date']);
            
            // Performance indexes for million records
            $table->index(['student_id', 'subject_id']);
            $table->index(['attendance_date', 'subject_id']);
            $table->index(['attendance_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance');
    }
};