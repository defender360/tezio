"""
Suggestion-related schemas for AI endpoints
"""

from pydantic import BaseModel, Field
from typing import List, Optional, Dict, Any
from datetime import datetime
from enum import Enum


class ResolutionStep(BaseModel):
    """Individual resolution step"""
    order: int = Field(..., ge=1, description="Step order")
    action: str = Field(..., description="Action to take")
    details: str = Field(..., description="Detailed instructions")
    estimated_time_minutes: Optional[int] = Field(None, description="Estimated time in minutes")
    requires_tools: Optional[List[str]] = Field(None, description="Required tools or access")


class ResolutionSuggestionRequest(BaseModel):
    """Request model for resolution suggestions"""
    ticket_id: int = Field(..., description="Ticket ID")
    issue_description: str = Field(..., min_length=1)
    category: Optional[str] = Field(None, description="Issue category")
    affected_systems: Optional[List[str]] = Field(None, description="Affected systems")
    error_messages: Optional[List[str]] = Field(None, description="Error messages")
    
    class Config:
        json_schema_extra = {
            "example": {
                "ticket_id": 12345,
                "issue_description": "User cannot print to network printer",
                "category": "hardware",
                "affected_systems": ["print_server"],
                "error_messages": ["Print spooler service not responding"]
            }
        }


class ResolutionSuggestionResponse(BaseModel):
    """Response model for resolution suggestions"""
    suggested_steps: List[ResolutionStep] = Field(..., description="Suggested resolution steps")
    confidence_score: float = Field(..., ge=0, le=1, description="Confidence in suggestion")
    similar_resolved_tickets: List[int] = Field(..., description="IDs of similar resolved tickets")
    estimated_total_time: int = Field(..., description="Total estimated time in minutes")
    success_probability: float = Field(..., ge=0, le=1, description="Probability of success")
    alternative_solutions: Optional[List[Dict[str, Any]]] = Field(None, description="Alternative approaches")


class ResponseTone(str, Enum):
    PROFESSIONAL = "professional"
    FRIENDLY = "friendly"
    FORMAL = "formal"
    CASUAL = "casual"
    EMPATHETIC = "empathetic"


class ResponseTemplateRequest(BaseModel):
    """Request model for response template generation"""
    context: str = Field(..., description="Context of the response")
    tone: ResponseTone = Field(ResponseTone.PROFESSIONAL, description="Desired tone")
    language: str = Field("en", description="Language code")
    customer_name: Optional[str] = Field(None, description="Customer name for personalization")
    issue_summary: str = Field(..., description="Brief summary of the issue")
    resolution_steps: Optional[List[str]] = Field(None, description="Steps taken or to be taken")
    
    class Config:
        json_schema_extra = {
            "example": {
                "context": "initial_response",
                "tone": "friendly",
                "customer_name": "John Smith",
                "issue_summary": "Email not syncing on mobile device",
                "resolution_steps": ["Verified email settings", "Reset email account"]
            }
        }


class ResponseTemplateResponse(BaseModel):
    """Response model for generated templates"""
    template: str = Field(..., description="Generated response template")
    personalization_score: float = Field(..., ge=0, le=1, description="Level of personalization")
    tone_match: float = Field(..., ge=0, le=1, description="How well tone matches request")
    suggested_follow_ups: List[str] = Field(..., description="Suggested follow-up questions")
    estimated_reading_time: int = Field(..., description="Estimated reading time in seconds")


class KnowledgeArticleSuggestion(BaseModel):
    """Model for suggested knowledge article"""
    article_id: int
    title: str
    relevance_score: float = Field(..., ge=0, le=1)
    excerpt: str = Field(..., max_length=500)
    category: str
    helpful_count: int = Field(..., ge=0)
    view_count: int = Field(..., ge=0)
    last_updated: datetime
    estimated_reading_time: int = Field(..., description="Reading time in minutes")