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
        Schema::create('incident_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('incident_id');
            $table->uuid('user_id');
            $table->string('action');
            $table->string('field')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('incident_id')->references('id')->on('incidents')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
            
            $table->index('tenant_id');
            $table->index('incident_id');
            $table->index('action');
            $table->index('created_at');
        });
        
        // Enable Row Level Security
        DB::statement('ALTER TABLE incident_histories ENABLE ROW LEVEL SECURITY');
        
        // Create policy for tenant isolation
        DB::statement('CREATE POLICY tenant_isolation ON incident_histories
            USING (tenant_id = current_setting(\'app.current_tenant_id\')::uuid)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON incident_histories');
        Schema::dropIfExists('incident_histories');
    }
};