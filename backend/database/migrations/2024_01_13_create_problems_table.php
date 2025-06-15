<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('problems', function (Blueprint $table) {
            $table->id();
            $table->string('problem_number')->unique();
            $table->string('title');
            $table->text('description');
            $table->enum('status', [
                'open', 'investigating', 'identified', 'resolved', 'closed'
            ])->default('open');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->string('category')->nullable();
            $table->text('root_cause')->nullable();
            $table->json('symptoms')->nullable();
            $table->text('impact_description')->nullable();
            $table->text('workaround')->nullable();
            $table->text('permanent_solution')->nullable();
            $table->foreignUuid('assigned_to')->nullable()->constrained('users');
            $table->foreignId('assigned_group_id')->nullable()->constrained('groups');
            $table->foreignUuid('reported_by')->constrained('users');
            $table->timestamp('detected_date');
            $table->timestamp('resolved_date')->nullable();
            $table->timestamp('closed_date')->nullable();
            $table->boolean('known_error')->default(false);
            $table->timestamp('known_error_date')->nullable();
            $table->timestamp('resolution_target_date')->nullable();
            $table->timestamp('actual_resolution_date')->nullable();
            $table->text('review_notes')->nullable();
            $table->text('lessons_learned')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'priority']);
            $table->index(['assigned_to', 'status']);
            $table->index(['known_error', 'status']);
            $table->index('detected_date');
        });

        Schema::create('problem_incident', function (Blueprint $table) {
            $table->id();
            $table->foreignId('problem_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('incident_id')->constrained()->onDelete('cascade');
            $table->string('relationship_type')->default('related'); // related, caused_by, root_cause
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['problem_id', 'incident_id']);
            $table->index('relationship_type');
        });

        Schema::create('problem_configuration_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('problem_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('configuration_item_id')->constrained()->onDelete('cascade');
            $table->string('impact_type')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['problem_id', 'configuration_item_id']);
        });

        Schema::create('problem_investigations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('problem_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('investigator_id')->constrained('users');
            $table->text('findings');
            $table->text('actions_taken')->nullable();
            $table->text('next_steps')->nullable();
            $table->enum('investigation_type', [
                'initial', 'technical', 'business_impact', 'root_cause', 'solution'
            ])->default('initial');
            $table->timestamps();
            
            $table->index(['problem_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('problem_investigations');
        Schema::dropIfExists('problem_configuration_item');
        Schema::dropIfExists('problem_incident');
        Schema::dropIfExists('problems');
    }
};