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
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('subdomain')->unique();
            $table->string('company_name')->nullable();
            $table->string('company_email')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_website')->nullable();
            $table->text('company_address')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('plan')->default('trial');
            $table->string('status')->default('trial');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->integer('max_users')->default(5);
            $table->integer('max_tickets_per_month')->default(100);
            $table->boolean('ai_features_enabled')->default(false);
            $table->json('settings')->nullable();
            $table->json('features')->nullable();
            $table->json('config')->nullable();
            $table->string('database')->nullable();
            $table->string('timezone')->default('UTC');
            $table->string('locale')->default('en');
            $table->string('currency')->default('USD');
            $table->boolean('mfa_required')->default(false);
            $table->integer('password_expiry_days')->nullable();
            $table->json('allowed_ip_addresses')->nullable();
            $table->boolean('sso_enabled')->default(false);
            $table->json('sso_config')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('favicon_url')->nullable();
            $table->json('brand_colors')->nullable();
            $table->text('custom_css')->nullable();
            $table->string('api_key')->unique()->nullable();
            $table->string('webhook_secret')->nullable();
            $table->json('integrations')->nullable();
            $table->uuid('created_by')->nullable();
            $table->string('onboarding_status')->default('pending');
            $table->timestamp('onboarded_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->bigInteger('storage_used_bytes')->default(0);
            $table->bigInteger('storage_limit_bytes')->default(1073741824); // 1GB default
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('subdomain');
            $table->index('slug');
            $table->index('domain');
            $table->index('status');
            $table->index('is_active');
            $table->index(['status', 'is_active']);
        });
        
        // Enable Row Level Security
        DB::statement('ALTER TABLE tenants ENABLE ROW LEVEL SECURITY');
        
        // Create policy for tenant isolation
        DB::statement('CREATE POLICY tenant_isolation ON tenants
            USING (id = current_setting(\'app.current_tenant_id\')::uuid)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON tenants');
        Schema::dropIfExists('tenants');
    }
};