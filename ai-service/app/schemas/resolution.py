"""
Resolution Suggestion Schemas

Pydantic schemas for resolution suggestion requests and responses including:
- Resolution suggestion requests
- Suggested resolution steps
- Knowledge base matches
- Historical resolution data
- Success rate tracking
"""

from pydantic import BaseModel, Field, validator
from typing import List, Optional, Dict, Any, Union
from datetime import datetime
from enum import Enum


class ResolutionSourceType(str, Enum):
    """Types of resolution sources"""
    KNOWLEDGE_BASE = "knowledge_base"
    HISTORICAL = "historical"
    TEMPLATE = "template"
    AI_GENERATED = "ai_generated"
    EXPERT_SYSTEM = "expert_system"
    MACHINE_LEARNING = "machine_learning"


class ResolutionPriority(str, Enum):
    """Priority levels for resolution suggestions"""
    LOW = "low"
    MEDIUM = "medium" 
    HIGH = "high"
    CRITICAL = "critical"


class ResolutionStatus(str, Enum):
    """Status of resolution suggestions"""
    PENDING = "pending"
    IN_PROGRESS = "in_progress"
    SUCCESSFUL = "successful"
    FAILED = "failed"
    PARTIALLY_SUCCESSFUL = "partially_successful"


class ResolutionRequest(BaseModel):
    """Request model for resolution suggestions"""
    
    title: str = Field(
        ...,
        min_length=1,
        max_length=500,
        description="Issue title"
    )
    
    description: str = Field(
        ...,
        min_length=1,
        max_length=10000,
        description="Detailed issue description"
    )
    
    category: Optional[str] = Field(
        None,
        description="Issue category (if known)"
    )
    
    affected_systems: Optional[List[str]] = Field(
        None,
        description="List of affected systems or components"
    )
    
    priority: Optional[ResolutionPriority] = Field(
        None,
        description="Issue priority level"
    )
    
    user_context: Optional[Dict[str, Any]] = Field(
        None,
        description="Additional context about the user/environment"
    )
    
    previous_attempts: Optional[List[str]] = Field(
        None,
        description="Previous resolution attempts"
    )
    
    limit: int = Field(
        default=5,
        ge=1,
        le=20,
        description="Maximum number of suggestions to return"
    )
    
    include_explanation: bool = Field(
        default=True,
        description="Include detailed explanations for suggestions"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "title": "Unable to access shared network drive",
                "description": "Users cannot connect to the shared drive \\\\server\\shared. Getting 'network path not found' error. This started after the weekend maintenance.",
                "category": "network",
                "affected_systems": ["file_server", "network"],
                "priority": "high",
                "user_context": {
                    "department": "accounting",
                    "affected_users": 15
                },
                "previous_attempts": [
                    "Restarted user computers",
                    "Checked network connectivity"
                ],
                "limit": 5,
                "include_explanation": True
            }
        }


class ResolutionStep(BaseModel):
    """Individual step in a resolution process"""
    
    step_number: int = Field(..., ge=1, description="Step sequence number")
    action: str = Field(..., description="Action to perform")
    details: Optional[str] = Field(None, description="Detailed instructions")
    expected_outcome: Optional[str] = Field(None, description="Expected result")
    verification: Optional[str] = Field(None, description="How to verify success")
    
    # Risk and complexity indicators
    risk_level: str = Field(default="low", description="Risk level (low, medium, high)")
    complexity: str = Field(default="easy", description="Complexity (easy, medium, hard)")
    estimated_time: Optional[str] = Field(None, description="Estimated time to complete")
    
    # Prerequisites and tools
    prerequisites: Optional[List[str]] = Field(None, description="Required prerequisites")
    required_tools: Optional[List[str]] = Field(None, description="Required tools or access")
    
    class Config:
        json_schema_extra = {
            "example": {
                "step_number": 1,
                "action": "Check network connectivity to server",
                "details": "Use ping command to test connectivity to the file server",
                "expected_outcome": "Successful ping responses",
                "verification": "Ping should return response times without timeouts",
                "risk_level": "low",
                "complexity": "easy",
                "estimated_time": "2-5 minutes",
                "required_tools": ["command_prompt", "network_access"]
            }
        }


class ResolutionSuggestion(BaseModel):
    """Complete resolution suggestion"""
    
    suggestion_id: str = Field(..., description="Unique suggestion identifier")
    title: str = Field(..., description="Resolution title/summary")
    description: str = Field(..., description="Detailed description of the solution")
    
    # Resolution steps
    steps: List[ResolutionStep] = Field(
        ...,
        min_items=1,
        description="Step-by-step resolution instructions"
    )
    
    # Confidence and quality metrics
    confidence: float = Field(..., ge=0, le=1, description="Confidence in suggestion")
    success_rate: float = Field(..., ge=0, le=1, description="Historical success rate")
    relevance_score: float = Field(..., ge=0, le=1, description="Relevance to the issue")
    
    # Source and attribution
    source: ResolutionSourceType = Field(..., description="Source of the suggestion")
    source_details: Optional[str] = Field(None, description="Detailed source information")
    knowledge_base_id: Optional[str] = Field(None, description="Knowledge base article ID")
    
    # Time and effort estimates
    estimated_time: str = Field(..., description="Estimated resolution time")
    difficulty_level: str = Field(..., description="Overall difficulty level")
    
    # Prerequisites and requirements
    prerequisites: List[str] = Field(
        default_factory=list,
        description="Prerequisites for this resolution"
    )
    
    required_skills: List[str] = Field(
        default_factory=list,
        description="Skills required to execute this resolution"
    )
    
    required_access: List[str] = Field(
        default_factory=list,
        description="System access or permissions required"
    )
    
    # Related information
    related_articles: Optional[List[str]] = Field(
        None,
        description="Related knowledge base articles"
    )
    
    alternative_approaches: Optional[List[str]] = Field(
        None,
        description="Alternative resolution approaches"
    )
    
    # Metadata
    created_at: datetime = Field(
        default_factory=datetime.utcnow,
        description="When suggestion was generated"
    )
    
    last_updated: Optional[datetime] = Field(
        None,
        description="When suggestion was last updated"
    )
    
    usage_count: int = Field(default=0, description="How many times this was suggested")
    
    class Config:
        json_schema_extra = {
            "example": {
                "suggestion_id": "res_123456",
                "title": "Network Drive Access Resolution",
                "description": "Comprehensive solution for network drive connectivity issues",
                "steps": [
                    {
                        "step_number": 1,
                        "action": "Check network connectivity",
                        "details": "Ping the file server to verify connectivity"
                    }
                ],
                "confidence": 0.92,
                "success_rate": 0.87,
                "relevance_score": 0.95,
                "source": "knowledge_base",
                "source_details": "KB Article #1234",
                "estimated_time": "15-30 minutes",
                "difficulty_level": "medium",
                "required_skills": ["network_troubleshooting", "windows_administration"]
            }
        }


class ResolutionResponse(BaseModel):
    """Response model for resolution suggestions"""
    
    request_id: str = Field(..., description="Original request identifier")
    
    suggestions: List[ResolutionSuggestion] = Field(
        ...,
        description="List of resolution suggestions"
    )
    
    total_suggestions: int = Field(..., description="Total number of suggestions found")
    
    # Search and matching metadata
    search_metadata: Dict[str, Any] = Field(
        ...,
        description="Metadata about the search process"
    )
    
    processing_time_ms: int = Field(
        ...,
        description="Processing time in milliseconds"
    )
    
    # Quality indicators
    overall_confidence: float = Field(
        ...,
        ge=0,
        le=1,
        description="Overall confidence in suggestions"
    )
    
    coverage_score: float = Field(
        ...,
        ge=0,
        le=1,
        description="How well suggestions cover the issue"
    )
    
    # Response timestamp
    generated_at: datetime = Field(
        default_factory=datetime.utcnow,
        description="When response was generated"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "request_id": "req_123456",
                "suggestions": [],
                "total_suggestions": 3,
                "search_metadata": {
                    "knowledge_base_matches": 2,
                    "historical_matches": 1,
                    "template_matches": 0
                },
                "processing_time_ms": 1250,
                "overall_confidence": 0.89,
                "coverage_score": 0.92
            }
        }


class ResolutionFeedback(BaseModel):
    """Feedback on resolution suggestion effectiveness"""
    
    suggestion_id: str = Field(..., description="ID of the suggestion being rated")
    request_id: str = Field(..., description="Original request ID")
    
    # Effectiveness ratings
    helpful: bool = Field(..., description="Whether suggestion was helpful")
    successful: bool = Field(..., description="Whether resolution was successful")
    
    effectiveness_rating: int = Field(
        ...,
        ge=1,
        le=5,
        description="Effectiveness rating (1-5 scale)"
    )
    
    clarity_rating: int = Field(
        ...,
        ge=1,
        le=5,
        description="Clarity of instructions rating (1-5 scale)"
    )
    
    # Time and effort feedback
    actual_time_taken: Optional[str] = Field(
        None,
        description="Actual time taken to complete resolution"
    )
    
    difficulty_experienced: Optional[str] = Field(
        None,
        description="Actual difficulty experienced"
    )
    
    # Detailed feedback
    what_worked: Optional[str] = Field(
        None,
        description="What aspects worked well"
    )
    
    what_didnt_work: Optional[str] = Field(
        None,
        description="What aspects didn't work"
    )
    
    suggested_improvements: Optional[str] = Field(
        None,
        description="Suggestions for improvement"
    )
    
    # Context
    user_role: Optional[str] = Field(None, description="Role of person providing feedback")
    environment_details: Optional[Dict[str, Any]] = Field(
        None,
        description="Environment details that might affect resolution"
    )
    
    feedback_timestamp: datetime = Field(
        default_factory=datetime.utcnow,
        description="When feedback was provided"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "suggestion_id": "res_123456",
                "request_id": "req_123456",
                "helpful": True,
                "successful": True,
                "effectiveness_rating": 4,
                "clarity_rating": 5,
                "actual_time_taken": "25 minutes",
                "difficulty_experienced": "medium",
                "what_worked": "Step-by-step instructions were clear",
                "what_didnt_work": "Step 3 needed admin permissions not mentioned",
                "suggested_improvements": "Add note about required permissions",
                "user_role": "it_technician"
            }
        }


class ResolutionOutcome(BaseModel):
    """Final outcome of a resolution attempt"""
    
    suggestion_id: str = Field(..., description="ID of the suggestion used")
    request_id: str = Field(..., description="Original request ID")
    
    status: ResolutionStatus = Field(..., description="Final status of resolution")
    
    # Success metrics
    issue_resolved: bool = Field(..., description="Whether the issue was fully resolved")
    time_to_resolution: Optional[str] = Field(None, description="Total time to resolve")
    steps_completed: int = Field(..., description="Number of steps completed")
    total_steps: int = Field(..., description="Total number of steps in suggestion")
    
    # Outcome details
    final_notes: Optional[str] = Field(None, description="Final notes about the resolution")
    modifications_made: Optional[List[str]] = Field(
        None,
        description="Modifications made to the suggested steps"
    )
    
    additional_steps_required: Optional[List[str]] = Field(
        None,
        description="Additional steps that were required"
    )
    
    # Follow-up information
    follow_up_required: bool = Field(default=False, description="Whether follow-up is needed")
    follow_up_notes: Optional[str] = Field(None, description="Follow-up requirements")
    
    # Attribution
    resolved_by: Optional[str] = Field(None, description="Who resolved the issue")
    resolution_timestamp: datetime = Field(
        default_factory=datetime.utcnow,
        description="When resolution was completed"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "suggestion_id": "res_123456",
                "request_id": "req_123456",
                "status": "successful",
                "issue_resolved": True,
                "time_to_resolution": "30 minutes",
                "steps_completed": 5,
                "total_steps": 5,
                "final_notes": "Network drive access restored successfully",
                "resolved_by": "john.doe@company.com"
            }
        }


class BulkResolutionRequest(BaseModel):
    """Request for bulk resolution suggestions"""
    
    requests: List[ResolutionRequest] = Field(
        ...,
        min_items=1,
        max_items=50,
        description="List of resolution requests"
    )
    
    parallel_processing: bool = Field(
        default=True,
        description="Whether to process requests in parallel"
    )
    
    common_context: Optional[Dict[str, Any]] = Field(
        None,
        description="Context common to all requests"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "requests": [
                    {
                        "title": "Email not working",
                        "description": "Cannot send emails"
                    },
                    {
                        "title": "Printer offline",
                        "description": "Network printer shows offline"
                    }
                ],
                "parallel_processing": True,
                "common_context": {
                    "department": "accounting",
                    "location": "main_office"
                }
            }
        }


class BulkResolutionResponse(BaseModel):
    """Response for bulk resolution suggestions"""
    
    batch_id: str = Field(..., description="Batch processing identifier")
    
    responses: List[ResolutionResponse] = Field(
        ...,
        description="Individual resolution responses"
    )
    
    # Batch statistics
    total_requests: int = Field(..., description="Total number of requests processed")
    successful_requests: int = Field(..., description="Number of successfully processed requests")
    failed_requests: int = Field(..., description="Number of failed requests")
    
    processing_summary: Dict[str, Any] = Field(
        ...,
        description="Summary of processing results"
    )
    
    total_processing_time_ms: int = Field(
        ...,
        description="Total processing time for all requests"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "batch_id": "batch_123456",
                "responses": [],
                "total_requests": 25,
                "successful_requests": 23,
                "failed_requests": 2,
                "processing_summary": {
                    "average_suggestions_per_request": 3.2,
                    "most_common_categories": ["network", "email", "software"]
                },
                "total_processing_time_ms": 45000
            }
        }


class ResolutionAnalytics(BaseModel):
    """Analytics data for resolution suggestions"""
    
    time_period: str = Field(..., description="Time period for analytics")
    
    # Usage statistics
    total_requests: int = Field(..., description="Total resolution requests")
    total_suggestions: int = Field(..., description="Total suggestions provided")
    unique_issues: int = Field(..., description="Number of unique issues")
    
    # Success metrics
    success_rate: float = Field(..., ge=0, le=1, description="Overall success rate")
    average_resolution_time: str = Field(..., description="Average time to resolution")
    
    # Category breakdown
    category_stats: Dict[str, Dict[str, Any]] = Field(
        ...,
        description="Statistics by issue category"
    )
    
    # Source effectiveness
    source_effectiveness: Dict[str, float] = Field(
        ...,
        description="Effectiveness by suggestion source"
    )
    
    # Top performing suggestions
    top_suggestions: List[Dict[str, Any]] = Field(
        ...,
        description="Most successful suggestions"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "time_period": "last_30_days",
                "total_requests": 1500,
                "total_suggestions": 4500,
                "unique_issues": 450,
                "success_rate": 0.87,
                "average_resolution_time": "45 minutes",
                "category_stats": {
                    "network": {
                        "requests": 300,
                        "success_rate": 0.92
                    }
                },
                "source_effectiveness": {
                    "knowledge_base": 0.91,
                    "historical": 0.85,
                    "ai_generated": 0.78
                }
            }
        }