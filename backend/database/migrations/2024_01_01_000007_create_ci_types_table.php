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
        Schema::create('ci_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->json('schema')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order_index')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('tenant_id');
            $table->index('code');
            $table->index('parent_id');
            $table->index('is_active');
            $table->index(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active']);
            
            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            // Self-referencing foreign key will be added after table creation
        });
        
        // Add self-referencing foreign key after table creation
        Schema::table('ci_types', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('ci_types')->onDelete('cascade');
        });
        
        // Enable Row Level Security
        DB::statement('ALTER TABLE ci_types ENABLE ROW LEVEL SECURITY');
        
        // Create policy for tenant isolation
        DB::statement('CREATE POLICY tenant_isolation ON ci_types
            USING (tenant_id = current_setting(\'app.current_tenant_id\')::uuid)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON ci_types');
        Schema::dropIfExists('ci_types');
    }
};