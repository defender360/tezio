"""
Vector Store initialization and management
"""

import logging
from typing import Optional, List, Dict, Any
import chromadb
from chromadb.config import Settings as ChromaSettings
from qdrant_client import QdrantClient
from qdrant_client.models import Distance, VectorParams
from app.core.config import settings

logger = logging.getLogger(__name__)

# Global vector store instances
vector_store: Optional[Any] = None


class VectorStoreManager:
    """Manages vector database operations"""
    
    def __init__(self):
        self.store_type = settings.VECTOR_DB_TYPE
        self.client = None
    
    async def initialize_chromadb(self):
        """Initialize ChromaDB vector store"""
        try:
            logger.info("Initializing ChromaDB...")
            
            # Create ChromaDB client
            self.client = chromadb.PersistentClient(
                path=settings.CHROMADB_PATH,
                settings=ChromaSettings(
                    anonymized_telemetry=False,
                    allow_reset=True
                )
            )
            
            # Create collections
            collections = [
                "incidents",
                "knowledge_base",
                "similar_tickets",
                "faq"
            ]
            
            for collection_name in collections:
                try:
                    self.client.create_collection(
                        name=collection_name,
                        metadata={"hnsw:space": "cosine"}
                    )
                    logger.info(f"Created collection: {collection_name}")
                except Exception as e:
                    # Collection might already exist
                    logger.debug(f"Collection {collection_name} already exists")
            
            logger.info("ChromaDB initialized successfully")
            
        except Exception as e:
            logger.error(f"Failed to initialize ChromaDB: {e}")
            raise
    
    async def initialize_qdrant(self):
        """Initialize Qdrant vector store"""
        try:
            logger.info("Initializing Qdrant...")
            
            # Create Qdrant client
            if settings.QDRANT_URL:
                self.client = QdrantClient(url=settings.QDRANT_URL)
            else:
                self.client = QdrantClient(path="/app/qdrant_db")
            
            # Create collections
            collections = {
                "incidents": 384,  # all-MiniLM-L6-v2 dimension
                "knowledge_base": 384,
                "similar_tickets": 384,
                "faq": 384
            }
            
            for collection_name, vector_size in collections.items():
                try:
                    self.client.create_collection(
                        collection_name=collection_name,
                        vectors_config=VectorParams(
                            size=vector_size,
                            distance=Distance.COSINE
                        )
                    )
                    logger.info(f"Created Qdrant collection: {collection_name}")
                except Exception as e:
                    # Collection might already exist
                    logger.debug(f"Collection {collection_name} already exists")
            
            logger.info("Qdrant initialized successfully")
            
        except Exception as e:
            logger.error(f"Failed to initialize Qdrant: {e}")
            raise
    
    def get_collection(self, name: str):
        """Get a collection by name"""
        if self.store_type == "chromadb":
            return self.client.get_collection(name)
        elif self.store_type == "qdrant":
            # Return collection name for Qdrant operations
            return name
        else:
            raise ValueError(f"Unknown vector store type: {self.store_type}")
    
    async def add_vectors(
        self,
        collection_name: str,
        vectors: List[List[float]],
        metadatas: List[Dict[str, Any]],
        ids: List[str]
    ):
        """Add vectors to a collection"""
        if self.store_type == "chromadb":
            collection = self.get_collection(collection_name)
            collection.add(
                embeddings=vectors,
                metadatas=metadatas,
                ids=ids
            )
        elif self.store_type == "qdrant":
            from qdrant_client.models import PointStruct
            
            points = [
                PointStruct(
                    id=idx,
                    vector=vector,
                    payload=metadata
                )
                for idx, (vector, metadata) in enumerate(zip(vectors, metadatas))
            ]
            
            self.client.upsert(
                collection_name=collection_name,
                points=points
            )
    
    async def search_similar(
        self,
        collection_name: str,
        query_vector: List[float],
        top_k: int = 5,
        filter_dict: Optional[Dict[str, Any]] = None
    ) -> List[Dict[str, Any]]:
        """Search for similar vectors"""
        if self.store_type == "chromadb":
            collection = self.get_collection(collection_name)
            results = collection.query(
                query_embeddings=[query_vector],
                n_results=top_k,
                where=filter_dict
            )
            
            # Format results
            formatted_results = []
            for i in range(len(results["ids"][0])):
                formatted_results.append({
                    "id": results["ids"][0][i],
                    "score": 1 - results["distances"][0][i],  # Convert distance to similarity
                    "metadata": results["metadatas"][0][i]
                })
            
            return formatted_results
            
        elif self.store_type == "qdrant":
            from qdrant_client.models import Filter, FieldCondition, MatchValue
            
            # Build filter if provided
            qdrant_filter = None
            if filter_dict:
                conditions = [
                    FieldCondition(
                        key=key,
                        match=MatchValue(value=value)
                    )
                    for key, value in filter_dict.items()
                ]
                qdrant_filter = Filter(must=conditions)
            
            results = self.client.search(
                collection_name=collection_name,
                query_vector=query_vector,
                limit=top_k,
                query_filter=qdrant_filter
            )
            
            # Format results
            formatted_results = [
                {
                    "id": str(hit.id),
                    "score": hit.score,
                    "metadata": hit.payload
                }
                for hit in results
            ]
            
            return formatted_results


async def initialize_vector_store():
    """Initialize the vector store"""
    global vector_store
    
    manager = VectorStoreManager()
    
    if settings.VECTOR_DB_TYPE == "chromadb":
        await manager.initialize_chromadb()
    elif settings.VECTOR_DB_TYPE == "qdrant":
        await manager.initialize_qdrant()
    else:
        raise ValueError(f"Unknown vector store type: {settings.VECTOR_DB_TYPE}")
    
    vector_store = manager
    logger.info(f"Vector store ({settings.VECTOR_DB_TYPE}) initialized")


def get_vector_store() -> VectorStoreManager:
    """Get the initialized vector store"""
    if vector_store is None:
        raise RuntimeError("Vector store not initialized")
    return vector_store