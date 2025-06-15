"""
Natural Language Processing endpoints
"""

from fastapi import APIRouter, Depends, HTTPException, UploadFile, File
from sqlalchemy.ext.asyncio import AsyncSession
from typing import List, Optional, Dict
import logging

from app.db.init_db import get_db
from app.schemas.nlp import (
    TextExtractionRequest,
    TextExtractionResponse,
    EntityRecognitionRequest,
    EntityRecognitionResponse,
    TextSummarizationRequest,
    TextSummarizationResponse,
    LanguageDetectionRequest,
    LanguageDetectionResponse,
    TranslationRequest,
    TranslationResponse
)
from app.services.nlp_processor import NLPProcessor
from app.services.text_extractor import TextExtractor
from app.services.translator import Translator

logger = logging.getLogger(__name__)
router = APIRouter()

# Initialize services
nlp_processor = NLPProcessor()
text_extractor = TextExtractor()
translator = Translator()


@router.post("/extract-text", response_model=TextExtractionResponse)
async def extract_text(
    file: UploadFile = File(...),
    extract_metadata: bool = True,
    db: AsyncSession = Depends(get_db)
):
    """
    Extract text from various file formats (PDF, DOCX, images, etc.)
    """
    try:
        logger.info(f"Extracting text from file: {file.filename}")
        
        # Read file content
        content = await file.read()
        
        extracted = await text_extractor.extract(
            content=content,
            filename=file.filename,
            content_type=file.content_type,
            extract_metadata=extract_metadata
        )
        
        return TextExtractionResponse(**extracted)
        
    except Exception as e:
        logger.error(f"Text extraction failed: {e}")
        raise HTTPException(status_code=500, detail="Extraction failed")


@router.post("/recognize-entities", response_model=EntityRecognitionResponse)
async def recognize_entities(
    request: EntityRecognitionRequest,
    db: AsyncSession = Depends(get_db)
):
    """
    Extract named entities from text (people, organizations, locations, etc.)
    """
    try:
        logger.info("Recognizing entities in text")
        
        entities = await nlp_processor.extract_entities(
            text=request.text,
            entity_types=request.entity_types,
            language=request.language
        )
        
        return EntityRecognitionResponse(**entities)
        
    except Exception as e:
        logger.error(f"Entity recognition failed: {e}")
        raise HTTPException(status_code=500, detail="Recognition failed")


@router.post("/summarize", response_model=TextSummarizationResponse)
async def summarize_text(
    request: TextSummarizationRequest,
    db: AsyncSession = Depends(get_db)
):
    """
    Generate a summary of the provided text
    """
    try:
        logger.info(f"Summarizing text with max length: {request.max_length}")
        
        summary = await nlp_processor.summarize(
            text=request.text,
            max_length=request.max_length,
            min_length=request.min_length,
            style=request.style
        )
        
        return TextSummarizationResponse(**summary)
        
    except Exception as e:
        logger.error(f"Summarization failed: {e}")
        raise HTTPException(status_code=500, detail="Summarization failed")


@router.post("/detect-language", response_model=LanguageDetectionResponse)
async def detect_language(
    request: LanguageDetectionRequest,
    db: AsyncSession = Depends(get_db)
):
    """
    Detect the language of the provided text
    """
    try:
        logger.info("Detecting language")
        
        detection = await nlp_processor.detect_language(
            text=request.text,
            return_all_scores=request.return_all_scores
        )
        
        return LanguageDetectionResponse(**detection)
        
    except Exception as e:
        logger.error(f"Language detection failed: {e}")
        raise HTTPException(status_code=500, detail="Detection failed")


@router.post("/translate", response_model=TranslationResponse)
async def translate_text(
    request: TranslationRequest,
    db: AsyncSession = Depends(get_db)
):
    """
    Translate text between languages
    """
    try:
        logger.info(f"Translating from {request.source_language} to {request.target_language}")
        
        translation = await translator.translate(
            text=request.text,
            source_language=request.source_language,
            target_language=request.target_language,
            preserve_formatting=request.preserve_formatting
        )
        
        return TranslationResponse(**translation)
        
    except Exception as e:
        logger.error(f"Translation failed: {e}")
        raise HTTPException(status_code=500, detail="Translation failed")


@router.post("/extract-keywords")
async def extract_keywords(
    text: str,
    max_keywords: int = 10,
    algorithm: str = "tfidf",
    db: AsyncSession = Depends(get_db)
):
    """
    Extract keywords from text
    """
    try:
        logger.info(f"Extracting keywords using {algorithm}")
        
        keywords = await nlp_processor.extract_keywords(
            text=text,
            max_keywords=max_keywords,
            algorithm=algorithm
        )
        
        return {"keywords": keywords}
        
    except Exception as e:
        logger.error(f"Keyword extraction failed: {e}")
        raise HTTPException(status_code=500, detail="Extraction failed")


@router.post("/classify-intent")
async def classify_intent(
    text: str,
    context: Optional[Dict[str, str]] = None,
    db: AsyncSession = Depends(get_db)
):
    """
    Classify the intent of user input
    """
    try:
        logger.info("Classifying user intent")
        
        intent = await nlp_processor.classify_intent(
            text=text,
            context=context
        )
        
        return intent
        
    except Exception as e:
        logger.error(f"Intent classification failed: {e}")
        raise HTTPException(status_code=500, detail="Classification failed")


@router.post("/generate-response")
async def generate_response(
    prompt: str,
    context: Optional[str] = None,
    max_tokens: int = 500,
    temperature: float = 0.7,
    db: AsyncSession = Depends(get_db)
):
    """
    Generate AI response based on prompt
    """
    try:
        logger.info("Generating AI response")
        
        response = await nlp_processor.generate_response(
            prompt=prompt,
            context=context,
            max_tokens=max_tokens,
            temperature=temperature
        )
        
        return {"response": response}
        
    except Exception as e:
        logger.error(f"Response generation failed: {e}")
        raise HTTPException(status_code=500, detail="Generation failed")