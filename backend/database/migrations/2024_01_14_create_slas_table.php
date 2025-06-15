<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['incident', 'service_request', 'change', 'problem'])->default('incident');
            $table->enum('target_type', ['response', 'resolution', 'both'])->default('both');
            $table->boolean('business_hours_only')->default(true);
            $table->integer('response_time')->nullable()->comment('Response time in minutes');
            $table->integer('resolution_time')->nullable()->comment('Resolution time in minutes');
            $table->integer('escalation_time')->nullable()->comment('Escalation time in minutes');
            $table->json('priority_matrix')->nullable()->comment('Different times for different priorities');
            $table->json('excluded_statuses')->nullable()->comment('Statuses that pause SLA clock');
            $table->json('conditions')->nullable()->comment('Conditions for SLA to apply');
            $table->json('penalties')->nullable()->comment('Penalties for breaching SLA');
            $table->boolean('is_active')->default(true);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['type', 'is_active']);
            $table->index(['valid_from', 'valid_until']);
        });

        Schema::create('sla_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sla_id')->constrained()->onDelete('cascade');
            $table->morphs('assignable'); // Can be assigned to departments, groups, customers, etc.
            $table->boolean('is_active')->default(true);
            $table->integer('priority_override')->nullable();
            $table->timestamps();
            $table->index(['sla_id', 'is_active']);
        });

        Schema::create('sla_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sla_id')->constrained()->onDelete('cascade');
            $table->morphs('measurable'); // Incident, ServiceRequest, Change, Problem
            $table->enum('metric_type', ['response', 'resolution', 'escalation'])->default('response');
            $table->timestamp('start_time');
            $table->timestamp('target_time');
            $table->timestamp('actual_time')->nullable();
            $table->boolean('is_breached')->default(false);
            $table->integer('breach_minutes')->nullable();
            $table->boolean('is_paused')->default(false);
            $table->integer('paused_minutes')->default(0);
            $table->json('pause_history')->nullable();
            $table->timestamps();
            $table->index(['sla_id', 'is_breached']);
            $table->index(['target_time', 'is_breached']);
        });

        Schema::create('sla_breach_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sla_metric_id')->constrained('sla_metrics')->onDelete('cascade');
            $table->enum('notification_type', ['warning', 'breached', 'escalation'])->default('warning');
            $table->integer('threshold_percentage')->default(80);
            $table->timestamp('notified_at');
            $table->json('notified_users')->nullable();
            $table->json('notification_channels')->nullable();
            $table->timestamps();
            
            $table->index(['sla_metric_id', 'notification_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_breach_notifications');
        Schema::dropIfExists('sla_metrics');
        Schema::dropIfExists('sla_assignments');
        Schema::dropIfExists('slas');
    }
};