-- ITSM Platform Database Initialization Script

-- Create extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";
CREATE EXTENSION IF NOT EXISTS "btree_gist";

-- Create additional schemas if needed
CREATE SCHEMA IF NOT EXISTS audit;
CREATE SCHEMA IF NOT EXISTS analytics;

-- Set default timezone
SET timezone = 'UTC';

-- Create audit log table
CREATE TABLE IF NOT EXISTS audit.activity_log (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    tenant_id UUID,
    user_id UUID,
    event_type VARCHAR(100) NOT NULL,
    model_type VARCHAR(100),
    model_id UUID,
    old_values JSONB,
    new_values JSONB,
    metadata JSONB,
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for audit log
CREATE INDEX idx_audit_activity_log_tenant_id ON audit.activity_log(tenant_id);
CREATE INDEX idx_audit_activity_log_user_id ON audit.activity_log(user_id);
CREATE INDEX idx_audit_activity_log_event_type ON audit.activity_log(event_type);
CREATE INDEX idx_audit_activity_log_created_at ON audit.activity_log(created_at DESC);
CREATE INDEX idx_audit_activity_log_model ON audit.activity_log(model_type, model_id);

-- Create performance monitoring table
CREATE TABLE IF NOT EXISTS analytics.performance_metrics (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    tenant_id UUID,
    metric_type VARCHAR(50) NOT NULL,
    endpoint VARCHAR(255),
    duration_ms INTEGER,
    memory_usage_mb DECIMAL(10,2),
    cpu_usage_percent DECIMAL(5,2),
    db_queries_count INTEGER,
    cache_hits INTEGER,
    cache_misses INTEGER,
    metadata JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for performance metrics
CREATE INDEX idx_analytics_performance_metrics_tenant_id ON analytics.performance_metrics(tenant_id);
CREATE INDEX idx_analytics_performance_metrics_created_at ON analytics.performance_metrics(created_at DESC);
CREATE INDEX idx_analytics_performance_metrics_metric_type ON analytics.performance_metrics(metric_type);
CREATE INDEX idx_analytics_performance_metrics_endpoint ON analytics.performance_metrics(endpoint);

-- Create function for updating updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Create function for tenant isolation in RLS (Row Level Security)
CREATE OR REPLACE FUNCTION tenant_isolation_policy(tenant_id_column TEXT)
RETURNS BOOLEAN AS $$
BEGIN
    RETURN current_setting('app.current_tenant_id', TRUE)::UUID = tenant_id_column::UUID;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Grant necessary permissions
GRANT USAGE ON SCHEMA audit TO itsm_user;
GRANT USAGE ON SCHEMA analytics TO itsm_user;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA audit TO itsm_user;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA analytics TO itsm_user;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA audit TO itsm_user;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA analytics TO itsm_user;

-- Set default search path
ALTER DATABASE itsm_platform SET search_path TO public, audit, analytics;

-- Create composite types for common structures
CREATE TYPE incident_priority AS ENUM ('low', 'medium', 'high', 'critical');
CREATE TYPE incident_status AS ENUM ('open', 'in_progress', 'pending', 'resolved', 'closed', 'cancelled');
CREATE TYPE ticket_type AS ENUM ('incident', 'service_request', 'problem', 'change');
CREATE TYPE user_role AS ENUM ('admin', 'manager', 'agent', 'viewer');
CREATE TYPE tenant_status AS ENUM ('active', 'suspended', 'cancelled', 'trial');
CREATE TYPE tenant_plan AS ENUM ('starter', 'professional', 'enterprise');

-- Output success message
DO $$
BEGIN
    RAISE NOTICE 'ITSM Platform database initialization completed successfully';
END $$;