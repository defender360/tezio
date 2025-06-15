"""
AI-powered suggestions endpoints
"""

from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.ext.asyncio import AsyncSession
from typing import List, Optional
import logging

from app.db.init_db import get_db
from app.schemas.suggestions import (
    ResolutionSuggestionRequest,
    ResolutionSuggestionResponse,
    ResponseTemplateRequest,
    ResponseTemplateResponse,
    KnowledgeArticleSuggestion
)
from app.services.resolution_suggester import ResolutionSuggester
from app.services.template_generator import TemplateGenerator
from app.services.knowledge_recommender import KnowledgeRecommender

logger = logging.getLogger(__name__)
router = APIRouter()

# Initialize services
resolution_suggester = ResolutionSuggester()
template_generator = TemplateGenerator()
knowledge_recommender = KnowledgeRecommender()


@router.post("/resolution", response_model=ResolutionSuggestionResponse)
async def suggest_resolution(
    request: ResolutionSuggestionRequest,
    db: AsyncSession = Depends(get_db)
):
    """
    Suggest resolution steps for a ticket
    """
    try:
        logger.info(f"Generating resolution suggestions for ticket: {request.ticket_id}")
        
        suggestions = await resolution_suggester.suggest(
            ticket_id=request.ticket_id,
            issue_description=request.issue_description,
            category=request.category,
            affected_systems=request.affected_systems,
            error_messages=request.error_messages
        )
        
        return ResolutionSuggestionResponse(**suggestions)
        
    except Exception as e:
        logger.error(f"Resolution suggestion failed: {e}")
        raise HTTPException(status_code=500, detail="Failed to generate suggestions")


@router.post("/response-template", response_model=ResponseTemplateResponse)
async def generate_response_template(
    request: ResponseTemplateRequest,
    db: AsyncSession = Depends(get_db)
):
    """
    Generate a response template for customer communication
    """
    try:
        logger.info(f"Generating response template for context: {request.context}")
        
        template = await template_generator.generate(
            context=request.context,
            tone=request.tone,
            language=request.language,
            customer_name=request.customer_name,
            issue_summary=request.issue_summary,
            resolution_steps=request.resolution_steps
        )
        
        return ResponseTemplateResponse(**template)
        
    except Exception as e:
        logger.error(f"Template generation failed: {e}")
        raise HTTPException(status_code=500, detail="Failed to generate template")


@router.get("/knowledge-articles", response_model=List[KnowledgeArticleSuggestion])
async def suggest_knowledge_articles(
    ticket_id: Optional[int] = Query(None),
    query: Optional[str] = Query(None),
    category: Optional[str] = Query(None),
    limit: int = Query(5, le=20),
    db: AsyncSession = Depends(get_db)
):
    """
    Suggest relevant knowledge base articles
    """
    try:
        if not ticket_id and not query:
            raise HTTPException(
                status_code=400,
                detail="Either ticket_id or query must be provided"
            )
        
        logger.info(f"Finding knowledge articles for ticket: {ticket_id} or query: {query}")
        
        articles = await knowledge_recommender.recommend(
            ticket_id=ticket_id,
            query=query,
            category=category,
            limit=limit
        )
        
        return articles
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Knowledge article recommendation failed: {e}")
        raise HTTPException(status_code=500, detail="Failed to find articles")


@router.post("/incident-suggestions")
async def suggest_incident_details(
    description: str,
    db: AsyncSession = Depends(get_db)
):
    """
    Suggest incident details based on description
    """
    try:
        logger.info("Generating incident suggestions")
        
        # TODO: Implement AI-based suggestion logic
        # For now, return mock suggestions
        suggestions = [
            "Check if this issue affects other users in the same department",
            "Verify if recent system updates may have caused this issue",
            "Consider escalating if business-critical systems are affected"
        ]
        
        return {"suggestions": suggestions}
        
    except Exception as e:
        logger.error(f"Incident suggestion failed: {e}")
        raise HTTPException(status_code=500, detail="Failed to generate suggestions")


@router.post("/tag-suggestions")
async def suggest_tags(
    title: str,
    description: str,
    db: AsyncSession = Depends(get_db)
):
    """
    Suggest relevant tags for a ticket
    """
    try:
        logger.info("Generating tag suggestions")
        
        # TODO: Implement NLP-based tag extraction
        # For now, return mock tags
        tags = [
            {"tag": "network", "confidence": 0.9},
            {"tag": "connectivity", "confidence": 0.85},
            {"tag": "urgent", "confidence": 0.7}
        ]
        
        return {"tags": tags}
        
    except Exception as e:
        logger.error(f"Tag suggestion failed: {e}")
        raise HTTPException(status_code=500, detail="Failed to generate tags")