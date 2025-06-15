<?php

namespace App\Services;

use App\Models\NotificationTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TemplateService
{
    /**
     * Get template by code and tenant.
     */
    public function getTemplate(string $code, ?string $tenantId = null): ?NotificationTemplate
    {
        $cacheKey = "notification_template:{$code}:{$tenantId}";

        return Cache::remember($cacheKey, 3600, function () use ($code, $tenantId) {
            // First try to get tenant-specific template
            if ($tenantId) {
                $template = NotificationTemplate::where('tenant_id', $tenantId)
                    ->where('code', $code)
                    ->where('is_active', true)
                    ->first();

                if ($template) {
                    return $template;
                }
            }

            // Fall back to system template
            return NotificationTemplate::whereNull('tenant_id')
                ->where('code', $code)
                ->where('is_active', true)
                ->where('is_system', true)
                ->first();
        });
    }

    /**
     * Create or update template.
     */
    public function createOrUpdate(array $data): NotificationTemplate
    {
        $template = NotificationTemplate::updateOrCreate(
            [
                'code' => $data['code'],
                'tenant_id' => $data['tenant_id'] ?? null,
            ],
            $data
        );

        // Clear cache
        $this->clearTemplateCache($template->code, $template->tenant_id);

        return $template;
    }

    /**
     * Create default system templates.
     */
    public function createDefaultTemplates(): void
    {
        $templates = $this->getDefaultTemplateDefinitions();

        foreach ($templates as $templateData) {
            NotificationTemplate::firstOrCreate(
                [
                    'code' => $templateData['code'],
                    'tenant_id' => null,
                ],
                array_merge($templateData, [
                    'is_system' => true,
                    'is_active' => true,
                ])
            );
        }
    }

    /**
     * Get default template definitions.
     */
    protected function getDefaultTemplateDefinitions(): array
    {
        return [
            // Incident templates
            [
                'code' => 'incident_created',
                'name' => 'Incident Created',
                'description' => 'Notification when a new incident is created',
                'category' => NotificationTemplate::CATEGORY_INCIDENT,
                'channels' => ['email', 'in_app', 'slack'],
                'email_subject' => '[{{priority}}] New Incident #{{incident_number}}: {{title}}',
                'email_body_html' => $this->getIncidentCreatedEmailHtml(),
                'email_body_text' => $this->getIncidentCreatedEmailText(),
                'in_app_title' => 'New Incident #{{incident_number}}',
                'in_app_body' => '{{title}} - Priority: {{priority}}',
                'available_variables' => [
                    'incident_number', 'title', 'description', 'priority', 'status',
                    'assigned_to', 'created_by', 'created_at', 'category', 'impact'
                ],
            ],
            [
                'code' => 'incident_updated',
                'name' => 'Incident Updated',
                'description' => 'Notification when an incident is updated',
                'category' => NotificationTemplate::CATEGORY_INCIDENT,
                'channels' => ['email', 'in_app'],
                'email_subject' => 'Incident #{{incident_number}} Updated: {{title}}',
                'email_body_html' => $this->getIncidentUpdatedEmailHtml(),
                'email_body_text' => $this->getIncidentUpdatedEmailText(),
                'in_app_title' => 'Incident #{{incident_number}} Updated',
                'in_app_body' => '{{update_summary}}',
                'available_variables' => [
                    'incident_number', 'title', 'update_summary', 'updated_by',
                    'updated_at', 'changes_made', 'current_status'
                ],
            ],
            [
                'code' => 'incident_resolved',
                'name' => 'Incident Resolved',
                'description' => 'Notification when an incident is resolved',
                'category' => NotificationTemplate::CATEGORY_INCIDENT,
                'channels' => ['email', 'in_app', 'slack'],
                'email_subject' => 'Incident #{{incident_number}} Resolved: {{title}}',
                'email_body_html' => $this->getIncidentResolvedEmailHtml(),
                'email_body_text' => $this->getIncidentResolvedEmailText(),
                'in_app_title' => 'Incident #{{incident_number}} Resolved',
                'in_app_body' => '{{resolution_summary}}',
                'available_variables' => [
                    'incident_number', 'title', 'resolution_summary', 'resolved_by',
                    'resolved_at', 'resolution_time', 'root_cause'
                ],
            ],

            // SLA templates
            [
                'code' => 'sla_breach',
                'name' => 'SLA Breach',
                'description' => 'Notification when an SLA is breached',
                'category' => NotificationTemplate::CATEGORY_SLA,
                'channels' => ['email', 'sms', 'in_app', 'slack'],
                'email_subject' => '[URGENT] SLA Breach: {{sla_name}} for {{related_type}} #{{related_number}}',
                'email_body_html' => $this->getSlaBreachEmailHtml(),
                'email_body_text' => $this->getSlaBreachEmailText(),
                'sms_body' => 'SLA BREACH: {{sla_name}} for {{related_type}} #{{related_number}}. Breached by {{breach_time}}.',
                'in_app_title' => 'SLA Breach Alert',
                'in_app_body' => '{{sla_name}} breached for {{related_type}} #{{related_number}}',
                'available_variables' => [
                    'sla_name', 'related_type', 'related_number', 'breach_time',
                    'target_time', 'current_status', 'assigned_to'
                ],
            ],
            [
                'code' => 'sla_warning',
                'name' => 'SLA Warning',
                'description' => 'Notification when an SLA is about to breach',
                'category' => NotificationTemplate::CATEGORY_SLA,
                'channels' => ['email', 'in_app', 'slack'],
                'email_subject' => '[WARNING] SLA at Risk: {{sla_name}} for {{related_type}} #{{related_number}}',
                'email_body_html' => $this->getSlaWarningEmailHtml(),
                'email_body_text' => $this->getSlaWarningEmailText(),
                'in_app_title' => 'SLA Warning',
                'in_app_body' => '{{sla_name}} at risk for {{related_type}} #{{related_number}} - {{time_remaining}} remaining',
                'available_variables' => [
                    'sla_name', 'related_type', 'related_number', 'time_remaining',
                    'target_time', 'percentage_consumed', 'assigned_to'
                ],
            ],

            // Change management templates
            [
                'code' => 'change_requested',
                'name' => 'Change Requested',
                'description' => 'Notification when a change is requested',
                'category' => NotificationTemplate::CATEGORY_CHANGE,
                'channels' => ['email', 'in_app'],
                'email_subject' => 'New Change Request #{{change_number}}: {{title}}',
                'email_body_html' => $this->getChangeRequestedEmailHtml(),
                'email_body_text' => $this->getChangeRequestedEmailText(),
                'in_app_title' => 'New Change Request #{{change_number}}',
                'in_app_body' => '{{title}} - Type: {{change_type}}',
                'available_variables' => [
                    'change_number', 'title', 'description', 'change_type',
                    'risk_level', 'requested_by', 'scheduled_date', 'impact_analysis'
                ],
            ],
            [
                'code' => 'change_approved',
                'name' => 'Change Approved',
                'description' => 'Notification when a change is approved',
                'category' => NotificationTemplate::CATEGORY_CHANGE,
                'channels' => ['email', 'in_app', 'slack'],
                'email_subject' => 'Change Request #{{change_number}} Approved: {{title}}',
                'email_body_html' => $this->getChangeApprovedEmailHtml(),
                'email_body_text' => $this->getChangeApprovedEmailText(),
                'in_app_title' => 'Change #{{change_number}} Approved',
                'in_app_body' => 'Approved by {{approved_by}} - Scheduled: {{scheduled_date}}',
                'available_variables' => [
                    'change_number', 'title', 'approved_by', 'approved_at',
                    'scheduled_date', 'implementation_plan', 'approver_comments'
                ],
            ],

            // Problem management templates
            [
                'code' => 'problem_created',
                'name' => 'Problem Created',
                'description' => 'Notification when a problem is created',
                'category' => NotificationTemplate::CATEGORY_PROBLEM,
                'channels' => ['email', 'in_app'],
                'email_subject' => 'New Problem #{{problem_number}}: {{title}}',
                'email_body_html' => $this->getProblemCreatedEmailHtml(),
                'email_body_text' => $this->getProblemCreatedEmailText(),
                'in_app_title' => 'New Problem #{{problem_number}}',
                'in_app_body' => '{{title}} - Priority: {{priority}}',
                'available_variables' => [
                    'problem_number', 'title', 'description', 'priority',
                    'created_by', 'assigned_to', 'related_incidents', 'impact'
                ],
            ],

            // Knowledge management templates
            [
                'code' => 'knowledge_article_published',
                'name' => 'Knowledge Article Published',
                'description' => 'Notification when a knowledge article is published',
                'category' => NotificationTemplate::CATEGORY_KNOWLEDGE,
                'channels' => ['email', 'in_app'],
                'email_subject' => 'New Knowledge Article: {{title}}',
                'email_body_html' => $this->getKnowledgePublishedEmailHtml(),
                'email_body_text' => $this->getKnowledgePublishedEmailText(),
                'in_app_title' => 'New Knowledge Article',
                'in_app_body' => '{{title}} - Category: {{category}}',
                'available_variables' => [
                    'title', 'summary', 'category', 'tags', 'author',
                    'published_at', 'article_url'
                ],
            ],

            // Service request templates
            [
                'code' => 'service_request_created',
                'name' => 'Service Request Created',
                'description' => 'Notification when a service request is created',
                'category' => NotificationTemplate::CATEGORY_SERVICE_REQUEST,
                'channels' => ['email', 'in_app'],
                'email_subject' => 'Service Request #{{request_number}}: {{title}}',
                'email_body_html' => $this->getServiceRequestCreatedEmailHtml(),
                'email_body_text' => $this->getServiceRequestCreatedEmailText(),
                'in_app_title' => 'New Service Request #{{request_number}}',
                'in_app_body' => '{{title}} - Requested by: {{requested_by}}',
                'available_variables' => [
                    'request_number', 'title', 'description', 'service_type',
                    'requested_by', 'requested_at', 'expected_completion'
                ],
            ],
        ];
    }

    /**
     * Clear template cache.
     */
    protected function clearTemplateCache(string $code, ?string $tenantId = null): void
    {
        Cache::forget("notification_template:{$code}:{$tenantId}");
    }

    /**
     * Clone system templates for tenant.
     */
    public function cloneSystemTemplatesForTenant(string $tenantId): Collection
    {
        $systemTemplates = NotificationTemplate::whereNull('tenant_id')
            ->where('is_system', true)
            ->get();

        $clonedTemplates = collect();

        DB::transaction(function () use ($systemTemplates, $tenantId, &$clonedTemplates) {
            foreach ($systemTemplates as $template) {
                $cloned = $template->replicate();
                $cloned->tenant_id = $tenantId;
                $cloned->is_system = false;
                $cloned->save();

                $clonedTemplates->push($cloned);
            }
        });

        return $clonedTemplates;
    }

    // Email template content methods
    protected function getIncidentCreatedEmailHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; border-radius: 5px 5px 0 0; }
        .content { background-color: #ffffff; padding: 20px; border: 1px solid #dee2e6; }
        .priority-high { color: #dc3545; font-weight: bold; }
        .priority-critical { color: #721c24; font-weight: bold; }
        .button { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>New Incident Created</h2>
        </div>
        <div class="content">
            <p><strong>Incident Number:</strong> #{{incident_number}}</p>
            <p><strong>Title:</strong> {{title}}</p>
            <p><strong>Priority:</strong> <span class="priority-{{priority}}">{{priority}}</span></p>
            <p><strong>Description:</strong></p>
            <p>{{description}}</p>
            <p><strong>Assigned To:</strong> {{assigned_to}}</p>
            <p><strong>Created By:</strong> {{created_by}}</p>
            <p><strong>Created At:</strong> {{created_at}}</p>
            <br>
            <a href="{{app_url}}/incidents/{{incident_number}}" class="button">View Incident</a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function getIncidentCreatedEmailText(): string
    {
        return <<<TEXT
New Incident Created

Incident Number: #{{incident_number}}
Title: {{title}}
Priority: {{priority}}
Description: {{description}}
Assigned To: {{assigned_to}}
Created By: {{created_by}}
Created At: {{created_at}}

View incident: {{app_url}}/incidents/{{incident_number}}
TEXT;
    }

    protected function getIncidentUpdatedEmailHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; border-radius: 5px 5px 0 0; }
        .content { background-color: #ffffff; padding: 20px; border: 1px solid #dee2e6; }
        .changes { background-color: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .button { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Incident Updated</h2>
        </div>
        <div class="content">
            <p><strong>Incident Number:</strong> #{{incident_number}}</p>
            <p><strong>Title:</strong> {{title}}</p>
            <p><strong>Updated By:</strong> {{updated_by}}</p>
            <p><strong>Updated At:</strong> {{updated_at}}</p>
            <div class="changes">
                <p><strong>Update Summary:</strong></p>
                <p>{{update_summary}}</p>
                <p><strong>Changes Made:</strong></p>
                <p>{{changes_made}}</p>
            </div>
            <p><strong>Current Status:</strong> {{current_status}}</p>
            <br>
            <a href="{{app_url}}/incidents/{{incident_number}}" class="button">View Incident</a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function getIncidentUpdatedEmailText(): string
    {
        return <<<TEXT
Incident Updated

Incident Number: #{{incident_number}}
Title: {{title}}
Updated By: {{updated_by}}
Updated At: {{updated_at}}

Update Summary:
{{update_summary}}

Changes Made:
{{changes_made}}

Current Status: {{current_status}}

View incident: {{app_url}}/incidents/{{incident_number}}
TEXT;
    }

    protected function getIncidentResolvedEmailHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #28a745; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
        .content { background-color: #ffffff; padding: 20px; border: 1px solid #dee2e6; }
        .resolution { background-color: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #c3e6cb; }
        .button { display: inline-block; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Incident Resolved</h2>
        </div>
        <div class="content">
            <p><strong>Incident Number:</strong> #{{incident_number}}</p>
            <p><strong>Title:</strong> {{title}}</p>
            <div class="resolution">
                <p><strong>Resolution Summary:</strong></p>
                <p>{{resolution_summary}}</p>
                <p><strong>Root Cause:</strong> {{root_cause}}</p>
            </div>
            <p><strong>Resolved By:</strong> {{resolved_by}}</p>
            <p><strong>Resolved At:</strong> {{resolved_at}}</p>
            <p><strong>Resolution Time:</strong> {{resolution_time}}</p>
            <br>
            <a href="{{app_url}}/incidents/{{incident_number}}" class="button">View Incident</a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function getIncidentResolvedEmailText(): string
    {
        return <<<TEXT
Incident Resolved

Incident Number: #{{incident_number}}
Title: {{title}}

Resolution Summary:
{{resolution_summary}}

Root Cause: {{root_cause}}

Resolved By: {{resolved_by}}
Resolved At: {{resolved_at}}
Resolution Time: {{resolution_time}}

View incident: {{app_url}}/incidents/{{incident_number}}
TEXT;
    }

    protected function getSlaBreachEmailHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #dc3545; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
        .content { background-color: #ffffff; padding: 20px; border: 1px solid #dee2e6; }
        .alert { background-color: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #f5c6cb; }
        .button { display: inline-block; padding: 10px 20px; background-color: #dc3545; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>⚠️ SLA Breach Alert</h2>
        </div>
        <div class="content">
            <div class="alert">
                <p><strong>URGENT:</strong> An SLA has been breached and requires immediate attention.</p>
            </div>
            <p><strong>SLA Name:</strong> {{sla_name}}</p>
            <p><strong>Related:</strong> {{related_type}} #{{related_number}}</p>
            <p><strong>Target Time:</strong> {{target_time}}</p>
            <p><strong>Breached By:</strong> {{breach_time}}</p>
            <p><strong>Current Status:</strong> {{current_status}}</p>
            <p><strong>Assigned To:</strong> {{assigned_to}}</p>
            <br>
            <a href="{{app_url}}/{{related_type}}s/{{related_number}}" class="button">Take Action</a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function getSlaBreachEmailText(): string
    {
        return <<<TEXT
⚠️ SLA BREACH ALERT

URGENT: An SLA has been breached and requires immediate attention.

SLA Name: {{sla_name}}
Related: {{related_type}} #{{related_number}}
Target Time: {{target_time}}
Breached By: {{breach_time}}
Current Status: {{current_status}}
Assigned To: {{assigned_to}}

Take action: {{app_url}}/{{related_type}}s/{{related_number}}
TEXT;
    }

    protected function getSlaWarningEmailHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #ffc107; color: #212529; padding: 20px; border-radius: 5px 5px 0 0; }
        .content { background-color: #ffffff; padding: 20px; border: 1px solid #dee2e6; }
        .warning { background-color: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #ffeeba; }
        .button { display: inline-block; padding: 10px 20px; background-color: #ffc107; color: #212529; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>⚠️ SLA Warning</h2>
        </div>
        <div class="content">
            <div class="warning">
                <p><strong>WARNING:</strong> An SLA is at risk of breaching.</p>
            </div>
            <p><strong>SLA Name:</strong> {{sla_name}}</p>
            <p><strong>Related:</strong> {{related_type}} #{{related_number}}</p>
            <p><strong>Time Remaining:</strong> {{time_remaining}}</p>
            <p><strong>Target Time:</strong> {{target_time}}</p>
            <p><strong>Percentage Consumed:</strong> {{percentage_consumed}}%</p>
            <p><strong>Assigned To:</strong> {{assigned_to}}</p>
            <br>
            <a href="{{app_url}}/{{related_type}}s/{{related_number}}" class="button">Take Action</a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function getSlaWarningEmailText(): string
    {
        return <<<TEXT
⚠️ SLA WARNING

WARNING: An SLA is at risk of breaching.

SLA Name: {{sla_name}}
Related: {{related_type}} #{{related_number}}
Time Remaining: {{time_remaining}}
Target Time: {{target_time}}
Percentage Consumed: {{percentage_consumed}}%
Assigned To: {{assigned_to}}

Take action: {{app_url}}/{{related_type}}s/{{related_number}}
TEXT;
    }

    protected function getChangeRequestedEmailHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #17a2b8; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
        .content { background-color: #ffffff; padding: 20px; border: 1px solid #dee2e6; }
        .details { background-color: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .risk-low { color: #28a745; }
        .risk-medium { color: #ffc107; }
        .risk-high { color: #dc3545; }
        .button { display: inline-block; padding: 10px 20px; background-color: #17a2b8; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>New Change Request</h2>
        </div>
        <div class="content">
            <p><strong>Change Number:</strong> #{{change_number}}</p>
            <p><strong>Title:</strong> {{title}}</p>
            <p><strong>Type:</strong> {{change_type}}</p>
            <p><strong>Risk Level:</strong> <span class="risk-{{risk_level}}">{{risk_level}}</span></p>
            <div class="details">
                <p><strong>Description:</strong></p>
                <p>{{description}}</p>
                <p><strong>Impact Analysis:</strong></p>
                <p>{{impact_analysis}}</p>
            </div>
            <p><strong>Requested By:</strong> {{requested_by}}</p>
            <p><strong>Scheduled Date:</strong> {{scheduled_date}}</p>
            <br>
            <a href="{{app_url}}/changes/{{change_number}}" class="button">Review Change</a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function getChangeRequestedEmailText(): string
    {
        return <<<TEXT
New Change Request

Change Number: #{{change_number}}
Title: {{title}}
Type: {{change_type}}
Risk Level: {{risk_level}}

Description:
{{description}}

Impact Analysis:
{{impact_analysis}}

Requested By: {{requested_by}}
Scheduled Date: {{scheduled_date}}

Review change: {{app_url}}/changes/{{change_number}}
TEXT;
    }

    protected function getChangeApprovedEmailHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #28a745; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
        .content { background-color: #ffffff; padding: 20px; border: 1px solid #dee2e6; }
        .approval { background-color: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #c3e6cb; }
        .button { display: inline-block; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>✅ Change Request Approved</h2>
        </div>
        <div class="content">
            <p><strong>Change Number:</strong> #{{change_number}}</p>
            <p><strong>Title:</strong> {{title}}</p>
            <div class="approval">
                <p><strong>Approved By:</strong> {{approved_by}}</p>
                <p><strong>Approved At:</strong> {{approved_at}}</p>
                <p><strong>Comments:</strong> {{approver_comments}}</p>
            </div>
            <p><strong>Scheduled Date:</strong> {{scheduled_date}}</p>
            <p><strong>Implementation Plan:</strong></p>
            <p>{{implementation_plan}}</p>
            <br>
            <a href="{{app_url}}/changes/{{change_number}}" class="button">View Change</a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function getChangeApprovedEmailText(): string
    {
        return <<<TEXT
✅ Change Request Approved

Change Number: #{{change_number}}
Title: {{title}}

Approved By: {{approved_by}}
Approved At: {{approved_at}}
Comments: {{approver_comments}}

Scheduled Date: {{scheduled_date}}

Implementation Plan:
{{implementation_plan}}

View change: {{app_url}}/changes/{{change_number}}
TEXT;
    }

    protected function getProblemCreatedEmailHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #6f42c1; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
        .content { background-color: #ffffff; padding: 20px; border: 1px solid #dee2e6; }
        .details { background-color: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .button { display: inline-block; padding: 10px 20px; background-color: #6f42c1; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>New Problem Record</h2>
        </div>
        <div class="content">
            <p><strong>Problem Number:</strong> #{{problem_number}}</p>
            <p><strong>Title:</strong> {{title}}</p>
            <p><strong>Priority:</strong> {{priority}}</p>
            <div class="details">
                <p><strong>Description:</strong></p>
                <p>{{description}}</p>
                <p><strong>Impact:</strong> {{impact}}</p>
                <p><strong>Related Incidents:</strong> {{related_incidents}}</p>
            </div>
            <p><strong>Created By:</strong> {{created_by}}</p>
            <p><strong>Assigned To:</strong> {{assigned_to}}</p>
            <br>
            <a href="{{app_url}}/problems/{{problem_number}}" class="button">View Problem</a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function getProblemCreatedEmailText(): string
    {
        return <<<TEXT
New Problem Record

Problem Number: #{{problem_number}}
Title: {{title}}
Priority: {{priority}}

Description:
{{description}}

Impact: {{impact}}
Related Incidents: {{related_incidents}}

Created By: {{created_by}}
Assigned To: {{assigned_to}}

View problem: {{app_url}}/problems/{{problem_number}}
TEXT;
    }

    protected function getKnowledgePublishedEmailHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #20c997; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
        .content { background-color: #ffffff; padding: 20px; border: 1px solid #dee2e6; }
        .article { background-color: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .tags { margin: 10px 0; }
        .tag { display: inline-block; background-color: #e9ecef; padding: 5px 10px; margin: 2px; border-radius: 3px; font-size: 12px; }
        .button { display: inline-block; padding: 10px 20px; background-color: #20c997; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>📚 New Knowledge Article Published</h2>
        </div>
        <div class="content">
            <h3>{{title}}</h3>
            <p><strong>Category:</strong> {{category}}</p>
            <div class="article">
                <p><strong>Summary:</strong></p>
                <p>{{summary}}</p>
            </div>
            <div class="tags">
                <strong>Tags:</strong> {{tags}}
            </div>
            <p><strong>Author:</strong> {{author}}</p>
            <p><strong>Published:</strong> {{published_at}}</p>
            <br>
            <a href="{{article_url}}" class="button">Read Article</a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function getKnowledgePublishedEmailText(): string
    {
        return <<<TEXT
📚 New Knowledge Article Published

Title: {{title}}
Category: {{category}}

Summary:
{{summary}}

Tags: {{tags}}
Author: {{author}}
Published: {{published_at}}

Read article: {{article_url}}
TEXT;
    }

    protected function getServiceRequestCreatedEmailHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #007bff; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
        .content { background-color: #ffffff; padding: 20px; border: 1px solid #dee2e6; }
        .request { background-color: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .button { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>New Service Request</h2>
        </div>
        <div class="content">
            <p><strong>Request Number:</strong> #{{request_number}}</p>
            <p><strong>Title:</strong> {{title}}</p>
            <p><strong>Service Type:</strong> {{service_type}}</p>
            <div class="request">
                <p><strong>Description:</strong></p>
                <p>{{description}}</p>
            </div>
            <p><strong>Requested By:</strong> {{requested_by}}</p>
            <p><strong>Requested At:</strong> {{requested_at}}</p>
            <p><strong>Expected Completion:</strong> {{expected_completion}}</p>
            <br>
            <a href="{{app_url}}/service-requests/{{request_number}}" class="button">View Request</a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function getServiceRequestCreatedEmailText(): string
    {
        return <<<TEXT
New Service Request

Request Number: #{{request_number}}
Title: {{title}}
Service Type: {{service_type}}

Description:
{{description}}

Requested By: {{requested_by}}
Requested At: {{requested_at}}
Expected Completion: {{expected_completion}}

View request: {{app_url}}/service-requests/{{request_number}}
TEXT;
    }
}