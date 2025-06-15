"""
Anomaly Detection Service

Detects anomalies in system metrics and patterns including:
- System performance anomalies (CPU, memory, disk, network)
- Ticket volume and pattern anomalies
- User behavior anomalies
- System health trend analysis
- Predictive failure detection
"""

import asyncio
import logging
from typing import Dict, List, Any, Optional, Tuple, Union
from datetime import datetime, timedelta
import numpy as np
import pandas as pd
from dataclasses import dataclass
from enum import Enum
from collections import defaultdict, deque
import statistics
from scipy import stats
from sklearn.ensemble import IsolationForest
from sklearn.preprocessing import StandardScaler

from app.core.config import settings
from app.core.logging import get_logger

logger = get_logger(__name__)


class AnomalyType(str, Enum):
    """Types of anomalies that can be detected"""
    PERFORMANCE = "performance"
    VOLUME = "volume"
    PATTERN = "pattern"
    TREND = "trend"
    THRESHOLD = "threshold"
    BEHAVIORAL = "behavioral"


class AnomalySeverity(str, Enum):
    """Severity levels for detected anomalies"""
    LOW = "low"
    MEDIUM = "medium"
    HIGH = "high"
    CRITICAL = "critical"


@dataclass
class AnomalyResult:
    """Result of anomaly detection"""
    anomaly_type: AnomalyType
    severity: AnomalySeverity
    score: float  # Anomaly score (0-1, higher = more anomalous)
    description: str
    affected_metrics: List[str]
    detected_at: datetime
    start_time: Optional[datetime] = None
    end_time: Optional[datetime] = None
    recommendations: List[str] = None
    context: Dict[str, Any] = None


class AnomalyDetector:
    """
    Comprehensive anomaly detection service using multiple detection methods
    """
    
    def __init__(self):
        self.metric_history = defaultdict(lambda: deque(maxlen=1000))  # Store last 1000 data points
        self.baseline_stats = {}
        self.isolation_forests = {}
        self.threshold_configs = {}
        self._initialized = False
        
        # Default thresholds for common metrics
        self.default_thresholds = {
            'cpu_usage': {'warning': 80, 'critical': 95},
            'memory_usage': {'warning': 85, 'critical': 95},
            'disk_usage': {'warning': 80, 'critical': 90},
            'network_utilization': {'warning': 70, 'critical': 90},
            'response_time': {'warning': 2.0, 'critical': 5.0},
            'error_rate': {'warning': 0.05, 'critical': 0.1},
            'ticket_volume': {'warning_multiplier': 2.0, 'critical_multiplier': 3.0},
            'resolution_time': {'warning_multiplier': 1.5, 'critical_multiplier': 2.5}
        }
        
        # Anomaly detection sensitivity settings
        self.sensitivity_settings = {
            'statistical_threshold': 2.5,  # Standard deviations
            'isolation_contamination': 0.1,  # Expected outlier ratio
            'trend_sensitivity': 0.05,  # Trend detection threshold
            'pattern_tolerance': 0.2  # Pattern deviation tolerance
        }
    
    async def initialize(self):
        """Initialize the anomaly detector"""
        if self._initialized:
            return
            
        try:
            logger.info("Initializing anomaly detector...")
            
            # Load baseline metrics and configurations
            await self._load_baseline_metrics()
            await self._load_threshold_configurations()
            
            # Initialize ML models for anomaly detection
            await self._initialize_ml_models()
            
            self._initialized = True
            logger.info("Anomaly detector initialized successfully")
            
        except Exception as e:
            logger.error(f"Failed to initialize anomaly detector: {e}")
            self._initialized = True  # Continue with limited functionality
    
    async def detect_anomalies(
        self,
        metrics: Dict[str, Any],
        metric_type: str = "system",
        detection_methods: Optional[List[str]] = None
    ) -> List[AnomalyResult]:
        """
        Detect anomalies in provided metrics
        
        Args:
            metrics: Dictionary of metric values
            metric_type: Type of metrics (system, ticket, user, etc.)
            detection_methods: Specific detection methods to use
            
        Returns:
            List of detected anomalies
        """
        await self.initialize()
        
        try:
            logger.info(f"Detecting anomalies in {metric_type} metrics...")
            
            # Store metrics for historical analysis
            await self._store_metrics(metrics, metric_type)
            
            # Default detection methods
            if detection_methods is None:
                detection_methods = ['threshold', 'statistical', 'isolation_forest', 'trend']
            
            anomalies = []
            
            # Run different detection methods
            for method in detection_methods:
                try:
                    method_anomalies = await self._run_detection_method(
                        method, metrics, metric_type
                    )
                    anomalies.extend(method_anomalies)
                except Exception as e:
                    logger.error(f"Detection method {method} failed: {e}")
                    continue
            
            # Remove duplicates and sort by severity
            unique_anomalies = self._deduplicate_anomalies(anomalies)
            sorted_anomalies = self._sort_anomalies_by_priority(unique_anomalies)
            
            if anomalies:
                logger.warning(f"Detected {len(sorted_anomalies)} anomalies")
            
            return sorted_anomalies
            
        except Exception as e:
            logger.error(f"Anomaly detection failed: {e}")
            return []
    
    async def _run_detection_method(
        self, 
        method: str, 
        metrics: Dict[str, Any], 
        metric_type: str
    ) -> List[AnomalyResult]:
        """Run a specific anomaly detection method"""
        
        if method == 'threshold':
            return await self._threshold_detection(metrics, metric_type)
        elif method == 'statistical':
            return await self._statistical_detection(metrics, metric_type)
        elif method == 'isolation_forest':
            return await self._isolation_forest_detection(metrics, metric_type)
        elif method == 'trend':
            return await self._trend_detection(metrics, metric_type)
        elif method == 'pattern':
            return await self._pattern_detection(metrics, metric_type)
        else:
            logger.warning(f"Unknown detection method: {method}")
            return []
    
    async def _threshold_detection(
        self, 
        metrics: Dict[str, Any], 
        metric_type: str
    ) -> List[AnomalyResult]:
        """Detect anomalies using predefined thresholds"""
        anomalies = []
        
        for metric_name, value in metrics.items():
            if not isinstance(value, (int, float)):
                continue
                
            # Get threshold configuration
            thresholds = self.threshold_configs.get(
                metric_name, 
                self.default_thresholds.get(metric_name, {})
            )
            
            if not thresholds:
                continue
            
            # Check against thresholds
            severity = None
            if 'critical' in thresholds and value >= thresholds['critical']:
                severity = AnomalySeverity.CRITICAL
            elif 'warning' in thresholds and value >= thresholds['warning']:
                severity = AnomalySeverity.HIGH
            
            if severity:
                anomalies.append(AnomalyResult(
                    anomaly_type=AnomalyType.THRESHOLD,
                    severity=severity,
                    score=min(1.0, value / thresholds.get('critical', thresholds.get('warning', value))),
                    description=f"{metric_name} exceeded threshold: {value}",
                    affected_metrics=[metric_name],
                    detected_at=datetime.utcnow(),
                    recommendations=self._get_threshold_recommendations(metric_name, value, thresholds),
                    context={'threshold_value': thresholds, 'current_value': value}
                ))
        
        return anomalies
    
    async def _statistical_detection(
        self, 
        metrics: Dict[str, Any], 
        metric_type: str
    ) -> List[AnomalyResult]:
        """Detect anomalies using statistical methods (z-score, IQR)"""
        anomalies = []
        
        for metric_name, value in metrics.items():
            if not isinstance(value, (int, float)):
                continue
                
            # Get historical data
            history = list(self.metric_history[f"{metric_type}:{metric_name}"])
            
            if len(history) < 30:  # Need sufficient history
                continue
            
            # Calculate statistical measures
            mean_val = statistics.mean(history)
            std_val = statistics.stdev(history)
            
            if std_val == 0:  # No variance
                continue
            
            # Z-score anomaly detection  
            z_score = abs((value - mean_val) / std_val)
            
            if z_score > self.sensitivity_settings['statistical_threshold']:
                # Determine severity based on z-score
                if z_score > 4.0:
                    severity = AnomalySeverity.CRITICAL
                elif z_score > 3.0:
                    severity = AnomalySeverity.HIGH
                elif z_score > 2.5:
                    severity = AnomalySeverity.MEDIUM
                else:
                    severity = AnomalySeverity.LOW
                
                anomalies.append(AnomalyResult(
                    anomaly_type=AnomalyType.PATTERN,
                    severity=severity,
                    score=min(1.0, z_score / 5.0),
                    description=f"{metric_name} is {z_score:.2f} standard deviations from normal",
                    affected_metrics=[metric_name],
                    detected_at=datetime.utcnow(),
                    recommendations=self._get_statistical_recommendations(metric_name, z_score),
                    context={
                        'z_score': z_score,
                        'mean': mean_val,
                        'std_dev': std_val,
                        'current_value': value
                    }
                ))
        
        return anomalies
    
    async def _isolation_forest_detection(
        self, 
        metrics: Dict[str, Any], 
        metric_type: str
    ) -> List[AnomalyResult]:
        """Detect anomalies using Isolation Forest algorithm"""
        anomalies = []
        
        try:
            # Prepare data for ML model
            numeric_metrics = {k: v for k, v in metrics.items() if isinstance(v, (int, float))}
            
            if len(numeric_metrics) < 2:  # Need multiple features
                return anomalies
            
            # Get or create isolation forest model
            model_key = f"{metric_type}_isolation_forest"
            if model_key not in self.isolation_forests:
                self.isolation_forests[model_key] = IsolationForest(
                    contamination=self.sensitivity_settings['isolation_contamination'],
                    random_state=42
                )
                
                # Train on historical data if available
                await self._train_isolation_forest(model_key, metric_type, numeric_metrics.keys())
            
            model = self.isolation_forests[model_key]
            
            # Prepare current data point
            feature_values = [numeric_metrics[key] for key in sorted(numeric_metrics.keys())]
            
            # Predict anomaly
            prediction = model.predict([feature_values])
            anomaly_score = model.decision_function([feature_values])[0]
            
            if prediction[0] == -1:  # Anomaly detected
                # Convert score to 0-1 range (more negative = more anomalous)
                normalized_score = max(0, min(1, (-anomaly_score + 0.5) / 0.5))
                
                # Determine severity
                if normalized_score > 0.8:
                    severity = AnomalySeverity.CRITICAL
                elif normalized_score > 0.6:
                    severity = AnomalySeverity.HIGH
                elif normalized_score > 0.4:
                    severity = AnomalySeverity.MEDIUM
                else:
                    severity = AnomalySeverity.LOW
                
                anomalies.append(AnomalyResult(
                    anomaly_type=AnomalyType.BEHAVIORAL,
                    severity=severity,
                    score=normalized_score,
                    description=f"Unusual pattern detected in {metric_type} metrics",
                    affected_metrics=list(numeric_metrics.keys()),
                    detected_at=datetime.utcnow(),
                    recommendations=self._get_ml_recommendations(metric_type, numeric_metrics),
                    context={
                        'anomaly_score': anomaly_score,
                        'metrics': numeric_metrics
                    }
                ))
        
        except Exception as e:
            logger.error(f"Isolation forest detection failed: {e}")
        
        return anomalies
    
    async def _trend_detection(
        self, 
        metrics: Dict[str, Any], 
        metric_type: str
    ) -> List[AnomalyResult]:
        """Detect anomalies in trends and patterns over time"""
        anomalies = []
        
        for metric_name, value in metrics.items():
            if not isinstance(value, (int, float)):
                continue
                
            # Get historical data
            history_key = f"{metric_type}:{metric_name}"
            history = list(self.metric_history[history_key])
            
            if len(history) < 10:  # Need sufficient history for trend analysis
                continue
            
            # Calculate trend using linear regression
            x = np.arange(len(history))
            y = np.array(history)
            
            try:
                slope, intercept, r_value, p_value, std_err = stats.linregress(x, y)
                
                # Detect significant trends
                if abs(r_value) > 0.7 and p_value < 0.05:  # Strong correlation
                    trend_direction = "increasing" if slope > 0 else "decreasing"
                    
                    # Check if trend is concerning
                    if self._is_concerning_trend(metric_name, slope, trend_direction):
                        severity = self._assess_trend_severity(metric_name, slope, r_value)
                        
                        anomalies.append(AnomalyResult(
                            anomaly_type=AnomalyType.TREND,
                            severity=severity,
                            score=abs(r_value),
                            description=f"{metric_name} showing {trend_direction} trend (slope: {slope:.4f})",
                            affected_metrics=[metric_name],
                            detected_at=datetime.utcnow(),
                            recommendations=self._get_trend_recommendations(metric_name, slope, trend_direction),
                            context={
                                'slope': slope,
                                'r_value': r_value,
                                'p_value': p_value,
                                'trend_direction': trend_direction
                            }
                        ))
                        
            except Exception as e:
                logger.error(f"Trend analysis failed for {metric_name}: {e}")
                continue
        
        return anomalies
    
    async def _pattern_detection(
        self, 
        metrics: Dict[str, Any], 
        metric_type: str
    ) -> List[AnomalyResult]:
        """Detect anomalies in patterns (seasonal, cyclical, etc.)"""
        anomalies = []
        
        # This would implement more sophisticated pattern detection
        # For now, implement basic pattern analysis
        
        for metric_name, value in metrics.items():
            if not isinstance(value, (int, float)):
                continue
                
            # Get historical data
            history_key = f"{metric_type}:{metric_name}"
            history = list(self.metric_history[history_key])
            
            if len(history) < 24:  # Need at least 24 data points for pattern analysis
                continue
            
            # Simple pattern detection: check for unusual values at this time
            current_hour = datetime.utcnow().hour
            
            # Get historical values for the same hour
            # This is a simplified approach - in production, you'd use more sophisticated methods
            similar_time_values = []
            for i, hist_value in enumerate(history):
                # Assuming data points are hourly
                hist_hour = (current_hour - (len(history) - i)) % 24
                if hist_hour == current_hour:
                    similar_time_values.append(hist_value)
            
            if len(similar_time_values) >= 3:
                mean_similar = statistics.mean(similar_time_values)
                std_similar = statistics.stdev(similar_time_values) if len(similar_time_values) > 1 else 0
                
                if std_similar > 0:
                    deviation = abs(value - mean_similar) / std_similar
                    
                    if deviation > 2.0:  # Unusual for this time pattern
                        severity = AnomalySeverity.MEDIUM if deviation > 3.0 else AnomalySeverity.LOW
                        
                        anomalies.append(AnomalyResult(
                            anomaly_type=AnomalyType.PATTERN,
                            severity=severity,
                            score=min(1.0, deviation / 4.0),
                            description=f"{metric_name} unusual for time pattern (hour {current_hour})",
                            affected_metrics=[metric_name],
                            detected_at=datetime.utcnow(),
                            recommendations=self._get_pattern_recommendations(metric_name, current_hour),
                            context={
                                'deviation': deviation,
                                'expected_mean': mean_similar,
                                'current_hour': current_hour,
                                'similar_values_count': len(similar_time_values)
                            }
                        ))
        
        return anomalies
    
    async def _store_metrics(self, metrics: Dict[str, Any], metric_type: str):
        """Store metrics for historical analysis"""
        try:
            for metric_name, value in metrics.items():
                if isinstance(value, (int, float)):
                    history_key = f"{metric_type}:{metric_name}"
                    self.metric_history[history_key].append(value)
        except Exception as e:
            logger.error(f"Failed to store metrics: {e}")
    
    async def _train_isolation_forest(self, model_key: str, metric_type: str, feature_names: List[str]):
        """Train isolation forest model on historical data"""
        try:
            # Collect historical data for training
            training_data = []
            
            # Get minimum history length across all features
            min_history = min(
                len(self.metric_history[f"{metric_type}:{name}"]) 
                for name in feature_names
            )
            
            if min_history < 50:  # Need sufficient training data
                return
            
            # Prepare training data matrix
            for i in range(min_history):
                data_point = []
                for name in sorted(feature_names):
                    history = list(self.metric_history[f"{metric_type}:{name}"])
                    data_point.append(history[i])
                training_data.append(data_point)
            
            # Train the model
            model = self.isolation_forests[model_key]
            model.fit(training_data)
            
            logger.info(f"Trained isolation forest model {model_key} on {len(training_data)} samples")
            
        except Exception as e:
            logger.error(f"Failed to train isolation forest model: {e}")
    
    def _deduplicate_anomalies(self, anomalies: List[AnomalyResult]) -> List[AnomalyResult]:
        """Remove duplicate anomalies"""
        unique_anomalies = []
        seen_combinations = set()
        
        for anomaly in anomalies:
            # Create a key for deduplication
            key = (
                anomaly.anomaly_type,
                tuple(sorted(anomaly.affected_metrics)),
                anomaly.severity
            )
            
            if key not in seen_combinations:
                unique_anomalies.append(anomaly)
                seen_combinations.add(key)
        
        return unique_anomalies
    
    def _sort_anomalies_by_priority(self, anomalies: List[AnomalyResult]) -> List[AnomalyResult]:
        """Sort anomalies by priority (severity and score)"""
        severity_order = {
            AnomalySeverity.CRITICAL: 4,
            AnomalySeverity.HIGH: 3,
            AnomalySeverity.MEDIUM: 2,
            AnomalySeverity.LOW: 1
        }
        
        return sorted(
            anomalies,
            key=lambda x: (severity_order[x.severity], x.score),
            reverse=True
        )
    
    def _is_concerning_trend(self, metric_name: str, slope: float, direction: str) -> bool:
        """Determine if a trend is concerning for a specific metric"""
        concerning_patterns = {
            'cpu_usage': {'increasing': True, 'decreasing': False},
            'memory_usage': {'increasing': True, 'decreasing': False},
            'disk_usage': {'increasing': True, 'decreasing': False},
            'error_rate': {'increasing': True, 'decreasing': False},
            'response_time': {'increasing': True, 'decreasing': False},
            'ticket_volume': {'increasing': True, 'decreasing': False},
            'resolution_time': {'increasing': True, 'decreasing': False}
        }
        
        pattern = concerning_patterns.get(metric_name, {'increasing': True, 'decreasing': True})
        return pattern.get(direction, False) and abs(slope) > self.sensitivity_settings['trend_sensitivity']
    
    def _assess_trend_severity(self, metric_name: str, slope: float, r_value: float) -> AnomalySeverity:
        """Assess the severity of a detected trend"""
        # Combine slope magnitude and correlation strength
        severity_score = abs(slope) * abs(r_value)
        
        if severity_score > 0.5:
            return AnomalySeverity.CRITICAL
        elif severity_score > 0.3:
            return AnomalySeverity.HIGH
        elif severity_score > 0.1:
            return AnomalySeverity.MEDIUM
        else:
            return AnomalySeverity.LOW
    
    def _get_threshold_recommendations(self, metric_name: str, value: float, thresholds: Dict[str, Any]) -> List[str]:
        """Get recommendations for threshold-based anomalies"""
        recommendations = []
        
        base_recommendations = {
            'cpu_usage': [
                "Check for resource-intensive processes",
                "Consider load balancing or scaling",
                "Review recent changes or deployments"
            ],
            'memory_usage': [
                "Check for memory leaks in applications",
                "Consider adding more RAM or optimizing applications",
                "Review memory usage patterns"
            ],
            'disk_usage': [
                "Clean up unnecessary files and logs",
                "Consider disk expansion or data archiving",
                "Check for large file accumulation"
            ],
            'response_time': [
                "Check network connectivity and latency",
                "Review application performance and database queries",
                "Consider caching or optimization strategies"
            ]
        }
        
        recommendations.extend(base_recommendations.get(metric_name, [
            f"Monitor {metric_name} closely",
            "Investigate root cause of elevated values",
            "Consider adjusting thresholds if this becomes normal"
        ]))
        
        return recommendations
    
    def _get_statistical_recommendations(self, metric_name: str, z_score: float) -> List[str]:
        """Get recommendations for statistical anomalies"""
        return [
            f"Investigate cause of unusual {metric_name} value",
            f"Check for recent changes that might explain the {z_score:.1f}σ deviation",
            "Monitor closely for sustained anomalous behavior",
            "Consider updating baseline if this represents a new normal"
        ]
    
    def _get_ml_recommendations(self, metric_type: str, metrics: Dict[str, Any]) -> List[str]:
        """Get recommendations for ML-detected anomalies"""
        return [
            f"Unusual pattern detected in {metric_type} metrics",
            "Investigate correlations between affected metrics",
            "Check for system changes or external factors",
            "Monitor for continued anomalous behavior"
        ]
    
    def _get_trend_recommendations(self, metric_name: str, slope: float, direction: str) -> List[str]:
        """Get recommendations for trend anomalies"""
        return [
            f"Address {direction} trend in {metric_name}",
            "Investigate root causes of the trend",
            "Consider proactive measures to prevent issues",
            "Monitor trend progression and impact"
        ]
    
    def _get_pattern_recommendations(self, metric_name: str, hour: int) -> List[str]:
        """Get recommendations for pattern anomalies"""
        return [
            f"Unusual {metric_name} value for hour {hour}",
            "Check for scheduled jobs or activities at this time",
            "Compare with previous days/weeks for context",
            "Consider if this represents a new usage pattern"
        ]
    
    async def _load_baseline_metrics(self):
        """Load baseline metrics for comparison"""
        try:
            # In production, this would load from database
            # For now, initialize with empty baselines
            self.baseline_stats = {}
            logger.info("Baseline metrics loaded")
        except Exception as e:
            logger.error(f"Failed to load baseline metrics: {e}")
    
    async def _load_threshold_configurations(self):
        """Load custom threshold configurations"""
        try:
            # In production, this would load from configuration system
            # For now, use default thresholds
            self.threshold_configs = self.default_thresholds.copy()
            logger.info("Threshold configurations loaded")
        except Exception as e:
            logger.error(f"Failed to load threshold configurations: {e}")
    
    async def _initialize_ml_models(self):
        """Initialize machine learning models for anomaly detection"""
        try:
            # Models will be created on-demand when needed
            self.isolation_forests = {}
            logger.info("ML models initialized")
        except Exception as e:
            logger.error(f"Failed to initialize ML models: {e}")
    
    async def get_anomaly_summary(self, time_range: timedelta = timedelta(hours=24)) -> Dict[str, Any]:
        """Get a summary of recent anomalies"""
        try:
            # In production, this would query anomaly history from database
            return {
                "total_anomalies": 0,
                "critical_count": 0,
                "high_count": 0,
                "medium_count": 0,
                "low_count": 0,
                "most_affected_metrics": [],
                "anomaly_types": {},
                "time_range": str(time_range)
            }
        except Exception as e:
            logger.error(f"Failed to get anomaly summary: {e}")
            return {}
    
    async def update_thresholds(self, metric_name: str, thresholds: Dict[str, float]):
        """Update threshold configuration for a metric"""
        try:
            self.threshold_configs[metric_name] = thresholds
            logger.info(f"Updated thresholds for {metric_name}: {thresholds}")
        except Exception as e:
            logger.error(f"Failed to update thresholds: {e}")