<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('njangi_payment_submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('member_id')
                ->constrained('members')
                ->cascadeOnDelete();

            $table->foreignId('njangi_cycle_id')
                ->constrained('njangi_cycles')
                ->cascadeOnDelete();

            $table->foreignId('njangi_session_id')
                ->constrained('njangi_sessions')
                ->cascadeOnDelete();

            $table->decimal('amount', 12, 2);

            $table->boolean('is_attending')->default(false);

            $table->string('screenshot_path');

            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending');

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->text('member_note')->nullable();
            $table->text('review_note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('njangi_payment_submissions');
    }
};