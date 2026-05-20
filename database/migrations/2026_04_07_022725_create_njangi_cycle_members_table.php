<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('njangi_cycle_members', function (Blueprint $table) {
            $table->id();

            $table->foreignId('njangi_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();

            $table->unsignedInteger('benefit_order')->nullable();

            $table->boolean('is_active')->default(true);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['njangi_cycle_id', 'member_id']);
            $table->unique(['njangi_cycle_id', 'benefit_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('njangi_cycle_members');
    }
};