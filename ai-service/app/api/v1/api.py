"""
API Router for v1 endpoints
"""

from fastapi import APIRouter
from app.api.v1.endpoints import (
    tickets,
    suggestions,
    analytics,
    nlp,
    knowledge,
    monitoring
)

api_router = APIRouter()

# Include all endpoint routers
api_router.include_router(
    tickets.router,
    prefix="/tickets",
    tags=["tickets"]
)

api_router.include_router(
    suggestions.router,
    prefix="/suggestions",
    tags=["suggestions"]
)

api_router.include_router(
    analytics.router,
    prefix="/analytics",
    tags=["analytics"]
)

api_router.include_router(
    nlp.router,
    prefix="/nlp",
    tags=["nlp"]
)

api_router.include_router(
    knowledge.router,
    prefix="/knowledge",
    tags=["knowledge"]
)

api_router.include_router(
    monitoring.router,
    prefix="/monitoring",
    tags=["monitoring"]
)