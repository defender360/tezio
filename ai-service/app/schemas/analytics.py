"""
Analytics schemas for reporting and insights
"""

from pydantic import BaseModel, Field
from typing import List, Optional, Dict, Any
from datetime import datetime
from enum import Enum


class TrendPoint(BaseModel):
    """Data point in a trend"""
    timestamp: datetime
    value: float
    label: Optional[str] = None


class CategoryDistribution(BaseModel):
    """Distribution by category"""
    category: str
    count: int
    percentage: float = Field(..., ge=0, le=100)
    trend: str = Field(..., description="up, down, or stable")


class TicketTrendsResponse(BaseModel):
    """Response model for ticket trends"""
    trend_data: List[TrendPoint] = Field(..., description="Time series data")
    total_tickets: int = Field(..., ge=0)
    average_daily: float = Field(..., ge=0)
    peak_day: datetime
    peak_value: int
    trend_direction: str = Field(..., description="increasing, decreasing, or stable")
    forecast_next_period: Optional[List[TrendPoint]] = None
    category_breakdown: Optional[List[CategoryDistribution]] = None


class AgentPerformance(BaseModel):
    """Agent performance metrics"""
    agent_id: int
    agent_name: str
    tickets_resolved: int = Field(..., ge=0)
    average_resolution_time: float = Field(..., ge=0, description="Hours")
    customer_satisfaction: Optional[float] = Field(None, ge=0, le=5)
    first_response_time: float = Field(..., ge=0, description="Minutes")
    sla_compliance_rate: float = Field(..., ge=0, le=100)
    efficiency_score: float = Field(..., ge=0, le=100)


class TeamPerformance(BaseModel):
    """Team performance metrics"""
    team_id: Optional[int]
    team_name: str
    total_tickets: int = Field(..., ge=0)
    resolved_tickets: int = Field(..., ge=0)
    pending_tickets: int = Field(..., ge=0)
    average_resolution_time: float = Field(..., ge=0)
    sla_compliance_rate: float = Field(..., ge=0, le=100)
    top_performers: List[AgentPerformance]


class PerformanceMetricsResponse(BaseModel):
    """Response model for performance metrics"""
    period: str
    team_metrics: Optional[TeamPerformance]
    agent_metrics: Optional[List[AgentPerformance]]
    comparison_to_previous: Dict[str, float] = Field(..., description="Percentage changes")
    insights: List[str] = Field(..., description="AI-generated insights")
    recommendations: List[str] = Field(..., description="Performance improvement suggestions")


class Anomaly(BaseModel):
    """Detected anomaly"""
    timestamp: datetime
    metric_name: str
    expected_value: float
    actual_value: float
    deviation_percentage: float
    severity: str = Field(..., description="low, medium, high, critical")
    possible_causes: List[str]
    recommended_actions: List[str]


class AnomalyDetectionResponse(BaseModel):
    """Response model for anomaly detection"""
    anomalies_detected: List[Anomaly]
    total_anomalies: int = Field(..., ge=0)
    time_range_analyzed: Dict[str, datetime]
    confidence_level: float = Field(..., ge=0, le=1)
    historical_baseline: Dict[str, float]
    alert_triggered: bool


class Prediction(BaseModel):
    """Prediction data point"""
    date: datetime
    predicted_value: float
    confidence_interval_lower: float
    confidence_interval_upper: float
    factors: List[str] = Field(..., description="Influencing factors")


class PredictiveAnalyticsResponse(BaseModel):
    """Response model for predictive analytics"""
    prediction_type: str
    predictions: List[Prediction]
    model_accuracy: float = Field(..., ge=0, le=1)
    confidence_level: float = Field(..., ge=0, le=1)
    influencing_factors: List[Dict[str, float]] = Field(..., description="Factor importance")
    recommendations: List[str]
    risk_assessment: Optional[Dict[str, Any]]


class SentimentBreakdown(BaseModel):
    """Sentiment analysis breakdown"""
    positive: float = Field(..., ge=0, le=100)
    neutral: float = Field(..., ge=0, le=100)
    negative: float = Field(..., ge=0, le=100)
    

class SentimentTrend(BaseModel):
    """Sentiment over time"""
    timestamp: datetime
    sentiment_score: float = Field(..., ge=-1, le=1)
    volume: int = Field(..., ge=0)


class SentimentAnalysisResponse(BaseModel):
    """Response model for sentiment analysis"""
    overall_sentiment: str = Field(..., description="positive, neutral, or negative")
    sentiment_score: float = Field(..., ge=-1, le=1)
    sentiment_breakdown: SentimentBreakdown
    sentiment_trends: List[SentimentTrend]
    top_positive_themes: List[str]
    top_negative_themes: List[str]
    sample_feedback: Dict[str, List[str]] = Field(..., description="Sample comments by sentiment")
    action_items: List[str] = Field(..., description="Suggested actions based on sentiment")