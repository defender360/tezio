"""
ML Model Loader - Handles loading and caching of ML models
"""

import logging
import os
from pathlib import Path
import torch
from transformers import AutoTokenizer, AutoModel, pipeline
from sentence_transformers import SentenceTransformer
import joblib
from typing import Dict, Any, Optional
from app.core.config import settings

logger = logging.getLogger(__name__)

# Global model storage
models: Dict[str, Any] = {}
tokenizers: Dict[str, Any] = {}
pipelines: Dict[str, Any] = {}


class ModelLoader:
    """Handles loading and management of ML models"""
    
    def __init__(self, cache_dir: str = None):
        self.cache_dir = Path(cache_dir or settings.MODEL_CACHE_DIR)
        self.cache_dir.mkdir(parents=True, exist_ok=True)
    
    async def load_embedding_model(self) -> SentenceTransformer:
        """Load sentence transformer for embeddings"""
        try:
            logger.info(f"Loading embedding model: {settings.EMBEDDING_MODEL}")
            model = SentenceTransformer(
                settings.EMBEDDING_MODEL,
                cache_folder=str(self.cache_dir)
            )
            models["embedding"] = model
            logger.info("Embedding model loaded successfully")
            return model
        except Exception as e:
            logger.error(f"Failed to load embedding model: {e}")
            raise
    
    async def load_classification_model(self):
        """Load classification model for ticket categorization"""
        try:
            logger.info(f"Loading classification model: {settings.CLASSIFICATION_MODEL}")
            
            # Load tokenizer
            tokenizer = AutoTokenizer.from_pretrained(
                settings.CLASSIFICATION_MODEL,
                cache_dir=str(self.cache_dir)
            )
            tokenizers["classification"] = tokenizer
            
            # Load model
            model = AutoModel.from_pretrained(
                settings.CLASSIFICATION_MODEL,
                cache_dir=str(self.cache_dir)
            )
            models["classification"] = model
            
            # Create pipeline
            classifier = pipeline(
                "text-classification",
                model=model,
                tokenizer=tokenizer,
                device=0 if torch.cuda.is_available() else -1
            )
            pipelines["classification"] = classifier
            
            logger.info("Classification model loaded successfully")
            return classifier
        except Exception as e:
            logger.error(f"Failed to load classification model: {e}")
            raise
    
    async def load_ner_model(self):
        """Load Named Entity Recognition model"""
        try:
            logger.info("Loading NER model")
            
            ner_pipeline = pipeline(
                "ner",
                model="dbmdz/bert-large-cased-finetuned-conll03-english",
                aggregation_strategy="simple",
                device=0 if torch.cuda.is_available() else -1
            )
            pipelines["ner"] = ner_pipeline
            
            logger.info("NER model loaded successfully")
            return ner_pipeline
        except Exception as e:
            logger.error(f"Failed to load NER model: {e}")
            raise
    
    async def load_sentiment_model(self):
        """Load sentiment analysis model"""
        try:
            logger.info("Loading sentiment analysis model")
            
            sentiment_pipeline = pipeline(
                "sentiment-analysis",
                model="distilbert-base-uncased-finetuned-sst-2-english",
                device=0 if torch.cuda.is_available() else -1
            )
            pipelines["sentiment"] = sentiment_pipeline
            
            logger.info("Sentiment model loaded successfully")
            return sentiment_pipeline
        except Exception as e:
            logger.error(f"Failed to load sentiment model: {e}")
            raise
    
    async def load_custom_models(self):
        """Load any custom trained models"""
        custom_models_dir = self.cache_dir / "custom"
        if custom_models_dir.exists():
            for model_file in custom_models_dir.glob("*.pkl"):
                try:
                    model_name = model_file.stem
                    model = joblib.load(model_file)
                    models[f"custom_{model_name}"] = model
                    logger.info(f"Loaded custom model: {model_name}")
                except Exception as e:
                    logger.error(f"Failed to load custom model {model_file}: {e}")


async def load_models():
    """Load all required ML models"""
    loader = ModelLoader()
    
    try:
        # Load models in parallel where possible
        await loader.load_embedding_model()
        await loader.load_classification_model()
        await loader.load_ner_model()
        await loader.load_sentiment_model()
        await loader.load_custom_models()
        
        logger.info("All models loaded successfully")
    except Exception as e:
        logger.error(f"Model loading failed: {e}")
        # Continue running even if some models fail to load
        # Individual endpoints will handle missing models


def get_model(model_name: str) -> Optional[Any]:
    """Get a loaded model by name"""
    return models.get(model_name)


def get_pipeline(pipeline_name: str) -> Optional[Any]:
    """Get a loaded pipeline by name"""
    return pipelines.get(pipeline_name)


def get_tokenizer(tokenizer_name: str) -> Optional[Any]:
    """Get a loaded tokenizer by name"""
    return tokenizers.get(tokenizer_name)