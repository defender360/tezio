"""
Ticket-related AI endpoints
"""

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.ext.asyncio import AsyncSession
from typing import List, Optional
import logging

from app.db.init_db import get_db
from app.schemas.tickets import (
    TicketAnalysisRequest,
    TicketAnalysisResponse,
    TicketClassificationRequest,
    TicketClassificationResponse,
    SimilarTicketsRequest,
    SimilarTicketsResponse
)
from app.services.ticket_analyzer import TicketAnalyzer
from app.services.ticket_classifier import TicketClassifier
from app.services.similarity_search import SimilaritySearch

logger = logging.getLogger(__name__)
router = APIRouter()

# Initialize services
ticket_analyzer = TicketAnalyzer()
ticket_classifier = TicketClassifier()
similarity_search = SimilaritySearch()


@router.post("/analyze", response_model=TicketAnalysisResponse)
async def analyze_ticket(
    request: TicketAnalysisRequest,
    db: AsyncSession = Depends(get_db)
):
    """
    Analyze a ticket to extract entities, sentiment, and generate insights
    """
    try:
        logger.info(f"Analyzing ticket: {request.title}")
        
        analysis = await ticket_analyzer.analyze(
            title=request.title,
            description=request.description,
            historical_context=request.historical_context
        )
        
        return TicketAnalysisResponse(**analysis)
        
    except Exception as e:
        logger.error(f"Ticket analysis failed: {e}")
        raise HTTPException(status_code=500, detail="Analysis failed")


@router.post("/classify", response_model=TicketClassificationResponse)
async def classify_ticket(
    request: TicketClassificationRequest,
    db: AsyncSession = Depends(get_db)
):
    """
    Classify a ticket into categories and suggest priority
    """
    try:
        logger.info(f"Classifying ticket: {request.title}")
        
        classification = await ticket_classifier.classify(
            title=request.title,
            description=request.description,
            affected_systems=request.affected_systems
        )
        
        return TicketClassificationResponse(**classification)
        
    except Exception as e:
        logger.error(f"Ticket classification failed: {e}")
        raise HTTPException(status_code=500, detail="Classification failed")


@router.post("/similar", response_model=SimilarTicketsResponse)
async def find_similar_tickets(
    request: SimilarTicketsRequest,
    db: AsyncSession = Depends(get_db)
):
    """
    Find similar tickets based on content similarity
    """
    try:
        logger.info(f"Finding similar tickets for: {request.title}")
        
        similar_tickets = await similarity_search.find_similar(
            title=request.title,
            description=request.description,
            category=request.category,
            limit=request.limit or 5
        )
        
        return SimilarTicketsResponse(
            tickets=similar_tickets,
            total=len(similar_tickets)
        )
        
    except Exception as e:
        logger.error(f"Similar ticket search failed: {e}")
        raise HTTPException(status_code=500, detail="Search failed")


@router.post("/auto-assign")
async def auto_assign_ticket(
    ticket_id: int,
    db: AsyncSession = Depends(get_db)
):
    """
    Automatically assign a ticket to the best available agent
    """
    try:
        logger.info(f"Auto-assigning ticket: {ticket_id}")
        
        # TODO: Implement auto-assignment logic
        # This would analyze agent skills, workload, and ticket requirements
        
        return {
            "ticket_id": ticket_id,
            "assigned_to": "agent_123",
            "assignment_reason": "Best match based on skills and availability",
            "confidence": 0.92
        }
        
    except Exception as e:
        logger.error(f"Auto-assignment failed: {e}")
        raise HTTPException(status_code=500, detail="Auto-assignment failed")


@router.post("/estimate-resolution-time")
async def estimate_resolution_time(
    ticket_id: int,
    db: AsyncSession = Depends(get_db)
):
    """
    Estimate the resolution time for a ticket based on historical data
    """
    try:
        logger.info(f"Estimating resolution time for ticket: {ticket_id}")
        
        # TODO: Implement ML-based estimation
        # This would analyze historical resolution times for similar tickets
        
        return {
            "ticket_id": ticket_id,
            "estimated_hours": 4.5,
            "confidence": 0.85,
            "factors": [
                "Similar tickets typically resolved in 3-6 hours",
                "Current team workload is moderate",
                "Issue complexity rated as medium"
            ]
        }
        
    except Exception as e:
        logger.error(f"Resolution time estimation failed: {e}")
        raise HTTPException(status_code=500, detail="Estimation failed")