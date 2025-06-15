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
        Schema::create('configuration_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('ci_type_id');
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('operational');
            $table->uuid('owner_id')->nullable();
            $table->string('location')->nullable();
            $table->string('criticality')->default('medium');
            $table->string('environment')->nullable();
            $table->json('attributes')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('discovered_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('decommissioned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('tenant_id');
            $table->index('ci_type_id');
            $table->index('code');
            $table->index('status');
            $table->index('owner_id');
            $table->index('criticality');
            $table->index('environment');
            $table->index('is_active');
            $table->index(['tenant_id', 'code']);
            $table->index(['tenant_id', 'ci_type_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'is_active']);
            
            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('ci_type_id')->references('id')->on('ci_types')->onDelete('restrict');
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('set null');
        });
        
        // Full text search index (PostgreSQL specific)
        DB::statement('CREATE INDEX configuration_items_search_idx ON configuration_items USING gin(to_tsvector(\'english\', name || \' \' || COALESCE(description, \'\')))');
        
        // Enable Row Level Security
        DB::statement('ALTER TABLE configuration_items ENABLE ROW LEVEL SECURITY');
        
        // Create policy for tenant isolation
        DB::statement('CREATE POLICY tenant_isolation ON configuration_items
            USING (tenant_id = current_setting(\'app.current_tenant_id\')::uuid)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON configuration_items');
        DB::statement('DROP INDEX IF EXISTS configuration_items_search_idx');
        Schema::dropIfExists('configuration_items');
    }
};