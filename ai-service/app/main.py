"""
Defender360 AI Service
FastAPI application for AI/ML features including ticket suggestions,
anomaly detection, and natural language processing.
"""

from fastapi import FastAPI, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.middleware.trustedhost import TrustedHostMiddleware
from contextlib import asynccontextmanager
import logging
from app.core.config import settings
from app.api.v1.api import api_router
from app.core.logging import setup_logging
from app.db.init_db import init_db
from app.ml.model_loader import load_models

# Setup logging
setup_logging()
logger = logging.getLogger(__name__)


@asynccontextmanager
async def lifespan(app: FastAPI):
    """
    Manage application lifecycle - startup and shutdown events
    """
    # Startup
    logger.info("Starting Defender360 AI Service...")
    
    # Initialize database
    await init_db()
    
    # Load ML models
    await load_models()
    
    logger.info("AI Service started successfully")
    
    yield
    
    # Shutdown
    logger.info("Shutting down AI Service...")


# Create FastAPI application
app = FastAPI(
    title=settings.PROJECT_NAME,
    version=settings.VERSION,
    openapi_url=f"{settings.API_V1_STR}/openapi.json",
    lifespan=lifespan,
    docs_url="/docs",
    redoc_url="/redoc"
)

# Add middlewares
app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.BACKEND_CORS_ORIGINS,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.add_middleware(
    TrustedHostMiddleware,
    allowed_hosts=settings.ALLOWED_HOSTS
)

# Include API router
app.include_router(api_router, prefix=settings.API_V1_STR)


@app.get("/")
async def root():
    """Root endpoint"""
    return {
        "service": "Defender360 AI Service",
        "version": settings.VERSION,
        "status": "operational"
    }


@app.get("/health")
async def health_check():
    """Health check endpoint"""
    return {
        "status": "healthy",
        "service": "ai-service",
        "version": settings.VERSION
    }