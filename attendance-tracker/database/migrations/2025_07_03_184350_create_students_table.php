<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number', 20)->unique();
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('email', 100)->unique();
            $table->enum('department', ['Business', 'IT', 'Science'])->default('IT');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('registration_number');
        });
    }

    public function down()
    {
        Schema::dropIfExists('students');
    }
};