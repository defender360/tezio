"""
Anomaly Detection Schemas

Pydantic schemas for anomaly detection requests and responses including:
- System metrics for anomaly detection
- Anomaly detection results
- Threshold configurations  
- Anomaly alerts and notifications
- Historical anomaly data
"""

from pydantic import BaseModel, Field, validator
from typing import List, Optional, Dict, Any, Union
from datetime import datetime, timedelta
from enum import Enum


class AnomalyType(str, Enum):
    """Types of anomalies that can be detected"""
    PERFORMANCE = "performance"
    VOLUME = "volume" 
    PATTERN = "pattern"
    TREND = "trend"
    THRESHOLD = "threshold"
    BEHAVIORAL = "behavioral"
    STATISTICAL = "statistical"


class AnomalySeverity(str, Enum):
    """Severity levels for detected anomalies"""
    LOW = "low"
    MEDIUM = "medium"
    HIGH = "high"
    CRITICAL = "critical"


class MetricType(str, Enum):
    """Types of metrics that can be analyzed"""
    SYSTEM = "system"
    APPLICATION = "application"
    NETWORK = "network"
    BUSINESS = "business"
    SECURITY = "security"
    USER = "user"


class DetectionMethod(str, Enum):
    """Methods for anomaly detection"""
    THRESHOLD = "threshold"
    STATISTICAL = "statistical"
    ISOLATION_FOREST = "isolation_forest"
    TREND_ANALYSIS = "trend_analysis"
    PATTERN_MATCHING = "pattern_matching"
    BEHAVIORAL_ANALYSIS = "behavioral_analysis"


class AnomalyDetectionRequest(BaseModel):
    """Request model for anomaly detection"""
    
    metrics: Dict[str, Union[float, int]] = Field(
        ...,
        description="Metrics to analyze for anomalies"
    )
    
    metric_type: MetricType = Field(
        default=MetricType.SYSTEM,
        description="Type of metrics being analyzed"
    )
    
    detection_methods: Optional[List[DetectionMethod]] = Field(
        None,
        description="Specific detection methods to use"
    )
    
    sensitivity: Optional[float] = Field(
        None,
        ge=0.1,
        le=1.0,
        description="Detection sensitivity (0.1 = very sensitive, 1.0 = less sensitive)"
    )
    
    historical_context: bool = Field(
        default=True,
        description="Use historical data for context"
    )
    
    time_window: Optional[str] = Field(
        None,
        description="Time window for analysis (e.g., '1h', '24h', '7d')"
    )
    
    metadata: Optional[Dict[str, Any]] = Field(
        None,
        description="Additional metadata for context"
    )
    
    @validator('metrics')
    def validate_metrics(cls, v):
        """Validate that metrics contain numeric values"""
        if not v:
            raise ValueError("metrics cannot be empty")
        
        for key, value in v.items():
            if not isinstance(value, (int, float)):
                raise ValueError(f"metric '{key}' must be numeric, got {type(value)}")
        
        return v
    
    class Config:
        json_schema_extra = {
            "example": {
                "metrics": {
                    "cpu_usage": 85.5,
                    "memory_usage": 78.2,
                    "disk_io": 450.0,
                    "network_throughput": 1250.5,
                    "response_time": 2.8,
                    "error_rate": 0.03
                },
                "metric_type": "system",
                "detection_methods": ["threshold", "statistical", "trend_analysis"],
                "sensitivity": 0.7,
                "historical_context": True,
                "time_window": "1h",
                "metadata": {
                    "server_id": "web-01",
                    "environment": "production"
                }
            }
        }


class AnomalyResult(BaseModel):
    """Individual anomaly detection result"""
    
    anomaly_id: str = Field(..., description="Unique anomaly identifier")
    anomaly_type: AnomalyType = Field(..., description="Type of anomaly detected")
    severity: AnomalySeverity = Field(..., description="Severity level")
    
    # Anomaly details
    score: float = Field(..., ge=0, le=1, description="Anomaly score (0-1, higher = more anomalous)")
    confidence: float = Field(..., ge=0, le=1, description="Confidence in detection")
    
    description: str = Field(..., description="Human-readable description of anomaly")
    
    # Affected metrics
    affected_metrics: List[str] = Field(..., description="Metrics that contributed to anomaly")
    metric_values: Dict[str, float] = Field(..., description="Current values of affected metrics")
    
    # Detection method and context
    detection_method: DetectionMethod = Field(..., description="Method used to detect anomaly")
    
    # Time information
    detected_at: datetime = Field(default_factory=datetime.utcnow, description="When anomaly was detected")
    start_time: Optional[datetime] = Field(None, description="When anomaly likely started")
    end_time: Optional[datetime] = Field(None, description="When anomaly ended (if applicable)")
    
    # Additional context
    baseline_values: Optional[Dict[str, float]] = Field(
        None,
        description="Baseline/expected values for comparison"
    )
    
    threshold_values: Optional[Dict[str, float]] = Field(
        None,
        description="Threshold values that were exceeded"
    )
    
    historical_comparison: Optional[Dict[str, Any]] = Field(
        None,
        description="Comparison with historical data"
    )
    
    # Recommendations and actions
    recommendations: List[str] = Field(
        default_factory=list,
        description="Recommended actions to address the anomaly"
    )
    
    suggested_actions: List[str] = Field(
        default_factory=list,
        description="Specific actions to take"
    )
    
    # Additional data
    context: Optional[Dict[str, Any]] = Field(
        None,
        description="Additional context information"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "anomaly_id": "anom_123456",
                "anomaly_type": "threshold",
                "severity": "high",
                "score": 0.87,
                "confidence": 0.95,
                "description": "CPU usage exceeded critical threshold of 90%",
                "affected_metrics": ["cpu_usage"],
                "metric_values": {"cpu_usage": 95.2},
                "detection_method": "threshold",
                "baseline_values": {"cpu_usage": 45.0},
                "threshold_values": {"cpu_usage": 90.0},
                "recommendations": [
                    "Check for resource-intensive processes",
                    "Consider scaling resources",
                    "Review recent changes"
                ]
            }
        }


class AnomalyDetectionResponse(BaseModel):
    """Response model for anomaly detection"""
    
    request_id: str = Field(..., description="Original request identifier")
    
    # Detection results
    anomalies: List[AnomalyResult] = Field(
        default_factory=list,
        description="List of detected anomalies"
    )
    
    total_anomalies: int = Field(..., description="Total number of anomalies detected")
    
    # Severity breakdown
    severity_breakdown: Dict[str, int] = Field(
        ...,
        description="Count of anomalies by severity level"
    )
    
    # Overall assessment
    overall_health_score: float = Field(
        ...,
        ge=0,
        le=1,
        description="Overall system health score (0=poor, 1=excellent)"
    )
    
    risk_level: str = Field(..., description="Overall risk level")
    
    # Processing metadata
    processing_time_ms: int = Field(..., description="Processing time in milliseconds")
    
    detection_summary: Dict[str, Any] = Field(
        ...,
        description="Summary of detection process"
    )
    
    # Timestamp
    analyzed_at: datetime = Field(
        default_factory=datetime.utcnow,
        description="When analysis was performed"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "request_id": "req_123456",
                "anomalies": [],
                "total_anomalies": 2,
                "severity_breakdown": {
                    "critical": 0,
                    "high": 1,
                    "medium": 1,
                    "low": 0
                },
                "overall_health_score": 0.73,
                "risk_level": "medium",
                "processing_time_ms": 850,
                "detection_summary": {
                    "methods_used": ["threshold", "statistical"],
                    "metrics_analyzed": 6,
                    "baseline_available": True
                }
            }
        }


class ThresholdConfiguration(BaseModel):
    """Configuration for threshold-based anomaly detection"""
    
    metric_name: str = Field(..., description="Name of the metric")
    
    # Threshold values
    warning_threshold: Optional[float] = Field(None, description="Warning threshold value")
    critical_threshold: Optional[float] = Field(None, description="Critical threshold value")
    
    # Threshold type
    threshold_type: str = Field(
        default="upper",
        description="Type of threshold (upper, lower, range)"
    )
    
    # Range thresholds (for range type)
    lower_bound: Optional[float] = Field(None, description="Lower bound for range threshold")
    upper_bound: Optional[float] = Field(None, description="Upper bound for range threshold")
    
    # Dynamic thresholds
    use_dynamic_threshold: bool = Field(
        default=False,
        description="Use dynamic thresholds based on historical data"
    )
    
    dynamic_multiplier: Optional[float] = Field(
        None,
        description="Multiplier for dynamic threshold calculation"
    )
    
    # Metadata
    description: Optional[str] = Field(None, description="Description of the threshold")
    enabled: bool = Field(default=True, description="Whether threshold is enabled")
    
    created_at: datetime = Field(default_factory=datetime.utcnow)
    updated_at: Optional[datetime] = Field(None)
    
    class Config:
        json_schema_extra = {
            "example": {
                "metric_name": "cpu_usage",
                "warning_threshold": 80.0,
                "critical_threshold": 95.0,
                "threshold_type": "upper",
                "use_dynamic_threshold": False,
                "description": "CPU usage thresholds for server monitoring",
                "enabled": True
            }
        }


class ThresholdConfigurationRequest(BaseModel):
    """Request to update threshold configurations"""
    
    configurations: List[ThresholdConfiguration] = Field(
        ...,
        description="List of threshold configurations to update"
    )
    
    apply_globally: bool = Field(
        default=False,
        description="Apply configurations globally or to specific context"
    )
    
    context: Optional[Dict[str, Any]] = Field(
        None,
        description="Context for configuration application"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "configurations": [
                    {
                        "metric_name": "cpu_usage",
                        "warning_threshold": 80.0,
                        "critical_threshold": 95.0
                    },
                    {
                        "metric_name": "memory_usage",
                        "warning_threshold": 85.0,
                        "critical_threshold": 95.0
                    }
                ],
                "apply_globally": True
            }
        }


class ThresholdConfigurationResponse(BaseModel):
    """Response for threshold configuration update"""
    
    success: bool = Field(..., description="Whether update was successful")
    message: str = Field(..., description="Status message")
    
    updated_configurations: List[ThresholdConfiguration] = Field(
        ...,
        description="Updated configurations"
    )
    
    errors: List[str] = Field(
        default_factory=list,
        description="Any errors encountered during update"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "success": True,
                "message": "Successfully updated 2 threshold configurations",
                "updated_configurations": [],
                "errors": []
            }
        }


class AnomalyAlert(BaseModel):
    """Anomaly alert/notification"""
    
    alert_id: str = Field(..., description="Unique alert identifier")
    anomaly_id: str = Field(..., description="Associated anomaly ID")
    
    # Alert details
    title: str = Field(..., description="Alert title")
    message: str = Field(..., description="Alert message")
    severity: AnomalySeverity = Field(..., description="Alert severity")
    
    # Affected system/service
    affected_system: Optional[str] = Field(None, description="Affected system or service")
    affected_components: List[str] = Field(
        default_factory=list,
        description="Affected components"
    )
    
    # Alert status
    status: str = Field(default="active", description="Alert status")
    acknowledged: bool = Field(default=False, description="Whether alert is acknowledged")
    resolved: bool = Field(default=False, description="Whether alert is resolved")
    
    # Time information
    triggered_at: datetime = Field(default_factory=datetime.utcnow)
    acknowledged_at: Optional[datetime] = Field(None)
    resolved_at: Optional[datetime] = Field(None)
    
    # Personnel
    assigned_to: Optional[str] = Field(None, description="Who the alert is assigned to")
    acknowledged_by: Optional[str] = Field(None, description="Who acknowledged the alert")
    resolved_by: Optional[str] = Field(None, description="Who resolved the alert")
    
    # Additional information
    escalation_level: int = Field(default=1, description="Current escalation level")
    escalation_history: List[Dict[str, Any]] = Field(
        default_factory=list,
        description="History of escalations"
    )
    
    notes: List[str] = Field(
        default_factory=list,
        description="Notes and comments on the alert"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "alert_id": "alert_123456",
                "anomaly_id": "anom_123456",
                "title": "High CPU Usage Detected",
                "message": "CPU usage on web-01 has exceeded 95% for the last 5 minutes",
                "severity": "high",
                "affected_system": "web-01",
                "affected_components": ["nginx", "php-fpm"],
                "status": "active",
                "acknowledged": False,
                "resolved": False
            }
        }


class BulkAnomalyDetectionRequest(BaseModel):
    """Request for bulk anomaly detection across multiple systems/metrics"""
    
    requests: List[AnomalyDetectionRequest] = Field(
        ...,
        min_items=1,
        max_items=100,
        description="List of anomaly detection requests"
    )
    
    parallel_processing: bool = Field(
        default=True,
        description="Process requests in parallel"
    )
    
    global_settings: Optional[Dict[str, Any]] = Field(
        None,
        description="Global settings applied to all requests"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "requests": [
                    {
                        "metrics": {"cpu_usage": 85.0, "memory_usage": 70.0},
                        "metric_type": "system",
                        "metadata": {"server": "web-01"}
                    },
                    {
                        "metrics": {"response_time": 2.5, "error_rate": 0.05},
                        "metric_type": "application",
                        "metadata": {"service": "api"}
                    }
                ],
                "parallel_processing": True,
                "global_settings": {
                    "sensitivity": 0.8
                }
            }
        }


class BulkAnomalyDetectionResponse(BaseModel):
    """Response for bulk anomaly detection"""
    
    batch_id: str = Field(..., description="Batch processing identifier")
    
    responses: List[AnomalyDetectionResponse] = Field(
        ...,
        description="Individual anomaly detection responses"
    )
    
    # Batch summary
    total_requests: int = Field(..., description="Total number of requests")
    successful_requests: int = Field(..., description="Number of successful requests")
    failed_requests: int = Field(..., description="Number of failed requests")
    
    # Aggregate statistics
    total_anomalies: int = Field(..., description="Total anomalies detected across all requests")
    critical_anomalies: int = Field(..., description="Number of critical anomalies")
    high_anomalies: int = Field(..., description="Number of high severity anomalies")
    
    overall_health_score: float = Field(
        ...,
        ge=0,
        le=1,
        description="Overall health score across all systems"
    )
    
    # Processing info
    total_processing_time_ms: int = Field(..., description="Total processing time")
    
    processed_at: datetime = Field(default_factory=datetime.utcnow)
    
    class Config:
        json_schema_extra = {
            "example": {
                "batch_id": "batch_123456",
                "responses": [],
                "total_requests": 10,
                "successful_requests": 9,
                "failed_requests": 1,
                "total_anomalies": 5,
                "critical_anomalies": 1,
                "high_anomalies": 2,
                "overall_health_score": 0.78,
                "total_processing_time_ms": 3500
            }
        }


class AnomalyHistoryRequest(BaseModel):
    """Request for historical anomaly data"""
    
    time_range: str = Field(..., description="Time range for historical data (e.g., '24h', '7d', '30d')")
    
    filters: Optional[Dict[str, Any]] = Field(
        None,
        description="Filters for anomaly data"
    )
    
    metric_types: Optional[List[MetricType]] = Field(
        None,
        description="Filter by metric types"
    )
    
    severity_levels: Optional[List[AnomalySeverity]] = Field(
        None,
        description="Filter by severity levels"
    )
    
    include_resolved: bool = Field(
        default=True,
        description="Include resolved anomalies"
    )
    
    limit: int = Field(
        default=100,
        ge=1,
        le=1000,
        description="Maximum number of records to return"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "time_range": "7d",
                "filters": {
                    "system": "web-cluster"
                },
                "metric_types": ["system", "application"],
                "severity_levels": ["high", "critical"],
                "include_resolved": True,
                "limit": 50
            }
        }


class AnomalyHistoryResponse(BaseModel):
    """Response for historical anomaly data"""
    
    anomalies: List[AnomalyResult] = Field(..., description="Historical anomaly data")
    
    total_count: int = Field(..., description="Total number of anomalies in time range")
    returned_count: int = Field(..., description="Number of anomalies returned")
    
    # Summary statistics
    statistics: Dict[str, Any] = Field(..., description="Summary statistics")
    
    # Trends
    trends: Dict[str, Any] = Field(..., description="Trend analysis")
    
    class Config:
        json_schema_extra = {
            "example": {
                "anomalies": [],
                "total_count": 150,
                "returned_count": 50,
                "statistics": {
                    "severity_distribution": {
                        "critical": 5,
                        "high": 15,
                        "medium": 25,
                        "low": 5
                    },
                    "most_common_types": ["threshold", "statistical", "trend"]
                },
                "trends": {
                    "anomaly_frequency": "increasing",
                    "most_affected_systems": ["web-01", "db-primary"]
                }
            }
        }