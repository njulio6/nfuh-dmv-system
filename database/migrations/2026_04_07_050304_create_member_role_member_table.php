<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_role_member', function (Blueprint $table) {
            $table->id();

            $table->foreignId('member_id')
                ->constrained('members')
                ->cascadeOnDelete();

            $table->foreignId('member_role_id')
                ->constrained('member_roles')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['member_id', 'member_role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_role_member');
    }
};