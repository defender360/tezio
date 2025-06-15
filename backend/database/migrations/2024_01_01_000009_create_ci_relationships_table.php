<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ci_relationships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('source_ci_id');
            $table->uuid('target_ci_id');
            $table->string('relationship_type');
            $table->text('description')->nullable();
            $table->string('impact_level')->default('medium');
            $table->boolean('is_active')->default(true);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_to')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('tenant_id');
            $table->index('source_ci_id');
            $table->index('target_ci_id');
            $table->index('relationship_type');
            $table->index('impact_level');
            $table->index('is_active');
            $table->index(['tenant_id', 'source_ci_id']);
            $table->index(['tenant_id', 'target_ci_id']);
            $table->index(['tenant_id', 'relationship_type']);
            $table->index(['tenant_id', 'is_active']);
            
            // Composite indexes for common queries
            $table->index(['source_ci_id', 'relationship_type']);
            $table->index(['target_ci_id', 'relationship_type']);
            
            // Unique constraint to prevent duplicate relationships
            $table->unique(['source_ci_id', 'target_ci_id', 'relationship_type']);
            
            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('source_ci_id')->references('id')->on('configuration_items')->onDelete('cascade');
            $table->foreign('target_ci_id')->references('id')->on('configuration_items')->onDelete('cascade');
        });
        
        // Enable Row Level Security
        DB::statement('ALTER TABLE ci_relationships ENABLE ROW LEVEL SECURITY');
        
        // Create policy for tenant isolation
        DB::statement('CREATE POLICY tenant_isolation ON ci_relationships
            USING (tenant_id = current_setting(\'app.current_tenant_id\')::uuid)');
        
        // Create check constraint to prevent self-referencing relationships
        DB::statement('ALTER TABLE ci_relationships ADD CONSTRAINT check_no_self_reference CHECK (source_ci_id != target_ci_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON ci_relationships');
        Schema::dropIfExists('ci_relationships');
    }
};