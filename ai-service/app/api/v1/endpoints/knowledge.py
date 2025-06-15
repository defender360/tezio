"""
Knowledge base AI endpoints
"""

from fastapi import APIRouter, Depends, HTTPException, Query, UploadFile, File
from sqlalchemy.ext.asyncio import AsyncSession
from typing import List, Optional
import logging

from app.db.init_db import get_db
from app.schemas.knowledge import (
    KnowledgeArticleRequest,
    KnowledgeArticleResponse,
    KnowledgeSearchRequest,
    KnowledgeSearchResponse,
    DocumentProcessingRequest,
    DocumentProcessingResponse,
    FAQGenerationRequest,
    FAQGenerationResponse
)
from app.services.knowledge_processor import KnowledgeProcessor
from app.services.document_analyzer import DocumentAnalyzer
from app.services.faq_generator import FAQGenerator

logger = logging.getLogger(__name__)
router = APIRouter()

# Initialize services
knowledge_processor = KnowledgeProcessor()
document_analyzer = DocumentAnalyzer()
faq_generator = FAQGenerator()


@router.post("/articles/create", response_model=KnowledgeArticleResponse)
async def create_knowledge_article(
    request: KnowledgeArticleRequest,
    db: AsyncSession = Depends(get_db)
):
    """
    Create or update a knowledge base article with AI enhancements
    """
    try:
        logger.info(f"Creating knowledge article: {request.title}")
        
        article = await knowledge_processor.create_article(
            title=request.title,
            content=request.content,
            category=request.category,
            tags=request.tags,
            auto_enhance=request.auto_enhance
        )
        
        return KnowledgeArticleResponse(**article)
        
    except Exception as e:
        logger.error(f"Article creation failed: {e}")
        raise HTTPException(status_code=500, detail="Creation failed")


@router.post("/search", response_model=KnowledgeSearchResponse)
async def search_knowledge_base(
    request: KnowledgeSearchRequest,
    db: AsyncSession = Depends(get_db)
):
    """
    Semantic search across knowledge base
    """
    try:
        logger.info(f"Searching knowledge base: {request.query}")
        
        results = await knowledge_processor.semantic_search(
            query=request.query,
            filters=request.filters,
            limit=request.limit,
            include_similar=request.include_similar
        )
        
        return KnowledgeSearchResponse(**results)
        
    except Exception as e:
        logger.error(f"Knowledge search failed: {e}")
        raise HTTPException(status_code=500, detail="Search failed")


@router.post("/process-document", response_model=DocumentProcessingResponse)
async def process_document(
    file: UploadFile = File(...),
    auto_categorize: bool = True,
    extract_qa_pairs: bool = True,
    generate_summary: bool = True,
    db: AsyncSession = Depends(get_db)
):
    """
    Process uploaded document and extract knowledge
    """
    try:
        logger.info(f"Processing document: {file.filename}")
        
        content = await file.read()
        
        processed = await document_analyzer.process(
            content=content,
            filename=file.filename,
            content_type=file.content_type,
            auto_categorize=auto_categorize,
            extract_qa_pairs=extract_qa_pairs,
            generate_summary=generate_summary
        )
        
        return DocumentProcessingResponse(**processed)
        
    except Exception as e:
        logger.error(f"Document processing failed: {e}")
        raise HTTPException(status_code=500, detail="Processing failed")


@router.post("/generate-faq", response_model=FAQGenerationResponse)
async def generate_faq(
    request: FAQGenerationRequest,
    db: AsyncSession = Depends(get_db)
):
    """
    Generate FAQ from tickets and knowledge articles
    """
    try:
        logger.info(f"Generating FAQ for category: {request.category}")
        
        faq = await faq_generator.generate(
            category=request.category,
            time_period=request.time_period,
            min_frequency=request.min_frequency,
            max_questions=request.max_questions
        )
        
        return FAQGenerationResponse(**faq)
        
    except Exception as e:
        logger.error(f"FAQ generation failed: {e}")
        raise HTTPException(status_code=500, detail="Generation failed")


@router.get("/articles/recommend")
async def recommend_articles(
    ticket_id: Optional[int] = Query(None),
    user_id: Optional[int] = Query(None),
    category: Optional[str] = Query(None),
    limit: int = Query(5, le=20),
    db: AsyncSession = Depends(get_db)
):
    """
    Get personalized article recommendations
    """
    try:
        recommendations = await knowledge_processor.get_recommendations(
            ticket_id=ticket_id,
            user_id=user_id,
            category=category,
            limit=limit
        )
        
        return {"recommendations": recommendations}
        
    except Exception as e:
        logger.error(f"Recommendation failed: {e}")
        raise HTTPException(status_code=500, detail="Recommendation failed")


@router.post("/articles/{article_id}/improve")
async def improve_article(
    article_id: int,
    feedback_data: Optional[dict] = None,
    db: AsyncSession = Depends(get_db)
):
    """
    Use AI to improve article based on feedback and usage
    """
    try:
        logger.info(f"Improving article: {article_id}")
        
        improvements = await knowledge_processor.improve_article(
            article_id=article_id,
            feedback_data=feedback_data
        )
        
        return improvements
        
    except Exception as e:
        logger.error(f"Article improvement failed: {e}")
        raise HTTPException(status_code=500, detail="Improvement failed")


@router.get("/topics/trending")
async def get_trending_topics(
    time_period: str = Query("week", regex="^(day|week|month)$"),
    limit: int = Query(10, le=50),
    db: AsyncSession = Depends(get_db)
):
    """
    Get trending topics from tickets and searches
    """
    try:
        topics = await knowledge_processor.get_trending_topics(
            time_period=time_period,
            limit=limit
        )
        
        return {"topics": topics}
        
    except Exception as e:
        logger.error(f"Trending topics failed: {e}")
        raise HTTPException(status_code=500, detail="Analysis failed")


@router.post("/chatbot/answer")
async def chatbot_answer(
    question: str,
    conversation_id: Optional[str] = None,
    context: Optional[dict] = None,
    db: AsyncSession = Depends(get_db)
):
    """
    Get AI-powered answer from knowledge base
    """
    try:
        logger.info(f"Answering question: {question}")
        
        answer = await knowledge_processor.answer_question(
            question=question,
            conversation_id=conversation_id,
            context=context
        )
        
        return answer
        
    except Exception as e:
        logger.error(f"Answer generation failed: {e}")
        raise HTTPException(status_code=500, detail="Answer generation failed")