<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type');
            $table->string('category');
            $table->string('priority')->default('medium');
            $table->string('risk_level')->default('medium');
            $table->boolean('cab_required')->default(false);
            $table->text('implementation_plan_template')->nullable();
            $table->text('rollback_plan_template')->nullable();
            $table->text('test_plan_template')->nullable();
            $table->text('communication_plan_template')->nullable();
            $table->json('checklist')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('changes', function (Blueprint $table) {
            $table->id();
            $table->string('change_number')->unique();
            $table->string('title');
            $table->text('description');
            $table->enum('type', ['standard', 'normal', 'emergency', 'routine'])->default('normal');
            $table->string('category');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('impact', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', [
                'draft', 'submitted', 'under_review', 'approved', 'scheduled',
                'in_progress', 'completed', 'failed', 'cancelled', 'rolled_back'
            ])->default('draft');
            $table->foreignUuid('requester_id')->constrained('users');
            $table->foreignUuid('assigned_to')->nullable()->constrained('users');
            $table->foreignUuid('change_manager_id')->nullable()->constrained('users');
            $table->boolean('cab_required')->default(false);
            $table->timestamp('cab_date')->nullable();
            $table->text('implementation_plan')->nullable();
            $table->text('rollback_plan')->nullable();
            $table->text('test_plan')->nullable();
            $table->text('communication_plan')->nullable();
            $table->timestamp('scheduled_start')->nullable();
            $table->timestamp('scheduled_end')->nullable();
            $table->timestamp('actual_start')->nullable();
            $table->timestamp('actual_end')->nullable();
            $table->boolean('downtime_required')->default(false);
            $table->integer('downtime_duration')->nullable()->comment('Duration in minutes');
            $table->json('affected_services')->nullable();
            $table->text('business_justification')->nullable();
            $table->text('technical_justification')->nullable();
            $table->json('risk_assessment')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'on_hold'])->default('pending');
            $table->enum('implementation_status', ['pending', 'success', 'partial', 'failed'])->nullable();
            $table->text('post_implementation_review')->nullable();
            $table->text('lessons_learned')->nullable();
            $table->boolean('emergency_change')->default(false);
            $table->foreignId('parent_change_id')->nullable()->constrained('changes');
            $table->foreignId('template_id')->nullable()->constrained('change_templates');
            $table->string('workflow_state')->default('draft');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'scheduled_start']);
            $table->index(['type', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index('change_manager_id');
            $table->index(['cab_required', 'cab_date']);
        });

        Schema::create('change_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignUuid('assigned_to')->nullable()->constrained('users');
            $table->integer('sequence')->default(0);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed', 'skipped'])->default('pending');
            $table->timestamp('planned_start')->nullable();
            $table->timestamp('planned_end')->nullable();
            $table->timestamp('actual_start')->nullable();
            $table->timestamp('actual_end')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_required')->default(true);
            $table->timestamps();
            
            $table->index(['change_id', 'sequence']);
            $table->index(['assigned_to', 'status']);
        });

        Schema::create('change_incident', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('incident_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['change_id', 'incident_id']);
        });

        Schema::create('change_problem', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_id')->constrained()->onDelete('cascade');
            $table->foreignId('problem_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['change_id', 'problem_id']);
        });

        Schema::create('change_configuration_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('configuration_item_id')->constrained()->onDelete('cascade');
            $table->string('impact_type')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['change_id', 'configuration_item_id']);
        });

        Schema::create('change_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('approver_id')->constrained('users');
            $table->enum('approval_type', ['technical', 'business', 'security', 'cab', 'emergency'])->default('technical');
            $table->enum('status', ['pending', 'approved', 'rejected', 'delegated'])->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignUuid('delegated_to')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['change_id', 'status']);
            $table->index(['approver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_approvals');
        Schema::dropIfExists('change_configuration_item');
        Schema::dropIfExists('change_problem');
        Schema::dropIfExists('change_incident');
        Schema::dropIfExists('change_tasks');
        Schema::dropIfExists('changes');
        Schema::dropIfExists('change_templates');
    }
};