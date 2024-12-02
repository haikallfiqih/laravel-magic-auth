<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(config('magic-auth.table', 'magic_links'), function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('token')->unique();
            $table->string('guard');
            $table->boolean('used')->default(false);
            $table->json('attributes')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['email', 'guard']);
            $table->index(['phone', 'guard']);
            $table->index(['token', 'used']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('magic-auth.table', 'magic_links'));
    }
};
