<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name');
            $table->string('event', 20);
            $table->string('subject_type');
            $table->string('subject_id', 64);
            $table->string('subject_label');
            $table->json('changes');
            $table->timestamp('created_at');

            $table->index(['subject_type', 'subject_id', 'id']);
            $table->index(['event', 'id']);
            $table->index(['actor_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
