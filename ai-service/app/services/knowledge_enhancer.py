"""
Knowledge Enhancement Service

Enhances knowledge base articles using AI by:
- Analyzing and improving existing articles
- Generating new articles from ticket resolutions
- Adding tags, categories, and metadata
- Identifying knowledge gaps
- Suggesting content improvements
- Maintaining article quality and relevance
"""

import asyncio
import logging
from typing import Dict, List, Any, Optional, Tuple, Set
from datetime import datetime, timedelta
import re
from collections import defaultdict, Counter
import json
from dataclasses import dataclass
from enum import Enum
import spacy
from sentence_transformers import SentenceTransformer
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import numpy as np

from app.core.config import settings
from app.core.logging import get_logger

logger = get_logger(__name__)


class ContentType(str, Enum):
    """Types of knowledge content"""
    ARTICLE = "article"
    FAQ = "faq"
    TUTORIAL = "tutorial"
    TROUBLESHOOTING = "troubleshooting"
    PROCEDURE = "procedure"
    REFERENCE = "reference"


class QualityMetric(str, Enum):
    """Quality metrics for knowledge articles"""
    CLARITY = "clarity"
    COMPLETENESS = "completeness"
    ACCURACY = "accuracy"
    RELEVANCE = "relevance"
    USEFULNESS = "usefulness"
    STRUCTURE = "structure"


@dataclass
class KnowledgeArticle:
    """Knowledge article data structure"""
    id: Optional[str] = None
    title: str = ""
    content: str = ""
    content_type: ContentType = ContentType.ARTICLE
    category: str = ""
    tags: List[str] = None
    author: str = ""
    created_at: datetime = None
    updated_at: datetime = None
    view_count: int = 0
    helpfulness_score: float = 0.0
    quality_scores: Dict[str, float] = None
    
    def __post_init__(self):
        if self.tags is None:
            self.tags = []
        if self.quality_scores is None:
            self.quality_scores = {}
        if self.created_at is None:
            self.created_at = datetime.utcnow()


@dataclass
class EnhancementSuggestion:
    """Article enhancement suggestion"""
    suggestion_type: str
    description: str
    confidence: float
    impact: str  # high, medium, low
    suggested_changes: Dict[str, Any]
    reasoning: str


class KnowledgeEnhancer:
    """
    AI-powered knowledge base enhancement service
    """
    
    def __init__(self):
        self.nlp = None
        self.embedding_model = None
        self.tfidf_vectorizer = None
        self.article_embeddings = {}
        self.knowledge_graph = defaultdict(list)
        self._initialized = False
        
        # Content quality indicators
        self.quality_indicators = {
            'clarity': {
                'positive': ['clear', 'simple', 'easy', 'understand', 'straightforward'],
                'negative': ['confusing', 'unclear', 'complicated', 'difficult', 'vague']
            },
            'completeness': {
                'required_sections': ['problem', 'solution', 'steps', 'examples'],
                'min_word_count': 100,
                'step_indicators': ['step', 'first', 'next', 'then', 'finally']
            },
            'structure': {
                'headers': ['#', '##', '###'],
                'lists': ['*', '-', '1.', '2.'],
                'code_blocks': ['```', '`']
            }
        }
        
        # Common knowledge gaps patterns
        self.gap_patterns = [
            r'how to.*?\?',
            r'what is.*?\?',
            r'why does.*?\?',
            r'when should.*?\?',
            r'where can.*?\?',
            r'cannot.*?',
            r'unable to.*?',
            r'error.*?',
            r'failed.*?',
            r'not working.*?'
        ]
        
        # Content improvement templates
        self.improvement_templates = {
            'add_examples': "Consider adding practical examples to illustrate the concept",
            'add_screenshots': "Visual aids like screenshots would enhance understanding",
            'add_prerequisites': "List prerequisites or required knowledge before proceeding",
            'add_troubleshooting': "Include common troubleshooting steps for potential issues",
            'improve_structure': "Improve document structure with clear headers and sections",
            'add_references': "Add references to related articles or external resources",
            'update_content': "Content may be outdated - consider updating with current information"
        }
    
    async def initialize(self):
        """Initialize the knowledge enhancer"""
        if self._initialized:
            return
            
        try:
            logger.info("Initializing knowledge enhancer...")
            
            # Load NLP models
            try:
                self.nlp = spacy.load("en_core_web_sm")
            except OSError:
                logger.warning("spaCy model not found. Some features will be limited.")
                self.nlp = None
            
            # Load embedding model for semantic analysis
            self.embedding_model = SentenceTransformer(settings.EMBEDDING_MODEL)
            
            # Initialize TF-IDF vectorizer for content analysis
            self.tfidf_vectorizer = TfidfVectorizer(
                max_features=5000,
                stop_words='english',
                ngram_range=(1, 2)
            )
            
            # Load existing knowledge base
            await self._load_knowledge_base()
            
            self._initialized = True
            logger.info("Knowledge enhancer initialized successfully")
            
        except Exception as e:
            logger.error(f"Failed to initialize knowledge enhancer: {e}")
            self._initialized = True  # Continue with limited functionality
    
    async def enhance_article(
        self,
        article: KnowledgeArticle,
        enhancement_types: Optional[List[str]] = None
    ) -> Dict[str, Any]:
        """
        Enhance a knowledge article with AI-powered improvements
        
        Args:
            article: The article to enhance
            enhancement_types: Specific enhancement types to apply
            
        Returns:
            Enhancement results and suggestions
        """
        await self.initialize()
        
        try:
            logger.info(f"Enhancing article: {article.title[:50]}...")
            
            # Default enhancement types
            if enhancement_types is None:
                enhancement_types = [
                    'quality_analysis',
                    'content_suggestions',
                    'tag_generation',
                    'category_suggestion',
                    'structure_improvement',
                    'gap_identification'
                ]
            
            enhancement_results = {}
            
            # Run enhancement tasks
            for enhancement_type in enhancement_types:
                try:
                    result = await self._run_enhancement(article, enhancement_type)
                    enhancement_results[enhancement_type] = result
                except Exception as e:
                    logger.error(f"Enhancement {enhancement_type} failed: {e}")
                    enhancement_results[enhancement_type] = {"error": str(e)}
            
            # Generate overall enhancement summary
            enhancement_results['summary'] = self._generate_enhancement_summary(enhancement_results)
            enhancement_results['enhanced_at'] = datetime.utcnow().isoformat()
            
            return enhancement_results
            
        except Exception as e:
            logger.error(f"Article enhancement failed: {e}")
            return {"error": str(e)}
    
    async def _run_enhancement(self, article: KnowledgeArticle, enhancement_type: str) -> Dict[str, Any]:
        """Run a specific enhancement type"""
        
        if enhancement_type == 'quality_analysis':
            return await self._analyze_article_quality(article)
        elif enhancement_type == 'content_suggestions':
            return await self._generate_content_suggestions(article)
        elif enhancement_type == 'tag_generation':
            return await self._generate_tags(article)
        elif enhancement_type == 'category_suggestion':
            return await self._suggest_category(article)
        elif enhancement_type == 'structure_improvement':
            return await self._analyze_structure(article)
        elif enhancement_type == 'gap_identification':
            return await self._identify_gaps(article)
        elif enhancement_type == 'similarity_analysis':
            return await self._analyze_similarity(article)
        else:
            return {"error": f"Unknown enhancement type: {enhancement_type}"}
    
    async def _analyze_article_quality(self, article: KnowledgeArticle) -> Dict[str, Any]:
        """Analyze the quality of an article"""
        try:
            quality_scores = {}
            suggestions = []
            
            # Analyze clarity
            clarity_score = self._analyze_clarity(article.content)
            quality_scores['clarity'] = clarity_score
            
            if clarity_score < 0.7:
                suggestions.append("Consider simplifying language and improving clarity")
            
            # Analyze completeness
            completeness_score = self._analyze_completeness(article.content)
            quality_scores['completeness'] = completeness_score
            
            if completeness_score < 0.7:
                suggestions.append("Article may be incomplete - consider adding more details")
            
            # Analyze structure
            structure_score = self._analyze_structure_quality(article.content)
            quality_scores['structure'] = structure_score
            
            if structure_score < 0.7:
                suggestions.append("Improve document structure with headers and formatting")
            
            # Analyze length and depth
            word_count = len(article.content.split())
            if word_count < 100:
                suggestions.append("Article is quite short - consider adding more detail")
            elif word_count > 2000:
                suggestions.append("Article is quite long - consider breaking into sections")
            
            # Overall quality score
            overall_score = np.mean(list(quality_scores.values()))
            
            return {
                'quality_scores': quality_scores,
                'overall_score': overall_score,
                'suggestions': suggestions,
                'word_count': word_count
            }
            
        except Exception as e:
            logger.error(f"Quality analysis failed: {e}")
            return {"error": str(e)}
    
    async def _generate_content_suggestions(self, article: KnowledgeArticle) -> Dict[str, Any]:
        """Generate content improvement suggestions"""
        try:
            suggestions = []
            
            content_lower = article.content.lower()
            
            # Check for common missing elements
            if 'example' not in content_lower and 'for example' not in content_lower:
                suggestions.append(EnhancementSuggestion(
                    suggestion_type="add_examples",
                    description="Add practical examples to illustrate concepts",
                    confidence=0.8,
                    impact="high",
                    suggested_changes={"section": "examples", "content": "practical examples"},
                    reasoning="Examples help users understand and apply the information"
                ))
            
            if 'screenshot' not in content_lower and 'image' not in content_lower:
                suggestions.append(EnhancementSuggestion(
                    suggestion_type="add_visuals",
                    description="Consider adding screenshots or diagrams",
                    confidence=0.7,
                    impact="medium",
                    suggested_changes={"section": "visuals", "content": "screenshots or diagrams"},
                    reasoning="Visual aids enhance understanding for complex procedures"
                ))
            
            # Check for step-by-step content
            if not self._has_step_by_step_content(article.content):
                suggestions.append(EnhancementSuggestion(
                    suggestion_type="add_steps",
                    description="Structure content as step-by-step instructions",
                    confidence=0.8,
                    impact="high",
                    suggested_changes={"format": "step_by_step"},
                    reasoning="Step-by-step format is easier to follow for procedures"
                ))
            
            # Check for troubleshooting section
            if 'troubleshooting' not in content_lower and 'problem' not in content_lower:
                suggestions.append(EnhancementSuggestion(
                    suggestion_type="add_troubleshooting",
                    description="Add troubleshooting section for common issues",
                    confidence=0.6,
                    impact="medium",
                    suggested_changes={"section": "troubleshooting"},
                    reasoning="Troubleshooting helps users resolve common problems"
                ))
            
            return {
                'suggestions': [s.__dict__ for s in suggestions],
                'total_suggestions': len(suggestions)
            }
            
        except Exception as e:
            logger.error(f"Content suggestion generation failed: {e}")
            return {"error": str(e)}
    
    async def _generate_tags(self, article: KnowledgeArticle) -> Dict[str, Any]:
        """Generate relevant tags for an article"""
        try:
            suggested_tags = []
            
            # Combine title and content for analysis
            full_text = f"{article.title} {article.content}".lower()
            
            # Extract technical terms and keywords
            if self.nlp:
                doc = self.nlp(full_text)
                
                # Extract named entities
                for ent in doc.ents:
                    if ent.label_ in ["ORG", "PRODUCT", "TECHNOLOGY"]:
                        suggested_tags.append(ent.text.lower())
                
                # Extract noun phrases
                for chunk in doc.noun_chunks:
                    if len(chunk.text.split()) <= 3 and len(chunk.text) > 3:
                        suggested_tags.append(chunk.text.lower())
            
            # Pattern-based tag extraction
            tech_patterns = {
                'email': ['email', 'outlook', 'exchange', 'smtp', 'imap'],
                'network': ['network', 'router', 'switch', 'firewall', 'vpn'],
                'security': ['security', 'password', 'authentication', 'encryption'],
                'software': ['software', 'application', 'program', 'install'],
                'hardware': ['hardware', 'server', 'computer', 'device'],
                'database': ['database', 'sql', 'mysql', 'postgresql'],
                'troubleshooting': ['error', 'problem', 'fix', 'solve', 'issue']
            }
            
            for tag, keywords in tech_patterns.items():
                if any(keyword in full_text for keyword in keywords):
                    suggested_tags.append(tag)
            
            # Remove duplicates and limit
            unique_tags = list(dict.fromkeys([tag for tag in suggested_tags if len(tag) > 2]))
            
            return {
                'suggested_tags': unique_tags[:10],  # Limit to 10 tags
                'existing_tags': article.tags,
                'tag_confidence': 0.7
            }
            
        except Exception as e:
            logger.error(f"Tag generation failed: {e}")
            return {"error": str(e)}
    
    async def _suggest_category(self, article: KnowledgeArticle) -> Dict[str, Any]:
        """Suggest appropriate category for an article"""
        try:
            # Predefined categories
            categories = {
                'Email & Communication': ['email', 'outlook', 'communication', 'smtp', 'exchange'],
                'Network & Connectivity': ['network', 'internet', 'connectivity', 'wifi', 'vpn'],
                'Security & Access': ['security', 'password', 'authentication', 'access', 'permissions'],
                'Software & Applications': ['software', 'application', 'program', 'install', 'update'],
                'Hardware & Devices': ['hardware', 'printer', 'computer', 'device', 'equipment'],
                'System Administration': ['server', 'admin', 'configuration', 'system', 'management'],
                'Troubleshooting': ['error', 'problem', 'fix', 'troubleshoot', 'issue'],
                'How-to Guides': ['how to', 'guide', 'tutorial', 'instructions', 'steps']
            }
            
            content_lower = f"{article.title} {article.content}".lower()
            
            # Calculate category scores
            category_scores = {}
            for category, keywords in categories.items():
                score = sum(1 for keyword in keywords if keyword in content_lower)
                if score > 0:
                    category_scores[category] = score / len(keywords)
            
            # Get top suggestion
            if category_scores:
                suggested_category = max(category_scores, key=category_scores.get)
                confidence = category_scores[suggested_category]
            else:
                suggested_category = "General"
                confidence = 0.3
            
            return {
                'suggested_category': suggested_category,
                'confidence': confidence,
                'all_scores': category_scores,
                'current_category': article.category
            }
            
        except Exception as e:
            logger.error(f"Category suggestion failed: {e}")
            return {"error": str(e)}
    
    async def _analyze_structure(self, article: KnowledgeArticle) -> Dict[str, Any]:
        """Analyze and suggest structural improvements"""
        try:
            content = article.content
            structure_analysis = {}
            suggestions = []
            
            # Check for headers
            headers = re.findall(r'^#{1,6}\s+(.+)$', content, re.MULTILINE)
            structure_analysis['headers'] = len(headers)
            
            if len(headers) == 0:
                suggestions.append("Add section headers to improve readability")
            
            # Check for lists
            lists = re.findall(r'^\s*[-*+]\s+(.+)$', content, re.MULTILINE)
            numbered_lists = re.findall(r'^\s*\d+\.\s+(.+)$', content, re.MULTILINE)
            structure_analysis['lists'] = len(lists) + len(numbered_lists)
            
            # Check for code blocks
            code_blocks = re.findall(r'```[\s\S]*?```', content)
            inline_code = re.findall(r'`[^`]+`', content)
            structure_analysis['code_blocks'] = len(code_blocks)
            structure_analysis['inline_code'] = len(inline_code)
            
            # Check for paragraphs
            paragraphs = [p.strip() for p in content.split('\n\n') if p.strip()]
            structure_analysis['paragraphs'] = len(paragraphs)
            
            # Analyze paragraph length
            paragraph_lengths = [len(p.split()) for p in paragraphs]
            if paragraph_lengths:
                avg_paragraph_length = sum(paragraph_lengths) / len(paragraph_lengths)
                structure_analysis['avg_paragraph_length'] = avg_paragraph_length
                
                if avg_paragraph_length > 100:
                    suggestions.append("Consider breaking long paragraphs into shorter ones")
            
            # Check for table of contents
            has_toc = any(phrase in content.lower() for phrase in ['table of contents', 'contents:', 'toc'])
            structure_analysis['has_toc'] = has_toc
            
            if len(headers) > 5 and not has_toc:
                suggestions.append("Consider adding a table of contents for long articles")
            
            return {
                'structure_analysis': structure_analysis,
                'suggestions': suggestions,
                'structure_score': self._calculate_structure_score(structure_analysis)
            }
            
        except Exception as e:
            logger.error(f"Structure analysis failed: {e}")
            return {"error": str(e)}
    
    async def _identify_gaps(self, article: KnowledgeArticle) -> Dict[str, Any]:
        """Identify knowledge gaps in the article"""
        try:
            gaps = []
            content_lower = article.content.lower()
            
            # Check for common gap patterns
            for pattern in self.gap_patterns:
                matches = re.findall(pattern, content_lower, re.IGNORECASE)
                for match in matches:
                    gaps.append({
                        'type': 'unanswered_question',
                        'content': match,
                        'suggestion': 'Consider addressing this question in the article'
                    })
            
            # Check for incomplete procedures
            if 'step' in content_lower:
                # Look for incomplete step sequences
                steps = re.findall(r'step\s+(\d+)', content_lower)
                if steps:
                    step_numbers = [int(s) for s in steps]
                    missing_steps = []
                    for i in range(1, max(step_numbers) + 1):
                        if i not in step_numbers:
                            missing_steps.append(i)
                    
                    if missing_steps:
                        gaps.append({
                            'type': 'missing_steps',
                            'content': f"Missing steps: {missing_steps}",
                            'suggestion': 'Complete the step-by-step procedure'
                        })
            
            # Check for missing common sections
            expected_sections = {
                'prerequisites': ['prerequisite', 'requirement', 'before you begin'],
                'examples': ['example', 'for instance', 'such as'],
                'troubleshooting': ['troubleshoot', 'common problems', 'issues'],
                'references': ['reference', 'see also', 'related']
            }
            
            for section_name, keywords in expected_sections.items():
                if not any(keyword in content_lower for keyword in keywords):
                    gaps.append({
                        'type': 'missing_section',
                        'content': section_name,
                        'suggestion': f'Consider adding a {section_name} section'
                    })
            
            return {
                'identified_gaps': gaps,
                'gap_count': len(gaps),
                'completeness_score': max(0, 1 - (len(gaps) * 0.1))
            }
            
        except Exception as e:
            logger.error(f"Gap identification failed: {e}")
            return {"error": str(e)}
    
    async def _analyze_similarity(self, article: KnowledgeArticle) -> Dict[str, Any]:
        """Analyze similarity with other articles"""
        try:
            if not self.embedding_model:
                return {"error": "Embedding model not available"}
            
            # Get article embedding
            article_text = f"{article.title} {article.content}"
            article_embedding = self.embedding_model.encode([article_text])
            
            similar_articles = []
            
            # Compare with existing articles
            for article_id, embedding_data in self.article_embeddings.items():
                if article_id == article.id:
                    continue
                
                similarity = cosine_similarity(article_embedding, [embedding_data['embedding']])[0][0]
                
                if similarity > 0.7:  # High similarity threshold
                    similar_articles.append({
                        'article_id': article_id,
                        'title': embedding_data['title'],
                        'similarity': similarity,
                        'suggestion': 'Consider merging or cross-referencing these articles'
                    })
            
            return {
                'similar_articles': similar_articles,
                'similarity_count': len(similar_articles),
                'uniqueness_score': 1 - max([a['similarity'] for a in similar_articles], default=0)
            }
            
        except Exception as e:
            logger.error(f"Similarity analysis failed: {e}")
            return {"error": str(e)}
    
    def _analyze_clarity(self, content: str) -> float:
        """Analyze content clarity"""
        try:
            words = content.lower().split()
            total_words = len(words)
            
            if total_words == 0:
                return 0.0
            
            # Count positive and negative clarity indicators
            positive_count = sum(1 for word in words if word in self.quality_indicators['clarity']['positive'])
            negative_count = sum(1 for word in words if word in self.quality_indicators['clarity']['negative'])
            
            # Calculate average sentence length
            sentences = re.split(r'[.!?]+', content)
            avg_sentence_length = sum(len(s.split()) for s in sentences) / max(len(sentences), 1)
            
            # Penalize very long sentences
            length_penalty = max(0, 1 - (avg_sentence_length - 20) / 50)
            
            # Calculate clarity score
            clarity_score = (positive_count - negative_count) / total_words + length_penalty
            
            return max(0, min(1, clarity_score))
            
        except Exception as e:
            logger.error(f"Clarity analysis failed: {e}")
            return 0.5
    
    def _analyze_completeness(self, content: str) -> float:
        """Analyze content completeness"""
        try:
            content_lower = content.lower()
            
            # Check for required sections
            required_sections = self.quality_indicators['completeness']['required_sections']
            section_score = sum(1 for section in required_sections if section in content_lower) / len(required_sections)
            
            # Check word count
            word_count = len(content.split())
            min_words = self.quality_indicators['completeness']['min_word_count']
            word_score = min(1, word_count / min_words)
            
            # Check for step indicators
            step_indicators = self.quality_indicators['completeness']['step_indicators']
            has_steps = any(indicator in content_lower for indicator in step_indicators)
            step_score = 1.0 if has_steps else 0.5
            
            # Combine scores
            completeness_score = (section_score + word_score + step_score) / 3
            
            return max(0, min(1, completeness_score))
            
        except Exception as e:
            logger.error(f"Completeness analysis failed: {e}")
            return 0.5
    
    def _analyze_structure_quality(self, content: str) -> float:
        """Analyze structural quality"""
        try:
            structure_score = 0
            
            # Check for headers
            headers = re.findall(r'^#{1,6}\s+(.+)$', content, re.MULTILINE)
            if headers:
                structure_score += 0.3
            
            # Check for lists
            lists = re.findall(r'^\s*[-*+]\s+(.+)$', content, re.MULTILINE)
            numbered_lists = re.findall(r'^\s*\d+\.\s+(.+)$', content, re.MULTILINE)
            if lists or numbered_lists:
                structure_score += 0.3
            
            # Check for code blocks
            code_blocks = re.findall(r'```[\s\S]*?```', content)
            if code_blocks:
                structure_score += 0.2
            
            # Check for proper paragraph breaks
            paragraphs = [p.strip() for p in content.split('\n\n') if p.strip()]
            if len(paragraphs) > 1:
                structure_score += 0.2
            
            return min(1, structure_score)
            
        except Exception as e:
            logger.error(f"Structure quality analysis failed: {e}")
            return 0.5
    
    def _has_step_by_step_content(self, content: str) -> bool:
        """Check if content has step-by-step format"""
        step_patterns = [
            r'step\s+\d+',
            r'\d+\.\s+',
            r'first.*?second.*?third',
            r'next.*?then.*?finally'
        ]
        
        return any(re.search(pattern, content.lower()) for pattern in step_patterns)
    
    def _calculate_structure_score(self, analysis: Dict[str, Any]) -> float:
        """Calculate overall structure score"""
        score = 0
        
        # Score based on different structural elements
        if analysis.get('headers', 0) > 0:
            score += 0.3
        if analysis.get('lists', 0) > 0:
            score += 0.2
        if analysis.get('code_blocks', 0) > 0:
            score += 0.2
        if analysis.get('paragraphs', 0) > 2:
            score += 0.2
        if analysis.get('avg_paragraph_length', 0) < 80:
            score += 0.1
        
        return min(1, score)
    
    def _generate_enhancement_summary(self, results: Dict[str, Any]) -> Dict[str, Any]:
        """Generate overall enhancement summary"""
        try:
            summary = {
                'total_enhancements': 0,
                'high_impact_suggestions': 0,
                'overall_quality': 0.0,
                'key_recommendations': []
            }
            
            # Count suggestions and calculate quality
            quality_scores = []
            for enhancement_type, result in results.items():
                if isinstance(result, dict) and 'error' not in result:
                    if 'suggestions' in result:
                        suggestions = result['suggestions']
                        if isinstance(suggestions, list):
                            summary['total_enhancements'] += len(suggestions)
                            # Count high impact suggestions
                            for suggestion in suggestions:
                                if isinstance(suggestion, dict) and suggestion.get('impact') == 'high':
                                    summary['high_impact_suggestions'] += 1
                    
                    # Collect quality scores
                    if 'overall_score' in result:
                        quality_scores.append(result['overall_score'])
                    elif 'structure_score' in result:
                        quality_scores.append(result['structure_score'])
                    elif 'completeness_score' in result:
                        quality_scores.append(result['completeness_score'])
            
            # Calculate overall quality
            if quality_scores:
                summary['overall_quality'] = sum(quality_scores) / len(quality_scores)
            
            # Generate key recommendations
            if summary['high_impact_suggestions'] > 0:
                summary['key_recommendations'].append("Focus on high-impact improvements first")
            if summary['overall_quality'] < 0.7:
                summary['key_recommendations'].append("Article needs significant improvement")
            if summary['total_enhancements'] > 10:
                summary['key_recommendations'].append("Consider breaking article into multiple pieces")
            
            return summary
            
        except Exception as e:
            logger.error(f"Enhancement summary generation failed: {e}")
            return {"error": str(e)}
    
    async def _load_knowledge_base(self):
        """Load existing knowledge base for analysis"""
        try:
            # In production, this would load from database
            # For now, create sample data
            sample_articles = {
                '1': {
                    'title': 'Email Configuration Guide',
                    'content': 'Step by step guide for configuring email clients...',
                    'embedding': None
                },
                '2': {
                    'title': 'Network Troubleshooting',
                    'content': 'Common network issues and their solutions...',
                    'embedding': None
                }
            }
            
            # Generate embeddings if model is available
            if self.embedding_model:
                for article_id, article_data in sample_articles.items():
                    text = f"{article_data['title']} {article_data['content']}"
                    embedding = self.embedding_model.encode([text])
                    article_data['embedding'] = embedding[0]
                    self.article_embeddings[article_id] = article_data
            
            logger.info(f"Loaded {len(self.article_embeddings)} articles for analysis")
            
        except Exception as e:
            logger.error(f"Failed to load knowledge base: {e}")
    
    async def generate_article_from_ticket(
        self,
        ticket_title: str,
        ticket_description: str,
        resolution_steps: List[str],
        category: Optional[str] = None
    ) -> KnowledgeArticle:
        """Generate a knowledge article from a resolved ticket"""
        try:
            logger.info(f"Generating article from ticket: {ticket_title}")
            
            # Create article structure
            article_title = self._generate_article_title(ticket_title)
            article_content = self._generate_article_content(
                ticket_description, resolution_steps, ticket_title
            )
            
            # Generate tags and category
            article = KnowledgeArticle(
                title=article_title,
                content=article_content,
                content_type=ContentType.TROUBLESHOOTING,
                category=category or "General"
            )
            
            # Enhance the generated article
            enhancement_results = await self.enhance_article(article, ['tag_generation', 'category_suggestion'])
            
            # Apply enhancements
            if 'tag_generation' in enhancement_results:
                article.tags = enhancement_results['tag_generation'].get('suggested_tags', [])
            
            if 'category_suggestion' in enhancement_results:
                article.category = enhancement_results['category_suggestion'].get('suggested_category', 'General')
            
            return article
            
        except Exception as e:
            logger.error(f"Article generation from ticket failed: {e}")
            return KnowledgeArticle(title="Error", content="Failed to generate article")
    
    def _generate_article_title(self, ticket_title: str) -> str:
        """Generate a knowledge article title from ticket title"""
        # Clean up the title
        title = ticket_title.strip()
        
        # Convert to how-to format if it's a problem statement
        if any(word in title.lower() for word in ['cannot', 'unable', 'not working', 'error', 'problem']):
            title = f"How to resolve: {title}"
        elif not title.lower().startswith(('how to', 'guide to', 'troubleshooting')):
            title = f"Guide: {title}"
        
        return title
    
    def _generate_article_content(
        self, 
        description: str, 
        resolution_steps: List[str], 
        title: str
    ) -> str:
        """Generate article content from ticket information"""
        content_parts = []
        
        # Add overview
        content_parts.append("## Overview")
        content_parts.append(f"This guide addresses the following issue: {description}")
        content_parts.append("")
        
        # Add resolution steps
        content_parts.append("## Resolution Steps")
        content_parts.append("")
        
        for i, step in enumerate(resolution_steps, 1):
            content_parts.append(f"{i}. {step}")
        
        content_parts.append("")
        
        # Add additional sections
        content_parts.append("## Additional Notes")
        content_parts.append("- Ensure all prerequisites are met before starting")
        content_parts.append("- Contact support if issues persist")
        content_parts.append("")
        
        content_parts.append("## Related Articles")
        content_parts.append("*This section will be populated with related content*")
        
        return "\n".join(content_parts)