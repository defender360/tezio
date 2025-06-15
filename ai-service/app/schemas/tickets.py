"""
Ticket-related schemas for API requests and responses
"""

from pydantic import BaseModel, Field
from typing import List, Optional, Dict, Any
from datetime import datetime
from enum import Enum


class TicketPriority(str, Enum):
    CRITICAL = "critical"
    HIGH = "high"
    MEDIUM = "medium"
    LOW = "low"


class TicketStatus(str, Enum):
    OPEN = "open"
    IN_PROGRESS = "in_progress"
    RESOLVED = "resolved"
    CLOSED = "closed"


class TicketAnalysisRequest(BaseModel):
    """Request model for ticket analysis"""
    title: str = Field(..., min_length=1, max_length=500)
    description: str = Field(..., min_length=1)
    historical_context: Optional[bool] = Field(True, description="Include historical analysis")
    
    class Config:
        json_schema_extra = {
            "example": {
                "title": "Email server not receiving messages",
                "description": "Users report that external emails are not being delivered...",
                "historical_context": True
            }
        }


class TicketAnalysisResponse(BaseModel):
    """Response model for ticket analysis"""
    entities: List[Dict[str, Any]] = Field(..., description="Extracted entities")
    sentiment: Dict[str, float] = Field(..., description="Sentiment scores")
    key_phrases: List[str] = Field(..., description="Important phrases")
    suggested_tags: List[str] = Field(..., description="Recommended tags")
    urgency_score: float = Field(..., ge=0, le=1, description="Urgency level (0-1)")
    insights: List[str] = Field(..., description="AI-generated insights")


class TicketClassificationRequest(BaseModel):
    """Request model for ticket classification"""
    title: str = Field(..., min_length=1, max_length=500)
    description: str = Field(..., min_length=1)
    affected_systems: Optional[List[str]] = Field(None, description="List of affected systems")
    
    class Config:
        json_schema_extra = {
            "example": {
                "title": "Cannot access shared drive",
                "description": "Getting access denied error when trying to connect...",
                "affected_systems": ["file_server", "active_directory"]
            }
        }


class TicketClassificationResponse(BaseModel):
    """Response model for ticket classification"""
    primary_category: str = Field(..., description="Main category")
    secondary_categories: List[str] = Field(..., description="Additional categories")
    suggested_priority: TicketPriority = Field(..., description="Recommended priority")
    confidence_scores: Dict[str, float] = Field(..., description="Classification confidence")
    skill_requirements: List[str] = Field(..., description="Required skills to resolve")


class SimilarTicketsRequest(BaseModel):
    """Request model for finding similar tickets"""
    title: str = Field(..., min_length=1, max_length=500)
    description: str = Field(..., min_length=1)
    category: Optional[str] = Field(None, description="Filter by category")
    limit: Optional[int] = Field(5, ge=1, le=20, description="Number of results")
    
    class Config:
        json_schema_extra = {
            "example": {
                "title": "Printer not working",
                "description": "HP LaserJet is showing offline status...",
                "limit": 5
            }
        }


class SimilarTicket(BaseModel):
    """Model for similar ticket result"""
    ticket_id: int
    title: str
    similarity_score: float = Field(..., ge=0, le=1)
    status: TicketStatus
    resolution: Optional[str]
    resolution_time_hours: Optional[float]
    created_at: datetime
    resolved_at: Optional[datetime]


class SimilarTicketsResponse(BaseModel):
    """Response model for similar tickets search"""
    tickets: List[SimilarTicket]
    total: int = Field(..., description="Total similar tickets found")