"""
Ticket Analysis Schemas

Pydantic schemas for ticket analysis requests and responses including:
- Ticket analysis requests
- Analysis results and insights
- Entity extraction results
- Sentiment analysis results
- Urgency scoring
"""

from pydantic import BaseModel, Field, validator
from typing import List, Optional, Dict, Any, Union
from datetime import datetime
from enum import Enum


class AnalysisType(str, Enum):
    """Types of analysis that can be performed"""
    FULL = "full"
    QUICK = "quick"
    ENTITIES_ONLY = "entities_only"
    SENTIMENT_ONLY = "sentiment_only"
    URGENCY_ONLY = "urgency_only"


class EntityType(str, Enum):
    """Types of entities that can be extracted"""
    SYSTEM_COMPONENT = "system_component"
    ERROR_CODE = "error_code"
    PERSON = "person"
    ORGANIZATION = "organization"
    PRODUCT = "product"
    LOCATION = "location"
    DATE = "date"
    TIME = "time"


class SentimentType(str, Enum):
    """Sentiment types"""
    POSITIVE = "positive"
    NEGATIVE = "negative"
    NEUTRAL = "neutral"


class UrgencyLevel(str, Enum):
    """Urgency levels"""
    LOW = "low"
    MEDIUM = "medium"
    HIGH = "high"
    CRITICAL = "critical"


class TicketAnalysisRequest(BaseModel):
    """Request model for ticket analysis"""
    
    title: str = Field(
        ...,
        min_length=1,
        max_length=500,
        description="Ticket title"
    )
    
    description: str = Field(
        ...,
        min_length=1,
        max_length=10000,
        description="Ticket description"
    )
    
    analysis_type: AnalysisType = Field(
        default=AnalysisType.FULL,
        description="Type of analysis to perform"
    )
    
    historical_context: bool = Field(
        default=True,
        description="Include historical context in analysis"
    )
    
    include_suggestions: bool = Field(
        default=True,
        description="Include improvement suggestions"
    )
    
    context_data: Optional[Dict[str, Any]] = Field(
        default=None,
        description="Additional context data for analysis"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "title": "Email server not receiving external messages",
                "description": "Users report that emails from external domains are not being delivered. Internal emails work fine. Started happening yesterday around 3 PM. Affecting approximately 50 users in the sales department.",
                "analysis_type": "full",
                "historical_context": True,
                "include_suggestions": True,
                "context_data": {
                    "reporter": "john.doe@company.com",
                    "affected_users": 50,
                    "department": "sales"
                }
            }
        }


class ExtractedEntity(BaseModel):
    """Extracted entity from ticket content"""
    
    entity_type: EntityType = Field(..., description="Type of entity")
    value: str = Field(..., description="Entity value/text")
    confidence: float = Field(..., ge=0, le=1, description="Confidence score")
    start_position: Optional[int] = Field(None, description="Start position in text")
    end_position: Optional[int] = Field(None, description="End position in text")
    context: Optional[str] = Field(None, description="Surrounding context")
    
    class Config:
        json_schema_extra = {
            "example": {
                "entity_type": "system_component",
                "value": "email server",
                "confidence": 0.95,
                "start_position": 0,
                "end_position": 12,
                "context": "email server not receiving"
            }
        }


class SentimentAnalysis(BaseModel):
    """Sentiment analysis results"""
    
    overall_sentiment: SentimentType = Field(..., description="Overall sentiment")
    confidence: float = Field(..., ge=0, le=1, description="Confidence in sentiment")
    sentiment_scores: Dict[str, float] = Field(
        ...,
        description="Detailed sentiment scores"
    )
    emotional_indicators: List[str] = Field(
        default_factory=list,
        description="Words/phrases indicating emotion"
    )
    
    @validator('sentiment_scores')
    def validate_sentiment_scores(cls, v):
        """Validate sentiment scores sum to approximately 1"""
        if not isinstance(v, dict):
            raise ValueError("sentiment_scores must be a dictionary")
        
        required_keys = ['positive', 'negative', 'neutral']
        if not all(key in v for key in required_keys):
            raise ValueError(f"sentiment_scores must contain keys: {required_keys}")
        
        score_sum = sum(v.values())
        if not (0.9 <= score_sum <= 1.1):  # Allow small floating point errors
            raise ValueError("sentiment_scores should sum to approximately 1.0")
        
        return v
    
    class Config:
        json_schema_extra = {
            "example": {
                "overall_sentiment": "negative",
                "confidence": 0.87,
                "sentiment_scores": {
                    "positive": 0.1,
                    "negative": 0.8,
                    "neutral": 0.1
                },
                "emotional_indicators": ["frustrated", "not working", "urgent"]
            }
        }


class UrgencyAssessment(BaseModel):
    """Urgency assessment results"""
    
    urgency_level: UrgencyLevel = Field(..., description="Assessed urgency level")
    urgency_score: float = Field(..., ge=0, le=1, description="Urgency score (0-1)")
    confidence: float = Field(..., ge=0, le=1, description="Confidence in assessment")
    
    contributing_factors: List[str] = Field(
        default_factory=list,
        description="Factors contributing to urgency"
    )
    
    impact_indicators: List[str] = Field(
        default_factory=list,
        description="Indicators of business impact"
    )
    
    time_indicators: List[str] = Field(
        default_factory=list,
        description="Time-related urgency indicators"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "urgency_level": "high",
                "urgency_score": 0.85,
                "confidence": 0.92,
                "contributing_factors": [
                    "Multiple users affected",
                    "Business critical system",
                    "External communication impacted"
                ],
                "impact_indicators": ["50 users affected", "sales department"],
                "time_indicators": ["started yesterday", "immediate attention needed"]
            }
        }


class KeyPhrase(BaseModel):
    """Key phrase extracted from ticket"""
    
    phrase: str = Field(..., description="The key phrase")
    relevance_score: float = Field(..., ge=0, le=1, description="Relevance score")
    phrase_type: str = Field(..., description="Type of phrase (noun, verb, technical, etc.)")
    frequency: int = Field(default=1, description="Frequency in text")
    
    class Config:
        json_schema_extra = {
            "example": {
                "phrase": "external email delivery",
                "relevance_score": 0.92,
                "phrase_type": "technical_issue",
                "frequency": 2
            }
        }


class AnalysisInsight(BaseModel):
    """AI-generated insight about the ticket"""
    
    insight_type: str = Field(..., description="Type of insight")
    description: str = Field(..., description="Insight description")
    confidence: float = Field(..., ge=0, le=1, description="Confidence in insight")
    actionable: bool = Field(default=False, description="Whether insight is actionable")
    priority: str = Field(default="medium", description="Priority of insight")
    
    class Config:
        json_schema_extra = {
            "example": {
                "insight_type": "root_cause_suggestion",
                "description": "Email delivery issues affecting external communications suggest potential SMTP relay or firewall configuration problem",
                "confidence": 0.78,
                "actionable": True,
                "priority": "high"
            }
        }


class TicketAnalysisResponse(BaseModel):
    """Response model for ticket analysis"""
    
    analysis_id: str = Field(..., description="Unique analysis identifier")
    
    # Core analysis results
    entities: List[ExtractedEntity] = Field(
        default_factory=list,
        description="Extracted entities from ticket content"
    )
    
    sentiment: Optional[SentimentAnalysis] = Field(
        None,
        description="Sentiment analysis results"
    )
    
    urgency: Optional[UrgencyAssessment] = Field(
        None,
        description="Urgency assessment results"
    )
    
    key_phrases: List[KeyPhrase] = Field(
        default_factory=list,
        description="Key phrases extracted from content"
    )
    
    suggested_tags: List[str] = Field(
        default_factory=list,
        description="AI-suggested tags for the ticket"
    )
    
    insights: List[AnalysisInsight] = Field(
        default_factory=list,
        description="AI-generated insights about the ticket"
    )
    
    # Analysis metadata
    analysis_timestamp: datetime = Field(
        default_factory=datetime.utcnow,
        description="When analysis was performed"
    )
    
    processing_time_ms: Optional[int] = Field(
        None,
        description="Analysis processing time in milliseconds"
    )
    
    confidence_score: float = Field(
        ...,
        ge=0,
        le=1,
        description="Overall confidence in analysis results"
    )
    
    analysis_version: str = Field(
        default="1.0",
        description="Analysis engine version"
    )
    
    # Optional detailed results
    detailed_results: Optional[Dict[str, Any]] = Field(
        None,
        description="Detailed analysis results and intermediate data"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "analysis_id": "analysis_123456",
                "entities": [
                    {
                        "entity_type": "system_component",
                        "value": "email server",
                        "confidence": 0.95,
                        "start_position": 0,
                        "end_position": 12
                    }
                ],
                "sentiment": {
                    "overall_sentiment": "negative",
                    "confidence": 0.87,
                    "sentiment_scores": {
                        "positive": 0.1,
                        "negative": 0.8,
                        "neutral": 0.1
                    }
                },
                "urgency": {
                    "urgency_level": "high",
                    "urgency_score": 0.85,
                    "confidence": 0.92
                },
                "key_phrases": [
                    {
                        "phrase": "external email delivery",
                        "relevance_score": 0.92,
                        "phrase_type": "technical_issue"
                    }
                ],
                "suggested_tags": ["email", "external", "delivery", "smtp"],
                "insights": [
                    {
                        "insight_type": "root_cause_suggestion",
                        "description": "Potential SMTP configuration issue",
                        "confidence": 0.78,
                        "actionable": True
                    }
                ],
                "confidence_score": 0.89,
                "analysis_version": "1.0"
            }
        }


class BulkAnalysisRequest(BaseModel):
    """Request model for analyzing multiple tickets"""
    
    tickets: List[TicketAnalysisRequest] = Field(
        ...,
        min_items=1,
        max_items=100,
        description="List of tickets to analyze"
    )
    
    analysis_type: AnalysisType = Field(
        default=AnalysisType.QUICK,
        description="Type of analysis for all tickets"
    )
    
    parallel_processing: bool = Field(
        default=True,
        description="Whether to process tickets in parallel"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "tickets": [
                    {
                        "title": "Email not working",
                        "description": "Cannot send emails from Outlook"
                    },
                    {
                        "title": "Network slow",
                        "description": "Internet connection is very slow"
                    }
                ],
                "analysis_type": "quick",
                "parallel_processing": True
            }
        }


class BulkAnalysisResponse(BaseModel):
    """Response model for bulk ticket analysis"""
    
    batch_id: str = Field(..., description="Batch processing identifier")
    
    results: List[TicketAnalysisResponse] = Field(
        ...,
        description="Analysis results for each ticket"
    )
    
    summary: Dict[str, Any] = Field(
        ...,
        description="Summary statistics for the batch"
    )
    
    processing_time_ms: int = Field(
        ...,
        description="Total processing time for all tickets"
    )
    
    success_count: int = Field(..., description="Number of successfully analyzed tickets")
    error_count: int = Field(..., description="Number of tickets with analysis errors")
    
    class Config:
        json_schema_extra = {
            "example": {
                "batch_id": "batch_123456",
                "results": [],
                "summary": {
                    "total_tickets": 50,
                    "average_urgency": 0.6,
                    "most_common_tags": ["email", "network", "software"],
                    "sentiment_distribution": {
                        "positive": 10,
                        "negative": 35,
                        "neutral": 5
                    }
                },
                "processing_time_ms": 15000,
                "success_count": 48,
                "error_count": 2
            }
        }


class AnalysisConfigurationRequest(BaseModel):
    """Request to update analysis configuration"""
    
    sensitivity_settings: Optional[Dict[str, float]] = Field(
        None,
        description="Sensitivity settings for various analysis components"
    )
    
    entity_extraction_config: Optional[Dict[str, Any]] = Field(
        None,
        description="Configuration for entity extraction"
    )
    
    sentiment_analysis_config: Optional[Dict[str, Any]] = Field(
        None,
        description="Configuration for sentiment analysis"
    )
    
    urgency_assessment_config: Optional[Dict[str, Any]] = Field(
        None,
        description="Configuration for urgency assessment"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "sensitivity_settings": {
                    "urgency_threshold": 0.7,
                    "entity_confidence_threshold": 0.8
                },
                "sentiment_analysis_config": {
                    "model": "roberta-base-sentiment",
                    "threshold": 0.6
                }
            }
        }


class AnalysisConfigurationResponse(BaseModel):
    """Response for analysis configuration update"""
    
    success: bool = Field(..., description="Whether configuration update was successful")
    message: str = Field(..., description="Status message")
    updated_config: Dict[str, Any] = Field(..., description="Updated configuration")
    
    class Config:
        json_schema_extra = {
            "example": {
                "success": True,
                "message": "Analysis configuration updated successfully",
                "updated_config": {
                    "sensitivity_settings": {
                        "urgency_threshold": 0.7
                    }
                }
            }
        }