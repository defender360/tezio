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
        Schema::create('notification_channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('type'); // email, sms, slack, teams, webhook
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('configuration'); // Channel-specific configuration
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            
            // Rate limiting
            $table->integer('rate_limit')->nullable(); // messages per minute
            $table->integer('daily_limit')->nullable(); // messages per day
            
            // Usage tracking
            $table->integer('messages_sent_today')->default(0);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('rate_limit_reset_at')->nullable();
            
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'type', 'is_active']);
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_channels');
    }
};