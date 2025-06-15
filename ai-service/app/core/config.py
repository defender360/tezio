"""
Application configuration using Pydantic settings
"""

from typing import List, Optional, Union
from pydantic_settings import BaseSettings
from pydantic import AnyHttpUrl, validator
import os
from functools import lru_cache


class Settings(BaseSettings):
    """Application settings"""
    
    # Project Info
    PROJECT_NAME: str = "Defender360 AI Service"
    VERSION: str = "1.0.0"
    API_V1_STR: str = "/api/v1"
    
    # Server
    HOST: str = "0.0.0.0"
    PORT: int = 8001
    WORKERS: int = 2
    LOG_LEVEL: str = "INFO"
    
    # Security
    SECRET_KEY: str = os.getenv("SECRET_KEY", "your-secret-key-here")
    ALGORITHM: str = "HS256"
    ACCESS_TOKEN_EXPIRE_MINUTES: int = 30
    
    # Database
    DATABASE_URL: str = os.getenv(
        "DATABASE_URL", 
        "postgresql+asyncpg://itsm_user:secure_password_here@postgres:5432/itsm_platform"
    )
    
    # Redis
    REDIS_URL: str = os.getenv("REDIS_URL", "redis://redis:6379")
    REDIS_CACHE_TTL: int = 3600  # 1 hour
    
    # AI Models
    CLAUDE_API_KEY: Optional[str] = os.getenv("CLAUDE_API_KEY")
    OPENAI_API_KEY: Optional[str] = os.getenv("OPENAI_API_KEY")
    
    # Model Settings
    MODEL_CACHE_DIR: str = "/app/models"
    EMBEDDING_MODEL: str = "sentence-transformers/all-MiniLM-L6-v2"
    CLASSIFICATION_MODEL: str = "distilbert-base-uncased"
    
    # Vector Database
    VECTOR_DB_TYPE: str = "chromadb"  # or "qdrant"
    CHROMADB_PATH: str = "/app/chroma_db"
    QDRANT_URL: Optional[str] = None
    
    # Backend Integration
    BACKEND_URL: str = os.getenv("BACKEND_URL", "http://nginx")
    BACKEND_API_KEY: Optional[str] = os.getenv("BACKEND_API_KEY")
    
    # CORS
    BACKEND_CORS_ORIGINS: List[AnyHttpUrl] = [
        "http://localhost",
        "http://localhost:80",
        "http://localhost:3000",
        "http://localhost:8000",
        "http://nginx"
    ]
    
    ALLOWED_HOSTS: List[str] = ["*"]
    
    # ML Settings
    MAX_TOKENS: int = 2048
    TEMPERATURE: float = 0.7
    TICKET_SUGGESTION_LIMIT: int = 5
    ANOMALY_THRESHOLD: float = 0.85
    
    # Performance
    BATCH_SIZE: int = 32
    MAX_CONCURRENT_REQUESTS: int = 10
    REQUEST_TIMEOUT: int = 30
    
    # Monitoring
    ENABLE_METRICS: bool = True
    METRICS_PORT: int = 9090
    
    @validator("BACKEND_CORS_ORIGINS", pre=True)
    def assemble_cors_origins(cls, v: Union[str, List[str]]) -> Union[List[str], str]:
        if isinstance(v, str) and not v.startswith("["):
            return [i.strip() for i in v.split(",")]
        elif isinstance(v, (list, str)):
            return v
        raise ValueError(v)
    
    class Config:
        env_file = ".env"
        case_sensitive = True


@lru_cache()
def get_settings() -> Settings:
    """Get cached settings instance"""
    return Settings()


# Export settings instance
settings = get_settings()