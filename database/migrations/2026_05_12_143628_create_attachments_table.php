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
        Schema::create('attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('task_id')->constrained('tasks')->restrictOnDelete();
            $table->foreignUuid('uploaded_by')->constrained('users')->restrictOnDelete();

            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_disk');
            $table->string('mime_type');
            $table->bigInteger('file_size');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
