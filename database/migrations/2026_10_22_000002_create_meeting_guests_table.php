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
        Schema::create('meeting_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('njangi_session_id')->constrained('njangi_sessions')->cascadeOnDelete();
            $table->string('guest_name');
            $table->string('guest_phone')->nullable();
            $table->string('guest_email')->nullable();
            $table->foreignId('invited_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('invited_by_name')->nullable();
            $table->timestamp('check_in_at')->useCurrent();
            $table->timestamps();

            $table->index(['njangi_session_id', 'guest_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_guests');
    }
};
