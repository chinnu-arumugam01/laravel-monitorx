<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_records', function (Blueprint $table) {
            $table->id();
            $table->string('module', 32)->index();
            $table->string('event', 120)->nullable()->index();
            $table->string('status', 32)->nullable()->index();
            $table->string('environment', 32)->index();
            $table->string('application', 120)->index();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedInteger('duration_ms')->nullable()->index();
            $table->string('summary', 500)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
            $table->index(['module', 'occurred_at']);
            $table->index(['environment', 'application', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_records');
    }
};
