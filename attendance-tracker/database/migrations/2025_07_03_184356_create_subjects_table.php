<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('subject_code', 10)->unique();
            $table->string('subject_name', 100);
            $table->text('description')->nullable();
            $table->enum('department', ['Business', 'IT', 'Science'])->default('IT');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['subject_code', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('subjects');
    }
};