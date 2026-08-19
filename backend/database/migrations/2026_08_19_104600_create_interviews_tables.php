<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scheduled_by')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->timestamp('scheduled_at');
            $table->unsignedSmallInteger('duration')->default(60);
            $table->string('location')->nullable();
            $table->string('meeting_url')->nullable();
            $table->string('status')->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('interview_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->unique(['interview_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_participants');
        Schema::dropIfExists('interviews');
    }
};