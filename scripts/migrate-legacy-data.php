#!/usr/bin/env php
<?php

/**
 * Legacy Data Migration Script
 * 
 * This script migrates data from legacy systems to the new ITSM platform.
 * Supports various data sources including CSV, JSON, and database connections.
 */

require_once __DIR__ . '/../backend/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use Illuminate\Support\Str;

class LegacyDataMigrator
{
    private $app;
    private $errors = [];
    private $mappings = [];
    private $stats = [
        'processed' => 0,
        'success' => 0,
        'failed' => 0,
        'skipped' => 0
    ];

    public function __construct()
    {
        $this->app = require_once __DIR__ . '/../backend/bootstrap/app.php';
        $kernel = $this->app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        
        $this->loadMappings();
    }

    /**
     * Load field mappings configuration
     */
    private function loadMappings()
    {
        $this->mappings = [
            'incidents' => [
                'ticket_id' => 'legacy_id',
                'ticket_number' => 'number',
                'summary' => 'title',
                'details' => 'description',
                'severity' => ['field' => 'priority', 'transform' => 'mapPriority'],
                'ticket_status' => ['field' => 'status', 'transform' => 'mapStatus'],
                'created_date' => ['field' => 'created_at', 'transform' => 'parseDate'],
                'resolved_date' => ['field' => 'resolved_at', 'transform' => 'parseDate'],
                'assigned_user' => ['field' => 'assigned_to', 'transform' => 'mapUser'],
                'category_name' => 'category'
            ],
            'users' => [
                'user_id' => 'legacy_id',
                'full_name' => 'name',
                'email_address' => 'email',
                'dept' => 'department',
                'phone_number' => 'phone',
                'user_role' => ['field' => 'role', 'transform' => 'mapRole'],
                'active_flag' => ['field' => 'is_active', 'transform' => 'mapBoolean']
            ],
            'assets' => [
                'asset_id' => 'legacy_id',
                'asset_name' => 'name',
                'asset_type' => ['field' => 'ci_type', 'transform' => 'mapAssetType'],
                'serial_no' => 'serial_number',
                'tag_number' => 'asset_tag',
                'status_code' => ['field' => 'status', 'transform' => 'mapAssetStatus'],
                'location_code' => 'location'
            ]
        ];
    }

    /**
     * Migrate data from CSV file
     */
    public function migrateFromCsv($file, $type, $tenantId, $options = [])
    {
        $this->output("Starting CSV migration for {$type}...");
        
        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return false;
        }

        $csv = Reader::createFromPath($file, 'r');
        $csv->setHeaderOffset(0);
        
        $records = $csv->getRecords();
        $mapping = $this->mappings[$type] ?? null;
        
        if (!$mapping) {
            $this->error("No mapping found for type: {$type}");
            return false;
        }

        DB::beginTransaction();

        try {
            foreach ($records as $offset => $record) {
                $this->stats['processed']++;
                
                // Skip empty records
                if (empty(array_filter($record))) {
                    $this->stats['skipped']++;
                    continue;
                }

                $data = $this->transformRecord($record, $mapping);
                $data['tenant_id'] = $tenantId;
                
                // Add timestamps if not present
                if (!isset($data['created_at'])) {
                    $data['created_at'] = now();
                }
                $data['updated_at'] = now();

                // Validate and insert
                if ($this->validateRecord($data, $type)) {
                    DB::table($type)->insert($data);
                    $this->stats['success']++;
                    
                    if ($this->stats['success'] % 100 === 0) {
                        $this->output("Processed {$this->stats['success']} records...");
                    }
                } else {
                    $this->stats['failed']++;
                    $this->error("Failed to validate record at line " . ($offset + 2));
                }
            }

            DB::commit();
            $this->outputStats();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Migration failed: " . $e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Migrate data from legacy database
     */
    public function migrateFromDatabase($config, $type, $tenantId, $options = [])
    {
        $this->output("Starting database migration for {$type}...");
        
        // Connect to legacy database
        $legacyDb = $this->connectToLegacyDatabase($config);
        if (!$legacyDb) {
            return false;
        }

        $mapping = $this->mappings[$type] ?? null;
        if (!$mapping) {
            $this->error("No mapping found for type: {$type}");
            return false;
        }

        // Build query
        $query = $this->buildLegacyQuery($type, $options);
        $records = $legacyDb->select($query);

        DB::beginTransaction();

        try {
            foreach ($records as $record) {
                $this->stats['processed']++;
                
                $data = $this->transformRecord((array)$record, $mapping);
                $data['tenant_id'] = $tenantId;
                
                if (!isset($data['created_at'])) {
                    $data['created_at'] = now();
                }
                $data['updated_at'] = now();

                if ($this->validateRecord($data, $type)) {
                    // Check for duplicates
                    if ($this->checkDuplicate($data, $type)) {
                        $this->stats['skipped']++;
                        continue;
                    }

                    DB::table($type)->insert($data);
                    $this->stats['success']++;
                    
                    // Create relationships
                    $this->createRelationships($record, $data, $type);
                    
                    if ($this->stats['success'] % 100 === 0) {
                        $this->output("Processed {$this->stats['success']} records...");
                    }
                } else {
                    $this->stats['failed']++;
                }
            }

            DB::commit();
            $this->outputStats();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Migration failed: " . $e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Transform legacy record to new format
     */
    private function transformRecord($record, $mapping)
    {
        $transformed = [];
        
        foreach ($mapping as $oldField => $newField) {
            if (!isset($record[$oldField])) {
                continue;
            }

            $value = $record[$oldField];
            
            if (is_array($newField)) {
                $fieldName = $newField['field'];
                $transformer = $newField['transform'];
                $value = $this->$transformer($value);
            } else {
                $fieldName = $newField;
            }
            
            $transformed[$fieldName] = $value;
        }
        
        return $transformed;
    }

    /**
     * Field transformation methods
     */
    private function mapPriority($value)
    {
        $map = [
            '1' => 'critical',
            '2' => 'high',
            '3' => 'medium',
            '4' => 'low',
            'urgent' => 'critical',
            'high' => 'high',
            'normal' => 'medium',
            'low' => 'low'
        ];
        
        return $map[strtolower($value)] ?? 'medium';
    }

    private function mapStatus($value)
    {
        $map = [
            'open' => 'new',
            'assigned' => 'in_progress',
            'pending' => 'in_progress',
            'solved' => 'resolved',
            'closed' => 'closed'
        ];
        
        return $map[strtolower($value)] ?? 'new';
    }

    private function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }
        
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function mapUser($value)
    {
        // Look up user by email or legacy ID
        $user = DB::table('users')
            ->where('email', $value)
            ->orWhere('legacy_id', $value)
            ->first();
        
        return $user ? $user->id : null;
    }

    private function mapRole($value)
    {
        $map = [
            'administrator' => 'admin',
            'supervisor' => 'manager',
            'agent' => 'technician',
            'end_user' => 'user'
        ];
        
        return $map[strtolower($value)] ?? 'user';
    }

    private function mapBoolean($value)
    {
        return in_array(strtolower($value), ['yes', 'y', '1', 'true', 'active']);
    }

    private function mapAssetType($value)
    {
        $map = [
            'computer' => 'Workstation',
            'server' => 'Server',
            'network' => 'Network Device',
            'software' => 'Software',
            'service' => 'Service'
        ];
        
        return $map[strtolower($value)] ?? 'Other';
    }

    private function mapAssetStatus($value)
    {
        $map = [
            'active' => 'operational',
            'inactive' => 'retired',
            'repair' => 'maintenance',
            'broken' => 'failed'
        ];
        
        return $map[strtolower($value)] ?? 'operational';
    }

    /**
     * Validate record before insertion
     */
    private function validateRecord($data, $type)
    {
        $requiredFields = [
            'incidents' => ['title', 'description', 'priority', 'status'],
            'users' => ['name', 'email'],
            'assets' => ['name', 'ci_type', 'status']
        ];
        
        $required = $requiredFields[$type] ?? [];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->error("Missing required field: {$field}");
                return false;
            }
        }
        
        return true;
    }

    /**
     * Check for duplicate records
     */
    private function checkDuplicate($data, $type)
    {
        $uniqueFields = [
            'incidents' => 'number',
            'users' => 'email',
            'assets' => 'serial_number'
        ];
        
        $field = $uniqueFields[$type] ?? null;
        if (!$field || empty($data[$field])) {
            return false;
        }
        
        return DB::table($type)
            ->where($field, $data[$field])
            ->where('tenant_id', $data['tenant_id'])
            ->exists();
    }

    /**
     * Create relationships after migration
     */
    private function createRelationships($legacyRecord, $newRecord, $type)
    {
        // Example: Link incidents to related CIs
        if ($type === 'incidents' && !empty($legacyRecord->related_assets)) {
            $assetIds = explode(',', $legacyRecord->related_assets);
            foreach ($assetIds as $assetId) {
                $ci = DB::table('configuration_items')
                    ->where('legacy_id', trim($assetId))
                    ->first();
                
                if ($ci) {
                    DB::table('incident_ci')->insert([
                        'incident_id' => $newRecord['id'],
                        'ci_id' => $ci->id,
                        'created_at' => now()
                    ]);
                }
            }
        }
    }

    /**
     * Connect to legacy database
     */
    private function connectToLegacyDatabase($config)
    {
        try {
            config(['database.connections.legacy' => $config]);
            return DB::connection('legacy');
        } catch (\Exception $e) {
            $this->error("Failed to connect to legacy database: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Build query for legacy data
     */
    private function buildLegacyQuery($type, $options)
    {
        $queries = [
            'incidents' => "SELECT * FROM tickets WHERE created_date >= ?",
            'users' => "SELECT * FROM users WHERE active_flag = 1",
            'assets' => "SELECT * FROM assets WHERE status_code != 'DISPOSED'"
        ];
        
        $query = $queries[$type] ?? "SELECT * FROM {$type}";
        
        // Add date filter if specified
        if (!empty($options['from_date'])) {
            $query .= " WHERE created_date >= '{$options['from_date']}'";
        }
        
        // Add limit if specified
        if (!empty($options['limit'])) {
            $query .= " LIMIT {$options['limit']}";
        }
        
        return $query;
    }

    /**
     * Output methods
     */
    private function output($message)
    {
        echo "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
    }

    private function error($message)
    {
        $this->errors[] = $message;
        echo "[ERROR] " . $message . "\n";
    }

    private function outputStats()
    {
        echo "\nMigration Statistics:\n";
        echo "Total Processed: {$this->stats['processed']}\n";
        echo "Successful: {$this->stats['success']}\n";
        echo "Failed: {$this->stats['failed']}\n";
        echo "Skipped: {$this->stats['skipped']}\n";
        
        if (!empty($this->errors)) {
            echo "\nErrors encountered:\n";
            foreach (array_unique($this->errors) as $error) {
                echo "- {$error}\n";
            }
        }
    }
}

// CLI Usage
if (php_sapi_name() === 'cli') {
    $migrator = new LegacyDataMigrator();
    
    // Parse command line arguments
    $options = getopt('', ['type:', 'source:', 'file:', 'tenant:', 'limit:', 'from-date:']);
    
    if (empty($options['type']) || empty($options['source']) || empty($options['tenant'])) {
        echo "Usage: php migrate-legacy-data.php --type=<type> --source=<csv|db> --tenant=<id> [options]\n";
        echo "Options:\n";
        echo "  --file=<path>      CSV file path (required for CSV source)\n";
        echo "  --limit=<number>   Limit number of records\n";
        echo "  --from-date=<date> Migrate records from this date\n";
        exit(1);
    }
    
    $type = $options['type'];
    $source = $options['source'];
    $tenantId = $options['tenant'];
    
    if ($source === 'csv') {
        if (empty($options['file'])) {
            echo "Error: --file is required for CSV source\n";
            exit(1);
        }
        $migrator->migrateFromCsv($options['file'], $type, $tenantId, $options);
    } else if ($source === 'db') {
        // Database configuration (should be in environment or config file)
        $dbConfig = [
            'driver' => 'mysql',
            'host' => env('LEGACY_DB_HOST', 'localhost'),
            'database' => env('LEGACY_DB_NAME', 'legacy_itsm'),
            'username' => env('LEGACY_DB_USER', 'root'),
            'password' => env('LEGACY_DB_PASS', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ];
        $migrator->migrateFromDatabase($dbConfig, $type, $tenantId, $options);
    } else {
        echo "Error: Invalid source. Use 'csv' or 'db'\n";
        exit(1);
    }
}