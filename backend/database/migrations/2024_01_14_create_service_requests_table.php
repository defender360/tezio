<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_catalog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('service_catalog_categories');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['parent_id', 'is_active']);
        });

        Schema::create('service_catalog_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('short_description');
            $table->longText('description')->nullable();
            $table->foreignId('category_id')->constrained('service_catalog_categories');
            $table->string('icon')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->integer('estimated_delivery_hours')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->json('approval_levels')->nullable();
            $table->json('form_schema')->nullable();
            $table->json('fulfillment_automation')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('popularity_score')->default(0);
            $table->integer('request_count')->default(0);
            $table->timestamps();
            
            $table->index(['category_id', 'is_active']);
            $table->index(['is_featured', 'is_active']);
        });

        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('service_item_id')->constrained('service_catalog_items');
            $table->foreignUuid('requester_id')->constrained('users');
            $table->foreignUuid('requested_for')->constrained('users');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', [
                'submitted', 'in_progress', 'pending_approval', 'approved', 
                'on_hold', 'completed', 'cancelled', 'closed'
            ])->default('submitted');
            $table->foreignUuid('assigned_to')->nullable()->constrained('users');
            $table->foreignId('assigned_group_id')->nullable()->constrained('groups');
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();
            $table->enum('urgency', ['low', 'medium', 'high'])->default('medium');
            $table->enum('impact', ['low', 'medium', 'high'])->default('medium');
            $table->timestamp('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('fulfilment_notes')->nullable();
            $table->integer('customer_satisfaction')->nullable();
            $table->boolean('approval_required')->default(false);
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->nullable();
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('actual_cost', 10, 2)->nullable();
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('actual_hours', 8, 2)->nullable();
            $table->json('variables')->nullable();
            $table->timestamp('sla_breach_at')->nullable();
            $table->string('workflow_state')->default('submitted');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'assigned_to']);
            $table->index(['status', 'assigned_group_id']);
            $table->index(['requester_id', 'status']);
            $table->index(['due_date', 'status']);
            $table->index('sla_breach_at');
        });

        Schema::create('service_request_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignUuid('assigned_to')->nullable()->constrained('users');
            $table->integer('sequence')->default(0);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->boolean('is_required')->default(true);
            $table->integer('estimated_minutes')->nullable();
            $table->integer('actual_minutes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['service_request_id', 'sequence']);
            $table->index(['assigned_to', 'status']);
        });

        Schema::create('service_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('approver_id')->constrained('users');
            $table->integer('level')->default(1);
            $table->enum('status', ['pending', 'approved', 'rejected', 'delegated'])->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignUuid('delegated_to')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['service_request_id', 'status']);
            $table->index(['approver_id', 'status']);
        });

        Schema::create('service_catalog_item_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_item_id')->constrained('service_catalog_items')->onDelete('cascade');
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
            $table->enum('access_type', ['requester', 'fulfiller', 'approver'])->default('requester');
            $table->timestamps();
            
            $table->unique(['service_item_id', 'group_id', 'access_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_catalog_item_groups');
        Schema::dropIfExists('service_request_approvals');
        Schema::dropIfExists('service_request_tasks');
        Schema::dropIfExists('service_requests');
        Schema::dropIfExists('service_catalog_items');
        Schema::dropIfExists('service_catalog_categories');
    }
};