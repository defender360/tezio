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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('user_id');
            $table->string('notification_type'); // incident_created, sla_breach, etc.
            $table->json('channels'); // ['email', 'sms', 'in_app']
            $table->boolean('enabled')->default(true);
            
            // Delivery preferences
            $table->string('frequency')->default('immediate'); // immediate, hourly, daily
            $table->json('schedule')->nullable(); // For scheduled delivery
            $table->string('timezone')->default('UTC');
            
            // Filtering rules
            $table->json('filters')->nullable(); // e.g., priority levels, categories
            
            // Quiet hours
            $table->boolean('enable_quiet_hours')->default(false);
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->json('quiet_hours_days')->nullable(); // ['mon', 'tue', ...]
            
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->unique(['user_id', 'notification_type']);
            $table->index(['tenant_id', 'user_id', 'enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};