#!/usr/bin/env php
<?php

/**
 * Database Seeding Script for ITSM Platform
 * 
 * This script populates the database with realistic test data for development and testing.
 * It creates a complete dataset including tenants, users, incidents, changes, problems,
 * service requests, knowledge articles, and related data.
 */

require_once __DIR__ . '/../backend/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$faker = Faker::create();

echo "Starting database seeding...\n";

DB::beginTransaction();

try {
    // Create Tenants
    echo "Creating tenants...\n";
    $tenants = [];
    $tenantNames = [
        'Acme Corporation',
        'Global Tech Solutions',
        'Innovate Industries',
        'Digital Dynamics',
        'Future Systems Inc'
    ];

    foreach ($tenantNames as $name) {
        $tenant = DB::table('tenants')->insertGetId([
            'name' => $name,
            'slug' => Str::slug($name),
            'domain' => Str::slug($name) . '.itsm.local',
            'settings' => json_encode([
                'timezone' => 'UTC',
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i:s',
                'working_hours' => ['start' => '09:00', 'end' => '18:00'],
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']
            ]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $tenants[] = $tenant;
    }

    // Create Users for each tenant
    echo "Creating users...\n";
    $users = [];
    $roles = ['admin', 'manager', 'technician', 'user'];
    
    foreach ($tenants as $tenantId) {
        // Create admin user
        $adminId = DB::table('users')->insertGetId([
            'name' => 'Admin User',
            'email' => "admin@tenant{$tenantId}.com",
            'password' => Hash::make('password123'),
            'tenant_id' => $tenantId,
            'role' => 'admin',
            'department' => 'IT',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $users[$tenantId]['admin'] = $adminId;

        // Create other users
        for ($i = 0; $i < 20; $i++) {
            $role = $faker->randomElement(['manager', 'technician', 'user']);
            $userId = DB::table('users')->insertGetId([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => Hash::make('password123'),
                'tenant_id' => $tenantId,
                'role' => $role,
                'department' => $faker->randomElement(['IT', 'HR', 'Finance', 'Sales', 'Operations']),
                'phone' => $faker->phoneNumber,
                'is_active' => $faker->boolean(90),
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => now()
            ]);
            $users[$tenantId][] = $userId;
        }
    }

    // Create Categories
    echo "Creating categories...\n";
    $categories = [
        'Hardware' => ['Desktop', 'Laptop', 'Printer', 'Server', 'Network Device'],
        'Software' => ['Operating System', 'Office Suite', 'Custom Application', 'Database', 'Security'],
        'Network' => ['Connectivity', 'VPN', 'Firewall', 'WiFi', 'LAN'],
        'Access' => ['Account', 'Password', 'Permissions', 'Single Sign-On', 'Multi-Factor Auth'],
        'Service Request' => ['New Equipment', 'Software Installation', 'Access Request', 'Training', 'Other']
    ];

    // Create Incidents
    echo "Creating incidents...\n";
    $incidentTitles = [
        'Email server not responding',
        'Cannot access shared drive',
        'Printer not working',
        'Software installation failed',
        'Network connection timeout',
        'Database performance issues',
        'Login problems after password reset',
        'Application crashes frequently',
        'File permissions error',
        'VPN connection dropped',
        'Website loading slowly',
        'Backup job failed',
        'Security alert triggered',
        'Hardware failure detected',
        'System update required'
    ];

    foreach ($tenants as $tenantId) {
        $tenantUsers = $users[$tenantId];
        
        for ($i = 0; $i < 200; $i++) {
            $category = $faker->randomElement(array_keys($categories));
            $subcategory = $faker->randomElement($categories[$category]);
            $createdAt = $faker->dateTimeBetween('-3 months', 'now');
            $status = $faker->randomElement(['new', 'in_progress', 'resolved', 'closed']);
            
            $incidentData = [
                'number' => 'INC-' . date('Y') . '-' . str_pad($i + 1, 6, '0', STR_PAD_LEFT),
                'title' => $faker->randomElement($incidentTitles) . ' - ' . $faker->word,
                'description' => $faker->paragraphs(3, true),
                'priority' => $faker->randomElement(['low', 'medium', 'high', 'critical']),
                'status' => $status,
                'impact' => $faker->randomElement(['low', 'medium', 'high']),
                'urgency' => $faker->randomElement(['low', 'medium', 'high']),
                'category' => $category,
                'subcategory' => $subcategory,
                'tenant_id' => $tenantId,
                'reporter_id' => $faker->randomElement($tenantUsers),
                'assigned_to' => $faker->boolean(80) ? $faker->randomElement($tenantUsers) : null,
                'assigned_group' => $faker->randomElement(['Level 1 Support', 'Level 2 Support', 'Infrastructure', 'Applications']),
                'created_at' => $createdAt,
                'updated_at' => $faker->dateTimeBetween($createdAt, 'now')
            ];

            // Add resolution data for resolved/closed incidents
            if (in_array($status, ['resolved', 'closed'])) {
                $resolvedAt = $faker->dateTimeBetween($createdAt, 'now');
                $incidentData['resolution'] = $faker->paragraph;
                $incidentData['resolved_at'] = $resolvedAt;
                $incidentData['resolved_by'] = $faker->randomElement($tenantUsers);
                
                if ($status === 'closed') {
                    $incidentData['closed_at'] = $faker->dateTimeBetween($resolvedAt, 'now');
                }
            }

            // Calculate SLA targets
            $priority = $incidentData['priority'];
            $slaResponseMinutes = ['low' => 480, 'medium' => 240, 'high' => 60, 'critical' => 30];
            $slaResolutionMinutes = ['low' => 2880, 'medium' => 1440, 'high' => 480, 'critical' => 240];
            
            $incidentData['sla_response_target'] = date('Y-m-d H:i:s', strtotime($createdAt->format('Y-m-d H:i:s')) + ($slaResponseMinutes[$priority] * 60));
            $incidentData['sla_resolution_target'] = date('Y-m-d H:i:s', strtotime($createdAt->format('Y-m-d H:i:s')) + ($slaResolutionMinutes[$priority] * 60));
            
            // Randomly breach some SLAs
            $incidentData['sla_breached'] = $faker->boolean(15);

            $incidentId = DB::table('incidents')->insertGetId($incidentData);

            // Add comments
            $commentCount = $faker->numberBetween(0, 10);
            for ($j = 0; $j < $commentCount; $j++) {
                DB::table('incident_comments')->insert([
                    'incident_id' => $incidentId,
                    'user_id' => $faker->randomElement($tenantUsers),
                    'content' => $faker->paragraph,
                    'is_private' => $faker->boolean(20),
                    'created_at' => $faker->dateTimeBetween($createdAt, 'now'),
                    'updated_at' => now()
                ]);
            }

            // Add attachments
            if ($faker->boolean(30)) {
                $attachmentCount = $faker->numberBetween(1, 5);
                for ($j = 0; $j < $attachmentCount; $j++) {
                    $extensions = ['pdf', 'docx', 'xlsx', 'png', 'jpg', 'txt', 'log'];
                    $extension = $faker->randomElement($extensions);
                    $mimeTypes = [
                        'pdf' => 'application/pdf',
                        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'png' => 'image/png',
                        'jpg' => 'image/jpeg',
                        'txt' => 'text/plain',
                        'log' => 'text/plain'
                    ];
                    
                    DB::table('incident_attachments')->insert([
                        'incident_id' => $incidentId,
                        'filename' => $faker->word . '.' . $extension,
                        'file_path' => 'attachments/' . $tenantId . '/' . $incidentId . '/' . Str::random(40) . '.' . $extension,
                        'file_size' => $faker->numberBetween(1024, 10485760), // 1KB to 10MB
                        'mime_type' => $mimeTypes[$extension],
                        'uploaded_by' => $faker->randomElement($tenantUsers),
                        'created_at' => $faker->dateTimeBetween($createdAt, 'now'),
                        'updated_at' => now()
                    ]);
                }
            }
        }
    }

    // Create Configuration Items
    echo "Creating configuration items...\n";
    $ciTypes = [
        'Server' => ['os' => ['Windows Server', 'Linux', 'VMware'], 'status' => 'operational'],
        'Workstation' => ['os' => ['Windows 10', 'Windows 11', 'macOS', 'Ubuntu'], 'status' => 'operational'],
        'Network Device' => ['type' => ['Router', 'Switch', 'Firewall', 'Access Point'], 'status' => 'operational'],
        'Software' => ['type' => ['Operating System', 'Application', 'Database', 'Middleware'], 'status' => 'active'],
        'Service' => ['type' => ['Web Service', 'Database Service', 'API', 'Microservice'], 'status' => 'running']
    ];

    foreach ($tenants as $tenantId) {
        foreach ($ciTypes as $type => $attributes) {
            $count = $faker->numberBetween(10, 50);
            for ($i = 0; $i < $count; $i++) {
                $ciData = [
                    'name' => $type . '-' . strtoupper($faker->bothify('??###')),
                    'ci_type' => $type,
                    'status' => $faker->randomElement(['operational', 'maintenance', 'retired', 'failed']),
                    'tenant_id' => $tenantId,
                    'serial_number' => strtoupper($faker->bothify('??#########')),
                    'asset_tag' => 'ASSET-' . str_pad($faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
                    'location' => $faker->randomElement(['Data Center 1', 'Data Center 2', 'Office Building A', 'Office Building B']),
                    'attributes' => json_encode(array_merge($attributes, [
                        'manufacturer' => $faker->company,
                        'model' => $faker->word . ' ' . $faker->numerify('####'),
                        'purchase_date' => $faker->dateTimeBetween('-5 years', '-6 months')->format('Y-m-d'),
                        'warranty_expires' => $faker->dateTimeBetween('now', '+3 years')->format('Y-m-d')
                    ])),
                    'created_at' => $faker->dateTimeBetween('-2 years', 'now'),
                    'updated_at' => now()
                ];
                
                DB::table('configuration_items')->insert($ciData);
            }
        }
    }

    // Create Problems
    echo "Creating problems...\n";
    foreach ($tenants as $tenantId) {
        for ($i = 0; $i < 30; $i++) {
            $createdAt = $faker->dateTimeBetween('-6 months', 'now');
            $status = $faker->randomElement(['open', 'investigating', 'known_error', 'resolved', 'closed']);
            
            $problemData = [
                'number' => 'PRB-' . date('Y') . '-' . str_pad($i + 1, 6, '0', STR_PAD_LEFT),
                'title' => 'Recurring: ' . $faker->randomElement($incidentTitles),
                'description' => $faker->paragraphs(4, true),
                'priority' => $faker->randomElement(['low', 'medium', 'high', 'critical']),
                'status' => $status,
                'impact' => $faker->randomElement(['low', 'medium', 'high']),
                'urgency' => $faker->randomElement(['low', 'medium', 'high']),
                'tenant_id' => $tenantId,
                'reported_by' => $faker->randomElement($users[$tenantId]),
                'assigned_to' => $faker->randomElement($users[$tenantId]),
                'root_cause' => $status !== 'open' ? $faker->paragraph : null,
                'workaround' => $faker->boolean(70) ? $faker->paragraph : null,
                'permanent_fix' => in_array($status, ['resolved', 'closed']) ? $faker->paragraph : null,
                'created_at' => $createdAt,
                'updated_at' => $faker->dateTimeBetween($createdAt, 'now')
            ];
            
            if (in_array($status, ['resolved', 'closed'])) {
                $problemData['resolved_at'] = $faker->dateTimeBetween($createdAt, 'now');
                if ($status === 'closed') {
                    $problemData['closed_at'] = $faker->dateTimeBetween($problemData['resolved_at'], 'now');
                }
            }
            
            DB::table('problems')->insert($problemData);
        }
    }

    // Create Changes
    echo "Creating changes...\n";
    $changeTypes = ['standard', 'normal', 'emergency'];
    $changeStatuses = ['draft', 'submitted', 'approved', 'scheduled', 'implementing', 'completed', 'cancelled'];
    
    foreach ($tenants as $tenantId) {
        for ($i = 0; $i < 50; $i++) {
            $createdAt = $faker->dateTimeBetween('-3 months', '+1 month');
            $type = $faker->randomElement($changeTypes);
            $status = $faker->randomElement($changeStatuses);
            
            $changeData = [
                'number' => 'CHG-' . date('Y') . '-' . str_pad($i + 1, 6, '0', STR_PAD_LEFT),
                'title' => $faker->sentence(6),
                'description' => $faker->paragraphs(3, true),
                'type' => $type,
                'status' => $status,
                'priority' => $faker->randomElement(['low', 'medium', 'high', 'critical']),
                'risk' => $faker->randomElement(['low', 'medium', 'high']),
                'impact' => $faker->randomElement(['low', 'medium', 'high']),
                'tenant_id' => $tenantId,
                'requested_by' => $faker->randomElement($users[$tenantId]),
                'assigned_to' => $faker->randomElement($users[$tenantId]),
                'implementation_plan' => $faker->paragraphs(2, true),
                'backout_plan' => $faker->paragraph,
                'test_plan' => $faker->paragraph,
                'scheduled_start' => $faker->dateTimeBetween('now', '+2 months'),
                'scheduled_end' => $faker->dateTimeBetween('+2 hours', '+2 months'),
                'created_at' => $createdAt,
                'updated_at' => now()
            ];
            
            if (in_array($status, ['completed', 'cancelled'])) {
                $changeData['actual_start'] = $changeData['scheduled_start'];
                $changeData['actual_end'] = $faker->dateTimeBetween($changeData['scheduled_start'], '+8 hours');
                $changeData['completion_notes'] = $faker->paragraph;
            }
            
            DB::table('changes')->insert($changeData);
        }
    }

    // Create Knowledge Articles
    echo "Creating knowledge articles...\n";
    $articleCategories = [
        'How-to Guides',
        'Troubleshooting',
        'FAQs',
        'Best Practices',
        'Security',
        'Updates & Patches'
    ];
    
    foreach ($tenants as $tenantId) {
        for ($i = 0; $i < 100; $i++) {
            $createdAt = $faker->dateTimeBetween('-1 year', 'now');
            
            $articleData = [
                'title' => $faker->sentence(8),
                'content' => $faker->paragraphs(6, true),
                'category' => $faker->randomElement($articleCategories),
                'tags' => json_encode($faker->words(5)),
                'status' => $faker->randomElement(['draft', 'published', 'archived']),
                'tenant_id' => $tenantId,
                'author_id' => $faker->randomElement($users[$tenantId]),
                'views' => $faker->numberBetween(0, 1000),
                'helpful_count' => $faker->numberBetween(0, 100),
                'not_helpful_count' => $faker->numberBetween(0, 20),
                'created_at' => $createdAt,
                'updated_at' => $faker->dateTimeBetween($createdAt, 'now'),
                'published_at' => $faker->boolean(80) ? $faker->dateTimeBetween($createdAt, 'now') : null
            ];
            
            DB::table('knowledge_articles')->insert($articleData);
        }
    }

    // Create Service Requests
    echo "Creating service requests...\n";
    $serviceTypes = [
        'New Hardware Request',
        'Software Installation',
        'Access Request',
        'Account Creation',
        'Equipment Replacement',
        'Training Request',
        'Office Move',
        'New Project Setup'
    ];
    
    foreach ($tenants as $tenantId) {
        for ($i = 0; $i < 80; $i++) {
            $createdAt = $faker->dateTimeBetween('-2 months', 'now');
            $status = $faker->randomElement(['submitted', 'approved', 'in_progress', 'completed', 'cancelled']);
            
            $serviceData = [
                'number' => 'SR-' . date('Y') . '-' . str_pad($i + 1, 6, '0', STR_PAD_LEFT),
                'title' => $faker->randomElement($serviceTypes),
                'description' => $faker->paragraphs(2, true),
                'type' => $faker->randomElement(['hardware', 'software', 'access', 'other']),
                'status' => $status,
                'priority' => $faker->randomElement(['low', 'medium', 'high']),
                'tenant_id' => $tenantId,
                'requested_by' => $faker->randomElement($users[$tenantId]),
                'assigned_to' => $faker->boolean(70) ? $faker->randomElement($users[$tenantId]) : null,
                'approved_by' => in_array($status, ['approved', 'in_progress', 'completed']) ? $users[$tenantId]['admin'] : null,
                'approved_at' => in_array($status, ['approved', 'in_progress', 'completed']) ? $faker->dateTimeBetween($createdAt, 'now') : null,
                'completed_at' => $status === 'completed' ? $faker->dateTimeBetween($createdAt, 'now') : null,
                'created_at' => $createdAt,
                'updated_at' => now()
            ];
            
            DB::table('service_requests')->insert($serviceData);
        }
    }

    // Create SLA configurations
    echo "Creating SLA configurations...\n";
    foreach ($tenants as $tenantId) {
        $priorities = ['low', 'medium', 'high', 'critical'];
        foreach ($priorities as $priority) {
            DB::table('slas')->insert([
                'name' => ucfirst($priority) . ' Priority SLA',
                'priority' => $priority,
                'response_time' => ['low' => 480, 'medium' => 240, 'high' => 60, 'critical' => 30][$priority],
                'resolution_time' => ['low' => 2880, 'medium' => 1440, 'high' => 480, 'critical' => 240][$priority],
                'escalation_time' => ['low' => 1440, 'medium' => 720, 'high' => 240, 'critical' => 60][$priority],
                'business_hours_only' => $priority !== 'critical',
                'tenant_id' => $tenantId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    // Create Notification Templates
    echo "Creating notification templates...\n";
    $templates = [
        ['name' => 'incident.created', 'subject' => 'New Incident: {{ incident.title }}', 'channels' => ['email', 'in_app']],
        ['name' => 'incident.assigned', 'subject' => 'Incident Assigned: {{ incident.number }}', 'channels' => ['email', 'in_app']],
        ['name' => 'incident.resolved', 'subject' => 'Incident Resolved: {{ incident.number }}', 'channels' => ['email', 'in_app']],
        ['name' => 'sla.breach.warning', 'subject' => 'SLA Warning: {{ incident.number }}', 'channels' => ['email', 'in_app', 'sms']],
        ['name' => 'change.approval.required', 'subject' => 'Change Approval Required: {{ change.number }}', 'channels' => ['email', 'in_app']],
        ['name' => 'knowledge.article.published', 'subject' => 'New Knowledge Article: {{ article.title }}', 'channels' => ['email']]
    ];
    
    foreach ($templates as $template) {
        DB::table('notification_templates')->insert([
            'name' => $template['name'],
            'subject' => $template['subject'],
            'body' => $faker->paragraph,
            'channels' => json_encode($template['channels']),
            'variables' => json_encode(['incident', 'user', 'change', 'article']),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    DB::commit();
    
    echo "\nDatabase seeding completed successfully!\n";
    echo "Created:\n";
    echo "- " . count($tenants) . " tenants\n";
    echo "- " . count($users) * 21 . " users (approx)\n";
    echo "- 1000 incidents (200 per tenant)\n";
    echo "- 150 problems (30 per tenant)\n";
    echo "- 250 changes (50 per tenant)\n";
    echo "- 500 knowledge articles (100 per tenant)\n";
    echo "- 400 service requests (80 per tenant)\n";
    echo "- Configuration items, SLAs, and notification templates\n";

} catch (Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    echo "Rolling back changes...\n";
    exit(1);
}