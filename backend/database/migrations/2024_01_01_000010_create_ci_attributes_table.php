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
        Schema::create('ci_attributes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('ci_type_id')->nullable();
            $table->uuid('configuration_item_id')->nullable();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->string('data_type');
            $table->text('value')->nullable();
            $table->text('default_value')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_unique')->default(false);
            $table->boolean('is_searchable')->default(false);
            $table->boolean('is_encrypted')->default(false);
            $table->json('validation_rules')->nullable();
            $table->json('options')->nullable();
            $table->integer('order_index')->default(0);
            $table->string('group_name')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('tenant_id');
            $table->index('ci_type_id');
            $table->index('configuration_item_id');
            $table->index('code');
            $table->index('data_type');
            $table->index('is_searchable');
            $table->index('group_name');
            $table->index(['tenant_id', 'ci_type_id']);
            $table->index(['tenant_id', 'configuration_item_id']);
            $table->index(['tenant_id', 'code']);
            
            // Composite indexes
            $table->index(['ci_type_id', 'code']);
            $table->index(['configuration_item_id', 'code']);
            
            // Unique constraints
            $table->unique(['ci_type_id', 'code']);
            $table->unique(['configuration_item_id', 'code']);
            
            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('ci_type_id')->references('id')->on('ci_types')->onDelete('cascade');
            $table->foreign('configuration_item_id')->references('id')->on('configuration_items')->onDelete('cascade');
        });
        
        // Enable Row Level Security
        DB::statement('ALTER TABLE ci_attributes ENABLE ROW LEVEL SECURITY');
        
        // Create policy for tenant isolation
        DB::statement('CREATE POLICY tenant_isolation ON ci_attributes
            USING (tenant_id = current_setting(\'app.current_tenant_id\')::uuid)');
        
        // Create check constraint to ensure either ci_type_id or configuration_item_id is set
        DB::statement('ALTER TABLE ci_attributes ADD CONSTRAINT check_attribute_owner 
            CHECK ((ci_type_id IS NOT NULL AND configuration_item_id IS NULL) OR 
                   (ci_type_id IS NULL AND configuration_item_id IS NOT NULL))');
                   
        // Create index for searchable values
        DB::statement('CREATE INDEX ci_attributes_value_search_idx ON ci_attributes 
            USING gin(to_tsvector(\'english\', value)) 
            WHERE is_searchable = true AND is_encrypted = false');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON ci_attributes');
        DB::statement('DROP INDEX IF EXISTS ci_attributes_value_search_idx');
        Schema::dropIfExists('ci_attributes');
    }
};