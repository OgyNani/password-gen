<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_passwords', function (Blueprint $table) {
            $table->id();
            $table->string('hash', 64)->unique();
            $table->unsignedSmallInteger('length');
            $table->json('options');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_passwords');
    }
};
