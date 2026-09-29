<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_filters', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('module', 32)->nullable()->index();
            $table->json('conditions');
            $table->string('logic', 3)->default('AND');
            $table->boolean('is_shared')->default(false);
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_filters');
    }
};
