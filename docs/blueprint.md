# 🚀 Blueprint Executável: ITSM Enterprise Platform v4.0
## Documento de Implementação com Integrações Enterprise (Datto RMM & Bitdefender)

**Versão**: 4.0 - Enterprise Integrations Edition  
**Data**: Junho 2025  
**Filosofia**: Zero ambiguidade, 100% executável, integrações enterprise nativas  

---

## 📋 Quick Start Guide

```bash
# Clone e execute em 3 comandos
git clone https://github.com/your-org/itsm-platform
cd itsm-platform
./scripts/quickstart.sh --with-integrations
```

---

## 1. ⚙️ Project DNA - Configuration as Code (Enterprise Edition)

### 1.1 Master Configuration with Integrations
```yaml
# Arquivo: /project.config.yml
# SINGLE SOURCE OF TRUTH - Incluindo integrações enterprise

project:
  name: itsm-enterprise-platform
  version: 4.0.0
  description: "Enterprise ITSM Platform with AI-powered ITIL processes and RMM/Security integrations"
  
  # Deployment targets
  environments:
    development:
      url: http://localhost:8080
      debug: true
      mock_integrations: true # Use mock APIs in dev
    staging:
      url: https://staging.itsm-platform.app
      debug: false
      mock_integrations: false
    production:
      url: https://itsm-platform.app
      debug: false
      mock_integrations: false
      
  # Feature flags
  features:
    ai_automation: true
    advanced_analytics: true
    mobile_app: false
    datto_rmm_integration: true
    bitdefender_integration: true
    auto_incident_creation: true
    asset_synchronization: true

# Core Architecture (mantido)
architecture:
  # ... (configurações existentes mantidas) ...

# NOVO: Enterprise Integrations Configuration
integrations:
  # Datto RMM Configuration
  datto_rmm:
    enabled: true
    api_version: "v2"
    base_url: "https://api.datto.com/v2"
    auth_type: "bearer_token"
    
    # Secrets reference (actual values in environment)
    secrets:
      api_key: "DATTO_RMM_API_KEY"
      api_secret: "DATTO_RMM_API_SECRET"
      
    # Endpoints configuration
    endpoints:
      auth: "/auth"
      sites: "/sites"
      devices: "/devices"
      alerts: "/alerts"
      patches: "/patches"
      
    # Sync configuration
    sync:
      # Asset synchronization
      assets:
        enabled: true
        schedule: "0 */6 * * *" # Every 6 hours
        batch_size: 100
        fields_mapping:
          hostname: "hostname"
          ip_address: "intIpAddress"
          mac_address: "intMacAddress"
          operating_system: "operatingSystem"
          last_seen: "lastSeen"
          
      # Alert synchronization
      alerts:
        enabled: true
        schedule: "*/5 * * * *" # Every 5 minutes
        auto_create_incident: true
        priority_mapping:
          Critical: 1
          High: 2
          Medium: 3
          Low: 4
        category_mapping:
          "Disk Space": "hardware.storage"
          "CPU Usage": "hardware.cpu"
          "Memory": "hardware.memory"
          "Service": "software.service"
          
      # Patch management sync
      patches:
        enabled: true
        schedule: "0 2 * * *" # Daily at 2 AM
        auto_create_change_request: false
        
    # Rate limiting
    rate_limit:
      requests_per_minute: 60
      burst: 100
      
    # Retry configuration
    retry:
      max_attempts: 3
      backoff_type: "exponential"
      initial_delay: 1000 # ms
      
  # Bitdefender GravityZone Configuration
  bitdefender_gravityzone:
    enabled: true
    api_version: "3.0"
    base_url: "https://cloud.gravityzone.bitdefender.com/api/v1.0/jsonrpc"
    auth_type: "api_key"
    
    # Secrets reference
    secrets:
      api_key: "BITDEFENDER_API_KEY"
      access_url: "BITDEFENDER_ACCESS_URL" # Custom URL for some deployments
      
    # API Methods configuration
    methods:
      # Network API
      getEndpointsList: "network.getEndpointsList"
      getEndpointDetails: "network.getEndpointDetails"
      getManagedEndpointsList: "network.getManagedEndpointsList"
      
      # Incidents API
      getIncidentsList: "incidents.getIncidentsList"
      getIncidentDetails: "incidents.getIncidentDetails"
      setIncidentStatus: "incidents.setIncidentStatus"
      
      # Threats API
      getThreatsStatistics: "threats.getThreatsStatistics"
      getBlockedApplications: "threats.getBlockedApplications"
      
      # Reports API
      getReportsList: "reports.getReportsList"
      
    # Sync configuration
    sync:
      # Endpoint synchronization
      endpoints:
        enabled: true
        schedule: "0 */4 * * *" # Every 4 hours
        batch_size: 200
        sync_unmanaged: false
        fields_mapping:
          name: "name"
          ip_address: "details.ip"
          mac_address: "details.mac"
          operating_system: "details.operatingSystem"
          agent_version: "details.agent.version"
          last_seen: "details.lastSeen"
          is_managed: "isManaged"
          
      # Security incidents sync
      incidents:
        enabled: true
        schedule: "*/15 * * * *" # Every 15 minutes
        auto_create_incident: true
        severity_to_priority_mapping:
          critical: 1
          high: 2
          medium: 3
          low: 4
        incident_type_mapping:
          malware: "security.malware"
          exploit: "security.exploit"
          phishing: "security.phishing"
          firewall: "security.firewall"
          
      # Threat intelligence sync
      threats:
        enabled: true
        schedule: "0 * * * *" # Every hour
        create_knowledge_articles: true
        
    # Additional settings
    settings:
      page_size: 100
      timeout_seconds: 60
      ssl_verify: true
      
  # Common integration settings
  common:
    # Webhook receiver configuration
    webhooks:
      enabled: true
      path: "/api/webhooks"
      verify_signature: true
      allowed_ips: [] # Empty = allow all
      
    # Integration health monitoring
    health_check:
      enabled: true
      interval: 300 # 5 minutes
      timeout: 30
      alert_on_failure: true
      
    # Data retention
    retention:
      raw_sync_data: 30 # days
      processed_data: 90 # days
      audit_logs: 365 # days

# ITIL Implementation (mantido com adições)
itil:
  version: v4
  maturity_target: level_3
  
  # Processos ITIL com integrações
  processes:
    incident_management:
      priority: critical
      auto_creation_sources:
        - datto_rmm_alerts
        - bitdefender_incidents
        - email_integration
      
    configuration_management:
      auto_discovery: true
      discovery_sources:
        - datto_rmm_assets
        - bitdefender_endpoints
        - network_scan
      ci_reconciliation:
        enabled: true
        match_by: ["hostname", "mac_address", "serial_number"]
        
    change_management:
      patch_management_integration: true
      automated_patch_approval:
        enabled: false # Requires manual approval
        auto_approve_criticality: ["low"] # Only low criticality
```

### 1.2 Environment Variables for Integrations
```bash
# Arquivo: /.env.example (additions)
# Integration credentials (never commit actual values)

# Datto RMM
DATTO_RMM_API_KEY=
DATTO_RMM_API_SECRET=
DATTO_RMM_REGION=us # us, eu, au

# Bitdefender GravityZone
BITDEFENDER_API_KEY=
BITDEFENDER_ACCESS_URL=https://cloud.gravityzone.bitdefender.com
BITDEFENDER_COMPANY_ID= # For MSP deployments

# Integration Features
FEATURE_AUTO_INCIDENT_CREATION=true
FEATURE_ASSET_AUTO_DISCOVERY=true
FEATURE_SECURITY_AUTOMATION=true
```

---

## 2. 🗃️ Database Schema for Enterprise Integrations

### 2.1 Integration Schema Design
```sql
/*
 * Arquivo: /database/schema/003_enterprise_integrations.sql
 * Schema for Datto RMM and Bitdefender integrations
 */

-- Create dedicated schema for integrations
CREATE SCHEMA IF NOT EXISTS integrations;

-- Integration sync status tracking
CREATE TABLE integrations.sync_status (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
    integration_name VARCHAR(50) NOT NULL,
    sync_type VARCHAR(50) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    started_at TIMESTAMPTZ,
    completed_at TIMESTAMPTZ,
    records_processed INT DEFAULT 0,
    records_created INT DEFAULT 0,
    records_updated INT DEFAULT 0,
    records_failed INT DEFAULT 0,
    error_message TEXT,
    metadata JSONB DEFAULT '{}',
    
    CONSTRAINT chk_integration_name CHECK (
        integration_name IN ('datto_rmm', 'bitdefender_gravityzone')
    ),
    CONSTRAINT chk_sync_type CHECK (
        sync_type IN ('assets', 'alerts', 'endpoints', 'incidents', 'patches', 'threats')
    ),
    CONSTRAINT chk_status CHECK (
        status IN ('pending', 'running', 'completed', 'failed', 'cancelled')
    )
);

CREATE INDEX idx_sync_status_tenant_integration ON integrations.sync_status(tenant_id, integration_name);
CREATE INDEX idx_sync_status_created ON integrations.sync_status(created_at DESC);

-- DATTO RMM TABLES
-- Sites/Accounts from Datto
CREATE TABLE integrations.datto_sites (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
    site_uid VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    notes TEXT,
    raw_data JSONB NOT NULL,
    is_active BOOLEAN DEFAULT true,
    last_sync_at TIMESTAMPTZ DEFAULT NOW(),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Devices/Assets from Datto RMM
CREATE TABLE integrations.datto_devices (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
    site_id UUID REFERENCES integrations.datto_sites(id) ON DELETE CASCADE,
    device_uid VARCHAR(255) UNIQUE NOT NULL,
    hostname VARCHAR(255),
    display_name VARCHAR(255),
    device_type VARCHAR(100),
    operating_system VARCHAR(255),
    os_version VARCHAR(100),
    domain VARCHAR(255),
    
    -- Network information
    internal_ip INET,
    external_ip INET,
    mac_addresses VARCHAR[] DEFAULT '{}',
    
    -- Status information
    status VARCHAR(50),
    is_online BOOLEAN DEFAULT false,
    last_seen TIMESTAMPTZ,
    last_reboot TIMESTAMPTZ,
    
    -- Agent information
    agent_version VARCHAR(50),
    
    -- Hardware information
    manufacturer VARCHAR(255),
    model VARCHAR(255),
    serial_number VARCHAR(255),
    warranty_expiry DATE,
    
    -- Linkage
    linked_ci_id UUID REFERENCES itil.configuration_items(id) ON DELETE SET NULL,
    
    -- Raw data and metadata
    raw_data JSONB NOT NULL,
    custom_fields JSONB DEFAULT '{}',
    
    -- Sync tracking
    last_sync_at TIMESTAMPTZ DEFAULT NOW(),
    sync_errors JSONB DEFAULT '{}',
    
    -- Timestamps
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    
    -- Indexes
    CONSTRAINT uq_datto_device_uid UNIQUE (device_uid)
);

CREATE INDEX idx_datto_devices_tenant ON integrations.datto_devices(tenant_id);
CREATE INDEX idx_datto_devices_site ON integrations.datto_devices(site_id);
CREATE INDEX idx_datto_devices_hostname ON integrations.datto_devices(hostname);
CREATE INDEX idx_datto_devices_linked_ci ON integrations.datto_devices(linked_ci_id);
CREATE INDEX idx_datto_devices_status ON integrations.datto_devices(status, is_online);

-- Alerts from Datto RMM
CREATE TABLE integrations.datto_alerts (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
    device_id UUID REFERENCES integrations.datto_devices(id) ON DELETE CASCADE,
    alert_uid BIGINT UNIQUE NOT NULL,
    alert_type VARCHAR(100),
    severity VARCHAR(20),
    status VARCHAR(50),
    
    -- Alert details
    monitor_type VARCHAR(255),
    alert_message TEXT,
    alert_context JSONB DEFAULT '{}',
    
    -- Timing
    triggered_at TIMESTAMPTZ,
    resolved_at TIMESTAMPTZ,
    acknowledged_at TIMESTAMPTZ,
    
    -- Processing
    processed_at TIMESTAMPTZ,
    generated_incident_id UUID REFERENCES itil.incidents(id) ON DELETE SET NULL,
    processing_notes TEXT,
    
    -- Raw data
    raw_data JSONB NOT NULL,
    
    -- Timestamps
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_datto_alerts_tenant ON integrations.datto_alerts(tenant_id);
CREATE INDEX idx_datto_alerts_device ON integrations.datto_alerts(device_id);
CREATE INDEX idx_datto_alerts_status ON integrations.datto_alerts(status);
CREATE INDEX idx_datto_alerts_triggered ON integrations.datto_alerts(triggered_at DESC);

-- Patch status from Datto RMM
CREATE TABLE integrations.datto_patches (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
    device_id UUID REFERENCES integrations.datto_devices(id) ON DELETE CASCADE,
    patch_uid VARCHAR(255) UNIQUE NOT NULL,
    
    -- Patch information
    kb_number VARCHAR(50),
    title VARCHAR(500),
    description TEXT,
    severity VARCHAR(20),
    category VARCHAR(100),
    
    -- Status
    status VARCHAR(50),
    is_approved BOOLEAN DEFAULT false,
    is_installed BOOLEAN DEFAULT false,
    
    -- Dates
    released_date DATE,
    approved_date TIMESTAMPTZ,
    installed_date TIMESTAMPTZ,
    
    -- Change management link
    change_request_id UUID REFERENCES itil.changes(id) ON DELETE SET NULL,
    
    -- Raw data
    raw_data JSONB NOT NULL,
    
    -- Timestamps
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- BITDEFENDER GRAVITYZONE TABLES
-- Endpoints from Bitdefender
CREATE TABLE integrations.bitdefender_endpoints (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
    endpoint_id VARCHAR(255) UNIQUE NOT NULL,
    
    -- Basic information
    name VARCHAR(255),
    label VARCHAR(255),
    fqdn VARCHAR(255),
    group_id VARCHAR(255),
    group_name VARCHAR(255),
    
    -- System information
    operating_system VARCHAR(255),
    operating_system_version VARCHAR(100),
    ip_addresses INET[] DEFAULT '{}',
    mac_addresses VARCHAR[] DEFAULT '{}',
    
    -- Security status
    is_managed BOOLEAN DEFAULT true,
    is_online BOOLEAN DEFAULT false,
    malware_status VARCHAR(50),
    
    -- Agent information
    agent_info JSONB DEFAULT '{}',
    modules_status JSONB DEFAULT '{}',
    policy_id VARCHAR(255),
    policy_name VARCHAR(255),
    
    -- Last activity
    last_seen TIMESTAMPTZ,
    last_update TIMESTAMPTZ,
    
    -- Linkage
    linked_ci_id UUID REFERENCES itil.configuration_items(id) ON DELETE SET NULL,
    linked_datto_device_id UUID REFERENCES integrations.datto_devices(id) ON DELETE SET NULL,
    
    -- Raw data
    raw_data JSONB NOT NULL,
    
    -- Sync tracking
    last_sync_at TIMESTAMPTZ DEFAULT NOW(),
    
    -- Timestamps
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_bitdefender_endpoints_tenant ON integrations.bitdefender_endpoints(tenant_id);
CREATE INDEX idx_bitdefender_endpoints_name ON integrations.bitdefender_endpoints(name);
CREATE INDEX idx_bitdefender_endpoints_linked_ci ON integrations.bitdefender_endpoints(linked_ci_id);
CREATE INDEX idx_bitdefender_endpoints_status ON integrations.bitdefender_endpoints(is_managed, is_online);

-- Security incidents from Bitdefender
CREATE TABLE integrations.bitdefender_incidents (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
    endpoint_id UUID REFERENCES integrations.bitdefender_endpoints(id) ON DELETE CASCADE,
    incident_id VARCHAR(255) UNIQUE NOT NULL,
    
    -- Incident details
    incident_type VARCHAR(100),
    threat_name VARCHAR(255),
    threat_type VARCHAR(100),
    severity VARCHAR(20),
    status VARCHAR(50),
    
    -- Detection information
    detection_time TIMESTAMPTZ,
    file_path TEXT,
    process_path TEXT,
    
    -- Action taken
    action_taken VARCHAR(100),
    quarantine_status VARCHAR(50),
    
    -- User information
    user_name VARCHAR(255),
    
    -- Processing
    processed_at TIMESTAMPTZ,
    generated_incident_id UUID REFERENCES itil.incidents(id) ON DELETE SET NULL,
    
    -- Raw data
    raw_data JSONB NOT NULL,
    
    -- Timestamps
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_bitdefender_incidents_tenant ON integrations.bitdefender_incidents(tenant_id);
CREATE INDEX idx_bitdefender_incidents_endpoint ON integrations.bitdefender_incidents(endpoint_id);
CREATE INDEX idx_bitdefender_incidents_severity ON integrations.bitdefender_incidents(severity);
CREATE INDEX idx_bitdefender_incidents_detection ON integrations.bitdefender_incidents(detection_time DESC);

-- Threat intelligence from Bitdefender
CREATE TABLE integrations.bitdefender_threats (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
    threat_hash VARCHAR(255) UNIQUE NOT NULL,
    threat_name VARCHAR(255),
    threat_type VARCHAR(100),
    first_seen TIMESTAMPTZ,
    last_seen TIMESTAMPTZ,
    occurrence_count INT DEFAULT 1,
    affected_endpoints INT DEFAULT 0,
    
    -- Threat details
    details JSONB DEFAULT '{}',
    iocs JSONB DEFAULT '{}', -- Indicators of Compromise
    
    -- Knowledge base link
    kb_article_id UUID REFERENCES itil.knowledge_articles(id) ON DELETE SET NULL,
    
    -- Raw data
    raw_data JSONB NOT NULL,
    
    -- Timestamps
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Audit log for all integration actions
CREATE TABLE integrations.audit_log (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL REFERENCES core.tenants(id) ON DELETE CASCADE,
    integration_name VARCHAR(50) NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id VARCHAR(255),
    old_data JSONB,
    new_data JSONB,
    user_id UUID REFERENCES core.users(id),
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_integration_audit_tenant ON integrations.audit_log(tenant_id);
CREATE INDEX idx_integration_audit_created ON integrations.audit_log(created_at DESC);
```

---

## 3. 🧠 Domain Logic for Enterprise Integrations

### 3.1 Datto RMM Sync Actions
```php
<?php
/*
 * Arquivo: /app/Domains/Integrations/Datto/Actions/SyncDattoDevicesAction.php
 * Synchronize devices from Datto RMM to our CMDB
 */

namespace App\Domains\Integrations\Datto\Actions;

use App\Core\Actions\Action;
use App\Core\Attributes\Scheduled;
use App\Core\Attributes\Transaction;
use App\Core\Attributes\Metric;
use App\Domains\Integrations\Datto\Client\DattoRmmClient;
use App\Domains\Integrations\Datto\Data\DeviceSyncData;
use App\Domains\CMDB\Services\AssetReconciliationService;

class SyncDattoDevicesAction extends Action
{
    public function __construct(
        private DattoRmmClient $client,
        private AssetReconciliationService $reconciliation,
        private DattoDeviceRepository $repository,
        private SyncStatusService $syncStatus
    ) {}

    /**
     * @Scheduled(expression="0 */6 * * *", queue="integrations")
     * @Transaction
     * @Metric("datto.devices.sync")
     */
    public function execute(Tenant $tenant): SyncResult
    {
        $sync = $this->syncStatus->start($tenant, 'datto_rmm', 'devices');
        
        try {
            // 1. Fetch all sites for this tenant
            $sites = $this->client->forTenant($tenant)->getSites();
            
            $totalProcessed = 0;
            $totalCreated = 0;
            $totalUpdated = 0;
            $errors = [];
            
            foreach ($sites as $siteData) {
                // Update or create site
                $site = $this->syncSite($tenant, $siteData);
                
                // 2. Fetch devices for each site
                $devices = $this->client->getDevicesForSite($siteData['uid']);
                
                foreach ($devices as $deviceData) {
                    try {
                        $device = $this->syncDevice($tenant, $site, $deviceData);
                        
                        // 3. Reconcile with CMDB
                        $this->reconcileCMDB($device);
                        
                        $totalProcessed++;
                        if ($device->wasRecentlyCreated) {
                            $totalCreated++;
                        } else {
                            $totalUpdated++;
                        }
                        
                    } catch (\Exception $e) {
                        $errors[] = [
                            'device_uid' => $deviceData['uid'],
                            'error' => $e->getMessage()
                        ];
                        Log::error('Datto device sync failed', [
                            'device' => $deviceData,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            // 4. Complete sync status
            $this->syncStatus->complete($sync, [
                'records_processed' => $totalProcessed,
                'records_created' => $totalCreated,
                'records_updated' => $totalUpdated,
                'records_failed' => count($errors),
                'errors' => $errors
            ]);
            
            // 5. Emit event for further processing
            event(new DattoDevicesSynced($tenant, $totalProcessed));
            
            return new SyncResult(
                success: true,
                processed: $totalProcessed,
                created: $totalCreated,
                updated: $totalUpdated,
                errors: $errors
            );
            
        } catch (\Exception $e) {
            $this->syncStatus->fail($sync, $e->getMessage());
            throw $e;
        }
    }
    
    private function syncDevice(Tenant $tenant, DattoSite $site, array $data): DattoDevice
    {
        return $this->repository->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'device_uid' => $data['uid']
            ],
            [
                'site_id' => $site->id,
                'hostname' => $data['hostname'],
                'display_name' => $data['displayName'] ?? $data['hostname'],
                'device_type' => $this->mapDeviceType($data['deviceType']),
                'operating_system' => $data['operatingSystem'],
                'os_version' => $data['osVersion'] ?? null,
                'domain' => $data['domain'] ?? null,
                'internal_ip' => $data['intIpAddress'] ?? null,
                'external_ip' => $data['extIpAddress'] ?? null,
                'mac_addresses' => $this->extractMacAddresses($data),
                'status' => $data['status'],
                'is_online' => $data['online'] ?? false,
                'last_seen' => $data['lastSeen'] ? Carbon::parse($data['lastSeen']) : null,
                'agent_version' => $data['agentVersion'] ?? null,
                'manufacturer' => $data['manufacturer'] ?? null,
                'model' => $data['model'] ?? null,
                'serial_number' => $data['serialNumber'] ?? null,
                'raw_data' => $data,
                'last_sync_at' => now()
            ]
        );
    }
    
    private function reconcileCMDB(DattoDevice $device): void
    {
        // Try to find existing CI by multiple criteria
        $ci = $this->reconciliation->findOrCreateCI([
            'hostname' => $device->hostname,
            'serial_number' => $device->serial_number,
            'mac_addresses' => $device->mac_addresses,
            'type' => $device->device_type
        ]);
        
        if ($ci) {
            // Link the device to CI
            $device->update(['linked_ci_id' => $ci->id]);
            
            // Update CI with latest information
            $ci->update([
                'name' => $device->display_name ?? $device->hostname,
                'ip_address' => $device->internal_ip,
                'operating_system' => $device->operating_system,
                'manufacturer' => $device->manufacturer,
                'model' => $device->model,
                'last_discovered' => now()
            ]);
        }
    }
}
```

### 3.2 Datto Alert Processing
```php
<?php
/*
 * Arquivo: /app/Domains/Integrations/Datto/Actions/ProcessDattoAlertAction.php
 * Convert Datto alerts to ITSM incidents
 */

namespace App\Domains\Integrations\Datto\Actions;

use App\Core\Actions\Action;
use App\Core\Attributes\PublishEvent;
use App\Core\Attributes\AuditLog;
use App\Domains\Incident\Actions\CreateIncidentAction;
use App\Domains\Integrations\Datto\Models\DattoAlert;

class ProcessDattoAlertAction extends Action
{
    public function __construct(
        private CreateIncidentAction $createIncident,
        private AlertPriorityMapper $priorityMapper,
        private AlertCategoryMapper $categoryMapper
    ) {}

    /**
     * @PublishEvent(DattoAlertProcessed::class)
     * @AuditLog("datto.alert.processed")
     */
    public function execute(DattoAlert $alert): ?Incident
    {
        // Skip if already processed
        if ($alert->generated_incident_id) {
            return null;
        }
        
        // Skip if alert is resolved
        if ($alert->status === 'resolved') {
            $alert->update(['status' => 'ignored']);
            return null;
        }
        
        // Map alert to incident data
        $incidentData = CreateIncidentData::from([
            'tenant' => $alert->tenant,
            'title' => $this->generateTitle($alert),
            'description' => $this->generateDescription($alert),
            'impact' => $this->priorityMapper->mapImpact($alert->severity),
            'urgency' => $this->priorityMapper->mapUrgency($alert->alert_type),
            'category_id' => $this->categoryMapper->map($alert->monitor_type),
            'affected_ci_id' => $alert->device->linked_ci_id,
            'custom_fields' => [
                'source' => 'datto_rmm',
                'external_id' => $alert->alert_uid,
                'external_url' => $this->buildDattoUrl($alert)
            ]
        ]);
        
        // Create incident
        $incident = $this->createIncident->execute($incidentData);
        
        // Link alert to incident
        $alert->update([
            'generated_incident_id' => $incident->id,
            'processed_at' => now(),
            'status' => 'processed'
        ]);
        
        // Add initial comment with alert details
        $incident->comments()->create([
            'body' => "Alert Details:\n" . json_encode($alert->alert_context, JSON_PRETTY_PRINT),
            'is_internal' => true,
            'created_by' => system_user()->id
        ]);
        
        return $incident;
    }
    
    private function generateTitle(DattoAlert $alert): string
    {
        return sprintf(
            "[%s] %s - %s",
            strtoupper($alert->severity),
            $alert->device->hostname,
            $alert->alert_message
        );
    }
    
    private function generateDescription(DattoAlert $alert): string
    {
        return implode("\n\n", [
            "**Alert from Datto RMM**",
            "Device: {$alert->device->display_name} ({$alert->device->hostname})",
            "Monitor Type: {$alert->monitor_type}",
            "Alert Type: {$alert->alert_type}",
            "Message: {$alert->alert_message}",
            "",
            "Triggered at: " . $alert->triggered_at->format('Y-m-d H:i:s'),
            "Device Last Seen: " . $alert->device->last_seen?->diffForHumans()
        ]);
    }
}
```

### 3.3 Bitdefender Endpoint Sync
```php
<?php
/*
 * Arquivo: /app/Domains/Integrations/Bitdefender/Actions/SyncBitdefenderEndpointsAction.php
 */

namespace App\Domains\Integrations\Bitdefender\Actions;

use App\Core\Actions\Action;
use App\Core\Attributes\Scheduled;
use App\Core\Attributes\Metric;
use App\Domains\Integrations\Bitdefender\Client\GravityZoneClient;

class SyncBitdefenderEndpointsAction extends Action
{
    public function __construct(
        private GravityZoneClient $client,
        private BitdefenderEndpointRepository $repository,
        private EndpointReconciliationService $reconciliation
    ) {}

    /**
     * @Scheduled(expression="0 */4 * * *", queue="integrations")
     * @Metric("bitdefender.endpoints.sync")
     */
    public function execute(Tenant $tenant): SyncResult
    {
        $client = $this->client->forTenant($tenant);
        $page = 1;
        $pageSize = 100;
        $hasMore = true;
        $totals = ['processed' => 0, 'created' => 0, 'updated' => 0];
        
        while ($hasMore) {
            // Call Bitdefender API
            $response = $client->call('getManagedEndpointsList', [
                'page' => $page,
                'perPage' => $pageSize,
                'filters' => [
                    'depth' => [
                        'allItemsRecursively' => true
                    ]
                ]
            ]);
            
            if (!isset($response['result']['items'])) {
                break;
            }
            
            foreach ($response['result']['items'] as $endpointData) {
                $endpoint = $this->syncEndpoint($tenant, $endpointData);
                $this->reconcileWithCMDB($endpoint);
                $this->reconcileWithDatto($endpoint);
                
                $totals['processed']++;
                if ($endpoint->wasRecentlyCreated) {
                    $totals['created']++;
                } else {
                    $totals['updated']++;
                }
            }
            
            $hasMore = $response['result']['pagesCount'] > $page;
            $page++;
        }
        
        event(new BitdefenderEndpointsSynced($tenant, $totals));
        
        return new SyncResult(
            success: true,
            processed: $totals['processed'],
            created: $totals['created'],
            updated: $totals['updated']
        );
    }
    
    private function syncEndpoint(Tenant $tenant, array $data): BitdefenderEndpoint
    {
        return $this->repository->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'endpoint_id' => $data['id']
            ],
            [
                'name' => $data['name'],
                'label' => $data['label'] ?? null,
                'fqdn' => $data['fqdn'] ?? null,
                'group_id' => $data['groupId'] ?? null,
                'group_name' => $this->extractGroupName($data),
                'operating_system' => $data['operatingSystemVersion'] ?? null,
                'ip_addresses' => $this->extractIpAddresses($data),
                'mac_addresses' => $this->extractMacAddresses($data),
                'is_managed' => $data['isManaged'] ?? true,
                'is_online' => $data['isOnline'] ?? false,
                'malware_status' => $data['malwareStatus']['status'] ?? null,
                'agent_info' => $data['agent'] ?? [],
                'modules_status' => $data['modules'] ?? [],
                'policy_id' => $data['policyId'] ?? null,
                'policy_name' => $data['policyName'] ?? null,
                'last_seen' => isset($data['lastSeen']) ? Carbon::parse($data['lastSeen']) : null,
                'raw_data' => $data,
                'last_sync_at' => now()
            ]
        );
    }
    
    private function reconcileWithDatto(BitdefenderEndpoint $endpoint): void
    {
        // Try to match with Datto device
        $dattoDevice = DattoDevice::where('tenant_id', $endpoint->tenant_id)
            ->where(function ($query) use ($endpoint) {
                $query->where('hostname', 'ILIKE', $endpoint->name)
                    ->orWhere('display_name', 'ILIKE', $endpoint->name);
                    
                if (!empty($endpoint->mac_addresses)) {
                    $query->orWhere(function ($q) use ($endpoint) {
                        foreach ($endpoint->mac_addresses as $mac) {
                            $q->orWhereRaw('? = ANY(mac_addresses)', [$mac]);
                        }
                    });
                }
            })
            ->first();
            
        if ($dattoDevice) {
            $endpoint->update(['linked_datto_device_id' => $dattoDevice->id]);
        }
    }
}
```

### 3.4 Bitdefender Security Incident Processing
```php
<?php
/*
 * Arquivo: /app/Domains/Integrations/Bitdefender/Actions/ProcessSecurityIncidentAction.php
 */

namespace App\Domains\Integrations\Bitdefender\Actions;

use App\Core\Actions\Action;
use App\Domains\Incident\Actions\CreateIncidentAction;
use App\Domains\Integrations\Bitdefender\Models\BitdefenderIncident;

class ProcessSecurityIncidentAction extends Action
{
    public function __construct(
        private CreateIncidentAction $createIncident,
        private SecurityPriorityMapper $priorityMapper,
        private ThreatIntelligenceService $threatIntel
    ) {}

    public function execute(BitdefenderIncident $securityIncident): Incident
    {
        // Check threat intelligence
        $threatInfo = $this->threatIntel->analyze(
            $securityIncident->threat_name,
            $securityIncident->threat_type
        );
        
        // Create high-priority security incident
        $incidentData = CreateIncidentData::from([
            'tenant' => $securityIncident->tenant,
            'title' => $this->generateSecurityTitle($securityIncident),
            'description' => $this->generateSecurityDescription($securityIncident, $threatInfo),
            'impact' => $this->priorityMapper->mapImpact($securityIncident->severity),
            'urgency' => '1-Critical', // Security incidents are always urgent
            'category_id' => $this->getSecurityCategoryId($securityIncident->incident_type),
            'affected_ci_id' => $securityIncident->endpoint->linked_ci_id,
            'custom_fields' => [
                'source' => 'bitdefender_gravityzone',
                'security_incident' => true,
                'threat_name' => $securityIncident->threat_name,
                'threat_type' => $securityIncident->threat_type,
                'iocs' => $threatInfo->iocs ?? []
            ]
        ]);
        
        $incident = $this->createIncident->execute($incidentData);
        
        // Link security incident
        $securityIncident->update([
            'generated_incident_id' => $incident->id,
            'processed_at' => now()
        ]);
        
        // Auto-escalate if critical
        if ($incident->priority === 1) {
            dispatch(new EscalateSecurityIncidentJob($incident));
        }
        
        // Create knowledge article if new threat
        if ($threatInfo->is_new_threat) {
            dispatch(new CreateThreatKnowledgeArticleJob($securityIncident, $threatInfo));
        }
        
        return $incident;
    }
    
    private function generateSecurityTitle(BitdefenderIncident $incident): string
    {
        return sprintf(
            "🚨 [SECURITY] %s detected on %s",
            $incident->threat_name,
            $incident->endpoint->name
        );
    }
    
    private function generateSecurityDescription(
        BitdefenderIncident $incident,
        ThreatInfo $threatInfo
    ): string {
        $description = [
            "**Security Incident Detected by Bitdefender GravityZone**",
            "",
            "**Threat Information:**",
            "- Threat Name: {$incident->threat_name}",
            "- Threat Type: {$incident->threat_type}",
            "- Severity: {$incident->severity}",
            "- Detection Time: {$incident->detection_time}",
            "",
            "**Affected Endpoint:**",
            "- Name: {$incident->endpoint->name}",
            "- IP: " . implode(', ', $incident->endpoint->ip_addresses),
            "- OS: {$incident->endpoint->operating_system}",
            "- Last Seen: {$incident->endpoint->last_seen}",
            "",
            "**Action Taken:**",
            "- {$incident->action_taken}",
            "- Quarantine Status: {$incident->quarantine_status}",
        ];
        
        if ($incident->file_path) {
            $description[] = "";
            $description[] = "**File Information:**";
            $description[] = "- Path: {$incident->file_path}";
        }
        
        if ($incident->process_path) {
            $description[] = "- Process: {$incident->process_path}";
        }
        
        if ($incident->user_name) {
            $description[] = "- User: {$incident->user_name}";
        }
        
        if ($threatInfo->description) {
            $description[] = "";
            $description[] = "**Threat Intelligence:**";
            $description[] = $threatInfo->description;
        }
        
        return implode("\n", $description);
    }
}
```

---

## 4. 🔌 Integration API Clients

### 4.1 Datto RMM API Client
```php
<?php
/*
 * Arquivo: /app/Domains/Integrations/Datto/Client/DattoRmmClient.php
 */

namespace App\Domains\Integrations\Datto\Client;

use App\Core\Integrations\BaseApiClient;
use App\Core\Attributes\RateLimited;
use App\Core\Attributes\Retryable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class DattoRmmClient extends BaseApiClient
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $apiSecret;
    protected ?string $accessToken = null;
    protected ?Carbon $tokenExpiry = null;
    
    public function __construct(array $config)
    {
        $this->baseUrl = $config['base_url'] ?? 'https://api.datto.com/v2';
        $this->apiKey = $config['api_key'];
        $this->apiSecret = $config['api_secret'];
    }
    
    /**
     * @RateLimited(maxAttempts=60, decayMinutes=1)
     * @Retryable(times=3, sleep=1000)
     */
    public function getSites(): array
    {
        return $this->get('/sites')['sites'] ?? [];
    }
    
    /**
     * @RateLimited(maxAttempts=60, decayMinutes=1)
     * @Retryable(times=3, sleep=1000)
     */
    public function getDevicesForSite(string $siteUid): array
    {
        return $this->get("/sites/{$siteUid}/devices")['devices'] ?? [];
    }
    
    /**
     * @RateLimited(maxAttempts=60, decayMinutes=1)
     * @Retryable(times=3, sleep=1000)
     */
    public function getAlerts(array $filters = []): array
    {
        $params = [
            'pageSize' => $filters['pageSize'] ?? 100,
            'page' => $filters['page'] ?? 1
        ];
        
        if (isset($filters['since'])) {
            $params['since'] = $filters['since'];
        }
        
        return $this->get('/alerts', $params)['alerts'] ?? [];
    }
    
    protected function authenticate(): void
    {
        if ($this->accessToken && $this->tokenExpiry && $this->tokenExpiry->isFuture()) {
            return;
        }
        
        $response = Http::asForm()
            ->post("{$this->baseUrl}/auth", [
                'grant_type' => 'client_credentials',
                'client_id' => $this->apiKey,
                'client_secret' => $this->apiSecret
            ]);
            
        if (!$response->successful()) {
            throw new DattoAuthenticationException(
                "Failed to authenticate with Datto RMM: " . $response->body()
            );
        }
        
        $data = $response->json();
        $this->accessToken = $data['access_token'];
        $this->tokenExpiry = now()->addSeconds($data['expires_in'] - 60); // 1 minute buffer
    }
    
    protected function request(string $method, string $endpoint, array $data = []): array
    {
        $this->authenticate();
        
        $response = Http::withToken($this->accessToken)
            ->timeout(30)
            ->retry(3, 1000)
            ->{$method}($this->baseUrl . $endpoint, $data);
            
        if (!$response->successful()) {
            $this->handleError($response);
        }
        
        return $response->json();
    }
    
    protected function handleError(Response $response): void
    {
        $error = $response->json();
        
        match ($response->status()) {
            401 => throw new DattoAuthenticationException($error['message'] ?? 'Unauthorized'),
            429 => throw new DattoRateLimitException($error['message'] ?? 'Rate limit exceeded'),
            default => throw new DattoApiException(
                "API request failed: " . ($error['message'] ?? $response->body())
            )
        };
    }
}
```

### 4.2 Bitdefender GravityZone API Client
```php
<?php
/*
 * Arquivo: /app/Domains/Integrations/Bitdefender/Client/GravityZoneClient.php
 */

namespace App\Domains\Integrations\Bitdefender\Client;

use App\Core\Integrations\JsonRpcClient;
use App\Core\Attributes\RateLimited;
use App\Core\Attributes\Retryable;

class GravityZoneClient extends JsonRpcClient
{
    protected string $baseUrl;
    protected string $apiKey;
    
    public function __construct(array $config)
    {
        $this->baseUrl = $config['access_url'] ?? 'https://cloud.gravityzone.bitdefender.com';
        $this->apiKey = $config['api_key'];
        
        parent::__construct($this->baseUrl . '/api/v1.0/jsonrpc');
    }
    
    /**
     * Get list of managed endpoints
     * 
     * @RateLimited(maxAttempts=100, decayMinutes=1)
     * @Retryable(times=3, sleep=2000)
     */
    public function getManagedEndpoints(array $params = []): array
    {
        $defaultParams = [
            'page' => 1,
            'perPage' => 100,
            'filters' => [
                'depth' => [
                    'allItemsRecursively' => true
                ],
                'security' => [
                    'management' => ['managedWithBest', 'managedRelays']
                ]
            ]
        ];
        
        return $this->call(
            'getManagedEndpointsList',
            array_merge($defaultParams, $params)
        );
    }
    
    /**
     * Get security incidents
     * 
     * @RateLimited(maxAttempts=100, decayMinutes=1)
     * @Retryable(times=3, sleep=2000)
     */
    public function getIncidents(array $filters = []): array
    {
        $params = [
            'page' => $filters['page'] ?? 1,
            'perPage' => $filters['perPage'] ?? 100,
            'filters' => []
        ];
        
        if (isset($filters['since'])) {
            $params['filters']['dateRange'] = [
                'start' => $filters['since']->toIso8601String(),
                'end' => now()->toIso8601String()
            ];
        }
        
        if (isset($filters['status'])) {
            $params['filters']['status'] = $filters['status'];
        }
        
        return $this->call('getIncidentsList', $params);
    }
    
    /**
     * Get endpoint details
     */
    public function getEndpointDetails(string $endpointId): array
    {
        return $this->call('getEndpointDetails', [
            'endpointId' => $endpointId
        ]);
    }
    
    /**
     * Update incident status
     */
    public function updateIncidentStatus(string $incidentId, string $status): array
    {
        return $this->call('setIncidentStatus', [
            'incidentId' => $incidentId,
            'status' => $status
        ]);
    }
    
    /**
     * Get threat statistics
     */
    public function getThreatStatistics(array $filters = []): array
    {
        return $this->call('getThreatsStatistics', $filters);
    }
    
    protected function getHeaders(): array
    {
        return [
            'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':'),
            'Content-Type' => 'application/json'
        ];
    }
    
    protected function handleError(array $error): void
    {
        if (isset($error['error'])) {
            $code = $error['error']['code'] ?? -1;
            $message = $error['error']['message'] ?? 'Unknown error';
            
            match ($code) {
                -32700 => throw new BitdefenderParseException($message),
                -32600 => throw new BitdefenderInvalidRequestException($message),
                -32601 => throw new BitdefenderMethodNotFoundException($message),
                -32602 => throw new BitdefenderInvalidParamsException($message),
                -32603 => throw new BitdefenderInternalErrorException($message),
                401 => throw new BitdefenderAuthenticationException($message),
                429 => throw new BitdefenderRateLimitException($message),
                default => throw new BitdefenderApiException("API Error [{$code}]: {$message}")
            };
        }
    }
}
```

---

## 5. 📜 API Specifications for Integrations

### 5.1 Integration Management API
```yaml
# Arquivo: /api/openapi/integrations.yml
# RESTful API for managing integrations

openapi: 3.1.0
info:
  title: ITSM Platform - Integrations API
  version: 1.0.0
  description: API for managing enterprise integrations (Datto RMM, Bitdefender)

paths:
  /api/v1/integrations/status:
    get:
      operationId: getIntegrationsStatus
      summary: Get status of all integrations
      tags: [Integrations]
      responses:
        '200':
          description: Integration status list
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/IntegrationStatus'
                      
  /api/v1/integrations/{integration}/sync:
    post:
      operationId: triggerSync
      summary: Manually trigger synchronization
      tags: [Integrations]
      parameters:
        - name: integration
          in: path
          required: true
          schema:
            type: string
            enum: [datto_rmm, bitdefender_gravityzone]
      requestBody:
        content:
          application/json:
            schema:
              type: object
              properties:
                sync_type:
                  type: string
                  enum: [assets, alerts, endpoints, incidents, all]
                force:
                  type: boolean
                  default: false
      responses:
        '202':
          description: Sync job queued
          content:
            application/json:
              schema:
                type: object
                properties:
                  job_id:
                    type: string
                    format: uuid
                  status:
                    type: string
                    default: queued
                    
  /api/v1/integrations/datto/devices:
    get:
      operationId: listDattoDevices
      summary: List synchronized Datto devices
      tags: [Integrations, Datto]
      parameters:
        - $ref: '#/components/parameters/PageNumber'
        - $ref: '#/components/parameters/PageSize'
        - name: filter[site_id]
          in: query
          schema:
            type: string
            format: uuid
        - name: filter[status]
          in: query
          schema:
            type: string
        - name: filter[linked]
          in: query
          description: Filter by CMDB linkage status
          schema:
            type: boolean
        - name: search
          in: query
          schema:
            type: string
      responses:
        '200':
          description: Datto devices list
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/DattoDeviceList'
                
  /api/v1/integrations/datto/devices/{device_id}/link-cmdb:
    post:
      operationId: linkDattoDeviceToCMDB
      summary: Link Datto device to Configuration Item
      tags: [Integrations, Datto]
      parameters:
        - name: device_id
          in: path
          required: true
          schema:
            type: string
            format: uuid
      requestBody:
        content:
          application/json:
            schema:
              type: object
              required: [ci_id]
              properties:
                ci_id:
                  type: string
                  format: uuid
                auto_sync:
                  type: boolean
                  default: true
                  description: Keep CI updated with device changes
      responses:
        '200':
          description: Link created successfully
          
  /api/v1/integrations/datto/alerts/{alert_id}/create-incident:
    post:
      operationId: createIncidentFromDattoAlert
      summary: Create ITSM incident from Datto alert
      tags: [Integrations, Datto]
      parameters:
        - name: alert_id
          in: path
          required: true
          schema:
            type: string
            format: uuid
      requestBody:
        content:
          application/json:
            schema:
              type: object
              properties:
                priority_override:
                  type: integer
                  minimum: 1
                  maximum: 5
                assigned_team_id:
                  type: string
                  format: uuid
                additional_notes:
                  type: string
      responses:
        '201':
          description: Incident created
          headers:
            Location:
              schema:
                type: string
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/IncidentResponse'
                
  /api/v1/integrations/bitdefender/endpoints:
    get:
      operationId: listBitdefenderEndpoints
      summary: List synchronized Bitdefender endpoints
      tags: [Integrations, Bitdefender]
      parameters:
        - $ref: '#/components/parameters/PageNumber'
        - $ref: '#/components/parameters/PageSize'
        - name: filter[managed]
          in: query
          schema:
            type: boolean
        - name: filter[online]
          in: query
          schema:
            type: boolean
        - name: filter[malware_status]
          in: query
          schema:
            type: string
        - name: filter[group_id]
          in: query
          schema:
            type: string
      responses:
        '200':
          description: Bitdefender endpoints list
          
  /api/v1/integrations/bitdefender/incidents/{incident_id}/process:
    post:
      operationId: processBitdefenderIncident
      summary: Process security incident from Bitdefender
      tags: [Integrations, Bitdefender]
      parameters:
        - name: incident_id
          in: path
          required: true
          schema:
            type: string
            format: uuid
      requestBody:
        content:
          application/json:
            schema:
              type: object
              properties:
                action:
                  type: string
                  enum: [create_incident, ignore, defer]
                create_options:
                  type: object
                  properties:
                    auto_escalate:
                      type: boolean
                      default: true
                    notify_security_team:
                      type: boolean
                      default: true
      responses:
        '200':
          description: Action performed successfully

components:
  schemas:
    IntegrationStatus:
      type: object
      properties:
        integration:
          type: string
          enum: [datto_rmm, bitdefender_gravityzone]
        enabled:
          type: boolean
        last_sync:
          type: object
          properties:
            sync_type:
              type: string
            started_at:
              type: string
              format: date-time
            completed_at:
              type: string
              format: date-time
            status:
              type: string
              enum: [pending, running, completed, failed]
            records_processed:
              type: integer
            errors:
              type: array
              items:
                type: object
        health:
          type: object
          properties:
            status:
              type: string
              enum: [healthy, degraded, unhealthy]
            last_check:
              type: string
              format: date-time
            message:
              type: string
```

---

## 6. 🤖 Enhanced Master Implementation Script

### 6.1 Master Script with Integration Support
```bash
#!/bin/bash
# Arquivo: /scripts/master_implementation_v4.sh
# Implementation script with enterprise integrations

set -euo pipefail
IFS=$'\n\t'

# ... (previous sections maintained) ...

# Phase 11: Enterprise Integrations
progress "Enterprise Integrations Setup"

log "Setting up integration schema..."
claude-code database migrate \
    --path="$PROJECT_ROOT/database/schema/003_enterprise_integrations.sql"

log "Generating Datto RMM integration..."
claude-code generate integration datto_rmm \
    --api-spec="https://api.datto.com/v2/swagger.json" \
    --config="$PROJECT_ROOT/integrations/datto/config.yml" \
    --with-client \
    --with-sync-jobs \
    --with-tests

log "Generating Bitdefender GravityZone integration..."
claude-code generate integration bitdefender_gravityzone \
    --api-spec="$PROJECT_ROOT/integrations/bitdefender/api_spec.json" \
    --config="$PROJECT_ROOT/integrations/bitdefender/config.yml" \
    --with-client \
    --with-sync-jobs \
    --with-tests

log "Generating integration actions..."
for action in sync_devices process_alerts sync_endpoints process_incidents; do
    claude-code generate action "Integrations/$action" \
        --with-tests \
        --with-events \
        --with-metrics
done

log "Setting up scheduled jobs..."
claude-code schedule-jobs \
    --from-config="$PROJECT_ROOT/project.config.yml" \
    --queue=integrations

log "Generating integration API endpoints..."
claude-code generate api \
    --from-openapi="$PROJECT_ROOT/api/openapi/integrations.yml" \
    --with-tests

log "Creating integration dashboards..."
claude-code generate dashboard integrations \
    --metrics="sync_status,device_count,alert_volume,incident_correlation" \
    --realtime

# Phase 12: Integration Testing
progress "Integration Testing"

log "Running integration client tests..."
claude-code test integration-clients \
    --use-mocks \
    --coverage

log "Testing sync workflows..."
claude-code test integration-sync \
    --scenarios="$PROJECT_ROOT/tests/integration/sync_scenarios.yml"

log "Validating security compliance..."
claude-code security-scan integrations \
    --check-api-keys \
    --check-encryption \
    --check-audit-logs

# ... (continue with remaining phases) ...
```

### 6.2 Integration Health Check Script
```bash
#!/bin/bash
# Arquivo: /scripts/check_integrations_health.sh
# Monitor integration health

set -euo pipefail

echo "🔍 Checking Integration Health..."

# Check Datto RMM
echo -n "Datto RMM API: "
if claude-code integration-health datto_rmm --test-auth; then
    echo "✅ Connected"
else
    echo "❌ Failed"
fi

# Check Bitdefender
echo -n "Bitdefender GravityZone API: "
if claude-code integration-health bitdefender --test-auth; then
    echo "✅ Connected"
else
    echo "❌ Failed"
fi

# Check sync status
echo -e "\n📊 Recent Sync Status:"
claude-code integration-status --last=5 --format=table

# Check for stuck jobs
echo -e "\n⚠️  Checking for stuck jobs..."
claude-code queue-health integrations --check-stuck --fix

echo -e "\n✅ Health check complete!"
```

---

## 7. 🧪 Integration Testing Scenarios

### 7.1 Datto Integration Tests
```yaml
# Arquivo: /tests/integration/datto_sync_scenarios.yml
# E2E tests for Datto RMM integration

scenarios:
  - name: "Complete Datto Device Sync"
    description: "Test full device synchronization flow"
    setup:
      - mock_datto_api:
          sites: 2
          devices_per_site: 10
      - create_tenant: { name: "MSP Test", integration_datto: true }
      
    steps:
      - id: trigger_sync
        action: POST /api/v1/integrations/datto_rmm/sync
        body:
          sync_type: assets
        expect:
          status: 202
          
      - id: wait_for_sync
        wait_for_job: "{{response.job_id}}"
        timeout: 60s
        
      - id: verify_devices
        action: GET /api/v1/integrations/datto/devices
        expect:
          data.length: 20
          
      - id: verify_cmdb
        action: GET /api/v1/cmdb/configuration-items
        params:
          filter[source]: datto_rmm
        expect:
          data.length: 20
          
  - name: "Datto Alert to Incident"
    description: "Test automatic incident creation from Datto alerts"
    setup:
      - mock_datto_alert:
          type: "Disk Space Critical"
          severity: "Critical"
          device: "PROD-SERVER-01"
          
    steps:
      - id: sync_alerts
        action: RUN_JOB ProcessDattoAlerts
        
      - id: verify_incident
        action: GET /api/v1/incidents
        params:
          filter[source]: datto_rmm
          filter[created_at]: "{{today}}"
        expect:
          data.length: 1
          data[0].attributes.title: contains "Disk Space Critical"
          data[0].attributes.priority: 1
          
      - id: verify_auto_assignment
        expect:
          data[0].attributes.assigned_team_id: not_null
          data[0].attributes.status: "Assigned"
          
  - name: "Datto Device Reconciliation"
    description: "Test matching Datto devices with existing CIs"
    setup:
      - create_ci:
          name: "WKS-001"
          serial_number: "ABC123"
          mac_address: "00:11:22:33:44:55"
      - mock_datto_device:
          hostname: "WKS-001"
          serial_number: "ABC123"
          mac_address: "00:11:22:33:44:55"
          
    steps:
      - id: sync_devices
        action: RUN_JOB SyncDattoDevices
        
      - id: verify_linkage
        action: GET /api/v1/integrations/datto/devices
        params:
          filter[hostname]: "WKS-001"
        expect:
          data[0].attributes.linked_ci_id: not_null
          
      - id: verify_ci_updated
        action: GET /api/v1/cmdb/configuration-items/{{ci_id}}
        expect:
          data.attributes.last_discovered: "{{today}}"
          data.attributes.source_integrations: contains "datto_rmm"
```

### 7.2 Bitdefender Integration Tests
```yaml
# Arquivo: /tests/integration/bitdefender_scenarios.yml
# E2E tests for Bitdefender GravityZone integration

scenarios:
  - name: "Bitdefender Security Incident Flow"
    description: "Test security incident to ITSM incident"
    setup:
      - mock_bitdefender_incident:
          type: "malware"
          threat_name: "Trojan.GenericKD.12345"
          severity: "high"
          endpoint: "LAPTOP-FINANCE-01"
          action_taken: "quarantined"
          
    steps:
      - id: sync_incidents
        action: RUN_JOB SyncBitdefenderIncidents
        
      - id: verify_security_incident
        action: GET /api/v1/incidents
        params:
          filter[category]: "security.malware"
          filter[created_at]: "{{today}}"
        expect:
          data.length: 1
          data[0].attributes.priority: 2
          data[0].attributes.title: contains "SECURITY"
          
      - id: verify_escalation
        wait: 5s
        action: GET /api/v1/incidents/{{incident_id}}
        expect:
          data.attributes.status: "Escalated"
          
      - id: verify_notifications
        action: GET /api/v1/notifications
        params:
          filter[type]: "security_incident"
        expect:
          data.length: gte 3 # Security team, manager, assigned team
          
  - name: "Endpoint Synchronization"
    description: "Test Bitdefender endpoint sync with Datto correlation"
    setup:
      - create_datto_device:
          hostname: "SRV-DC-01"
          mac_address: "AA:BB:CC:DD:EE:FF"
      - mock_bitdefender_endpoint:
          name: "SRV-DC-01"
          mac_address: "AA:BB:CC:DD:EE:FF"
          is_managed: true
          malware_status: "clean"
          
    steps:
      - id: sync_endpoints
        action: RUN_JOB SyncBitdefenderEndpoints
        
      - id: verify_endpoint
        action: GET /api/v1/integrations/bitdefender/endpoints
        params:
          filter[name]: "SRV-DC-01"
        expect:
          data[0].attributes.linked_datto_device_id: not_null
          
      - id: verify_security_posture
        action: GET /api/v1/cmdb/configuration-items
        params:
          filter[name]: "SRV-DC-01"
        expect:
          data[0].attributes.security_status: "protected"
          data[0].attributes.integrations: contains_all ["datto_rmm", "bitdefender"]
          
  - name: "Threat Intelligence Creation"
    description: "Test automatic KB article from new threats"
    setup:
      - mock_bitdefender_threat:
          threat_name: "NewRansomware.X"
          threat_type: "ransomware"
          first_occurrence: true
          
    steps:
      - id: process_threat
        action: RUN_JOB ProcessBitdefenderThreats
        
      - id: verify_kb_article
        action: GET /api/v1/knowledge/articles
        params:
          filter[auto_generated]: true
          filter[category]: "security.threats"
        expect:
          data.length: 1
          data[0].attributes.title: "Threat Intelligence: NewRansomware.X"
          
      - id: verify_iocs
        expect:
          data[0].attributes.content: contains "Indicators of Compromise"
          data[0].attributes.tags: contains "ransomware"
```

---

## 8. 🚦 Integration Monitoring & Alerts

### 8.1 Monitoring Configuration
```yaml
# Arquivo: /monitoring/integrations_monitoring.yml
# Prometheus alerts for integrations

groups:
  - name: integration_health
    interval: 30s
    rules:
      # Datto RMM Alerts
      - alert: DattoAPIDown
        expr: probe_success{job="blackbox",integration="datto_rmm"} == 0
        for: 5m
        labels:
          severity: critical
          integration: datto_rmm
        annotations:
          summary: "Datto RMM API is unreachable"
          description: "Cannot connect to Datto RMM API for {{ $value }} minutes"
          
      - alert: DattoSyncFailed
        expr: |
          rate(integration_sync_failures_total{integration="datto_rmm"}[5m]) > 0.1
        for: 10m
        labels:
          severity: warning
          integration: datto_rmm
        annotations:
          summary: "High Datto sync failure rate"
          description: "Sync failure rate: {{ $value | humanizePercentage }}"
          
      - alert: DattoSyncDelayed
        expr: |
          time() - integration_last_sync_timestamp{integration="datto_rmm"} > 21600
        labels:
          severity: warning
          integration: datto_rmm
        annotations:
          summary: "Datto sync is delayed"
          description: "Last successful sync was {{ $value | humanizeDuration }} ago"
          
      # Bitdefender Alerts
      - alert: BitdefenderAPIDown
        expr: probe_success{job="blackbox",integration="bitdefender"} == 0
        for: 5m
        labels:
          severity: critical
          integration: bitdefender
        annotations:
          summary: "Bitdefender GravityZone API is unreachable"
          
      - alert: SecurityIncidentBacklog
        expr: |
          integration_unprocessed_items{integration="bitdefender",type="incidents"} > 50
        for: 15m
        labels:
          severity: warning
          integration: bitdefender
        annotations:
          summary: "Unprocessed security incidents backlog"
          description: "{{ $value }} security incidents awaiting processing"
          
      - alert: ThreatDetectionSpike
        expr: |
          rate(bitdefender_threats_detected[1h]) > 10
        labels:
          severity: critical
          integration: bitdefender
        annotations:
          summary: "Unusual spike in threat detections"
          description: "{{ $value }} threats detected in the last hour"
          
  - name: integration_business_metrics
    interval: 60s
    rules:
      - alert: AssetDiscrepancy
        expr: |
          abs(count(datto_devices) - count(bitdefender_endpoints)) / count(datto_devices) > 0.1
        for: 30m
        labels:
          severity: info
        annotations:
          summary: "Asset count discrepancy between integrations"
          description: "{{ $value | humanizePercentage }} difference in device counts"
          
      - alert: AutoIncidentCreationDisabled
        expr: |
          integration_feature_enabled{feature="auto_incident_creation"} == 0
        for: 1h
        labels:
          severity: info
        annotations:
          summary: "Automatic incident creation is disabled"
          description: "Alerts are being received but not creating incidents"
```

### 8.2 Integration Dashboard
```yaml
# Arquivo: /monitoring/dashboards/integrations_dashboard.json
# Grafana dashboard for integration monitoring

{
  "dashboard": {
    "title": "Enterprise Integrations Dashboard",
    "panels": [
      {
        "title": "Integration Health Status",
        "type": "stat",
        "targets": [
          {
            "expr": "probe_success{integration=~\"datto_rmm|bitdefender\"}",
            "legendFormat": "{{ integration }}"
          }
        ]
      },
      {
        "title": "Sync Performance",
        "type": "graph",
        "targets": [
          {
            "expr": "rate(integration_sync_duration_seconds[5m])",
            "legendFormat": "{{ integration }} - {{ sync_type }}"
          }
        ]
      },
      {
        "title": "Records Synchronized (24h)",
        "type": "stat",
        "targets": [
          {
            "expr": "sum(increase(integration_records_synced[24h])) by (integration)",
            "legendFormat": "{{ integration }}"
          }
        ]
      },
      {
        "title": "Auto-Created Incidents",
        "type": "graph",
        "targets": [
          {
            "expr": "sum(rate(incidents_created{source=~\"datto_rmm|bitdefender\"}[1h])) by (source)",
            "legendFormat": "{{ source }}"
          }
        ]
      },
      {
        "title": "API Rate Limit Usage",
        "type": "gauge",
        "targets": [
          {
            "expr": "integration_api_rate_limit_remaining / integration_api_rate_limit_total * 100",
            "legendFormat": "{{ integration }}"
          }
        ]
      },
      {
        "title": "Security Threats by Type",
        "type": "piechart",
        "targets": [
          {
            "expr": "sum by (threat_type) (bitdefender_threats_total)",
            "legendFormat": "{{ threat_type }}"
          }
        ]
      }
    ]
  }
}
```

---

## 9. 📊 Business Value Metrics

### 9.1 Integration ROI Tracking
```yaml
# Arquivo: /analytics/integration_roi_metrics.yml
# Track business value of integrations

metrics:
  automation_metrics:
    - name: incidents_auto_created
      description: "Incidents automatically created from integrations"
      query: |
        COUNT(*) FROM incidents 
        WHERE source IN ('datto_rmm', 'bitdefender') 
        AND created_at >= NOW() - INTERVAL '30 days'
      value_per_incident: 15 # minutes saved
      
    - name: assets_auto_discovered
      description: "Assets automatically discovered and maintained"
      query: |
        COUNT(*) FROM configuration_items 
        WHERE discovery_source IN ('datto_rmm', 'bitdefender') 
        AND last_discovered >= NOW() - INTERVAL '30 days'
      value_per_asset: 10 # minutes saved on manual entry
      
    - name: security_threats_prevented
      description: "Security threats detected and prevented"
      query: |
        COUNT(*) FROM bitdefender_incidents 
        WHERE status = 'quarantined' 
        AND detection_time >= NOW() - INTERVAL '30 days'
      value_per_threat: 240 # minutes of incident response saved
      
  efficiency_metrics:
    - name: mean_time_to_detect
      description: "Average time from alert to incident creation"
      query: |
        AVG(EXTRACT(EPOCH FROM (i.created_at - da.triggered_at))/60) as minutes
        FROM incidents i
        JOIN datto_alerts da ON i.id = da.generated_incident_id
        WHERE i.created_at >= NOW() - INTERVAL '30 days'
      target: < 5 # minutes
      
    - name: asset_accuracy
      description: "Percentage of assets with complete information"
      query: |
        COUNT(CASE WHEN serial_number IS NOT NULL 
                    AND ip_addresses IS NOT NULL 
                    AND operating_system IS NOT NULL THEN 1 END) * 100.0 / COUNT(*)
        FROM configuration_items
        WHERE source_integrations && ARRAY['datto_rmm', 'bitdefender']
      target: > 95 # percent

dashboards:
  - name: "Integration ROI Dashboard"
    refresh: "5m"
    panels:
      - title: "Monthly Time Saved"
        calculation: |
          (incidents_auto_created * 15) + 
          (assets_auto_discovered * 10) + 
          (security_threats_prevented * 240)
        unit: "hours"
        
      - title: "Cost Savings"
        calculation: "monthly_time_saved * average_hourly_rate"
        unit: "currency"
        
      - title: "Integration Efficiency Score"
        calculation: |
          (asset_accuracy * 0.3) + 
          (100 - mean_time_to_detect) * 0.4 +
          (automation_rate * 0.3)
        unit: "percentage"
```

---

## 10. 🎯 Integration Best Practices

### 10.1 Security Guidelines
```yaml
# Arquivo: /docs/integration_security_guidelines.yml

security_practices:
  api_key_management:
    - store_in: "Environment variables or secret management service"
    - never_in: "Code, config files, or version control"
    - rotation: "Every 90 days"
    - access: "Least privilege principle"
    
  data_handling:
    - encryption: "All integration data encrypted at rest and in transit"
    - retention: "Raw sync data retained for 30 days only"
    - pii_handling: "Mask or encrypt sensitive fields"
    - audit: "All integration actions logged"
    
  network_security:
    - ip_whitelist: "Use IP allowlists where supported"
    - tls_version: "Minimum TLS 1.2"
    - certificate_validation: "Always validate SSL certificates"
    - timeout: "Implement reasonable timeouts (30-60s)"
    
  error_handling:
    - api_errors: "Never expose raw API errors to end users"
    - retry_logic: "Exponential backoff with jitter"
    - circuit_breaker: "Prevent cascade failures"
    - fallback: "Graceful degradation when integration unavailable"
```

### 10.2 Performance Optimization
```yaml
# Arquivo: /docs/integration_performance_guide.yml

performance_guidelines:
  sync_optimization:
    - batch_processing: "Process records in batches of 100-500"
    - incremental_sync: "Only sync changes since last run"
    - parallel_processing: "Use queue workers for parallel execution"
    - caching: "Cache frequently accessed data (5-15 min TTL)"
    
  api_efficiency:
    - field_selection: "Request only needed fields"
    - pagination: "Always use pagination for large datasets"
    - compression: "Enable gzip compression"
    - connection_pooling: "Reuse HTTP connections"
    
  database_optimization:
    - bulk_operations: "Use bulk insert/update operations"
    - index_usage: "Ensure queries use appropriate indexes"
    - archival: "Archive old sync data regularly"
    - vacuum: "Regular PostgreSQL maintenance"
```

---

## 📚 Conclusão - Enterprise Integration Edition

Este Blueprint v4.0 adiciona **integrações enterprise completas** com Datto RMM e Bitdefender GravityZone, transformando a plataforma ITSM em uma solução verdadeiramente integrada para MSPs e departamentos de TI.

### Principais Adições:

✅ **Arquitetura de Integrações Robusta**
- Schema dedicado para dados de integração
- Desacoplamento completo do domínio principal
- Sincronização assíncrona e resiliente

✅ **Segurança Enterprise**
- Gestão segura de API keys
- Auditoria completa de todas as ações
- Isolamento por tenant

✅ **Automação Inteligente**
- Criação automática de incidentes
- Descoberta automática de ativos
- Correlação entre plataformas

✅ **Monitoramento Completo**
- Dashboards específicos para integrações
- Alertas de saúde e performance
- Métricas de ROI

✅ **Processamento Escalável**
- Filas dedicadas para integração
- Retry automático com backoff
- Circuit breaker para resiliência

### Benefícios de Negócio:

1. **Redução de 80% no tempo** de entrada manual de dados
2. **Detecção 10x mais rápida** de incidentes de segurança
3. **Visibilidade completa** de todos os ativos de TI
4. **ROI mensurável** através de métricas automatizadas

Com estas integrações, a plataforma ITSM se torna uma **verdadeira central de operações de TI**, consolidando informações de múltiplas fontes e automatizando processos críticos.

---

## 11. 🎨 Design System - Defender360 Visual Identity

### 11.1 Brand Integration
```yaml
# Arquivo: /frontend/design-system/defender360.config.yml
# Design system baseado no brandbook Defender360

brand:
  name: "Defender360"
  tagline: "segurança, proteção, integração tecnológica"
  
  vision: |
    Ser reconhecidos como a solução líder em segurança cibernética integrada,
    redefinindo o padrão de proteção para empresas.
    
  mission: |
    Proteger empresas de todos os tamanhos contra ameaças digitais,
    oferecendo soluções integradas e automatizadas de segurança cibernética
    e gestão de TI.

# Color System
colors:
  # Primary Colors
  primary:
    deep_sea:
      name: "Mar Profundo"
      hex: "#043659"
      rgb: "4, 54, 89"
      pantone: "7463 PC"
      usage: "Fundos principais, cabeçalhos, elementos de segurança"
      
    tech_horizon:
      name: "Horizonte Tecnológico"  
      hex: "#0070AF"
      rgb: "0, 112, 175"
      pantone: "2196 PC"
      usage: "CTAs, botões primários, destaques de inovação"
      
    arctic_breeze:
      name: "Brisa Ártica"
      hex: "#60B4CD"
      rgb: "96, 180, 205"
      pantone: "6127 PC"
      usage: "Fundos secundários, estados de sucesso"
      
  # Secondary Colors  
  secondary:
    shadow_forest:
      name: "Floresta Sombra"
      hex: "#00434F"
      rgb: "0, 67, 79"
      pantone: "309 PC"
      usage: "Ícones de segurança, elementos de proteção"
      
    vital_energy:
      name: "Energia Vital"
      hex: "#009F8D"
      rgb: "0, 159, 141"
      pantone: "2399 CP"
      usage: "Estados de sucesso, indicadores positivos"
      
  # Neutral Colors
  neutral:
    white: "#FFFFFF"
    light_gray: "#F5F5F5"
    medium_gray: "#E0E0E0"
    dark_gray: "#757575"
    black: "#212121"
    
  # Semantic Colors (ITSM specific)
  semantic:
    # Incident Priority Colors
    priority:
      p1_critical: "#D32F2F" # Red - Critical
      p2_high: "#F57C00"     # Orange - High
      p3_medium: "#FBC02D"   # Yellow - Medium
      p4_low: "#388E3C"      # Green - Low
      
    # Status Colors
    status:
      new: "#0070AF"         # Tech Horizon
      in_progress: "#009F8D" # Vital Energy
      pending: "#FBC02D"     # Yellow
      resolved: "#388E3C"    # Green
      closed: "#757575"      # Gray
      
    # Alert Colors
    alerts:
      error: "#D32F2F"
      warning: "#F57C00"
      info: "#0070AF"
      success: "#009F8D"

# Typography System
typography:
  # Primary Font - Headers and UI
  primary:
    family: "Brain Wants"
    fallback: "Helvetica, Arial, sans-serif"
    weights:
      regular: 400
      medium: 500
      bold: 700
    usage:
      - "Headers (H1-H6)"
      - "Navigation items"
      - "Button text"
      - "Card titles"
      
  # Secondary Font - Display
  secondary:
    family: "Tipografix"
    fallback: "Arial, sans-serif"
    weights:
      regular: 400
      bold: 700
    usage:
      - "Dashboard metrics"
      - "Large numbers"
      - "Feature highlights"
      
  # Body Font - Content
  body:
    family: "PlayFair Display"
    fallback: "Georgia, serif"
    weights:
      regular: 400
      italic: 400
      bold: 700
    usage:
      - "Body text"
      - "Descriptions"
      - "Form labels"
      - "Table content"
      
  # Type Scale
  scale:
    h1: "3.5rem"    # 56px
    h2: "2.5rem"    # 40px
    h3: "2rem"      # 32px
    h4: "1.5rem"    # 24px
    h5: "1.25rem"   # 20px
    h6: "1rem"      # 16px
    body: "1rem"    # 16px
    small: "0.875rem" # 14px
    tiny: "0.75rem"  # 12px

# Component Styling
components:
  # Logo Usage
  logo:
    variations:
      - primary: "Full color on light background"
      - inverse: "White on dark background"
      - monochrome: "Single color for special uses"
    clear_space: "Minimum clear space = height of '3' in logo"
    minimum_size: "140px width for digital"
    
  # Buttons
  buttons:
    primary:
      background: "$colors.primary.tech_horizon"
      text: "$colors.neutral.white"
      hover: "darken($background, 10%)"
      active: "darken($background, 20%)"
      
    secondary:
      background: "$colors.secondary.vital_energy"
      text: "$colors.neutral.white"
      hover: "darken($background, 10%)"
      
    outline:
      border: "$colors.primary.deep_sea"
      text: "$colors.primary.deep_sea"
      hover_bg: "$colors.primary.deep_sea"
      hover_text: "$colors.neutral.white"
      
  # Cards
  cards:
    incident:
      border_left: "4px solid {priority_color}"
      background: "$colors.neutral.white"
      shadow: "0 2px 4px rgba(4, 54, 89, 0.1)"
      hover_shadow: "0 4px 8px rgba(4, 54, 89, 0.15)"
      
    dashboard:
      background: "$colors.neutral.white"
      header_bg: "$colors.primary.deep_sea"
      header_text: "$colors.neutral.white"
      
  # Forms
  forms:
    input:
      border: "1px solid $colors.neutral.medium_gray"
      focus_border: "$colors.primary.tech_horizon"
      error_border: "$colors.semantic.alerts.error"
      background: "$colors.neutral.white"
      
    label:
      color: "$colors.primary.deep_sea"
      required_indicator: "$colors.semantic.alerts.error"
      
  # Navigation
  navigation:
    background: "$colors.primary.deep_sea"
    text: "$colors.neutral.white"
    active_bg: "$colors.primary.tech_horizon"
    hover_bg: "rgba(0, 112, 175, 0.1)"
    
  # Tables
  tables:
    header_bg: "$colors.primary.deep_sea"
    header_text: "$colors.neutral.white"
    row_hover: "$colors.primary.arctic_breeze"
    border: "$colors.neutral.medium_gray"
    
# Layout System
layout:
  grid:
    columns: 12
    gutter: "24px"
    max_width: "1440px"
    
  spacing:
    xs: "4px"
    sm: "8px"
    md: "16px"
    lg: "24px"
    xl: "32px"
    xxl: "48px"
    
  breakpoints:
    mobile: "0px"
    tablet: "768px"
    desktop: "1024px"
    wide: "1440px"
    
  border_radius:
    small: "4px"
    medium: "8px"
    large: "16px"
    full: "9999px"
    
  shadows:
    small: "0 2px 4px rgba(4, 54, 89, 0.1)"
    medium: "0 4px 8px rgba(4, 54, 89, 0.15)"
    large: "0 8px 16px rgba(4, 54, 89, 0.2)"
    
# Animation & Transitions
motion:
  duration:
    fast: "150ms"
    normal: "250ms"
    slow: "350ms"
    
  easing:
    default: "cubic-bezier(0.4, 0, 0.2, 1)"
    ease_in: "cubic-bezier(0.4, 0, 1, 1)"
    ease_out: "cubic-bezier(0, 0, 0.2, 1)"
    
  transitions:
    color: "color $duration.fast $easing.default"
    shadow: "box-shadow $duration.normal $easing.default"
    transform: "transform $duration.normal $easing.default"
```

### 11.2 UI Component Library
```typescript
// Arquivo: /frontend/src/components/design-system/index.ts
// Defender360 component library implementation

import { defineComponent } from 'vue'

// Button Component
export const D360Button = defineComponent({
  name: 'D360Button',
  props: {
    variant: {
      type: String as () => 'primary' | 'secondary' | 'outline' | 'danger',
      default: 'primary'
    },
    size: {
      type: String as () => 'small' | 'medium' | 'large',
      default: 'medium'
    },
    loading: Boolean,
    disabled: Boolean
  },
  template: `
    <button
      :class="[
        'defender360-btn',
        'defender360-btn--' + variant,
        'defender360-btn--' + size,
        {
          'defender360-btn--loading': loading,
          'defender360-btn--disabled': disabled
        }
      ]"
      :disabled="disabled || loading"
    >
      <span v-if="loading" class="defender360-spinner"></span>
      <slot />
    </button>
  `
})

// Incident Card Component
export const D360IncidentCard = defineComponent({
  name: 'D360IncidentCard',
  props: {
    incident: {
      type: Object,
      required: true
    }
  },
  template: `
    <div 
      class="defender360-card incident-card"
      :class="'priority-' + incident.priority"
    >
      <div class="incident-header">
        <span class="incident-number">{{ incident.number }}</span>
        <D360StatusBadge :status="incident.status" />
      </div>
      <h3 class="incident-title">{{ incident.title }}</h3>
      <div class="incident-meta">
        <D360Icon name="clock" />
        <span>{{ formatTimeAgo(incident.created_at) }}</span>
        <D360Icon name="user" />
        <span>{{ incident.assigned_user?.name || 'Unassigned' }}</span>
      </div>
      <D360SLAIndicator :incident="incident" />
    </div>
  `
})

// Dashboard Widget Component
export const D360Widget = defineComponent({
  name: 'D360Widget',
  props: {
    title: String,
    value: [String, Number],
    trend: Object,
    icon: String,
    color: String
  },
  template: `
    <div class="defender360-widget" :style="{ '--widget-color': color }">
      <div class="widget-header">
        <D360Icon :name="icon" />
        <h4>{{ title }}</h4>
      </div>
      <div class="widget-value">{{ formatValue(value) }}</div>
      <div v-if="trend" class="widget-trend" :class="trend.direction">
        <D360Icon :name="trend.direction === 'up' ? 'arrow-up' : 'arrow-down'" />
        <span>{{ trend.value }}%</span>
      </div>
    </div>
  `
})
```

### 11.3 Tailwind Configuration
```javascript
// Arquivo: /frontend/tailwind.config.js
// Tailwind config with Defender360 design tokens

module.exports = {
  content: ['./src/**/*.{vue,js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        // Primary Palette
        'deep-sea': {
          DEFAULT: '#043659',
          50: '#E6EEF4',
          100: '#CCDDE9',
          200: '#99BBD3',
          300: '#6699BD',
          400: '#3377A7',
          500: '#043659',
          600: '#032B47',
          700: '#022035',
          800: '#011523',
          900: '#000A12'
        },
        'tech-horizon': {
          DEFAULT: '#0070AF',
          50: '#E6F2FA',
          100: '#CCE5F5',
          200: '#99CBEB',
          300: '#66B1E1',
          400: '#3397D7',
          500: '#0070AF',
          600: '#005A8C',
          700: '#004369',
          800: '#002D46',
          900: '#001623'
        },
        'arctic-breeze': {
          DEFAULT: '#60B4CD',
          50: '#F0F8FB',
          100: '#E1F1F7',
          200: '#C3E3EF',
          300: '#A5D5E7',
          400: '#87C7DF',
          500: '#60B4CD',
          600: '#4D90A4',
          700: '#3A6C7B',
          800: '#274852',
          900: '#132429'
        },
        'shadow-forest': {
          DEFAULT: '#00434F',
          50: '#E6F0F2',
          100: '#CCE1E5',
          200: '#99C3CB',
          300: '#66A5B1',
          400: '#338797',
          500: '#00434F',
          600: '#00363F',
          700: '#00282F',
          800: '#001B20',
          900: '#000D10'
        },
        'vital-energy': {
          DEFAULT: '#009F8D',
          50: '#E6F7F5',
          100: '#CCEFEB',
          200: '#99DFD7',
          300: '#66CFC3',
          400: '#33BFAF',
          500: '#009F8D',
          600: '#007F71',
          700: '#005F55',
          800: '#004038',
          900: '#00201C'
        }
      },
      fontFamily: {
        'brain': ['Brain Wants', 'Helvetica', 'Arial', 'sans-serif'],
        'tipografix': ['Tipografix', 'Arial', 'sans-serif'],
        'playfair': ['PlayFair Display', 'Georgia', 'serif']
      },
      boxShadow: {
        'defender-sm': '0 2px 4px rgba(4, 54, 89, 0.1)',
        'defender-md': '0 4px 8px rgba(4, 54, 89, 0.15)',
        'defender-lg': '0 8px 16px rgba(4, 54, 89, 0.2)',
        'defender-xl': '0 16px 32px rgba(4, 54, 89, 0.25)'
      },
      animation: {
        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
        'slide-in': 'slideIn 0.3s ease-out',
        'fade-in': 'fadeIn 0.3s ease-out'
      }
    }
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography')
  ]
}
```

### 11.4 Component Examples
```vue
<!-- Arquivo: /frontend/src/views/Dashboard.vue -->
<!-- Dashboard com identidade visual Defender360 -->

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation Header -->
    <nav class="bg-deep-sea-500 text-white shadow-defender-lg">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex items-center">
            <img 
              src="/logo-defender360.svg" 
              alt="Defender360" 
              class="h-10"
            />
            <div class="ml-10 flex items-baseline space-x-4">
              <a 
                v-for="item in navigation" 
                :key="item.name"
                :href="item.href"
                class="px-3 py-2 rounded-md text-sm font-brain font-medium hover:bg-tech-horizon-500 transition-colors"
                :class="item.current ? 'bg-tech-horizon-500' : ''"
              >
                {{ item.name }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Dashboard Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <!-- Metrics Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <D360Widget
          title="Incidentes Ativos"
          :value="metrics.activeIncidents"
          :trend="{ direction: 'down', value: 12 }"
          icon="alert-circle"
          color="#D32F2F"
        />
        <D360Widget
          title="Taxa de Resolução"
          :value="metrics.resolutionRate + '%'"
          :trend="{ direction: 'up', value: 5 }"
          icon="check-circle"
          color="#009F8D"
        />
        <D360Widget
          title="SLA Compliance"
          :value="metrics.slaCompliance + '%'"
          :trend="{ direction: 'up', value: 3 }"
          icon="clock"
          color="#0070AF"
        />
        <D360Widget
          title="Ameaças Bloqueadas"
          :value="metrics.threatsBlocked"
          :trend="{ direction: 'up', value: 28 }"
          icon="shield"
          color="#00434F"
        />
      </div>

      <!-- Incident Queue -->
      <div class="bg-white shadow-defender-md rounded-lg overflow-hidden">
        <div class="bg-deep-sea-500 px-6 py-4">
          <h2 class="text-xl font-brain font-bold text-white">
            Fila de Incidentes
          </h2>
        </div>
        <div class="p-6">
          <div class="space-y-4">
            <D360IncidentCard 
              v-for="incident in incidents"
              :key="incident.id"
              :incident="incident"
              @click="openIncident(incident)"
            />
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<style>
/* Defender360 Custom Styles */
.defender360-btn {
  @apply inline-flex items-center justify-center font-brain font-medium 
         rounded-md transition-all duration-250 ease-out
         focus:outline-none focus:ring-2 focus:ring-offset-2;
}

.defender360-btn--primary {
  @apply bg-tech-horizon-500 text-white hover:bg-tech-horizon-600 
         focus:ring-tech-horizon-500;
}

.defender360-btn--secondary {
  @apply bg-vital-energy-500 text-white hover:bg-vital-energy-600 
         focus:ring-vital-energy-500;
}

.defender360-btn--medium {
  @apply px-4 py-2 text-base;
}

.incident-card {
  @apply bg-white rounded-lg shadow-defender-sm hover:shadow-defender-md 
         transition-shadow duration-250 p-4 cursor-pointer
         border-l-4;
}

.incident-card.priority-1 {
  @apply border-red-600;
}

.incident-card.priority-2 {
  @apply border-orange-600;
}

.incident-card.priority-3 {
  @apply border-yellow-600;
}

.incident-card.priority-4 {
  @apply border-green-600;
}

.defender360-widget {
  @apply bg-white rounded-lg shadow-defender-sm p-6 
         hover:shadow-defender-md transition-shadow;
}

.widget-value {
  @apply text-3xl font-tipografix font-bold text-deep-sea-500 mt-2;
}

.widget-trend {
  @apply flex items-center mt-2 text-sm font-medium;
}

.widget-trend.up {
  @apply text-vital-energy-500;
}

.widget-trend.down {
  @apply text-red-600;
}
</style>
```

### 11.5 Design Documentation
```markdown
# Arquivo: /docs/design-guidelines.md
# Defender360 Design Guidelines for ITSM Platform

## Visual Identity Integration

### 1. Logo Usage
- Always maintain clear space around the logo
- Use the full-color version on light backgrounds
- Use the white version on dark backgrounds (deep-sea)
- Minimum size: 140px width for web applications

### 2. Color Application

#### Primary Actions
- **Tech Horizon Blue (#0070AF)**: Primary buttons, links, active states
- **Vital Energy Green (#009F8D)**: Success states, positive actions

#### System Feedback
- **Error**: #D32F2F (Red)
- **Warning**: #F57C00 (Orange)
- **Info**: Tech Horizon Blue
- **Success**: Vital Energy Green

#### Priority Indicators
- P1 Critical: #D32F2F (Red) 
- P2 High: #F57C00 (Orange)
- P3 Medium: #FBC02D (Yellow)
- P4 Low: #388E3C (Green)

### 3. Typography Hierarchy

#### Headers
- H1: Brain Wants Bold, 56px (3.5rem)
- H2: Brain Wants Medium, 40px (2.5rem)
- H3: Brain Wants Medium, 32px (2rem)

#### Body Text
- Primary: PlayFair Display Regular, 16px
- Secondary: PlayFair Display Regular, 14px
- Captions: PlayFair Display Regular, 12px

#### Data Display
- Metrics: Tipografix Bold
- Table Headers: Brain Wants Medium
- Form Labels: Brain Wants Regular

### 4. Component Patterns

#### Cards
- White background with subtle shadow
- 8px border radius
- Left border for status/priority indication
- Hover: Elevated shadow

#### Forms
- Clean, minimal design
- Tech Horizon focus states
- Clear error messaging in red
- Sufficient spacing between elements

#### Navigation
- Deep Sea background
- White text with hover states
- Active state: Tech Horizon background

### 5. Iconography
- Use line icons for consistency
- Maintain 24px base size
- Color icons to match context
- Ensure sufficient contrast

### 6. Spacing System
- Base unit: 8px
- Consistent padding: 16px, 24px, 32px
- Maintain visual breathing room
- Use white space effectively

### 7. Motion Principles
- Subtle, purposeful animations
- 250ms standard duration
- Ease-out curve for natural feel
- No distracting movements
```

---
