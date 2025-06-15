<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable(); // null for system templates
            $table->string('code')->unique(); // incident_created, sla_breach, etc.
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category'); // incident, change, problem, system
            $table->json('channels'); // ['email', 'sms', 'in_app', 'webhook']
            
            // Email template
            $table->string('email_subject')->nullable();
            $table->text('email_body_html')->nullable();
            $table->text('email_body_text')->nullable();
            
            // SMS template
            $table->text('sms_body')->nullable();
            
            // In-app notification template
            $table->string('in_app_title')->nullable();
            $table->text('in_app_body')->nullable();
            
            // Webhook template
            $table->json('webhook_payload')->nullable();
            
            // Variables available in template
            $table->json('available_variables')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // System templates cannot be deleted
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'code', 'is_active']);
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};