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
        Schema::create('incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('title');
            $table->text('description');
            $table->string('status')->default('open');
            $table->string('priority');
            $table->string('impact');
            $table->string('urgency');
            $table->string('category');
            $table->string('subcategory')->nullable();
            $table->uuid('assigned_to')->nullable();
            $table->string('assigned_group')->nullable();
            $table->uuid('created_by');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('sla_deadline')->nullable();
            $table->timestamp('breach_time')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->text('customer_notes')->nullable();
            $table->json('tags')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
            
            $table->index('tenant_id');
            $table->index('status');
            $table->index('priority');
            $table->index('assigned_to');
            $table->index('created_by');
            $table->index('sla_deadline');
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'priority']);
        });
        
        // Enable Row Level Security
        DB::statement('ALTER TABLE incidents ENABLE ROW LEVEL SECURITY');
        
        // Create policy for tenant isolation
        DB::statement('CREATE POLICY tenant_isolation ON incidents
            USING (tenant_id = current_setting(\'app.current_tenant_id\')::uuid)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON incidents');
        Schema::dropIfExists('incidents');
    }
};