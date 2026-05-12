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
        Schema::create('organisation_memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('organisation_id')->constrained('organisations')->restrictOnDelete();
            $table->foreignUuid('invited_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['user_id', 'organisation_id']);
            
            $table->string('role');
            $table->timestamp('invited_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organisation_memberships');
    }
};
