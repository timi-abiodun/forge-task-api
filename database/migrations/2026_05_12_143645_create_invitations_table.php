<?php

use App\Enums\MembershipRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\InvitationStatus;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organisation_id')->constrained('organisations')->restrictOnDelete();
            $table->foreignUuid('invited_by')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('email');
            $table->unique(['email', 'organisation_id']);
            $table->string('role')->default(MembershipRole::MEMBER->value);
            $table->string('token')->unique();
            $table->string('status')->default(InvitationStatus::PENDING->value);

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
