"""
Knowledge Enhancement Schemas

Pydantic schemas for knowledge enhancement requests and responses including:
- Knowledge article enhancement requests
- Quality analysis results
- Content improvement suggestions
- Auto-generation of articles
- Knowledge gap identification
"""

from pydantic import BaseModel, Field, validator
from typing import List, Optional, Dict, Any, Union
from datetime import datetime
from enum import Enum


class ContentType(str, Enum):
    """Types of knowledge content"""
    ARTICLE = "article"
    FAQ = "faq"
    TUTORIAL = "tutorial"
    TROUBLESHOOTING = "troubleshooting"
    PROCEDURE = "procedure"
    REFERENCE = "reference"
    GUIDE = "guide"


class QualityMetric(str, Enum):
    """Quality metrics for knowledge articles"""
    CLARITY = "clarity"
    COMPLETENESS = "completeness"
    ACCURACY = "accuracy"
    RELEVANCE = "relevance"
    USEFULNESS = "usefulness"
    STRUCTURE = "structure"
    READABILITY = "readability"


class EnhancementType(str, Enum):
    """Types of enhancements that can be performed"""
    QUALITY_ANALYSIS = "quality_analysis"
    CONTENT_SUGGESTIONS = "content_suggestions"
    TAG_GENERATION = "tag_generation"
    CATEGORY_SUGGESTION = "category_suggestion"
    STRUCTURE_IMPROVEMENT = "structure_improvement"
    GAP_IDENTIFICATION = "gap_identification"
    SIMILARITY_ANALYSIS = "similarity_analysis"
    AUTO_GENERATION = "auto_generation"


class SuggestionType(str, Enum):
    """Types of content suggestions"""
    ADD_EXAMPLES = "add_examples"
    ADD_SCREENSHOTS = "add_screenshots"
    ADD_PREREQUISITES = "add_prerequisites"
    ADD_TROUBLESHOOTING = "add_troubleshooting"
    IMPROVE_STRUCTURE = "improve_structure"
    ADD_REFERENCES = "add_references"
    UPDATE_CONTENT = "update_content"
    SIMPLIFY_LANGUAGE = "simplify_language"
    ADD_STEPS = "add_steps"


class KnowledgeArticle(BaseModel):
    """Knowledge article data model"""
    
    id: Optional[str] = Field(None, description="Article ID")
    title: str = Field(..., min_length=1, max_length=500, description="Article title")
    content: str = Field(..., min_length=1, description="Article content")
    
    content_type: ContentType = Field(
        default=ContentType.ARTICLE,
        description="Type of content"
    )
    
    category: str = Field(default="", description="Article category")
    
    tags: List[str] = Field(
        default_factory=list,
        description="Article tags"
    )
    
    author: str = Field(default="", description="Article author")
    
    # Timestamps
    created_at: Optional[datetime] = Field(None, description="Creation timestamp")
    updated_at: Optional[datetime] = Field(None, description="Last update timestamp")
    
    # Metrics
    view_count: int = Field(default=0, description="Number of views")
    helpfulness_score: float = Field(default=0.0, ge=0, le=5, description="Helpfulness rating")
    
    # Quality scores
    quality_scores: Dict[str, float] = Field(
        default_factory=dict,
        description="Quality assessment scores"
    )
    
    # Metadata
    metadata: Optional[Dict[str, Any]] = Field(
        None,
        description="Additional metadata"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "id": "kb_123456",
                "title": "Email Server Configuration Guide",
                "content": "This guide explains how to configure email servers...",
                "content_type": "guide",
                "category": "Email & Communication",
                "tags": ["email", "configuration", "server"],
                "author": "john.doe@company.com",
                "view_count": 150,
                "helpfulness_score": 4.2,
                "quality_scores": {
                    "clarity": 0.85,
                    "completeness": 0.90,
                    "structure": 0.75
                }
            }
        }


class KnowledgeEnhancementRequest(BaseModel):
    """Request model for knowledge enhancement"""
    
    article: KnowledgeArticle = Field(..., description="Article to enhance")
    
    enhancement_types: Optional[List[EnhancementType]] = Field(
        None,
        description="Specific enhancement types to apply"
    )
    
    include_suggestions: bool = Field(
        default=True,
        description="Include improvement suggestions"
    )
    
    analyze_quality: bool = Field(
        default=True,
        description="Perform quality analysis"
    )
    
    generate_metadata: bool = Field(
        default=True,
        description="Generate tags, categories, and metadata"
    )
    
    comparison_articles: Optional[List[str]] = Field(
        None,
        description="IDs of articles to compare against"
    )
    
    target_audience: Optional[str] = Field(
        None,
        description="Target audience for the article"
    )
    
    context: Optional[Dict[str, Any]] = Field(
        None,
        description="Additional context for enhancement"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "article": {
                    "title": "Printer Setup Guide",
                    "content": "Step 1: Connect printer to network..."
                },
                "enhancement_types": ["quality_analysis", "content_suggestions", "tag_generation"],
                "include_suggestions": True,
                "analyze_quality": True,
                "generate_metadata": True,
                "target_audience": "end_users"
            }
        }


class QualityAnalysis(BaseModel):
    """Quality analysis results for an article"""
    
    overall_score: float = Field(..., ge=0, le=1, description="Overall quality score")
    
    quality_scores: Dict[str, float] = Field(
        ...,
        description="Individual quality metric scores"
    )
    
    strengths: List[str] = Field(
        default_factory=list,
        description="Identified strengths"
    )
    
    weaknesses: List[str] = Field(
        default_factory=list,
        description="Identified weaknesses"
    )
    
    suggestions: List[str] = Field(
        default_factory=list,
        description="Quality improvement suggestions"
    )
    
    # Detailed metrics
    word_count: int = Field(..., description="Total word count")
    readability_score: Optional[float] = Field(None, description="Readability score")
    structure_score: float = Field(..., description="Structure quality score")
    
    # Content analysis
    has_examples: bool = Field(..., description="Whether article contains examples")
    has_steps: bool = Field(..., description="Whether article has step-by-step content")
    has_visuals: bool = Field(..., description="Whether article references visuals")
    
    class Config:
        json_schema_extra = {
            "example": {
                "overall_score": 0.78,
                "quality_scores": {
                    "clarity": 0.85,
                    "completeness": 0.70,
                    "structure": 0.80,
                    "usefulness": 0.75
                },
                "strengths": ["Clear step-by-step instructions", "Good structure"],
                "weaknesses": ["Missing examples", "No troubleshooting section"],
                "suggestions": ["Add practical examples", "Include common issues section"],
                "word_count": 450,
                "structure_score": 0.80,
                "has_examples": False,
                "has_steps": True,
                "has_visuals": False
            }
        }


class ContentSuggestion(BaseModel):
    """Individual content improvement suggestion"""
    
    suggestion_id: str = Field(..., description="Unique suggestion identifier")
    suggestion_type: SuggestionType = Field(..., description="Type of suggestion")
    
    title: str = Field(..., description="Suggestion title")
    description: str = Field(..., description="Detailed description")
    
    confidence: float = Field(..., ge=0, le=1, description="Confidence in suggestion")
    impact: str = Field(..., description="Expected impact (low, medium, high)")
    
    # Implementation details
    suggested_changes: Dict[str, Any] = Field(
        ...,
        description="Specific changes to implement"
    )
    
    reasoning: str = Field(..., description="Why this suggestion is recommended")
    
    # Location information
    target_section: Optional[str] = Field(None, description="Which section to modify")
    position: Optional[str] = Field(None, description="Where to add content")
    
    # Example content
    example_content: Optional[str] = Field(None, description="Example of suggested content")
    
    class Config:
        json_schema_extra = {
            "example": {
                "suggestion_id": "sug_123456",
                "suggestion_type": "add_examples",
                "title": "Add Practical Examples",
                "description": "Include real-world examples to illustrate the concepts",
                "confidence": 0.85,
                "impact": "high",
                "suggested_changes": {
                    "section": "main_content",
                    "type": "addition",
                    "content_type": "examples"
                },
                "reasoning": "Examples help users understand and apply the information better",
                "target_section": "Configuration Steps",
                "example_content": "For example, when configuring the SMTP server..."
            }
        }


class TagSuggestion(BaseModel):
    """Tag generation results"""
    
    suggested_tags: List[str] = Field(..., description="AI-suggested tags")
    existing_tags: List[str] = Field(..., description="Current article tags")
    
    tag_confidence: Dict[str, float] = Field(
        ...,
        description="Confidence scores for each suggested tag"
    )
    
    tag_categories: Dict[str, List[str]] = Field(
        ...,
        description="Tags grouped by categories"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "suggested_tags": ["email", "smtp", "configuration", "server", "troubleshooting"],
                "existing_tags": ["email", "setup"],
                "tag_confidence": {
                    "smtp": 0.95,
                    "configuration": 0.90,
                    "server": 0.85
                },
                "tag_categories": {
                    "technology": ["smtp", "server"],
                    "task": ["configuration", "setup"],
                    "domain": ["email"]
                }
            }
        }


class CategorySuggestion(BaseModel):
    """Category suggestion results"""
    
    suggested_category: str = Field(..., description="AI-suggested category")
    current_category: str = Field(..., description="Current article category")
    
    confidence: float = Field(..., ge=0, le=1, description="Confidence in suggestion")
    
    category_scores: Dict[str, float] = Field(
        ...,
        description="Scores for all considered categories"
    )
    
    reasoning: str = Field(..., description="Why this category was suggested")
    
    class Config:
        json_schema_extra = {
            "example": {
                "suggested_category": "Email & Communication",
                "current_category": "General",
                "confidence": 0.92,
                "category_scores": {
                    "Email & Communication": 0.92,
                    "Network & Connectivity": 0.35,
                    "System Administration": 0.28
                },
                "reasoning": "Article primarily deals with email server configuration"
            }
        }


class StructureAnalysis(BaseModel):
    """Document structure analysis"""
    
    structure_score: float = Field(..., ge=0, le=1, description="Overall structure score")
    
    # Structure elements
    headers_count: int = Field(..., description="Number of headers")
    lists_count: int = Field(..., description="Number of lists")
    code_blocks_count: int = Field(..., description="Number of code blocks")
    paragraphs_count: int = Field(..., description="Number of paragraphs")
    
    # Structure quality indicators
    has_toc: bool = Field(..., description="Has table of contents")
    logical_flow: bool = Field(..., description="Has logical content flow")
    consistent_formatting: bool = Field(..., description="Consistent formatting")
    
    # Improvement suggestions
    structure_suggestions: List[str] = Field(
        ...,
        description="Structure improvement suggestions"
    )
    
    # Detailed analysis
    section_analysis: Dict[str, Any] = Field(
        ...,
        description="Analysis of individual sections"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "structure_score": 0.75,
                "headers_count": 4,
                "lists_count": 2,
                "code_blocks_count": 1,
                "paragraphs_count": 8,
                "has_toc": False,
                "logical_flow": True,
                "consistent_formatting": True,
                "structure_suggestions": [
                    "Add table of contents for better navigation",
                    "Break long paragraphs into shorter ones"
                ],
                "section_analysis": {
                    "introduction": {"length": "appropriate", "clarity": "good"},
                    "main_content": {"length": "long", "structure": "needs_improvement"}
                }
            }
        }


class GapIdentification(BaseModel):
    """Knowledge gap identification results"""
    
    identified_gaps: List[Dict[str, Any]] = Field(
        ...,
        description="List of identified knowledge gaps"
    )
    
    gap_count: int = Field(..., description="Total number of gaps identified")
    
    completeness_score: float = Field(
        ...,
        ge=0,
        le=1,
        description="Content completeness score"
    )
    
    missing_sections: List[str] = Field(
        ...,
        description="Commonly expected sections that are missing"
    )
    
    unanswered_questions: List[str] = Field(
        ...,
        description="Questions that the article doesn't address"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "identified_gaps": [
                    {
                        "type": "missing_section",
                        "content": "troubleshooting",
                        "suggestion": "Add a troubleshooting section for common issues"
                    },
                    {
                        "type": "unanswered_question",
                        "content": "What if the configuration doesn't work?",
                        "suggestion": "Address common failure scenarios"
                    }
                ],
                "gap_count": 3,
                "completeness_score": 0.70,
                "missing_sections": ["troubleshooting", "prerequisites"],
                "unanswered_questions": ["What if it doesn't work?", "How to verify success?"]
            }
        }


class SimilarityAnalysis(BaseModel):
    """Analysis of article similarity with existing content"""
    
    similar_articles: List[Dict[str, Any]] = Field(
        ...,
        description="List of similar articles"
    )
    
    similarity_count: int = Field(..., description="Number of similar articles found")
    
    uniqueness_score: float = Field(
        ...,
        ge=0,
        le=1,
        description="How unique this article is (1 = completely unique)"
    )
    
    duplicate_risk: str = Field(..., description="Risk of content duplication (low, medium, high)")
    
    merge_suggestions: List[str] = Field(
        ...,
        description="Suggestions for merging or cross-referencing"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "similar_articles": [
                    {
                        "article_id": "kb_789",
                        "title": "Email Setup for Outlook",
                        "similarity": 0.75,
                        "overlap_areas": ["email configuration", "SMTP settings"]
                    }
                ],
                "similarity_count": 2,
                "uniqueness_score": 0.65,
                "duplicate_risk": "medium",
                "merge_suggestions": [
                    "Consider cross-referencing with email setup guides",
                    "Avoid duplicating SMTP configuration details"
                ]
            }
        }


class KnowledgeEnhancementResponse(BaseModel):
    """Response model for knowledge enhancement"""
    
    enhancement_id: str = Field(..., description="Unique enhancement session ID")
    article_id: Optional[str] = Field(None, description="ID of the enhanced article")
    
    # Enhancement results
    quality_analysis: Optional[QualityAnalysis] = Field(None, description="Quality analysis results")
    content_suggestions: Optional[List[ContentSuggestion]] = Field(None, description="Content suggestions")
    tag_suggestions: Optional[TagSuggestion] = Field(None, description="Tag suggestions")
    category_suggestion: Optional[CategorySuggestion] = Field(None, description="Category suggestion")
    structure_analysis: Optional[StructureAnalysis] = Field(None, description="Structure analysis")
    gap_identification: Optional[GapIdentification] = Field(None, description="Gap identification")
    similarity_analysis: Optional[SimilarityAnalysis] = Field(None, description="Similarity analysis")
    
    # Overall enhancement summary
    enhancement_summary: Dict[str, Any] = Field(
        ...,
        description="Summary of all enhancements"
    )
    
    # Processing metadata
    processing_time_ms: int = Field(..., description="Processing time in milliseconds")
    enhanced_at: datetime = Field(default_factory=datetime.utcnow, description="Enhancement timestamp")
    
    # Recommendations
    priority_actions: List[str] = Field(
        ...,
        description="High-priority actions to improve the article"
    )
    
    estimated_improvement_impact: str = Field(
        ...,
        description="Expected impact of implementing suggestions"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "enhancement_id": "enh_123456",
                "article_id": "kb_123456",
                "enhancement_summary": {
                    "total_suggestions": 8,
                    "high_priority_suggestions": 3,
                    "overall_quality_score": 0.78,
                    "main_areas_for_improvement": ["add_examples", "improve_structure"]
                },
                "processing_time_ms": 2500,
                "priority_actions": [
                    "Add practical examples",
                    "Include troubleshooting section",
                    "Improve document structure"
                ],
                "estimated_improvement_impact": "high"
            }
        }


class ArticleGenerationRequest(BaseModel):
    """Request to generate a knowledge article from ticket resolution"""
    
    ticket_title: str = Field(..., description="Original ticket title")
    ticket_description: str = Field(..., description="Ticket description")
    
    resolution_steps: List[str] = Field(
        ...,
        min_items=1,
        description="Steps taken to resolve the issue"
    )
    
    category: Optional[str] = Field(None, description="Suggested category")
    tags: Optional[List[str]] = Field(None, description="Suggested tags")
    
    # Context information
    resolution_time: Optional[str] = Field(None, description="Time taken to resolve")
    complexity: Optional[str] = Field(None, description="Resolution complexity")
    tools_used: Optional[List[str]] = Field(None, description="Tools or systems used")
    
    # Generation preferences
    target_audience: str = Field(default="technical", description="Target audience level")
    article_type: ContentType = Field(default=ContentType.TROUBLESHOOTING, description="Type of article to generate")
    include_troubleshooting: bool = Field(default=True, description="Include troubleshooting section")
    
    class Config:
        json_schema_extra = {
            "example": {
                "ticket_title": "Email server not receiving external messages",
                "ticket_description": "Users cannot receive emails from external domains",
                "resolution_steps": [
                    "Checked firewall rules for SMTP ports",
                    "Verified MX records in DNS",
                    "Restarted email service",
                    "Tested external email delivery"
                ],
                "category": "Email & Communication",
                "tags": ["email", "smtp", "firewall"],
                "resolution_time": "45 minutes",
                "complexity": "medium",
                "target_audience": "technical",
                "article_type": "troubleshooting"
            }
        }


class ArticleGenerationResponse(BaseModel):
    """Response for article generation"""
    
    generated_article: KnowledgeArticle = Field(..., description="Generated knowledge article")
    
    generation_metadata: Dict[str, Any] = Field(
        ...,
        description="Metadata about the generation process"
    )
    
    quality_preview: QualityAnalysis = Field(
        ...,
        description="Initial quality analysis of generated article"
    )
    
    suggested_improvements: List[str] = Field(
        ...,
        description="Suggestions to further improve the generated article"
    )
    
    confidence_score: float = Field(
        ...,
        ge=0,
        le=1,
        description="Confidence in the generated content quality"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "generated_article": {
                    "title": "How to resolve: Email server not receiving external messages",
                    "content": "## Overview\nThis guide addresses issues with email servers...",
                    "content_type": "troubleshooting",
                    "category": "Email & Communication",
                    "tags": ["email", "smtp", "troubleshooting"]
                },
                "generation_metadata": {
                    "source_ticket": "ticket_123456",
                    "generation_method": "template_based",
                    "processing_time_ms": 1800
                },
                "confidence_score": 0.85,
                "suggested_improvements": [
                    "Add screenshots for visual guidance",
                    "Include additional troubleshooting scenarios"
                ]
            }
        }


class BulkEnhancementRequest(BaseModel):
    """Request for bulk enhancement of multiple articles"""
    
    articles: List[KnowledgeArticle] = Field(
        ...,
        min_items=1,
        max_items=50,
        description="Articles to enhance"
    )
    
    enhancement_types: List[EnhancementType] = Field(
        ...,
        description="Enhancement types to apply to all articles"
    )
    
    parallel_processing: bool = Field(
        default=True,
        description="Process articles in parallel"
    )
    
    common_settings: Optional[Dict[str, Any]] = Field(
        None,
        description="Settings applied to all enhancements"
    )
    
    class Config:
        json_schema_extra = {
            "example": {
                "articles": [
                    {"title": "Email Setup", "content": "Guide for email setup..."},
                    {"title": "Printer Config", "content": "Printer configuration guide..."}
                ],
                "enhancement_types": ["quality_analysis", "tag_generation"],
                "parallel_processing": True,
                "common_settings": {
                    "target_audience": "end_users"
                }
            }
        }


class BulkEnhancementResponse(BaseModel):
    """Response for bulk enhancement"""
    
    batch_id: str = Field(..., description="Batch processing identifier")
    
    results: List[KnowledgeEnhancementResponse] = Field(
        ...,
        description="Enhancement results for each article"
    )
    
    # Batch summary
    total_articles: int = Field(..., description="Total articles processed")
    successful_enhancements: int = Field(..., description="Successful enhancements")
    failed_enhancements: int = Field(..., description="Failed enhancements")
    
    # Aggregate insights
    common_issues: List[str] = Field(
        ...,
        description="Issues common across multiple articles"
    )
    
    overall_quality_score: float = Field(
        ...,
        ge=0,
        le=1,
        description="Average quality score across all articles"
    )
    
    total_processing_time_ms: int = Field(..., description="Total processing time")
    
    class Config:
        json_schema_extra = {
            "example": {
                "batch_id": "batch_123456",
                "results": [],
                "total_articles": 25,
                "successful_enhancements": 23,
                "failed_enhancements": 2,
                "common_issues": [
                    "Missing examples",
                    "Poor structure",
                    "Lack of troubleshooting sections"
                ],
                "overall_quality_score": 0.72,
                "total_processing_time_ms": 45000
            }
        }