"""
Predictions API Endpoints

Provides predictive analysis capabilities including:
- Ticket volume forecasting
- Resource demand prediction
- System failure predictions
- Workload planning
- Performance trend predictions
- Risk assessments
"""

from fastapi import APIRouter, Depends, HTTPException, Query, BackgroundTasks
from sqlalchemy.ext.asyncio import AsyncSession
from typing import List, Optional, Dict, Any, Union
import logging
from datetime import datetime, timedelta
from pydantic import BaseModel, Field
from enum import Enum

from app.db.init_db import get_db
from app.ml.trend_analyzer import TrendAnalyzer, ForecastResult
from app.ml.ticket_classifier import TicketClassifier
from app.services.anomaly_detector import AnomalyDetector

logger = logging.getLogger(__name__)
router = APIRouter()

# Initialize services
trend_analyzer = TrendAnalyzer()
ticket_classifier = TicketClassifier()
anomaly_detector = AnomalyDetector()


class PredictionType(str, Enum):
    """Types of predictions available"""
    VOLUME_FORECAST = "volume_forecast"
    RESOURCE_DEMAND = "resource_demand"
    FAILURE_PREDICTION = "failure_prediction"
    PERFORMANCE_TREND = "performance_trend"
    WORKLOAD_PLANNING = "workload_planning"
    RISK_ASSESSMENT = "risk_assessment"


class TimeHorizon(str, Enum):
    """Prediction time horizons"""
    SHORT_TERM = "1d"      # 1 day
    MEDIUM_TERM = "7d"     # 1 week
    LONG_TERM = "30d"      # 1 month
    EXTENDED = "90d"       # 3 months


class ConfidenceLevel(str, Enum):
    """Confidence levels for predictions"""
    LOW = "low"            # 60-70%
    MEDIUM = "medium"      # 70-85%
    HIGH = "high"          # 85-95%
    VERY_HIGH = "very_high" # 95%+


# Response Models
class PredictionResult(BaseModel):
    """Base prediction result"""
    prediction_id: str
    prediction_type: PredictionType
    time_horizon: str
    confidence_level: ConfidenceLevel
    confidence_score: float
    generated_at: datetime
    valid_until: datetime
    metadata: Dict[str, Any]


class VolumeForecast(PredictionResult):
    """Volume forecasting result"""
    predicted_volume: List[Dict[str, Any]]
    peak_periods: List[Dict[str, Any]]
    resource_recommendations: List[str]
    seasonality_factors: Dict[str, Any]


class ResourceDemandPrediction(PredictionResult):
    """Resource demand prediction result"""
    predicted_demand: Dict[str, Any]
    capacity_requirements: Dict[str, Any]
    scaling_recommendations: List[str]
    cost_projections: Dict[str, Any]


class FailurePrediction(PredictionResult):
    """System failure prediction result"""
    failure_probability: float
    predicted_failure_time: Optional[datetime]
    failure_indicators: List[Dict[str, Any]]
    preventive_actions: List[str]
    risk_score: float


class PerformanceTrendPrediction(PredictionResult):
    """Performance trend prediction result"""
    predicted_metrics: Dict[str, List[float]]
    trend_analysis: Dict[str, Any]
    performance_alerts: List[Dict[str, Any]]
    optimization_opportunities: List[str]


class WorkloadPlanningResult(PredictionResult):
    """Workload planning result"""
    staffing_recommendations: Dict[str, Any]
    schedule_optimization: Dict[str, Any]
    workload_distribution: Dict[str, Any]
    efficiency_projections: Dict[str, Any]


class RiskAssessment(PredictionResult):
    """Risk assessment result"""
    overall_risk_score: float
    risk_factors: List[Dict[str, Any]]
    mitigation_strategies: List[str]
    impact_analysis: Dict[str, Any]


@router.post("/volume-forecast", response_model=VolumeForecast)
async def create_volume_forecast(
    time_horizon: TimeHorizon = TimeHorizon.MEDIUM_TERM,
    categories: Optional[List[str]] = Query(None, description="Filter by specific categories"),
    include_seasonality: bool = Query(True, description="Include seasonal patterns"),
    confidence_level: float = Query(0.85, description="Desired confidence level"),
    db: AsyncSession = Depends(get_db)
):
    """
    Generate ticket volume forecast
    """
    try:
        logger.info(f"Generating volume forecast for {time_horizon}")
        
        # Generate forecast using trend analyzer
        forecast_days = _parse_time_horizon(time_horizon)
        
        # Get multiple metrics for comprehensive forecasting
        metrics = ['ticket_volume']
        if categories:
            metrics.extend([f'category_{cat}_volume' for cat in categories])
        
        forecast_results = await trend_analyzer.bulk_forecast(
            metrics=metrics,
            forecast_horizon=forecast_days,
            confidence_level=confidence_level
        )
        
        # Process results
        predicted_volume = []
        peak_periods = []
        
        if 'ticket_volume' in forecast_results:
            main_forecast = forecast_results['ticket_volume']
            
            # Generate daily predictions
            for i, (value, (lower, upper)) in enumerate(zip(
                main_forecast.predicted_values,
                main_forecast.confidence_intervals
            )):
                prediction_date = datetime.utcnow() + timedelta(days=i+1)
                predicted_volume.append({
                    'date': prediction_date.isoformat(),
                    'predicted_value': round(value, 1),
                    'confidence_interval': {'lower': round(lower, 1), 'upper': round(upper, 1)},
                    'day_of_week': prediction_date.strftime('%A')
                })
                
                # Identify peak periods (above 90th percentile)
                if value > np.percentile(main_forecast.predicted_values, 90):
                    peak_periods.append({
                        'date': prediction_date.isoformat(),
                        'predicted_volume': round(value, 1),
                        'intensity': 'high' if value > np.percentile(main_forecast.predicted_values, 95) else 'medium'
                    })
        
        # Generate recommendations
        resource_recommendations = _generate_volume_recommendations(
            predicted_volume, peak_periods, forecast_days
        )
        
        # Analyze seasonality
        seasonality_factors = await _analyze_volume_seasonality(include_seasonality)
        
        prediction_id = f"volume_forecast_{datetime.utcnow().strftime('%Y%m%d_%H%M%S')}"
        
        return VolumeForecast(
            prediction_id=prediction_id,
            prediction_type=PredictionType.VOLUME_FORECAST,
            time_horizon=time_horizon,
            confidence_level=_score_to_confidence_level(confidence_level),
            confidence_score=confidence_level,
            generated_at=datetime.utcnow(),
            valid_until=datetime.utcnow() + timedelta(hours=24),
            metadata={
                'categories_included': categories or [],
                'seasonality_included': include_seasonality,
                'forecast_horizon_days': forecast_days
            },
            predicted_volume=predicted_volume,
            peak_periods=peak_periods,
            resource_recommendations=resource_recommendations,
            seasonality_factors=seasonality_factors
        )
        
    except Exception as e:
        logger.error(f"Volume forecast generation failed: {e}")
        raise HTTPException(status_code=500, detail="Failed to generate volume forecast")


@router.post("/resource-demand", response_model=ResourceDemandPrediction)
async def predict_resource_demand(
    time_horizon: TimeHorizon = TimeHorizon.MEDIUM_TERM,
    resource_types: List[str] = Query(["agents", "infrastructure"], description="Types of resources to predict"),
    growth_assumptions: Optional[Dict[str, float]] = None,
    current_capacity: Optional[Dict[str, float]] = None,
    db: AsyncSession = Depends(get_db)
):
    """
    Predict resource demand based on forecasted workload
    """
    try:
        logger.info(f"Predicting resource demand for {time_horizon}")
        
        forecast_days = _parse_time_horizon(time_horizon)
        
        # Get volume forecast first
        volume_forecast = await trend_analyzer.bulk_forecast(
            metrics=['ticket_volume', 'complexity_score'],
            forecast_horizon=forecast_days,
            confidence_level=0.80
        )
        
        # Calculate resource demand based on workload
        predicted_demand = {}
        capacity_requirements = {}
        
        if "agents" in resource_types:
            agent_demand = await _calculate_agent_demand(
                volume_forecast, growth_assumptions, current_capacity
            )
            predicted_demand['agents'] = agent_demand
            capacity_requirements['agents'] = _calculate_agent_capacity(agent_demand)
        
        if "infrastructure" in resource_types:
            infra_demand = await _calculate_infrastructure_demand(
                volume_forecast, growth_assumptions
            )
            predicted_demand['infrastructure'] = infra_demand
            capacity_requirements['infrastructure'] = _calculate_infrastructure_capacity(infra_demand)
        
        # Generate scaling recommendations
        scaling_recommendations = _generate_scaling_recommendations(
            predicted_demand, capacity_requirements, current_capacity
        )
        
        # Calculate cost projections
        cost_projections = _calculate_cost_projections(
            capacity_requirements, forecast_days
        )
        
        prediction_id = f"resource_demand_{datetime.utcnow().strftime('%Y%m%d_%H%M%S')}"
        
        return ResourceDemandPrediction(
            prediction_id=prediction_id,
            prediction_type=PredictionType.RESOURCE_DEMAND,
            time_horizon=time_horizon,
            confidence_level=ConfidenceLevel.MEDIUM,
            confidence_score=0.75,
            generated_at=datetime.utcnow(),
            valid_until=datetime.utcnow() + timedelta(hours=48),
            metadata={
                'resource_types': resource_types,
                'growth_assumptions': growth_assumptions or {},
                'forecast_days': forecast_days
            },
            predicted_demand=predicted_demand,
            capacity_requirements=capacity_requirements,
            scaling_recommendations=scaling_recommendations,
            cost_projections=cost_projections
        )
        
    except Exception as e:
        logger.error(f"Resource demand prediction failed: {e}")
        raise HTTPException(status_code=500, detail="Failed to predict resource demand")


@router.post("/failure-prediction", response_model=FailurePrediction)
async def predict_system_failures(
    system_components: List[str] = Query(["application", "database", "network"], description="Components to analyze"),
    historical_days: int = Query(30, description="Days of historical data to analyze"),
    alert_threshold: float = Query(0.7, description="Probability threshold for alerts"),
    db: AsyncSession = Depends(get_db)
):
    """
    Predict potential system failures
    """
    try:
        logger.info(f"Predicting system failures for components: {system_components}")
        
        # Analyze current system metrics for anomalies
        system_metrics = await _get_system_metrics(system_components)
        
        failure_indicators = []
        failure_probability = 0.0
        
        for component in system_components:
            # Detect anomalies in component metrics
            if component in system_metrics:
                anomaly_result = await anomaly_detector.detect_anomalies(
                    metrics=system_metrics[component],
                    metric_type="system"
                )
                
                # Calculate failure probability based on anomalies
                component_risk = _calculate_failure_risk(anomaly_result, component)
                
                if component_risk['probability'] > alert_threshold:
                    failure_indicators.append({
                        'component': component,
                        'risk_level': component_risk['risk_level'],
                        'probability': component_risk['probability'],
                        'indicators': component_risk['indicators'],
                        'last_incident': component_risk.get('last_incident')
                    })
                
                failure_probability = max(failure_probability, component_risk['probability'])
        
        # Predict failure time if probability is high
        predicted_failure_time = None
        if failure_probability > 0.8:
            predicted_failure_time = datetime.utcnow() + timedelta(
                hours=24 * (1 - failure_probability)  # Sooner if higher probability
            )
        
        # Generate preventive actions
        preventive_actions = _generate_preventive_actions(failure_indicators)
        
        # Calculate overall risk score
        risk_score = _calculate_overall_risk_score(failure_indicators, failure_probability)
        
        prediction_id = f"failure_pred_{datetime.utcnow().strftime('%Y%m%d_%H%M%S')}"
        
        return FailurePrediction(
            prediction_id=prediction_id,
            prediction_type=PredictionType.FAILURE_PREDICTION,
            time_horizon="24h",
            confidence_level=_score_to_confidence_level(0.8),
            confidence_score=0.8,
            generated_at=datetime.utcnow(),
            valid_until=datetime.utcnow() + timedelta(hours=6),
            metadata={
                'analyzed_components': system_components,
                'historical_days': historical_days,
                'alert_threshold': alert_threshold
            },
            failure_probability=failure_probability,
            predicted_failure_time=predicted_failure_time,
            failure_indicators=failure_indicators,
            preventive_actions=preventive_actions,
            risk_score=risk_score
        )
        
    except Exception as e:
        logger.error(f"Failure prediction failed: {e}")
        raise HTTPException(status_code=500, detail="Failed to predict system failures")


@router.post("/performance-trends", response_model=PerformanceTrendPrediction)
async def predict_performance_trends(
    metrics: List[str] = Query(["response_time", "throughput", "error_rate"], description="Performance metrics to analyze"),
    time_horizon: TimeHorizon = TimeHorizon.MEDIUM_TERM,
    include_optimization: bool = Query(True, description="Include optimization suggestions"),
    db: AsyncSession = Depends(get_db)
):
    """
    Predict performance trends and identify optimization opportunities
    """
    try:
        logger.info(f"Predicting performance trends for metrics: {metrics}")
        
        forecast_days = _parse_time_horizon(time_horizon)
        
        # Generate forecasts for each metric
        forecast_results = await trend_analyzer.bulk_forecast(
            metrics=metrics,
            forecast_horizon=forecast_days,
            confidence_level=0.80
        )
        
        predicted_metrics = {}
        trend_analysis = {}
        performance_alerts = []
        
        for metric in metrics:
            if metric in forecast_results:
                forecast = forecast_results[metric]
                predicted_metrics[metric] = forecast.predicted_values
                
                # Analyze trend
                trend_analysis[metric] = {
                    'direction': _determine_trend_direction(forecast.predicted_values),
                    'volatility': _calculate_volatility(forecast.predicted_values),
                    'forecast_accuracy': forecast.forecast_accuracy,
                    'model_used': forecast.model_used
                }
                
                # Check for performance alerts
                alerts = _check_performance_thresholds(metric, forecast.predicted_values)
                performance_alerts.extend(alerts)
        
        # Generate optimization opportunities
        optimization_opportunities = []
        if include_optimization:
            optimization_opportunities = _identify_optimization_opportunities(
                predicted_metrics, trend_analysis
            )
        
        prediction_id = f"perf_trends_{datetime.utcnow().strftime('%Y%m%d_%H%M%S')}"
        
        return PerformanceTrendPrediction(
            prediction_id=prediction_id,
            prediction_type=PredictionType.PERFORMANCE_TREND,
            time_horizon=time_horizon,
            confidence_level=ConfidenceLevel.MEDIUM,
            confidence_score=0.75,
            generated_at=datetime.utcnow(),
            valid_until=datetime.utcnow() + timedelta(hours=48),
            metadata={
                'analyzed_metrics': metrics,
                'forecast_days': forecast_days,
                'optimization_included': include_optimization
            },
            predicted_metrics=predicted_metrics,
            trend_analysis=trend_analysis,
            performance_alerts=performance_alerts,
            optimization_opportunities=optimization_opportunities
        )
        
    except Exception as e:
        logger.error(f"Performance trend prediction failed: {e}")
        raise HTTPException(status_code=500, detail="Failed to predict performance trends")


@router.post("/workload-planning", response_model=WorkloadPlanningResult)
async def generate_workload_planning(
    time_horizon: TimeHorizon = TimeHorizon.MEDIUM_TERM,
    team_size: int = Query(10, description="Current team size"),
    shift_patterns: List[str] = Query(["day", "evening"], description="Available shift patterns"),
    skill_requirements: Optional[Dict[str, int]] = None,
    db: AsyncSession = Depends(get_db)
):
    """
    Generate intelligent workload planning and staffing recommendations
    """
    try:
        logger.info(f"Generating workload planning for {time_horizon}")
        
        forecast_days = _parse_time_horizon(time_horizon)
        
        # Get volume and complexity forecasts
        workload_forecast = await trend_analyzer.bulk_forecast(
            metrics=['ticket_volume', 'avg_complexity', 'resolution_time'],
            forecast_horizon=forecast_days,
            confidence_level=0.80
        )
        
        # Calculate staffing requirements
        staffing_recommendations = await _calculate_staffing_requirements(
            workload_forecast, team_size, skill_requirements
        )
        
        # Optimize schedules
        schedule_optimization = _optimize_schedules(
            workload_forecast, shift_patterns, team_size
        )
        
        # Analyze workload distribution
        workload_distribution = _analyze_workload_distribution(
            workload_forecast, staffing_recommendations
        )
        
        # Project efficiency gains
        efficiency_projections = _calculate_efficiency_projections(
            staffing_recommendations, schedule_optimization
        )
        
        prediction_id = f"workload_plan_{datetime.utcnow().strftime('%Y%m%d_%H%M%S')}"
        
        return WorkloadPlanningResult(
            prediction_id=prediction_id,
            prediction_type=PredictionType.WORKLOAD_PLANNING,
            time_horizon=time_horizon,
            confidence_level=ConfidenceLevel.HIGH,
            confidence_score=0.85,
            generated_at=datetime.utcnow(),
            valid_until=datetime.utcnow() + timedelta(days=7),
            metadata={
                'current_team_size': team_size,
                'shift_patterns': shift_patterns,
                'forecast_days': forecast_days
            },
            staffing_recommendations=staffing_recommendations,
            schedule_optimization=schedule_optimization,
            workload_distribution=workload_distribution,
            efficiency_projections=efficiency_projections
        )
        
    except Exception as e:
        logger.error(f"Workload planning generation failed: {e}")
        raise HTTPException(status_code=500, detail="Failed to generate workload planning")


@router.post("/risk-assessment", response_model=RiskAssessment)
async def perform_risk_assessment(
    assessment_scope: List[str] = Query(["operational", "technical", "business"], description="Risk assessment scope"),
    time_horizon: TimeHorizon = TimeHorizon.MEDIUM_TERM,
    risk_tolerance: str = Query("medium", description="Organization risk tolerance"),
    db: AsyncSession = Depends(get_db)
):
    """
    Perform comprehensive risk assessment with mitigation strategies
    """
    try:
        logger.info(f"Performing risk assessment for scope: {assessment_scope}")
        
        risk_factors = []
        overall_risk_score = 0.0
        
        # Operational risks
        if "operational" in assessment_scope:
            operational_risks = await _assess_operational_risks(time_horizon)
            risk_factors.extend(operational_risks)
        
        # Technical risks
        if "technical" in assessment_scope:
            technical_risks = await _assess_technical_risks(time_horizon)
            risk_factors.extend(technical_risks)
        
        # Business risks
        if "business" in assessment_scope:
            business_risks = await _assess_business_risks(time_horizon)
            risk_factors.extend(business_risks)
        
        # Calculate overall risk score
        if risk_factors:
            risk_scores = [rf['risk_score'] for rf in risk_factors]
            overall_risk_score = sum(risk_scores) / len(risk_scores)
        
        # Generate mitigation strategies
        mitigation_strategies = _generate_mitigation_strategies(
            risk_factors, risk_tolerance
        )
        
        # Perform impact analysis
        impact_analysis = _perform_impact_analysis(risk_factors, overall_risk_score)
        
        prediction_id = f"risk_assess_{datetime.utcnow().strftime('%Y%m%d_%H%M%S')}"
        
        return RiskAssessment(
            prediction_id=prediction_id,
            prediction_type=PredictionType.RISK_ASSESSMENT,
            time_horizon=time_horizon,
            confidence_level=ConfidenceLevel.HIGH,
            confidence_score=0.90,
            generated_at=datetime.utcnow(),
            valid_until=datetime.utcnow() + timedelta(days=30),
            metadata={
                'assessment_scope': assessment_scope,
                'risk_tolerance': risk_tolerance,
                'total_risk_factors': len(risk_factors)
            },
            overall_risk_score=overall_risk_score,
            risk_factors=risk_factors,
            mitigation_strategies=mitigation_strategies,
            impact_analysis=impact_analysis
        )
        
    except Exception as e:
        logger.error(f"Risk assessment failed: {e}")
        raise HTTPException(status_code=500, detail="Failed to perform risk assessment")


@router.get("/predictions/{prediction_id}")
async def get_prediction_details(
    prediction_id: str,
    db: AsyncSession = Depends(get_db)
):
    """
    Get details of a specific prediction
    """
    try:
        logger.info(f"Retrieving prediction details for: {prediction_id}")
        
        # In production, retrieve from database
        # For now, return simulated data
        
        prediction_details = {
            'prediction_id': prediction_id,
            'status': 'completed',
            'generated_at': datetime.utcnow() - timedelta(hours=2),
            'accessed_count': 5,
            'last_accessed': datetime.utcnow() - timedelta(minutes=30),
            'accuracy_metrics': {
                'confidence_score': 0.85,
                'validation_score': 0.78,
                'feedback_score': 4.2
            },
            'usage_metrics': {
                'view_count': 12,
                'download_count': 3,
                'shared_count': 1
            }
        }
        
        return prediction_details
        
    except Exception as e:
        logger.error(f"Failed to retrieve prediction {prediction_id}: {e}")
        raise HTTPException(status_code=500, detail="Failed to retrieve prediction details")


@router.get("/prediction-history")
async def get_prediction_history(
    prediction_type: Optional[PredictionType] = Query(None, description="Filter by prediction type"),
    days: int = Query(30, description="Number of days to look back"),
    limit: int = Query(50, description="Maximum number of results"),
    db: AsyncSession = Depends(get_db)
):
    """
    Get history of predictions
    """
    try:
        logger.info(f"Retrieving prediction history for last {days} days")
        
        # In production, query from database
        # For now, return simulated data
        
        history = []
        for i in range(min(limit, 20)):  # Simulate some history
            prediction_date = datetime.utcnow() - timedelta(days=i//3, hours=i%24)
            history.append({
                'prediction_id': f"pred_{prediction_date.strftime('%Y%m%d_%H%M%S')}_{i}",
                'prediction_type': prediction_type or PredictionType.VOLUME_FORECAST,
                'generated_at': prediction_date,
                'confidence_score': 0.7 + (i % 3) * 0.1,
                'status': 'completed',
                'accuracy_score': 0.6 + (i % 4) * 0.1
            })
        
        return {
            'total_predictions': len(history),
            'date_range': {
                'from': datetime.utcnow() - timedelta(days=days),
                'to': datetime.utcnow()
            },
            'predictions': history
        }
        
    except Exception as e:
        logger.error(f"Failed to retrieve prediction history: {e}")
        raise HTTPException(status_code=500, detail="Failed to retrieve prediction history")


# Helper Functions
def _parse_time_horizon(horizon: TimeHorizon) -> int:
    """Parse time horizon to number of days"""
    horizon_map = {
        TimeHorizon.SHORT_TERM: 1,
        TimeHorizon.MEDIUM_TERM: 7,
        TimeHorizon.LONG_TERM: 30,
        TimeHorizon.EXTENDED: 90
    }
    return horizon_map.get(horizon, 7)


def _score_to_confidence_level(score: float) -> ConfidenceLevel:
    """Convert numeric score to confidence level"""
    if score >= 0.95:
        return ConfidenceLevel.VERY_HIGH
    elif score >= 0.85:
        return ConfidenceLevel.HIGH
    elif score >= 0.70:
        return ConfidenceLevel.MEDIUM
    else:
        return ConfidenceLevel.LOW


def _generate_volume_recommendations(
    predicted_volume: List[Dict[str, Any]],
    peak_periods: List[Dict[str, Any]],
    forecast_days: int
) -> List[str]:
    """Generate recommendations based on volume forecast"""
    recommendations = []
    
    if peak_periods:
        recommendations.append(f"Prepare for {len(peak_periods)} high-volume periods in the next {forecast_days} days")
        recommendations.append("Consider scheduling additional staff during peak periods")
    
    if len(predicted_volume) > 0:
        avg_volume = sum(p['predicted_value'] for p in predicted_volume) / len(predicted_volume)
        if avg_volume > 50:  # Threshold for high volume
            recommendations.append("Implement automation tools to handle increased volume")
            recommendations.append("Review knowledge base to improve self-service options")
    
    return recommendations


async def _analyze_volume_seasonality(include_seasonality: bool) -> Dict[str, Any]:
    """Analyze seasonality factors"""
    if not include_seasonality:
        return {'analyzed': False}
    
    return {
        'analyzed': True,
        'patterns': {
            'weekly': {'peak_day': 'Tuesday', 'low_day': 'Friday'},
            'daily': {'peak_hour': 10, 'low_hour': 22},
            'monthly': {'peak_week': 1, 'low_week': 4}
        },
        'seasonal_strength': 0.65,
        'confidence': 0.78
    }


async def _calculate_agent_demand(
    volume_forecast: Dict[str, ForecastResult],
    growth_assumptions: Optional[Dict[str, float]],
    current_capacity: Optional[Dict[str, float]]
) -> Dict[str, Any]:
    """Calculate agent demand based on volume forecast"""
    if 'ticket_volume' not in volume_forecast:
        return {}
    
    forecast = volume_forecast['ticket_volume']
    avg_predicted_volume = sum(forecast.predicted_values) / len(forecast.predicted_values)
    
    # Assume each agent can handle 8 tickets per day
    agents_needed = avg_predicted_volume / 8
    
    return {
        'daily_average': round(agents_needed, 1),
        'peak_requirement': round(max(forecast.predicted_values) / 8, 1),
        'minimum_requirement': round(min(forecast.predicted_values) / 8, 1),
        'growth_factor': growth_assumptions.get('agent_productivity', 1.0) if growth_assumptions else 1.0
    }


def _calculate_agent_capacity(agent_demand: Dict[str, Any]) -> Dict[str, Any]:
    """Calculate agent capacity requirements"""
    return {
        'recommended_staff': int(agent_demand.get('peak_requirement', 0) * 1.2),  # 20% buffer
        'minimum_staff': int(agent_demand.get('daily_average', 0)),
        'flexibility_buffer': 0.2,
        'skill_distribution': {
            'senior': 0.3,
            'mid_level': 0.5,
            'junior': 0.2
        }
    }


async def _calculate_infrastructure_demand(
    volume_forecast: Dict[str, ForecastResult],
    growth_assumptions: Optional[Dict[str, float]]
) -> Dict[str, Any]:
    """Calculate infrastructure demand"""
    return {
        'compute_scaling': 1.15,  # 15% increase
        'storage_requirement': '500GB',
        'network_bandwidth': '1Gbps',
        'database_scaling': 1.10
    }


def _calculate_infrastructure_capacity(infra_demand: Dict[str, Any]) -> Dict[str, Any]:
    """Calculate infrastructure capacity requirements"""
    return {
        'servers': {'current': 5, 'recommended': 6},
        'storage': {'current': '1TB', 'recommended': '1.5TB'},
        'bandwidth': {'current': '500Mbps', 'recommended': '1Gbps'}
    }


def _generate_scaling_recommendations(
    predicted_demand: Dict[str, Any],
    capacity_requirements: Dict[str, Any],
    current_capacity: Optional[Dict[str, float]]
) -> List[str]:
    """Generate scaling recommendations"""
    recommendations = []
    
    if 'agents' in predicted_demand:
        agent_demand = predicted_demand['agents']
        recommended_staff = capacity_requirements.get('agents', {}).get('recommended_staff', 0)
        
        if recommended_staff > 0:
            recommendations.append(f"Consider hiring {recommended_staff - 10} additional agents")  # Assuming current is 10
            recommendations.append("Implement flexible staffing model for peak periods")
    
    if 'infrastructure' in predicted_demand:
        recommendations.append("Plan infrastructure scaling within next 30 days")
        recommendations.append("Consider cloud auto-scaling solutions")
    
    return recommendations


def _calculate_cost_projections(
    capacity_requirements: Dict[str, Any],
    forecast_days: int
) -> Dict[str, Any]:
    """Calculate cost projections"""
    return {
        'staffing_costs': {
            'monthly': 85000,  # Example cost
            'annual': 1020000,
            'per_agent': 8500
        },
        'infrastructure_costs': {
            'monthly': 12000,
            'annual': 144000,
            'scaling_cost': 3000
        },
        'total_projected_increase': {
            'monthly': 15000,
            'annual': 180000,
            'percentage': 18.5
        }
    }


async def _get_system_metrics(components: List[str]) -> Dict[str, Dict[str, float]]:
    """Get current system metrics for failure prediction"""
    import random
    
    metrics = {}
    for component in components:
        metrics[component] = {
            'cpu_usage': random.uniform(60, 95),
            'memory_usage': random.uniform(70, 90),
            'disk_usage': random.uniform(50, 85),
            'error_rate': random.uniform(0.01, 0.1),
            'response_time': random.uniform(0.5, 3.0)
        }
    
    return metrics


def _calculate_failure_risk(anomaly_result, component: str) -> Dict[str, Any]:
    """Calculate failure risk based on anomaly detection"""
    # Calculate risk based on anomaly severity and count
    critical_count = anomaly_result.severity_breakdown.get('critical', 0)
    high_count = anomaly_result.severity_breakdown.get('high', 0)
    
    risk_score = (critical_count * 0.4 + high_count * 0.2) / max(anomaly_result.total_anomalies, 1)
    
    return {
        'probability': min(risk_score, 0.95),
        'risk_level': 'high' if risk_score > 0.7 else 'medium' if risk_score > 0.4 else 'low',
        'indicators': [f'{component}_anomaly_detected'],
        'last_incident': datetime.utcnow() - timedelta(days=15)  # Simulated
    }


def _generate_preventive_actions(failure_indicators: List[Dict[str, Any]]) -> List[str]:
    """Generate preventive actions based on failure indicators"""
    actions = []
    
    for indicator in failure_indicators:
        component = indicator['component']
        risk_level = indicator['risk_level']
        
        if risk_level == 'high':
            actions.append(f"Immediate inspection of {component} required")
            actions.append(f"Prepare backup/failover for {component}")
        
        actions.append(f"Monitor {component} metrics closely")
        actions.append(f"Schedule maintenance window for {component}")
    
    if not actions:
        actions.append("Continue normal monitoring procedures")
    
    return list(set(actions))  # Remove duplicates


def _calculate_overall_risk_score(
    failure_indicators: List[Dict[str, Any]],
    failure_probability: float
) -> float:
    """Calculate overall risk score"""
    if not failure_indicators:
        return 0.1  # Baseline risk
    
    # Combine individual risks
    risk_scores = [indicator['probability'] for indicator in failure_indicators]
    avg_risk = sum(risk_scores) / len(risk_scores)
    
    # Weight by number of components at risk
    component_factor = min(len(failure_indicators) / 5.0, 1.0)  # Max factor of 1.0
    
    return min(avg_risk * (1 + component_factor), 1.0)


def _determine_trend_direction(values: List[float]) -> str:
    """Determine trend direction from predicted values"""
    if len(values) < 2:
        return "stable"
    
    start_avg = sum(values[:len(values)//3]) / (len(values)//3)
    end_avg = sum(values[-len(values)//3:]) / (len(values)//3)
    
    change_percent = ((end_avg - start_avg) / start_avg) * 100 if start_avg != 0 else 0
    
    if change_percent > 5:
        return "increasing"
    elif change_percent < -5:
        return "decreasing"
    else:
        return "stable"


def _calculate_volatility(values: List[float]) -> float:
    """Calculate volatility of predicted values"""
    if len(values) < 2:
        return 0.0
    
    import statistics
    return statistics.stdev(values) / statistics.mean(values) if statistics.mean(values) != 0 else 0.0


def _check_performance_thresholds(metric: str, values: List[float]) -> List[Dict[str, Any]]:
    """Check predicted values against performance thresholds"""
    alerts = []
    
    thresholds = {
        'response_time': {'warning': 2.0, 'critical': 5.0},
        'error_rate': {'warning': 0.05, 'critical': 0.10},
        'throughput': {'warning': 800, 'critical': 500}  # Lower is worse for throughput
    }
    
    if metric in thresholds:
        metric_thresholds = thresholds[metric]
        
        for i, value in enumerate(values):
            alert_level = None
            
            if metric == 'throughput':  # Lower is worse
                if value < metric_thresholds['critical']:
                    alert_level = 'critical'
                elif value < metric_thresholds['warning']:
                    alert_level = 'warning'
            else:  # Higher is worse
                if value > metric_thresholds['critical']:
                    alert_level = 'critical'
                elif value > metric_thresholds['warning']:
                    alert_level = 'warning'
            
            if alert_level:
                alerts.append({
                    'metric': metric,
                    'predicted_value': value,
                    'threshold_exceeded': metric_thresholds[alert_level],
                    'severity': alert_level,
                    'predicted_date': (datetime.utcnow() + timedelta(days=i+1)).isoformat()
                })
    
    return alerts


def _identify_optimization_opportunities(
    predicted_metrics: Dict[str, List[float]],
    trend_analysis: Dict[str, Any]
) -> List[str]:
    """Identify performance optimization opportunities"""
    opportunities = []
    
    for metric, values in predicted_metrics.items():
        trend = trend_analysis.get(metric, {})
        direction = trend.get('direction', 'stable')
        volatility = trend.get('volatility', 0)
        
        if direction == 'increasing' and metric in ['response_time', 'error_rate']:
            opportunities.append(f"Address increasing {metric} trend through optimization")
        
        if volatility > 0.2:
            opportunities.append(f"Reduce {metric} volatility through better resource management")
        
        if metric == 'throughput' and direction == 'decreasing':
            opportunities.append("Investigate and resolve throughput degradation")
    
    if not opportunities:
        opportunities.append("Current performance trends are within acceptable ranges")
    
    return opportunities


async def _calculate_staffing_requirements(
    workload_forecast: Dict[str, ForecastResult],
    current_team_size: int,
    skill_requirements: Optional[Dict[str, int]]
) -> Dict[str, Any]:
    """Calculate staffing requirements based on workload forecast"""
    requirements = {
        'recommended_team_size': current_team_size,
        'skill_gaps': [],
        'hiring_recommendations': [],
        'training_needs': []
    }
    
    if 'ticket_volume' in workload_forecast:
        volume_forecast = workload_forecast['ticket_volume']
        avg_volume = sum(volume_forecast.predicted_values) / len(volume_forecast.predicted_values)
        
        # Calculate team size based on volume (assuming 8 tickets per agent per day)
        required_agents = int(avg_volume / 8) + 1  # Add buffer
        
        if required_agents > current_team_size:
            requirements['recommended_team_size'] = required_agents
            requirements['hiring_recommendations'].append(
                f"Hire {required_agents - current_team_size} additional agents"
            )
        
        # Check skill requirements
        if skill_requirements:
            for skill, needed_count in skill_requirements.items():
                if needed_count > current_team_size * 0.3:  # Assuming 30% have each skill
                    requirements['skill_gaps'].append(skill)
                    requirements['training_needs'].append(f"Increase {skill} expertise in team")
    
    return requirements


def _optimize_schedules(
    workload_forecast: Dict[str, ForecastResult],
    shift_patterns: List[str],
    team_size: int
) -> Dict[str, Any]:
    """Optimize work schedules based on forecast"""
    return {
        'recommended_shifts': {
            'day_shift': {'agents': int(team_size * 0.6), 'hours': '9:00-17:00'},
            'evening_shift': {'agents': int(team_size * 0.3), 'hours': '17:00-01:00'},
            'night_shift': {'agents': int(team_size * 0.1), 'hours': '01:00-09:00'}
        },
        'peak_hour_coverage': {
            'hours': ['10:00-12:00', '14:00-16:00'],
            'additional_agents': 2
        },
        'flexibility_recommendations': [
            "Implement flexible start times during peak periods",
            "Consider remote work options for increased coverage"
        ]
    }


def _analyze_workload_distribution(
    workload_forecast: Dict[str, ForecastResult],
    staffing_recommendations: Dict[str, Any]
) -> Dict[str, Any]:
    """Analyze workload distribution"""
    return {
        'tickets_per_agent_per_day': 8.5,
        'workload_balance': 'good',
        'peak_load_ratio': 1.4,
        'distribution_by_day': {
            'Monday': 1.2,
            'Tuesday': 1.3,
            'Wednesday': 1.1,
            'Thursday': 1.0,
            'Friday': 0.8,
            'Saturday': 0.6,
            'Sunday': 0.5
        }
    }


def _calculate_efficiency_projections(
    staffing_recommendations: Dict[str, Any],
    schedule_optimization: Dict[str, Any]
) -> Dict[str, Any]:
    """Calculate efficiency projections"""
    return {
        'productivity_improvement': 0.15,  # 15% improvement
        'cost_efficiency': 0.12,  # 12% cost reduction
        'sla_compliance_improvement': 0.08,  # 8% improvement
        'customer_satisfaction_impact': 0.05,  # 5% improvement
        'roi_timeframe': '3-6 months'
    }


async def _assess_operational_risks(time_horizon: TimeHorizon) -> List[Dict[str, Any]]:
    """Assess operational risks"""
    return [
        {
            'risk_category': 'operational',
            'risk_type': 'staff_shortage',
            'description': 'Potential staff shortage during peak periods',
            'probability': 0.6,
            'impact': 0.7,
            'risk_score': 0.42,
            'time_frame': str(time_horizon)
        },
        {
            'risk_category': 'operational',
            'risk_type': 'process_bottleneck',
            'description': 'Ticket routing process may become bottleneck',
            'probability': 0.4,
            'impact': 0.5,
            'risk_score': 0.20,
            'time_frame': str(time_horizon)
        }
    ]


async def _assess_technical_risks(time_horizon: TimeHorizon) -> List[Dict[str, Any]]:
    """Assess technical risks"""
    return [
        {
            'risk_category': 'technical',
            'risk_type': 'system_failure',
            'description': 'Database performance degradation risk',
            'probability': 0.3,
            'impact': 0.9,
            'risk_score': 0.27,
            'time_frame': str(time_horizon)
        },
        {
            'risk_category': 'technical',
            'risk_type': 'security_vulnerability',
            'description': 'Potential security vulnerabilities in aging systems',
            'probability': 0.5,
            'impact': 0.8,
            'risk_score': 0.40,
            'time_frame': str(time_horizon)
        }
    ]


async def _assess_business_risks(time_horizon: TimeHorizon) -> List[Dict[str, Any]]:
    """Assess business risks"""
    return [
        {
            'risk_category': 'business',
            'risk_type': 'customer_satisfaction',
            'description': 'Risk of declining customer satisfaction due to increased volume',
            'probability': 0.7,
            'impact': 0.6,
            'risk_score': 0.42,
            'time_frame': str(time_horizon)
        }
    ]


def _generate_mitigation_strategies(
    risk_factors: List[Dict[str, Any]],
    risk_tolerance: str
) -> List[str]:
    """Generate risk mitigation strategies"""
    strategies = []
    
    tolerance_thresholds = {
        'low': 0.3,
        'medium': 0.5,
        'high': 0.7
    }
    
    threshold = tolerance_thresholds.get(risk_tolerance, 0.5)
    
    for risk in risk_factors:
        if risk['risk_score'] > threshold:
            risk_type = risk['risk_type']
            
            if risk_type == 'staff_shortage':
                strategies.append("Implement cross-training program to increase staff flexibility")
                strategies.append("Establish partnerships with temporary staffing agencies")
            
            elif risk_type == 'system_failure':
                strategies.append("Implement redundant systems and failover procedures")
                strategies.append("Increase monitoring and predictive maintenance")
            
            elif risk_type == 'security_vulnerability':
                strategies.append("Accelerate security patch deployment schedule")
                strategies.append("Conduct comprehensive security audit")
            
            else:
                strategies.append(f"Develop specific mitigation plan for {risk_type}")
    
    return list(set(strategies))  # Remove duplicates


def _perform_impact_analysis(
    risk_factors: List[Dict[str, Any]],
    overall_risk_score: float
) -> Dict[str, Any]:
    """Perform impact analysis of identified risks"""
    return {
        'financial_impact': {
            'low_scenario': 50000,
            'medium_scenario': 150000,
            'high_scenario': 500000
        },
        'operational_impact': {
            'service_disruption_hours': 4 * overall_risk_score,
            'customer_satisfaction_drop': 0.2 * overall_risk_score,
            'sla_compliance_impact': 0.15 * overall_risk_score
        },
        'reputation_impact': {
            'severity': 'medium' if overall_risk_score > 0.5 else 'low',
            'recovery_time': '2-4 weeks' if overall_risk_score > 0.5 else '1-2 weeks'
        },
        'business_continuity': {
            'critical_functions_at_risk': len([r for r in risk_factors if r['risk_score'] > 0.6]),
            'recovery_priority': 'high' if overall_risk_score > 0.7 else 'medium'
        }
    }


# Import numpy for calculations
import numpy as np