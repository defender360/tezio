<?php

namespace Database\Seeders;

use App\Domains\Tenant\Models\Tenant;
use App\Domains\Tenant\Models\TenantDomain;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default tenant for development
        $defaultTenant = Tenant::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Defender360 Demo',
            'slug' => 'demo',
            'subdomain' => 'demo',
            'company_name' => 'Defender360 Demo Company',
            'company_email' => 'demo@defender360.com',
            'company_phone' => '+1 (555) 123-4567',
            'company_website' => 'https://demo.defender360.com',
            'company_address' => '123 Demo Street, Suite 100, San Francisco, CA 94105',
            'tax_id' => 'DEMO-123456789',
            'plan' => 'enterprise',
            'status' => 'active',
            'trial_ends_at' => now()->addDays(30),
            'max_users' => 1000,
            'max_tickets_per_month' => 10000,
            'ai_features_enabled' => true,
            'settings' => [
                'ticket_prefix' => 'DEMO',
                'auto_assign_tickets' => true,
                'email_notifications' => true,
                'sla_enabled' => true,
                'business_hours' => [
                    'start' => '09:00',
                    'end' => '18:00',
                    'days' => [1, 2, 3, 4, 5], // Monday to Friday
                ],
                'ticket_categories' => [
                    'Hardware',
                    'Software',
                    'Network',
                    'Security',
                    'Account',
                    'Other',
                ],
                'priority_levels' => [
                    'low' => ['name' => 'Low', 'sla_hours' => 48],
                    'medium' => ['name' => 'Medium', 'sla_hours' => 24],
                    'high' => ['name' => 'High', 'sla_hours' => 8],
                    'critical' => ['name' => 'Critical', 'sla_hours' => 2],
                ],
            ],
            'features' => [
                'incidents' => true,
                'service_requests' => true,
                'problems' => true,
                'changes' => true,
                'knowledge_base' => true,
                'asset_management' => true,
                'ai_suggestions' => true,
                'custom_workflows' => true,
                'api_access' => true,
                'white_label' => false,
            ],
            'timezone' => 'America/Los_Angeles',
            'locale' => 'en',
            'currency' => 'USD',
            'mfa_required' => false,
            'onboarding_status' => 'completed',
            'onboarded_at' => now(),
            'last_activity_at' => now(),
        ]);

        // Create primary domain
        TenantDomain::create([
            'tenant_id' => $defaultTenant->id,
            'domain' => 'demo.defender360.local',
            'is_primary' => true,
            'is_verified' => true,
            'verified_at' => now(),
            'ssl_status' => 'active',
        ]);

        // Create test tenant for multi-tenancy testing
        $testTenant = Tenant::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Test Corporation',
            'slug' => 'test-corp',
            'subdomain' => 'test-corp',
            'company_name' => 'Test Corporation Inc.',
            'company_email' => 'admin@testcorp.com',
            'company_phone' => '+1 (555) 987-6543',
            'company_website' => 'https://testcorp.example.com',
            'company_address' => '456 Test Avenue, Floor 20, New York, NY 10001',
            'tax_id' => 'TEST-987654321',
            'plan' => 'professional',
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
            'max_users' => 50,
            'max_tickets_per_month' => 1000,
            'ai_features_enabled' => true,
            'settings' => [
                'ticket_prefix' => 'TEST',
                'auto_assign_tickets' => false,
                'email_notifications' => true,
                'sla_enabled' => true,
                'business_hours' => [
                    'start' => '08:00',
                    'end' => '17:00',
                    'days' => [1, 2, 3, 4, 5],
                ],
            ],
            'features' => [
                'incidents' => true,
                'service_requests' => true,
                'problems' => true,
                'changes' => false,
                'knowledge_base' => true,
                'asset_management' => false,
                'ai_suggestions' => true,
                'custom_workflows' => false,
                'api_access' => true,
                'white_label' => false,
            ],
            'timezone' => 'America/New_York',
            'locale' => 'en',
            'currency' => 'USD',
            'mfa_required' => true,
            'onboarding_status' => 'in_progress',
            'last_activity_at' => now(),
        ]);

        // Create domain for test tenant
        TenantDomain::create([
            'tenant_id' => $testTenant->id,
            'domain' => 'test-corp.defender360.local',
            'is_primary' => true,
            'is_verified' => true,
            'verified_at' => now(),
            'ssl_status' => 'active',
        ]);

        $this->command->info('Tenants seeded successfully!');
        $this->command->table(
            ['Tenant', 'Subdomain', 'Plan', 'Status'],
            [
                [$defaultTenant->name, $defaultTenant->subdomain, $defaultTenant->plan, $defaultTenant->status],
                [$testTenant->name, $testTenant->subdomain, $testTenant->plan, $testTenant->status],
            ]
        );
    }
}