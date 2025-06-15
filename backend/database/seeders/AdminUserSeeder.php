<?php

namespace Database\Seeders;

use App\Core\Models\User;
use App\Domains\Tenant\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin (platform owner)
        $superAdmin = User::create([
            'id' => Str::uuid()->toString(),
            'email' => 'superadmin@defender360.com',
            'username' => 'superadmin',
            'email_verified_at' => now(),
            'password' => Hash::make('SuperAdmin@2024!'),
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'display_name' => 'Platform Administrator',
            'phone' => '+1 (555) 000-0001',
            'avatar_url' => null,
            'job_title' => 'Platform Administrator',
            'timezone' => 'UTC',
            'locale' => 'en',
            'auth_provider' => 'local',
            'mfa_enabled' => true,
            'mfa_secret' => null, // Will be set when user enables MFA
            'is_super_admin' => true,
            'status' => 'active',
            'activated_at' => now(),
            'last_login_at' => null,
            'preferences' => [
                'theme' => 'light',
                'sidebar_collapsed' => false,
                'notifications' => [
                    'email' => true,
                    'push' => true,
                    'sms' => false,
                ],
            ],
            'notification_settings' => [
                'new_tenant' => true,
                'system_alerts' => true,
                'performance_issues' => true,
                'security_alerts' => true,
            ],
        ]);

        // Get demo tenant
        $demoTenant = Tenant::where('slug', 'demo')->first();
        
        if ($demoTenant) {
            // Create Tenant Admin for demo tenant
            $tenantAdmin = User::create([
                'id' => Str::uuid()->toString(),
                'email' => 'admin@demo.defender360.com',
                'username' => 'demoadmin',
                'email_verified_at' => now(),
                'password' => Hash::make('DemoAdmin@2024!'),
                'first_name' => 'Demo',
                'last_name' => 'Admin',
                'display_name' => 'Demo Administrator',
                'phone' => '+1 (555) 123-4568',
                'job_title' => 'IT Manager',
                'timezone' => 'America/Los_Angeles',
                'locale' => 'en',
                'auth_provider' => 'local',
                'mfa_enabled' => false,
                'is_super_admin' => false,
                'status' => 'active',
                'activated_at' => now(),
                'preferences' => [
                    'theme' => 'light',
                    'sidebar_collapsed' => false,
                ],
            ]);

            // Attach admin to demo tenant
            $demoTenant->users()->attach($tenantAdmin->id, [
                'role' => 'admin',
                'is_owner' => true,
                'is_active' => true,
                'permissions' => json_encode(['*']), // All permissions
                'department' => 'IT',
                'job_title' => 'IT Manager',
                'joined_at' => now(),
            ]);

            // Create regular users for demo tenant
            $users = [
                [
                    'email' => 'agent1@demo.defender360.com',
                    'username' => 'agent1',
                    'first_name' => 'John',
                    'last_name' => 'Smith',
                    'role' => 'agent',
                    'department' => 'IT Support',
                    'job_title' => 'Support Agent',
                ],
                [
                    'email' => 'agent2@demo.defender360.com',
                    'username' => 'agent2',
                    'first_name' => 'Jane',
                    'last_name' => 'Doe',
                    'role' => 'agent',
                    'department' => 'IT Support',
                    'job_title' => 'Senior Support Agent',
                ],
                [
                    'email' => 'manager@demo.defender360.com',
                    'username' => 'manager',
                    'first_name' => 'Mike',
                    'last_name' => 'Johnson',
                    'role' => 'manager',
                    'department' => 'IT',
                    'job_title' => 'IT Support Manager',
                ],
            ];

            foreach ($users as $userData) {
                $user = User::create([
                    'id' => Str::uuid()->toString(),
                    'email' => $userData['email'],
                    'username' => $userData['username'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('Password@2024!'),
                    'first_name' => $userData['first_name'],
                    'last_name' => $userData['last_name'],
                    'display_name' => $userData['first_name'] . ' ' . $userData['last_name'],
                    'job_title' => $userData['job_title'],
                    'timezone' => 'America/Los_Angeles',
                    'locale' => 'en',
                    'auth_provider' => 'local',
                    'mfa_enabled' => false,
                    'is_super_admin' => false,
                    'status' => 'active',
                    'activated_at' => now(),
                ]);

                $demoTenant->users()->attach($user->id, [
                    'role' => $userData['role'],
                    'is_owner' => false,
                    'is_active' => true,
                    'department' => $userData['department'],
                    'job_title' => $userData['job_title'],
                    'joined_at' => now(),
                ]);
            }
        }

        // Get test tenant
        $testTenant = Tenant::where('slug', 'test-corp')->first();
        
        if ($testTenant) {
            // Create admin for test tenant
            $testAdmin = User::create([
                'id' => Str::uuid()->toString(),
                'email' => 'admin@testcorp.com',
                'username' => 'testadmin',
                'email_verified_at' => now(),
                'password' => Hash::make('TestAdmin@2024!'),
                'first_name' => 'Test',
                'last_name' => 'Admin',
                'display_name' => 'Test Administrator',
                'phone' => '+1 (555) 987-6544',
                'job_title' => 'IT Director',
                'timezone' => 'America/New_York',
                'locale' => 'en',
                'auth_provider' => 'local',
                'mfa_enabled' => true,
                'is_super_admin' => false,
                'status' => 'active',
                'activated_at' => now(),
            ]);

            $testTenant->users()->attach($testAdmin->id, [
                'role' => 'admin',
                'is_owner' => true,
                'is_active' => true,
                'permissions' => json_encode(['*']),
                'department' => 'IT',
                'job_title' => 'IT Director',
                'joined_at' => now(),
            ]);
        }

        $this->command->info('Admin users seeded successfully!');
        $this->command->table(
            ['Email', 'Username', 'Role', 'Tenant'],
            [
                ['superadmin@defender360.com', 'superadmin', 'Super Admin', 'Platform'],
                ['admin@demo.defender360.com', 'demoadmin', 'Admin', 'Demo Tenant'],
                ['agent1@demo.defender360.com', 'agent1', 'Agent', 'Demo Tenant'],
                ['agent2@demo.defender360.com', 'agent2', 'Agent', 'Demo Tenant'],
                ['manager@demo.defender360.com', 'manager', 'Manager', 'Demo Tenant'],
                ['admin@testcorp.com', 'testadmin', 'Admin', 'Test Tenant'],
            ]
        );
        $this->command->warn('Default password for all users (except super admin): Password@2024!');
        $this->command->warn('Super Admin password: SuperAdmin@2024!');
    }
}