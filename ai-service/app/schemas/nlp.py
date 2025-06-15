"""
NLP schemas for text processing endpoints
"""

from pydantic import BaseModel, Field
from typing import List, Optional, Dict, Any
from enum import Enum


class Entity(BaseModel):
    """Extracted entity"""
    text: str
    type: str = Field(..., description="Entity type (PERSON, ORG, LOC, etc.)")
    score: float = Field(..., ge=0, le=1)
    start_position: int = Field(..., ge=0)
    end_position: int = Field(..., ge=0)


class TextExtractionRequest(BaseModel):
    """Request model for text extraction from files"""
    extract_metadata: bool = Field(True, description="Extract file metadata")


class TextExtractionResponse(BaseModel):
    """Response model for text extraction"""
    text: str = Field(..., description="Extracted text content")
    metadata: Optional[Dict[str, Any]] = Field(None, description="File metadata")
    page_count: Optional[int] = Field(None, description="Number of pages (if applicable)")
    word_count: int = Field(..., ge=0)
    language: str = Field(..., description="Detected language")
    format: str = Field(..., description="Original file format")


class EntityRecognitionRequest(BaseModel):
    """Request model for entity recognition"""
    text: str = Field(..., min_length=1, max_length=10000)
    entity_types: Optional[List[str]] = Field(None, description="Filter by entity types")
    language: str = Field("en", description="Text language")
    
    class Config:
        json_schema_extra = {
            "example": {
                "text": "John Smith from Microsoft called about the server issue in Seattle.",
                "entity_types": ["PERSON", "ORG", "LOC"]
            }
        }


class EntityRecognitionResponse(BaseModel):
    """Response model for entity recognition"""
    entities: List[Entity]
    total_entities: int = Field(..., ge=0)
    processing_time_ms: int = Field(..., ge=0)
    model_used: str


class SummarizationStyle(str, Enum):
    BULLET_POINTS = "bullet_points"
    PARAGRAPH = "paragraph"
    EXECUTIVE = "executive"
    TECHNICAL = "technical"


class TextSummarizationRequest(BaseModel):
    """Request model for text summarization"""
    text: str = Field(..., min_length=50, max_length=50000)
    max_length: int = Field(150, ge=20, le=500, description="Maximum summary length in words")
    min_length: int = Field(50, ge=10, le=200, description="Minimum summary length in words")
    style: SummarizationStyle = Field(SummarizationStyle.PARAGRAPH)
    
    class Config:
        json_schema_extra = {
            "example": {
                "text": "Long technical document text here...",
                "max_length": 150,
                "style": "bullet_points"
            }
        }


class TextSummarizationResponse(BaseModel):
    """Response model for text summarization"""
    summary: str = Field(..., description="Generated summary")
    original_length: int = Field(..., ge=0, description="Original text word count")
    summary_length: int = Field(..., ge=0, description="Summary word count")
    compression_ratio: float = Field(..., ge=0, le=1)
    key_points: List[str] = Field(..., description="Main points extracted")


class LanguageScore(BaseModel):
    """Language detection score"""
    language: str = Field(..., description="ISO 639-1 language code")
    confidence: float = Field(..., ge=0, le=1)
    

class LanguageDetectionRequest(BaseModel):
    """Request model for language detection"""
    text: str = Field(..., min_length=10, max_length=5000)
    return_all_scores: bool = Field(False, description="Return scores for all languages")


class LanguageDetectionResponse(BaseModel):
    """Response model for language detection"""
    detected_language: str = Field(..., description="Primary language ISO code")
    confidence: float = Field(..., ge=0, le=1)
    language_name: str = Field(..., description="Human-readable language name")
    all_scores: Optional[List[LanguageScore]] = None


class TranslationRequest(BaseModel):
    """Request model for text translation"""
    text: str = Field(..., min_length=1, max_length=5000)
    source_language: str = Field(..., description="Source language ISO code")
    target_language: str = Field(..., description="Target language ISO code")
    preserve_formatting: bool = Field(True, description="Preserve text formatting")
    
    class Config:
        json_schema_extra = {
            "example": {
                "text": "Hello, how can I help you today?",
                "source_language": "en",
                "target_language": "es"
            }
        }


class TranslationResponse(BaseModel):
    """Response model for translation"""
    translated_text: str
    source_language: str
    target_language: str
    confidence_score: float = Field(..., ge=0, le=1)
    alternative_translations: Optional[List[str]] = None