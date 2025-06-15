"""
Trend Analysis ML Model

Advanced trend analysis and prediction system for:
- Ticket volume trends and forecasting
- Issue pattern recognition
- Seasonal analysis
- Performance trend monitoring
- Predictive insights and alerts
- Resource planning recommendations
"""

import asyncio
import logging
import numpy as np
import pandas as pd
from typing import Dict, List, Any, Optional, Tuple, Union
from datetime import datetime, timedelta
from dataclasses import dataclass
from enum import Enum
import json
from collections import defaultdict, deque
import statistics

# Time series and ML imports
from sklearn.linear_model import LinearRegression, Ridge
from sklearn.ensemble import RandomForestRegressor, GradientBoostingRegressor
from sklearn.preprocessing import StandardScaler, MinMaxScaler
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from sklearn.cluster import KMeans
import scipy.stats as stats
from scipy.signal import find_peaks, savgol_filter
from scipy.fft import fft, fftfreq

# Advanced time series (optional)
try:
    from statsmodels.tsa.seasonal import seasonal_decompose
    from statsmodels.tsa.arima.model import ARIMA
    from statsmodels.tsa.holtwinters import ExponentialSmoothing
    ADVANCED_TS_AVAILABLE = True
except ImportError:
    ADVANCED_TS_AVAILABLE = False

from app.core.config import settings
from app.core.logging import get_logger

logger = get_logger(__name__)


class TrendDirection(str, Enum):
    """Direction of trend"""
    INCREASING = "increasing"
    DECREASING = "decreasing"
    STABLE = "stable"
    VOLATILE = "volatile"


class SeasonalityType(str, Enum):
    """Types of seasonality"""
    DAILY = "daily"
    WEEKLY = "weekly"
    MONTHLY = "monthly"
    QUARTERLY = "quarterly"
    YEARLY = "yearly"
    NONE = "none"


@dataclass
class TrendResult:
    """Result of trend analysis"""
    metric_name: str
    time_period: str
    trend_direction: TrendDirection
    trend_strength: float  # 0-1, how strong the trend is
    slope: float
    r_squared: float
    confidence: float
    seasonal_patterns: Dict[str, Any]
    anomalies: List[Dict[str, Any]]
    forecast: Dict[str, Any]
    recommendations: List[str]


@dataclass
class ForecastResult:
    """Result of forecasting"""
    metric_name: str
    forecast_horizon: str
    predicted_values: List[float]
    confidence_intervals: List[Tuple[float, float]]
    forecast_accuracy: float
    model_used: str
    uncertainty: float


class TrendAnalyzer:
    """
    Advanced trend analysis and forecasting system
    """
    
    def __init__(self):
        self.time_series_data = defaultdict(lambda: deque(maxlen=10000))
        self.trend_models = {}
        self.scalers = {}
        self.seasonal_patterns = {}
        self.baseline_metrics = {}
        self._initialized = False
        
        # Analysis configurations
        self.config = {
            'min_data_points': 10,
            'trend_window': 30,
            'seasonality_threshold': 0.3,
            'anomaly_threshold': 2.5,
            'forecast_horizon': 7,  # days
            'confidence_level': 0.95
        }
        
        # Trend detection methods
        self.trend_methods = {
            'linear': self._linear_trend_analysis,
            'polynomial': self._polynomial_trend_analysis,
            'seasonal': self._seasonal_trend_analysis,
            'decomposition': self._decomposition_analysis
        }
        
        # Forecasting models
        self.forecast_models = {
            'linear_regression': LinearRegression(),
            'random_forest': RandomForestRegressor(n_estimators=100, random_state=42),
            'gradient_boosting': GradientBoostingRegressor(random_state=42)
        }
        
        # Common metrics to track
        self.tracked_metrics = [
            'ticket_volume',
            'resolution_time',
            'customer_satisfaction',
            'agent_utilization',
            'system_performance',
            'error_rate',
            'response_time'
        ]
    
    async def initialize(self):
        """Initialize the trend analyzer"""
        if self._initialized:
            return
            
        try:
            logger.info("Initializing trend analyzer...")
            
            # Load historical data
            await self._load_historical_data()
            
            # Initialize baseline metrics
            await self._initialize_baselines()
            
            # Load or create trend models
            await self._initialize_models()
            
            self._initialized = True
            logger.info("Trend analyzer initialized successfully")
            
        except Exception as e:
            logger.error(f"Failed to initialize trend analyzer: {e}")
            self._initialized = True  # Continue with limited functionality
    
    async def analyze_trends(
        self,
        metric_name: str,
        time_period: str = "30d",
        analysis_methods: Optional[List[str]] = None,
        include_forecast: bool = True,
        detect_seasonality: bool = True
    ) -> TrendResult:
        """
        Analyze trends for a specific metric
        
        Args:
            metric_name: Name of the metric to analyze
            time_period: Time period for analysis (e.g., "7d", "30d", "90d")
            analysis_methods: Specific methods to use
            include_forecast: Whether to include forecasting
            detect_seasonality: Whether to detect seasonal patterns
            
        Returns:
            Trend analysis results
        """
        await self.initialize()
        
        try:
            logger.info(f"Analyzing trends for {metric_name} over {time_period}")
            
            # Get time series data
            data = await self._get_time_series_data(metric_name, time_period)
            
            if len(data) < self.config['min_data_points']:
                logger.warning(f"Insufficient data points ({len(data)}) for trend analysis")
                return self._get_insufficient_data_result(metric_name, time_period)
            
            # Prepare data for analysis
            df = self._prepare_dataframe(data)
            
            # Run trend analysis methods
            if analysis_methods is None:
                analysis_methods = ['linear', 'seasonal']
            
            trend_results = {}
            for method in analysis_methods:
                if method in self.trend_methods:
                    try:
                        result = await self.trend_methods[method](df, metric_name)
                        trend_results[method] = result
                    except Exception as e:
                        logger.error(f"Trend analysis method {method} failed: {e}")
            
            # Combine results
            combined_result = self._combine_trend_results(trend_results, metric_name, time_period)
            
            # Detect anomalies
            anomalies = await self._detect_trend_anomalies(df, metric_name)
            combined_result.anomalies = anomalies
            
            # Detect seasonality if requested
            if detect_seasonality:
                seasonal_patterns = await self._detect_seasonality(df, metric_name)
                combined_result.seasonal_patterns = seasonal_patterns
            
            # Generate forecast if requested
            if include_forecast:
                forecast = await self._generate_forecast(df, metric_name)
                combined_result.forecast = forecast
            
            # Generate recommendations
            recommendations = self._generate_trend_recommendations(combined_result)
            combined_result.recommendations = recommendations
            
            return combined_result
            
        except Exception as e:
            logger.error(f"Trend analysis failed for {metric_name}: {e}")
            return self._get_error_result(metric_name, time_period, str(e))
    
    async def add_data_point(
        self,
        metric_name: str,
        value: float,
        timestamp: Optional[datetime] = None,
        metadata: Optional[Dict[str, Any]] = None
    ):
        """Add a new data point for trend analysis"""
        try:
            if timestamp is None:
                timestamp = datetime.utcnow()
            
            data_point = {
                'timestamp': timestamp,
                'value': value,
                'metadata': metadata or {}
            }
            
            self.time_series_data[metric_name].append(data_point)
            
            # Update models if we have enough data
            if len(self.time_series_data[metric_name]) % 100 == 0:
                await self._update_models(metric_name)
            
        except Exception as e:
            logger.error(f"Failed to add data point for {metric_name}: {e}")
    
    async def bulk_forecast(
        self,
        metrics: List[str],
        forecast_horizon: int = 7,
        confidence_level: float = 0.95
    ) -> Dict[str, ForecastResult]:
        """Generate forecasts for multiple metrics"""
        await self.initialize()
        
        try:
            logger.info(f"Generating forecasts for {len(metrics)} metrics")
            
            forecast_results = {}
            
            for metric_name in metrics:
                try:
                    # Get recent data
                    data = await self._get_time_series_data(metric_name, "30d")
                    
                    if len(data) >= self.config['min_data_points']:
                        df = self._prepare_dataframe(data)
                        forecast = await self._generate_detailed_forecast(
                            df, metric_name, forecast_horizon, confidence_level
                        )
                        forecast_results[metric_name] = forecast
                    else:
                        logger.warning(f"Insufficient data for forecasting {metric_name}")
                        
                except Exception as e:
                    logger.error(f"Forecast failed for {metric_name}: {e}")
                    continue
            
            return forecast_results
            
        except Exception as e:
            logger.error(f"Bulk forecasting failed: {e}")
            return {}
    
    async def detect_pattern_changes(
        self,
        metric_name: str,
        window_size: int = 30,
        sensitivity: float = 0.5
    ) -> List[Dict[str, Any]]:
        """Detect significant pattern changes in time series"""
        try:
            logger.info(f"Detecting pattern changes for {metric_name}")
            
            # Get historical data
            data = await self._get_time_series_data(metric_name, "90d")
            
            if len(data) < window_size * 2:
                return []
            
            df = self._prepare_dataframe(data)
            values = df['value'].values
            
            changes = []
            
            # Sliding window analysis
            for i in range(window_size, len(values) - window_size):
                before_window = values[i-window_size:i]
                after_window = values[i:i+window_size]
                
                # Statistical tests for change detection
                statistic, p_value = stats.ttest_ind(before_window, after_window)
                
                if p_value < sensitivity:
                    # Significant change detected
                    change_magnitude = np.abs(np.mean(after_window) - np.mean(before_window))
                    change_direction = "increase" if np.mean(after_window) > np.mean(before_window) else "decrease"
                    
                    changes.append({
                        'timestamp': df.iloc[i]['timestamp'],
                        'change_type': 'pattern_shift',
                        'direction': change_direction,
                        'magnitude': change_magnitude,
                        'significance': 1 - p_value,
                        'before_mean': np.mean(before_window),
                        'after_mean': np.mean(after_window)
                    })
            
            # Remove changes that are too close together
            filtered_changes = self._filter_close_changes(changes, timedelta(days=7))
            
            logger.info(f"Detected {len(filtered_changes)} pattern changes for {metric_name}")
            return filtered_changes
            
        except Exception as e:
            logger.error(f"Pattern change detection failed: {e}")
            return []
    
    async def _linear_trend_analysis(self, df: pd.DataFrame, metric_name: str) -> Dict[str, Any]:
        """Perform linear trend analysis"""
        try:
            values = df['value'].values
            time_indices = np.arange(len(values))
            
            # Fit linear regression
            model = LinearRegression()
            model.fit(time_indices.reshape(-1, 1), values)
            
            # Calculate metrics
            predictions = model.predict(time_indices.reshape(-1, 1))
            slope = model.coef_[0]
            r_squared = r2_score(values, predictions)
            
            # Determine trend direction
            if abs(slope) < np.std(values) * 0.1:
                direction = TrendDirection.STABLE
            elif slope > 0:
                direction = TrendDirection.INCREASING
            else:
                direction = TrendDirection.DECREASING
            
            # Calculate trend strength
            trend_strength = min(abs(r_squared), 1.0)
            
            return {
                'method': 'linear',
                'slope': slope,
                'r_squared': r_squared,
                'direction': direction,
                'strength': trend_strength,
                'confidence': r_squared
            }
            
        except Exception as e:
            logger.error(f"Linear trend analysis failed: {e}")
            return {'method': 'linear', 'error': str(e)}
    
    async def _polynomial_trend_analysis(self, df: pd.DataFrame, metric_name: str) -> Dict[str, Any]:
        """Perform polynomial trend analysis"""
        try:
            values = df['value'].values
            time_indices = np.arange(len(values))
            
            # Fit polynomial (degree 2)
            coefficients = np.polyfit(time_indices, values, deg=2)
            poly_func = np.poly1d(coefficients)
            
            predictions = poly_func(time_indices)
            r_squared = r2_score(values, predictions)
            
            # Analyze curvature
            second_derivative = 2 * coefficients[0]
            
            if abs(second_derivative) < 0.01:
                direction = TrendDirection.STABLE
            elif second_derivative > 0:
                direction = TrendDirection.INCREASING  # Accelerating upward
            else:
                direction = TrendDirection.DECREASING  # Accelerating downward
            
            return {
                'method': 'polynomial',
                'coefficients': coefficients.tolist(),
                'r_squared': r_squared,
                'direction': direction,
                'curvature': second_derivative,
                'confidence': r_squared
            }
            
        except Exception as e:
            logger.error(f"Polynomial trend analysis failed: {e}")
            return {'method': 'polynomial', 'error': str(e)}
    
    async def _seasonal_trend_analysis(self, df: pd.DataFrame, metric_name: str) -> Dict[str, Any]:
        """Perform seasonal trend analysis"""
        try:
            if len(df) < 14:  # Need at least 2 weeks
                return {'method': 'seasonal', 'error': 'Insufficient data for seasonal analysis'}
            
            # Create time features
            df['hour'] = df['timestamp'].dt.hour
            df['day_of_week'] = df['timestamp'].dt.dayofweek
            df['day_of_month'] = df['timestamp'].dt.day
            
            seasonal_patterns = {}
            
            # Hourly patterns
            if len(df['hour'].unique()) > 5:
                hourly_means = df.groupby('hour')['value'].agg(['mean', 'std']).to_dict()
                seasonal_patterns['hourly'] = hourly_means
            
            # Daily patterns
            if len(df['day_of_week'].unique()) > 3:
                daily_means = df.groupby('day_of_week')['value'].agg(['mean', 'std']).to_dict()
                seasonal_patterns['daily'] = daily_means
            
            # Monthly patterns (if enough data)
            if len(df['day_of_month'].unique()) > 10:
                monthly_means = df.groupby('day_of_month')['value'].agg(['mean', 'std']).to_dict()
                seasonal_patterns['monthly'] = monthly_means
            
            # Calculate seasonality strength
            total_variance = df['value'].var()
            seasonal_variance = 0
            
            for pattern_type, pattern_data in seasonal_patterns.items():
                if 'mean' in pattern_data:
                    pattern_variance = np.var(list(pattern_data['mean'].values()))
                    seasonal_variance += pattern_variance
            
            seasonality_strength = min(seasonal_variance / max(total_variance, 1), 1.0)
            
            return {
                'method': 'seasonal',
                'patterns': seasonal_patterns,
                'seasonality_strength': seasonality_strength,
                'direction': TrendDirection.STABLE,  # Seasonal analysis focuses on patterns
                'confidence': seasonality_strength
            }
            
        except Exception as e:
            logger.error(f"Seasonal trend analysis failed: {e}")
            return {'method': 'seasonal', 'error': str(e)}
    
    async def _decomposition_analysis(self, df: pd.DataFrame, metric_name: str) -> Dict[str, Any]:
        """Perform time series decomposition analysis"""
        try:
            if not ADVANCED_TS_AVAILABLE or len(df) < 28:
                return {'method': 'decomposition', 'error': 'Advanced time series library not available or insufficient data'}
            
            # Prepare data for decomposition
            ts_data = df.set_index('timestamp')['value']
            ts_data = ts_data.asfreq('H', method='ffill')  # Hourly frequency
            
            # Perform seasonal decomposition
            decomposition = seasonal_decompose(ts_data, model='additive', period=24)
            
            # Extract components
            trend_component = decomposition.trend.dropna()
            seasonal_component = decomposition.seasonal
            residual_component = decomposition.resid.dropna()
            
            # Analyze trend component
            if len(trend_component) > 1:
                trend_slope = (trend_component.iloc[-1] - trend_component.iloc[0]) / len(trend_component)
                if abs(trend_slope) < trend_component.std() * 0.1:
                    direction = TrendDirection.STABLE
                elif trend_slope > 0:
                    direction = TrendDirection.INCREASING
                else:
                    direction = TrendDirection.DECREASING
            else:
                direction = TrendDirection.STABLE
                trend_slope = 0
            
            # Calculate component strengths
            trend_strength = trend_component.var() / ts_data.var() if ts_data.var() > 0 else 0
            seasonal_strength = seasonal_component.var() / ts_data.var() if ts_data.var() > 0 else 0
            noise_strength = residual_component.var() / ts_data.var() if ts_data.var() > 0 else 0
            
            return {
                'method': 'decomposition',
                'direction': direction,
                'trend_slope': trend_slope,
                'trend_strength': trend_strength,
                'seasonal_strength': seasonal_strength,
                'noise_strength': noise_strength,
                'confidence': trend_strength + seasonal_strength
            }
            
        except Exception as e:
            logger.error(f"Decomposition analysis failed: {e}")
            return {'method': 'decomposition', 'error': str(e)}
    
    async def _detect_trend_anomalies(self, df: pd.DataFrame, metric_name: str) -> List[Dict[str, Any]]:
        """Detect anomalies in the trend"""
        try:
            values = df['value'].values
            timestamps = df['timestamp'].values
            
            # Use z-score for anomaly detection
            z_scores = np.abs(stats.zscore(values))
            threshold = self.config['anomaly_threshold']
            
            anomalies = []
            for i, (timestamp, value, z_score) in enumerate(zip(timestamps, values, z_scores)):
                if z_score > threshold:
                    anomalies.append({
                        'timestamp': timestamp,
                        'value': value,
                        'z_score': z_score,
                        'severity': 'high' if z_score > threshold * 1.5 else 'medium',
                        'type': 'statistical_outlier'
                    })
            
            # Additional anomaly detection using moving averages
            if len(values) > 10:
                window_size = min(10, len(values) // 3)
                rolling_mean = pd.Series(values).rolling(window=window_size).mean()
                rolling_std = pd.Series(values).rolling(window=window_size).std()
                
                for i in range(window_size, len(values)):
                    deviation = abs(values[i] - rolling_mean.iloc[i])
                    if deviation > 2 * rolling_std.iloc[i]:
                        anomalies.append({
                            'timestamp': timestamps[i],
                            'value': values[i],
                            'expected_value': rolling_mean.iloc[i],
                            'deviation': deviation,
                            'severity': 'medium',
                            'type': 'trend_deviation'
                        })
            
            # Remove duplicate anomalies (same timestamp)
            unique_anomalies = []
            seen_timestamps = set()
            for anomaly in anomalies:
                if anomaly['timestamp'] not in seen_timestamps:
                    unique_anomalies.append(anomaly)
                    seen_timestamps.add(anomaly['timestamp'])
            
            return unique_anomalies[:20]  # Limit to top 20 anomalies
            
        except Exception as e:
            logger.error(f"Anomaly detection failed: {e}")
            return []
    
    async def _detect_seasonality(self, df: pd.DataFrame, metric_name: str) -> Dict[str, Any]:
        """Detect seasonal patterns in the data"""
        try:
            if len(df) < 48:  # Need at least 2 days of hourly data
                return {'detected': False, 'reason': 'Insufficient data'}
            
            values = df['value'].values
            
            # FFT-based seasonality detection
            fft_values = fft(values)
            frequencies = fftfreq(len(values))
            
            # Find dominant frequencies
            magnitude = np.abs(fft_values)
            dominant_freq_idx = np.argmax(magnitude[1:len(magnitude)//2]) + 1
            dominant_frequency = frequencies[dominant_freq_idx]
            
            # Convert frequency to period
            if dominant_frequency != 0:
                period = 1 / abs(dominant_frequency)
            else:
                period = 0
            
            # Determine seasonality type
            seasonality_type = SeasonalityType.NONE
            if 20 <= period <= 28:  # Daily pattern (24 hours ± variation)
                seasonality_type = SeasonalityType.DAILY
            elif 160 <= period <= 180:  # Weekly pattern (168 hours ± variation)
                seasonality_type = SeasonalityType.WEEKLY
            elif 700 <= period <= 750:  # Monthly pattern (~720 hours ± variation)
                seasonality_type = SeasonalityType.MONTHLY
            
            # Calculate seasonality strength
            seasonal_strength = magnitude[dominant_freq_idx] / np.sum(magnitude)
            
            return {
                'detected': seasonal_strength > self.config['seasonality_threshold'],
                'type': seasonality_type,
                'period': period,
                'strength': seasonal_strength,
                'dominant_frequency': dominant_frequency
            }
            
        except Exception as e:
            logger.error(f"Seasonality detection failed: {e}")
            return {'detected': False, 'error': str(e)}
    
    async def _generate_forecast(self, df: pd.DataFrame, metric_name: str) -> Dict[str, Any]:
        """Generate forecast for the metric"""
        try:
            values = df['value'].values
            
            if len(values) < 5:
                return {'error': 'Insufficient data for forecasting'}
            
            # Simple linear extrapolation
            time_indices = np.arange(len(values))
            future_indices = np.arange(len(values), len(values) + self.config['forecast_horizon'])
            
            # Fit linear model
            model = LinearRegression()
            model.fit(time_indices.reshape(-1, 1), values)
            
            # Generate predictions
            future_predictions = model.predict(future_indices.reshape(-1, 1))
            
            # Calculate confidence intervals (simplified)
            residuals = values - model.predict(time_indices.reshape(-1, 1))
            std_error = np.std(residuals)
            confidence_margin = 1.96 * std_error  # 95% confidence
            
            confidence_intervals = [
                (pred - confidence_margin, pred + confidence_margin)
                for pred in future_predictions
            ]
            
            # Generate future timestamps
            last_timestamp = df['timestamp'].iloc[-1]
            future_timestamps = [
                last_timestamp + timedelta(days=i+1)
                for i in range(self.config['forecast_horizon'])
            ]
            
            return {
                'horizon_days': self.config['forecast_horizon'],
                'predictions': future_predictions.tolist(),
                'timestamps': [ts.isoformat() for ts in future_timestamps],
                'confidence_intervals': confidence_intervals,
                'model_accuracy': model.score(time_indices.reshape(-1, 1), values),
                'method': 'linear_regression'
            }
            
        except Exception as e:
            logger.error(f"Forecast generation failed: {e}")
            return {'error': str(e)}
    
    async def _generate_detailed_forecast(
        self,
        df: pd.DataFrame,
        metric_name: str,
        horizon: int,
        confidence_level: float
    ) -> ForecastResult:
        """Generate detailed forecast with multiple models"""
        try:
            values = df['value'].values
            time_indices = np.arange(len(values))
            
            # Prepare features
            features = self._create_forecast_features(df)
            
            # Try different models and select best
            best_model = None
            best_score = float('-inf')
            best_predictions = None
            
            for model_name, model in self.forecast_models.items():
                try:
                    # Split data for validation
                    split_point = max(5, len(values) - horizon)
                    train_features = features[:split_point]
                    train_values = values[:split_point]
                    test_features = features[split_point:]
                    test_values = values[split_point:]
                    
                    if len(test_values) > 0:
                        # Train and validate
                        model.fit(train_features, train_values)
                        test_predictions = model.predict(test_features)
                        score = r2_score(test_values, test_predictions)
                        
                        if score > best_score:
                            best_score = score
                            best_model = model_name
                            best_predictions = test_predictions
                    else:
                        # Use all data for training
                        model.fit(features, values)
                        best_model = model_name
                        best_score = 0.5  # Default score
                        
                except Exception as e:
                    logger.error(f"Model {model_name} failed: {e}")
                    continue
            
            # Generate future predictions
            if best_model:
                future_features = self._create_future_features(df, horizon)
                future_predictions = self.forecast_models[best_model].predict(future_features)
                
                # Calculate uncertainty
                residuals = values - self.forecast_models[best_model].predict(features)
                uncertainty = np.std(residuals)
                
                # Generate confidence intervals
                z_score = stats.norm.ppf((1 + confidence_level) / 2)
                margin = z_score * uncertainty
                
                confidence_intervals = [
                    (pred - margin, pred + margin)
                    for pred in future_predictions
                ]
                
                return ForecastResult(
                    metric_name=metric_name,
                    forecast_horizon=f"{horizon}d",
                    predicted_values=future_predictions.tolist(),
                    confidence_intervals=confidence_intervals,
                    forecast_accuracy=max(best_score, 0),
                    model_used=best_model,
                    uncertainty=uncertainty
                )
            else:
                raise Exception("No suitable forecasting model found")
                
        except Exception as e:
            logger.error(f"Detailed forecast generation failed: {e}")
            return ForecastResult(
                metric_name=metric_name,
                forecast_horizon=f"{horizon}d",
                predicted_values=[],
                confidence_intervals=[],
                forecast_accuracy=0.0,
                model_used="none",
                uncertainty=1.0
            )
    
    def _create_forecast_features(self, df: pd.DataFrame) -> np.ndarray:
        """Create features for forecasting models"""
        try:
            features = []
            
            # Time-based features
            df['hour'] = df['timestamp'].dt.hour
            df['day_of_week'] = df['timestamp'].dt.dayofweek
            df['day_of_month'] = df['timestamp'].dt.day
            df['month'] = df['timestamp'].dt.month
            
            # Lag features
            for lag in [1, 2, 3, 7]:
                if len(df) > lag:
                    df[f'lag_{lag}'] = df['value'].shift(lag)
            
            # Rolling statistics
            for window in [3, 7, 14]:
                if len(df) > window:
                    df[f'rolling_mean_{window}'] = df['value'].rolling(window=window).mean()
                    df[f'rolling_std_{window}'] = df['value'].rolling(window=window).std()
            
            # Select feature columns
            feature_columns = ['hour', 'day_of_week', 'day_of_month', 'month']
            for col in df.columns:
                if col.startswith('lag_') or col.startswith('rolling_'):
                    feature_columns.append(col)
            
            # Create feature matrix
            feature_df = df[feature_columns].fillna(method='ffill').fillna(0)
            return feature_df.values
            
        except Exception as e:
            logger.error(f"Feature creation failed: {e}")
            # Return basic time index as fallback
            return np.arange(len(df)).reshape(-1, 1)
    
    def _create_future_features(self, df: pd.DataFrame, horizon: int) -> np.ndarray:
        """Create features for future time periods"""
        try:
            # Get last timestamp
            last_timestamp = df['timestamp'].iloc[-1]
            
            # Generate future timestamps
            future_data = []
            for i in range(1, horizon + 1):
                future_timestamp = last_timestamp + timedelta(days=i)
                future_data.append({
                    'timestamp': future_timestamp,
                    'hour': future_timestamp.hour,
                    'day_of_week': future_timestamp.weekday(),
                    'day_of_month': future_timestamp.day,
                    'month': future_timestamp.month
                })
            
            future_df = pd.DataFrame(future_data)
            
            # Add lag features (use last known values)
            last_values = df['value'].tail(7).values  # Last 7 values
            for i, row_idx in enumerate(range(len(future_df))):
                for lag in [1, 2, 3, 7]:
                    if lag <= len(last_values):
                        future_df.loc[row_idx, f'lag_{lag}'] = last_values[-(lag)]
                    else:
                        future_df.loc[row_idx, f'lag_{lag}'] = last_values[0]
            
            # Add rolling statistics (use last known values)
            for window in [3, 7, 14]:
                last_mean = df['value'].tail(window).mean()
                last_std = df['value'].tail(window).std()
                future_df[f'rolling_mean_{window}'] = last_mean
                future_df[f'rolling_std_{window}'] = last_std if not pd.isna(last_std) else 0
            
            # Select feature columns (same as training)
            feature_columns = ['hour', 'day_of_week', 'day_of_month', 'month']
            for col in future_df.columns:
                if col.startswith('lag_') or col.startswith('rolling_'):
                    feature_columns.append(col)
            
            return future_df[feature_columns].fillna(0).values
            
        except Exception as e:
            logger.error(f"Future feature creation failed: {e}")
            # Return basic future time indices
            return np.arange(len(df), len(df) + horizon).reshape(-1, 1)
    
    def _combine_trend_results(
        self,
        trend_results: Dict[str, Dict[str, Any]],
        metric_name: str,
        time_period: str
    ) -> TrendResult:
        """Combine results from multiple trend analysis methods"""
        try:
            # Find best result based on confidence/r_squared
            best_result = None
            best_confidence = 0
            
            for method, result in trend_results.items():
                if 'error' not in result:
                    confidence = result.get('confidence', 0)
                    if confidence > best_confidence:
                        best_confidence = confidence
                        best_result = result
            
            if best_result is None:
                # Use default values if all methods failed
                return TrendResult(
                    metric_name=metric_name,
                    time_period=time_period,
                    trend_direction=TrendDirection.STABLE,
                    trend_strength=0.0,
                    slope=0.0,
                    r_squared=0.0,
                    confidence=0.0,
                    seasonal_patterns={},
                    anomalies=[],
                    forecast={},
                    recommendations=["Insufficient data for reliable trend analysis"]
                )
            
            # Extract values from best result
            direction = best_result.get('direction', TrendDirection.STABLE)
            slope = best_result.get('slope', 0.0)
            r_squared = best_result.get('r_squared', best_result.get('confidence', 0.0))
            trend_strength = best_result.get('strength', best_confidence)
            
            return TrendResult(
                metric_name=metric_name,
                time_period=time_period,
                trend_direction=direction,
                trend_strength=trend_strength,
                slope=slope,
                r_squared=r_squared,
                confidence=best_confidence,
                seasonal_patterns={},  # Will be filled by caller
                anomalies=[],  # Will be filled by caller
                forecast={},  # Will be filled by caller
                recommendations=[]  # Will be filled by caller
            )
            
        except Exception as e:
            logger.error(f"Failed to combine trend results: {e}")
            return self._get_error_result(metric_name, time_period, str(e))
    
    def _generate_trend_recommendations(self, trend_result: TrendResult) -> List[str]:
        """Generate actionable recommendations based on trend analysis"""
        recommendations = []
        
        try:
            direction = trend_result.trend_direction
            strength = trend_result.trend_strength
            confidence = trend_result.confidence
            
            # Direction-based recommendations
            if direction == TrendDirection.INCREASING:
                if strength > 0.7:
                    recommendations.append(f"Strong upward trend detected in {trend_result.metric_name} - monitor for capacity planning")
                    if "ticket" in trend_result.metric_name.lower():
                        recommendations.append("Consider increasing support team capacity or investigating root causes")
                else:
                    recommendations.append(f"Moderate upward trend in {trend_result.metric_name} - continue monitoring")
                    
            elif direction == TrendDirection.DECREASING:
                if strength > 0.7:
                    recommendations.append(f"Strong downward trend in {trend_result.metric_name} - investigate for improvement opportunities")
                    if "resolution_time" in trend_result.metric_name.lower():
                        recommendations.append("Excellent improvement in resolution times - document successful practices")
                else:
                    recommendations.append(f"Moderate downward trend in {trend_result.metric_name} - maintain current practices")
                    
            elif direction == TrendDirection.VOLATILE:
                recommendations.append(f"High volatility in {trend_result.metric_name} - investigate causes of instability")
                recommendations.append("Consider implementing more consistent processes or monitoring")
                
            else:  # STABLE
                if confidence > 0.8:
                    recommendations.append(f"{trend_result.metric_name} shows stable performance - maintain current practices")
                else:
                    recommendations.append(f"Trend unclear for {trend_result.metric_name} - collect more data for better analysis")
            
            # Confidence-based recommendations
            if confidence < 0.5:
                recommendations.append("Low confidence in trend analysis - consider extending observation period")
            
            # Seasonality-based recommendations
            if trend_result.seasonal_patterns.get('detected', False):
                pattern_type = trend_result.seasonal_patterns.get('type', 'unknown')
                recommendations.append(f"Seasonal {pattern_type} pattern detected - plan resources accordingly")
            
            # Anomaly-based recommendations
            if len(trend_result.anomalies) > 0:
                high_severity_count = sum(1 for a in trend_result.anomalies if a.get('severity') == 'high')
                if high_severity_count > 0:
                    recommendations.append(f"{high_severity_count} high-severity anomalies detected - investigate immediately")
                else:
                    recommendations.append(f"{len(trend_result.anomalies)} anomalies detected - review for patterns")
            
            # Forecast-based recommendations
            if trend_result.forecast and 'predictions' in trend_result.forecast:
                predictions = trend_result.forecast['predictions']
                if predictions:
                    future_trend = "increasing" if predictions[-1] > predictions[0] else "decreasing"
                    recommendations.append(f"Forecast suggests {future_trend} trend - prepare accordingly")
            
            return recommendations[:5]  # Limit to top 5 recommendations
            
        except Exception as e:
            logger.error(f"Recommendation generation failed: {e}")
            return ["Unable to generate specific recommendations due to analysis error"]
    
    def _filter_close_changes(self, changes: List[Dict], min_gap: timedelta) -> List[Dict]:
        """Filter out changes that are too close together"""
        if not changes:
            return []
        
        # Sort by timestamp
        sorted_changes = sorted(changes, key=lambda x: x['timestamp'])
        
        filtered = [sorted_changes[0]]
        
        for change in sorted_changes[1:]:
            last_change = filtered[-1]
            if change['timestamp'] - last_change['timestamp'] >= min_gap:
                filtered.append(change)
        
        return filtered
    
    def _prepare_dataframe(self, data: List[Dict]) -> pd.DataFrame:
        """Prepare data for analysis"""
        df = pd.DataFrame(data)
        df['timestamp'] = pd.to_datetime(df['timestamp'])
        df = df.sort_values('timestamp')
        return df
    
    async def _get_time_series_data(self, metric_name: str, time_period: str) -> List[Dict]:
        """Get time series data for a metric within a time period"""
        try:
            # Parse time period
            if time_period.endswith('d'):
                days = int(time_period[:-1])
                cutoff_time = datetime.utcnow() - timedelta(days=days)
            elif time_period.endswith('h'):
                hours = int(time_period[:-1])
                cutoff_time = datetime.utcnow() - timedelta(hours=hours)
            else:
                cutoff_time = datetime.utcnow() - timedelta(days=30)  # Default
            
            # Filter data
            if metric_name in self.time_series_data:
                filtered_data = [
                    point for point in self.time_series_data[metric_name]
                    if point['timestamp'] >= cutoff_time
                ]
                return filtered_data
            else:
                return []
                
        except Exception as e:
            logger.error(f"Failed to get time series data: {e}")
            return []
    
    def _get_insufficient_data_result(self, metric_name: str, time_period: str) -> TrendResult:
        """Return result for insufficient data"""
        return TrendResult(
            metric_name=metric_name,
            time_period=time_period,
            trend_direction=TrendDirection.STABLE,
            trend_strength=0.0,
            slope=0.0,
            r_squared=0.0,
            confidence=0.0,
            seasonal_patterns={'detected': False, 'reason': 'insufficient_data'},
            anomalies=[],
            forecast={'error': 'insufficient_data'},
            recommendations=["Collect more data points for reliable trend analysis"]
        )
    
    def _get_error_result(self, metric_name: str, time_period: str, error_msg: str) -> TrendResult:
        """Return result for analysis error"""
        return TrendResult(
            metric_name=metric_name,
            time_period=time_period,
            trend_direction=TrendDirection.STABLE,
            trend_strength=0.0,
            slope=0.0,
            r_squared=0.0,
            confidence=0.0,
            seasonal_patterns={'error': error_msg},
            anomalies=[],
            forecast={'error': error_msg},
            recommendations=[f"Analysis failed: {error_msg}"]
        )
    
    async def _load_historical_data(self):
        """Load historical data from storage"""
        try:
            # In production, load from database
            # For now, initialize empty
            logger.info("Historical data storage initialized")
            
        except Exception as e:
            logger.error(f"Failed to load historical data: {e}")
    
    async def _initialize_baselines(self):
        """Initialize baseline metrics"""
        try:
            # Calculate baseline values for tracked metrics
            for metric_name in self.tracked_metrics:
                if metric_name in self.time_series_data:
                    data = list(self.time_series_data[metric_name])
                    if data:
                        values = [point['value'] for point in data]
                        self.baseline_metrics[metric_name] = {
                            'mean': statistics.mean(values),
                            'median': statistics.median(values),
                            'std': statistics.stdev(values) if len(values) > 1 else 0,
                            'min': min(values),
                            'max': max(values)
                        }
            
            logger.info(f"Initialized baselines for {len(self.baseline_metrics)} metrics")
            
        except Exception as e:
            logger.error(f"Failed to initialize baselines: {e}")
    
    async def _initialize_models(self):
        """Initialize or load trend models"""
        try:
            # Initialize models for each tracked metric
            for metric_name in self.tracked_metrics:
                self.trend_models[metric_name] = {
                    'linear': LinearRegression(),
                    'polynomial': None,  # Will be created when needed
                    'seasonal': None   # Will be created when needed
                }
            
            logger.info("Trend models initialized")
            
        except Exception as e:
            logger.error(f"Failed to initialize models: {e}")
    
    async def _update_models(self, metric_name: str):
        """Update models with new data"""
        try:
            # This would retrain models with new data
            # For now, just log the update
            logger.info(f"Models updated for {metric_name}")
            
        except Exception as e:
            logger.error(f"Failed to update models for {metric_name}: {e}")
    
    async def get_trend_summary(self) -> Dict[str, Any]:
        """Get summary of trend analysis capabilities"""
        return {
            'tracked_metrics': self.tracked_metrics,
            'available_methods': list(self.trend_methods.keys()),
            'forecast_models': list(self.forecast_models.keys()),
            'data_points': {metric: len(data) for metric, data in self.time_series_data.items()},
            'advanced_ts_available': ADVANCED_TS_AVAILABLE,
            'configuration': self.config
        }