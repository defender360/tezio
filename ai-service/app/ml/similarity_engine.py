"""
Similarity Engine ML Model

Advanced text similarity engine for:
- Finding similar tickets/knowledge articles
- Semantic search and matching
- Duplicate detection
- Content recommendation
- Knowledge base optimization
"""

import asyncio
import logging
import numpy as np
import pandas as pd
from typing import Dict, List, Any, Optional, Tuple, Union
from datetime import datetime
from pathlib import Path
import json
import pickle
from dataclasses import dataclass
from collections import defaultdict
import re

# ML and NLP imports
from sentence_transformers import SentenceTransformer
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity, euclidean_distances
from sklearn.decomposition import PCA, TruncatedSVD
from sklearn.preprocessing import StandardScaler
from sklearn.cluster import KMeans, DBSCAN
import faiss  # For efficient similarity search
import numpy as np

# Advanced NLP imports
try:
    import spacy
    from transformers import AutoTokenizer, AutoModel
    import torch
    ADVANCED_NLP_AVAILABLE = True
except ImportError:
    ADVANCED_NLP_AVAILABLE = False

from app.core.config import settings
from app.core.logging import get_logger

logger = get_logger(__name__)


@dataclass
class SimilarityResult:
    """Result of similarity search"""
    item_id: str
    score: float
    item_type: str  # 'ticket', 'article', 'faq'
    title: str
    content_preview: str
    metadata: Dict[str, Any]


@dataclass
class EmbeddingMetadata:
    """Metadata for stored embeddings"""
    item_id: str
    item_type: str
    title: str
    content: str
    category: str
    tags: List[str]
    created_at: datetime
    embedding_model: str
    embedding_version: str


class SimilarityEngine:
    """
    Advanced similarity engine for text matching and semantic search
    """
    
    def __init__(self):
        self.embedding_models = {}
        self.tfidf_vectorizers = {}
        self.embeddings_store = {}
        self.metadata_store = {}
        self.similarity_indices = {}
        self.clustering_models = {}
        self._initialized = False
        
        # Configuration
        self.config = {
            'embedding_model': settings.EMBEDDING_MODEL,
            'max_embeddings': 100000,
            'similarity_threshold': 0.7,
            'clustering_enabled': True,
            'faiss_enabled': True
        }
        
        # Similarity methods
        self.similarity_methods = {
            'semantic': self._semantic_similarity,
            'tfidf': self._tfidf_similarity,
            'hybrid': self._hybrid_similarity,
            'fuzzy': self._fuzzy_similarity
        }
        
        # Text preprocessing patterns
        self.preprocessing_patterns = [
            (r'\b\d+\b', '<NUMBER>'),  # Replace numbers
            (r'\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b', '<EMAIL>'),  # Replace emails
            (r'https?://\S+', '<URL>'),  # Replace URLs
            (r'\b(?:\d{1,3}\.){3}\d{1,3}\b', '<IP>'),  # Replace IP addresses
        ]
    
    async def initialize(self):
        """Initialize the similarity engine"""
        if self._initialized:
            return
            
        try:
            logger.info("Initializing similarity engine...")
            
            # Load embedding model
            await self._load_embedding_models()
            
            # Initialize TF-IDF vectorizers
            await self._initialize_tfidf_vectorizers()
            
            # Load existing embeddings
            await self._load_embeddings()
            
            # Initialize FAISS indices if available
            if self.config['faiss_enabled']:
                await self._initialize_faiss_indices()
            
            # Initialize clustering models
            if self.config['clustering_enabled']:
                await self._initialize_clustering()
            
            self._initialized = True
            logger.info("Similarity engine initialized successfully")
            
        except Exception as e:
            logger.error(f"Failed to initialize similarity engine: {e}")
            self._initialized = True  # Continue with limited functionality
    
    async def find_similar(
        self,
        query_text: str,
        item_type: Optional[str] = None,
        category: Optional[str] = None,
        tags: Optional[List[str]] = None,
        limit: int = 10,
        similarity_method: str = 'hybrid',
        min_similarity: float = 0.5
    ) -> List[SimilarityResult]:
        """
        Find similar items to the query text
        
        Args:
            query_text: Text to find similarities for
            item_type: Filter by item type ('ticket', 'article', 'faq')
            category: Filter by category
            tags: Filter by tags
            limit: Maximum number of results
            similarity_method: Method to use ('semantic', 'tfidf', 'hybrid', 'fuzzy')
            min_similarity: Minimum similarity score
            
        Returns:
            List of similarity results
        """
        await self.initialize()
        
        try:
            logger.info(f"Finding similar items for query (method: {similarity_method})...")
            
            # Preprocess query
            processed_query = self._preprocess_text(query_text)
            
            # Get similarity method
            similarity_func = self.similarity_methods.get(similarity_method, self._hybrid_similarity)
            
            # Calculate similarities
            similarities = await similarity_func(processed_query, item_type, category, tags)
            
            # Filter by minimum similarity
            filtered_similarities = [
                result for result in similarities 
                if result.score >= min_similarity
            ]
            
            # Sort by similarity score
            sorted_similarities = sorted(filtered_similarities, key=lambda x: x.score, reverse=True)
            
            # Return top results
            return sorted_similarities[:limit]
            
        except Exception as e:
            logger.error(f"Similarity search failed: {e}")
            return []
    
    async def add_item(
        self,
        item_id: str,
        title: str,
        content: str,
        item_type: str,
        category: str = "",
        tags: List[str] = None,
        metadata: Dict[str, Any] = None
    ) -> bool:
        """
        Add an item to the similarity index
        
        Args:
            item_id: Unique identifier for the item
            title: Item title
            content: Item content
            item_type: Type of item ('ticket', 'article', 'faq')
            category: Item category
            tags: Item tags
            metadata: Additional metadata
            
        Returns:
            Success status
        """
        await self.initialize()
        
        try:
            logger.info(f"Adding item to similarity index: {item_id}")
            
            # Combine title and content
            full_text = f"{title}. {content}"
            processed_text = self._preprocess_text(full_text)
            
            # Generate embeddings
            embeddings = await self._generate_embeddings(processed_text)
            
            # Store embeddings
            self.embeddings_store[item_id] = embeddings
            
            # Store metadata
            self.metadata_store[item_id] = EmbeddingMetadata(
                item_id=item_id,
                item_type=item_type,
                title=title,
                content=content,
                category=category,
                tags=tags or [],
                created_at=datetime.utcnow(),
                embedding_model=self.config['embedding_model'],
                embedding_version='1.0'
            )
            
            # Update FAISS index if enabled
            if self.config['faiss_enabled']:
                await self._update_faiss_index(item_id, embeddings)
            
            # Update clustering if needed
            if self.config['clustering_enabled'] and len(self.embeddings_store) % 100 == 0:
                await self._update_clustering()
            
            logger.info(f"Successfully added item {item_id} to similarity index")
            return True
            
        except Exception as e:
            logger.error(f"Failed to add item {item_id}: {e}")
            return False
    
    async def remove_item(self, item_id: str) -> bool:
        """Remove an item from the similarity index"""
        try:
            if item_id in self.embeddings_store:
                del self.embeddings_store[item_id]
            
            if item_id in self.metadata_store:
                del self.metadata_store[item_id]
            
            # Update indices
            if self.config['faiss_enabled']:
                await self._rebuild_faiss_index()
            
            logger.info(f"Removed item {item_id} from similarity index")
            return True
            
        except Exception as e:
            logger.error(f"Failed to remove item {item_id}: {e}")
            return False
    
    async def detect_duplicates(
        self,
        similarity_threshold: float = 0.85,
        item_type: Optional[str] = None
    ) -> List[Dict[str, Any]]:
        """
        Detect potential duplicate content
        
        Args:
            similarity_threshold: Minimum similarity to consider as duplicate
            item_type: Filter by item type
            
        Returns:
            List of potential duplicate pairs
        """
        await self.initialize()
        
        try:
            logger.info(f"Detecting duplicates with threshold {similarity_threshold}")
            
            duplicates = []
            processed_items = set()
            
            # Get filtered items
            items_to_check = []
            for item_id, metadata in self.metadata_store.items():
                if item_type is None or metadata.item_type == item_type:
                    items_to_check.append(item_id)
            
            # Compare all pairs
            for i, item_id1 in enumerate(items_to_check):
                if item_id1 in processed_items:
                    continue
                    
                for item_id2 in items_to_check[i+1:]:
                    if item_id2 in processed_items:
                        continue
                    
                    # Calculate similarity
                    similarity = await self._calculate_item_similarity(item_id1, item_id2)
                    
                    if similarity >= similarity_threshold:
                        duplicates.append({
                            'item1_id': item_id1,
                            'item1_title': self.metadata_store[item_id1].title,
                            'item2_id': item_id2,
                            'item2_title': self.metadata_store[item_id2].title,
                            'similarity_score': similarity,
                            'detection_method': 'semantic'
                        })
                        
                        processed_items.add(item_id2)
            
            logger.info(f"Found {len(duplicates)} potential duplicate pairs")
            return duplicates
            
        except Exception as e:
            logger.error(f"Duplicate detection failed: {e}")
            return []
    
    async def get_content_clusters(
        self,
        n_clusters: int = 10,
        item_type: Optional[str] = None
    ) -> Dict[str, Any]:
        """
        Get content clusters for analysis
        
        Args:
            n_clusters: Number of clusters to create
            item_type: Filter by item type
            
        Returns:
            Clustering results
        """
        await self.initialize()
        
        try:
            logger.info(f"Generating {n_clusters} content clusters")
            
            # Get filtered embeddings
            embeddings = []
            item_ids = []
            
            for item_id, metadata in self.metadata_store.items():
                if item_type is None or metadata.item_type == item_type:
                    if item_id in self.embeddings_store:
                        embeddings.append(self.embeddings_store[item_id]['semantic'])
                        item_ids.append(item_id)
            
            if len(embeddings) < n_clusters:
                logger.warning(f"Not enough items ({len(embeddings)}) for {n_clusters} clusters")
                n_clusters = max(2, len(embeddings) // 2)
            
            # Perform clustering
            embeddings_array = np.array(embeddings)
            kmeans = KMeans(n_clusters=n_clusters, random_state=42)
            cluster_labels = kmeans.fit_predict(embeddings_array)
            
            # Organize results
            clusters = defaultdict(list)
            for item_id, label in zip(item_ids, cluster_labels):
                clusters[f"cluster_{label}"].append({
                    'item_id': item_id,
                    'title': self.metadata_store[item_id].title,
                    'category': self.metadata_store[item_id].category,
                    'item_type': self.metadata_store[item_id].item_type
                })
            
            # Calculate cluster statistics
            cluster_stats = {}
            for cluster_id, items in clusters.items():
                categories = [item['category'] for item in items if item['category']]
                category_counts = Counter(categories)
                
                cluster_stats[cluster_id] = {
                    'item_count': len(items),
                    'most_common_category': category_counts.most_common(1)[0] if category_counts else None,
                    'category_distribution': dict(category_counts)
                }
            
            return {
                'clusters': dict(clusters),
                'cluster_stats': cluster_stats,
                'total_items': len(item_ids),
                'n_clusters': n_clusters,
                'clustering_method': 'kmeans'
            }
            
        except Exception as e:
            logger.error(f"Content clustering failed: {e}")
            return {}
    
    async def _semantic_similarity(
        self,
        query: str,
        item_type: Optional[str] = None,
        category: Optional[str] = None,
        tags: Optional[List[str]] = None
    ) -> List[SimilarityResult]:
        """Calculate semantic similarities using embeddings"""
        try:
            # Generate query embedding
            query_embeddings = await self._generate_embeddings(query)
            query_vector = query_embeddings['semantic']
            
            similarities = []
            
            for item_id, metadata in self.metadata_store.items():
                # Apply filters
                if item_type and metadata.item_type != item_type:
                    continue
                if category and metadata.category != category:
                    continue
                if tags and not any(tag in metadata.tags for tag in tags):
                    continue
                
                # Calculate similarity
                if item_id in self.embeddings_store:
                    item_vector = self.embeddings_store[item_id]['semantic']
                    similarity = cosine_similarity([query_vector], [item_vector])[0][0]
                    
                    similarities.append(SimilarityResult(
                        item_id=item_id,
                        score=float(similarity),
                        item_type=metadata.item_type,
                        title=metadata.title,
                        content_preview=metadata.content[:200] + "...",
                        metadata={
                            'category': metadata.category,
                            'tags': metadata.tags,
                            'created_at': metadata.created_at.isoformat()
                        }
                    ))
            
            return similarities
            
        except Exception as e:
            logger.error(f"Semantic similarity calculation failed: {e}")
            return []
    
    async def _tfidf_similarity(
        self,
        query: str,
        item_type: Optional[str] = None,
        category: Optional[str] = None,
        tags: Optional[List[str]] = None
    ) -> List[SimilarityResult]:
        """Calculate TF-IDF based similarities"""
        try:
            # Get or create TF-IDF vectorizer for item type
            vectorizer_key = item_type or 'general'
            if vectorizer_key not in self.tfidf_vectorizers:
                return []
            
            vectorizer = self.tfidf_vectorizers[vectorizer_key]
            
            # Get documents for this item type
            documents = []
            item_ids = []
            
            for item_id, metadata in self.metadata_store.items():
                # Apply filters
                if item_type and metadata.item_type != item_type:
                    continue
                if category and metadata.category != category:
                    continue
                if tags and not any(tag in metadata.tags for tag in tags):
                    continue
                
                documents.append(f"{metadata.title}. {metadata.content}")
                item_ids.append(item_id)
            
            if not documents:
                return []
            
            # Transform documents and query
            all_texts = documents + [query]
            tfidf_matrix = vectorizer.fit_transform(all_texts)
            
            # Calculate similarities
            query_vector = tfidf_matrix[-1]
            document_vectors = tfidf_matrix[:-1]
            
            similarities = cosine_similarity(query_vector, document_vectors)[0]
            
            results = []
            for i, (item_id, similarity) in enumerate(zip(item_ids, similarities)):
                metadata = self.metadata_store[item_id]
                results.append(SimilarityResult(
                    item_id=item_id,
                    score=float(similarity),
                    item_type=metadata.item_type,
                    title=metadata.title,
                    content_preview=metadata.content[:200] + "...",
                    metadata={
                        'category': metadata.category,
                        'tags': metadata.tags,
                        'created_at': metadata.created_at.isoformat()
                    }
                ))
            
            return results
            
        except Exception as e:
            logger.error(f"TF-IDF similarity calculation failed: {e}")
            return []
    
    async def _hybrid_similarity(
        self,
        query: str,
        item_type: Optional[str] = None,
        category: Optional[str] = None,
        tags: Optional[List[str]] = None
    ) -> List[SimilarityResult]:
        """Calculate hybrid similarities combining multiple methods"""
        try:
            # Get results from different methods
            semantic_results = await self._semantic_similarity(query, item_type, category, tags)
            tfidf_results = await self._tfidf_similarity(query, item_type, category, tags)
            
            # Combine results with weights
            semantic_weight = 0.7
            tfidf_weight = 0.3
            
            # Create mapping for quick lookup
            semantic_scores = {result.item_id: result.score for result in semantic_results}
            tfidf_scores = {result.item_id: result.score for result in tfidf_results}
            
            # Calculate combined scores
            combined_results = []
            all_item_ids = set(semantic_scores.keys()) | set(tfidf_scores.keys())
            
            for item_id in all_item_ids:
                semantic_score = semantic_scores.get(item_id, 0)
                tfidf_score = tfidf_scores.get(item_id, 0)
                
                combined_score = (semantic_score * semantic_weight + 
                                tfidf_score * tfidf_weight)
                
                # Get metadata
                if item_id in self.metadata_store:
                    metadata = self.metadata_store[item_id]
                    combined_results.append(SimilarityResult(
                        item_id=item_id,
                        score=combined_score,
                        item_type=metadata.item_type,
                        title=metadata.title,
                        content_preview=metadata.content[:200] + "...",
                        metadata={
                            'category': metadata.category,
                            'tags': metadata.tags,
                            'created_at': metadata.created_at.isoformat(),
                            'semantic_score': semantic_score,
                            'tfidf_score': tfidf_score
                        }
                    ))
            
            return combined_results
            
        except Exception as e:
            logger.error(f"Hybrid similarity calculation failed: {e}")
            return []
    
    async def _fuzzy_similarity(
        self,
        query: str,
        item_type: Optional[str] = None,
        category: Optional[str] = None,
        tags: Optional[List[str]] = None
    ) -> List[SimilarityResult]:
        """Calculate fuzzy string similarities"""
        try:
            from difflib import SequenceMatcher
            
            similarities = []
            
            for item_id, metadata in self.metadata_store.items():
                # Apply filters
                if item_type and metadata.item_type != item_type:
                    continue
                if category and metadata.category != category:
                    continue
                if tags and not any(tag in metadata.tags for tag in tags):
                    continue
                
                # Calculate fuzzy similarity
                full_text = f"{metadata.title}. {metadata.content}"
                similarity = SequenceMatcher(None, query.lower(), full_text.lower()).ratio()
                
                similarities.append(SimilarityResult(
                    item_id=item_id,
                    score=similarity,
                    item_type=metadata.item_type,
                    title=metadata.title,
                    content_preview=metadata.content[:200] + "...",
                    metadata={
                        'category': metadata.category,
                        'tags': metadata.tags,
                        'created_at': metadata.created_at.isoformat()
                    }
                ))
            
            return similarities
            
        except Exception as e:
            logger.error(f"Fuzzy similarity calculation failed: {e}")
            return []
    
    async def _generate_embeddings(self, text: str) -> Dict[str, np.ndarray]:
        """Generate embeddings for text using multiple methods"""
        embeddings = {}
        
        try:
            # Semantic embeddings using sentence transformers
            if 'semantic' in self.embedding_models:
                model = self.embedding_models['semantic']
                semantic_embedding = model.encode([text])[0]
                embeddings['semantic'] = semantic_embedding
            
            # Add other embedding methods here if needed
            # e.g., word2vec, BERT, etc.
            
        except Exception as e:
            logger.error(f"Embedding generation failed: {e}")
            # Return zero vector as fallback
            embeddings['semantic'] = np.zeros(384)  # Default dimension
        
        return embeddings
    
    def _preprocess_text(self, text: str) -> str:
        """Preprocess text for better similarity matching"""
        try:
            # Convert to lowercase
            processed = text.lower()
            
            # Apply preprocessing patterns
            for pattern, replacement in self.preprocessing_patterns:
                processed = re.sub(pattern, replacement, processed)
            
            # Remove extra whitespace
            processed = re.sub(r'\s+', ' ', processed).strip()
            
            return processed
            
        except Exception as e:
            logger.error(f"Text preprocessing failed: {e}")
            return text.lower()
    
    async def _calculate_item_similarity(self, item_id1: str, item_id2: str) -> float:
        """Calculate similarity between two items"""
        try:
            if item_id1 not in self.embeddings_store or item_id2 not in self.embeddings_store:
                return 0.0
            
            embedding1 = self.embeddings_store[item_id1]['semantic']
            embedding2 = self.embeddings_store[item_id2]['semantic']
            
            similarity = cosine_similarity([embedding1], [embedding2])[0][0]
            return float(similarity)
            
        except Exception as e:
            logger.error(f"Item similarity calculation failed: {e}")
            return 0.0
    
    async def _load_embedding_models(self):
        """Load embedding models"""
        try:
            # Load sentence transformer model
            model_name = self.config['embedding_model']
            self.embedding_models['semantic'] = SentenceTransformer(model_name)
            
            logger.info(f"Loaded embedding model: {model_name}")
            
        except Exception as e:
            logger.error(f"Failed to load embedding models: {e}")
    
    async def _initialize_tfidf_vectorizers(self):
        """Initialize TF-IDF vectorizers"""
        try:
            # Create vectorizers for different item types
            for item_type in ['ticket', 'article', 'faq', 'general']:
                self.tfidf_vectorizers[item_type] = TfidfVectorizer(
                    max_features=5000,
                    stop_words='english',
                    ngram_range=(1, 2),
                    lowercase=True
                )
            
            logger.info("TF-IDF vectorizers initialized")
            
        except Exception as e:
            logger.error(f"Failed to initialize TF-IDF vectorizers: {e}")
    
    async def _load_embeddings(self):
        """Load existing embeddings from storage"""
        try:
            # In production, this would load from persistent storage
            # For now, start with empty stores
            self.embeddings_store = {}
            self.metadata_store = {}
            
            logger.info("Embeddings storage initialized")
            
        except Exception as e:
            logger.error(f"Failed to load embeddings: {e}")
    
    async def _initialize_faiss_indices(self):
        """Initialize FAISS indices for efficient similarity search"""
        try:
            # This would set up FAISS indices for fast similarity search
            # Implementation depends on FAISS availability
            logger.info("FAISS indices would be initialized here")
            
        except Exception as e:
            logger.error(f"Failed to initialize FAISS indices: {e}")
    
    async def _initialize_clustering(self):
        """Initialize clustering models"""
        try:
            # Initialize clustering models for content analysis
            self.clustering_models['kmeans'] = None  # Will be created when needed
            
            logger.info("Clustering models initialized")
            
        except Exception as e:
            logger.error(f"Failed to initialize clustering: {e}")
    
    async def _update_faiss_index(self, item_id: str, embeddings: Dict[str, np.ndarray]):
        """Update FAISS index with new embedding"""
        try:
            # This would update the FAISS index
            # Implementation depends on FAISS setup
            pass
            
        except Exception as e:
            logger.error(f"Failed to update FAISS index: {e}")
    
    async def _rebuild_faiss_index(self):
        """Rebuild FAISS index after item removal"""
        try:
            # This would rebuild the FAISS index
            # Implementation depends on FAISS setup
            pass
            
        except Exception as e:
            logger.error(f"Failed to rebuild FAISS index: {e}")
    
    async def _update_clustering(self):
        """Update clustering models with new data"""
        try:
            # This would update clustering models periodically
            # Implementation depends on clustering strategy
            pass
            
        except Exception as e:
            logger.error(f"Failed to update clustering: {e}")
    
    async def get_similarity_stats(self) -> Dict[str, Any]:
        """Get statistics about the similarity engine"""
        return {
            'total_items': len(self.metadata_store),
            'item_type_distribution': {
                item_type: sum(1 for m in self.metadata_store.values() if m.item_type == item_type)
                for item_type in ['ticket', 'article', 'faq']
            },
            'embedding_model': self.config['embedding_model'],
            'similarity_methods': list(self.similarity_methods.keys()),
            'faiss_enabled': self.config['faiss_enabled'],
            'clustering_enabled': self.config['clustering_enabled']
        }