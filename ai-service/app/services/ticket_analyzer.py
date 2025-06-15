"""
Ticket Analysis Service

Provides comprehensive analysis of support tickets including:
- Entity extraction (systems, components, error codes)
- Sentiment analysis
- Key phrase extraction
- Tag suggestions
- Urgency scoring
- AI-generated insights
"""

import asyncio
import logging
import re
from typing import Dict, List, Any, Optional, Tuple
from datetime import datetime, timedelta
import spacy
from transformers import pipeline, AutoTokenizer, AutoModelForSequenceClassification
import numpy as np

from app.core.config import settings
from app.core.logging import get_logger

logger = get_logger(__name__)


class TicketAnalyzer:
    """
    Advanced ticket analysis service using NLP and ML techniques
    """
    
    def __init__(self):
        self.nlp = None
        self.sentiment_analyzer = None
        self.urgency_classifier = None
        self._initialized = False
        
        # System/component patterns for entity extraction
        self.system_patterns = [
            r'\b(?:server|database|db|sql|mysql|postgresql|oracle)\b',
            r'\b(?:email|exchange|outlook|smtp|imap)\b',
            r'\b(?:network|router|switch|firewall|vpn)\b',
            r'\b(?:active\s*directory|ad|ldap|authentication)\b',
            r'\b(?:backup|storage|san|nas)\b',
            r'\b(?:application|app|software|system)\b',
            r'\b(?:printer|printing|scanner)\b',
            r'\b(?:phone|telephony|voip|pbx)\b',
            r'\b(?:security|antivirus|malware|virus)\b',
            r'\b(?:website|web|http|https|ssl|tls)\b'
        ]
        
        # Error code patterns
        self.error_patterns = [
            r'\b(?:error|exception|fail|failure)\s*(?:code|#)?\s*:?\s*([A-Z0-9-_]+)\b',
            r'\b(0x[0-9A-Fa-f]+)\b',
            r'\b([A-Z]{2,5}-\d{3,5})\b',
            r'\b(HTTP\s*[1-5]\d{2})\b'
        ]
        
        # Urgency indicators
        self.urgency_keywords = {
            'critical': ['production down', 'system down', 'outage', 'critical', 'emergency', 'urgent'],
            'high': ['not working', 'broken', 'failed', 'error', 'cannot access', 'unable to'],
            'medium': ['slow', 'performance', 'issue', 'problem', 'help needed'],
            'low': ['question', 'how to', 'request', 'enhancement', 'feature']
        }
    
    async def initialize(self):
        """Initialize ML models and NLP components"""
        if self._initialized:
            return
            
        try:
            logger.info("Initializing ticket analyzer components...")
            
            # Load spaCy model for NLP
            try:
                self.nlp = spacy.load("en_core_web_sm")
            except OSError:
                logger.warning("spaCy English model not found. Install with: python -m spacy download en_core_web_sm")
                # Fallback to basic processing
                self.nlp = None
            
            # Load sentiment analysis model
            self.sentiment_analyzer = pipeline(
                "sentiment-analysis",
                model="cardiffnlp/twitter-roberta-base-sentiment-latest",
                return_all_scores=True
            )
            
            # Load urgency classification model (using a general text classifier)
            self.urgency_classifier = pipeline(
                "text-classification",
                model="microsoft/DialoGPT-medium",
                return_all_scores=True
            )
            
            self._initialized = True
            logger.info("Ticket analyzer initialized successfully")
            
        except Exception as e:
            logger.error(f"Failed to initialize ticket analyzer: {e}")
            # Initialize with basic functionality
            self._initialized = True
    
    async def analyze(
        self,
        title: str,
        description: str,
        historical_context: bool = True
    ) -> Dict[str, Any]:
        """
        Comprehensive ticket analysis
        
        Args:
            title: Ticket title
            description: Ticket description
            historical_context: Whether to include historical analysis
            
        Returns:
            Analysis results including entities, sentiment, insights, etc.
        """
        await self.initialize()
        
        try:
            logger.info(f"Analyzing ticket: {title[:50]}...")
            
            # Combine title and description for analysis
            full_text = f"{title}. {description}"
            
            # Run analysis tasks concurrently
            analysis_tasks = [
                self._extract_entities(full_text),
                self._analyze_sentiment(full_text),
                self._extract_key_phrases(full_text),
                self._suggest_tags(full_text),
                self._calculate_urgency_score(full_text),
                self._generate_insights(title, description)
            ]
            
            results = await asyncio.gather(*analysis_tasks, return_exceptions=True)
            
            # Process results
            entities = results[0] if not isinstance(results[0], Exception) else []
            sentiment = results[1] if not isinstance(results[1], Exception) else {"positive": 0.0, "negative": 0.0, "neutral": 1.0}
            key_phrases = results[2] if not isinstance(results[2], Exception) else []
            suggested_tags = results[3] if not isinstance(results[3], Exception) else []
            urgency_score = results[4] if not isinstance(results[4], Exception) else 0.5
            insights = results[5] if not isinstance(results[5], Exception) else []
            
            return {
                "entities": entities,
                "sentiment": sentiment,
                "key_phrases": key_phrases,
                "suggested_tags": suggested_tags,
                "urgency_score": urgency_score,
                "insights": insights,
                "analysis_timestamp": datetime.utcnow().isoformat(),
                "confidence_score": self._calculate_confidence(results)
            }
            
        except Exception as e:
            logger.error(f"Ticket analysis failed: {e}")
            # Return basic analysis
            return {
                "entities": [],
                "sentiment": {"positive": 0.0, "negative": 0.0, "neutral": 1.0},
                "key_phrases": self._extract_basic_phrases(full_text),
                "suggested_tags": ["general"],
                "urgency_score": 0.5,
                "insights": ["Analysis completed with limited functionality"],
                "analysis_timestamp": datetime.utcnow().isoformat(),
                "confidence_score": 0.3
            }
    
    async def _extract_entities(self, text: str) -> List[Dict[str, Any]]:
        """Extract entities from ticket text"""
        entities = []
        
        try:
            # Extract systems and components
            for pattern in self.system_patterns:
                matches = re.finditer(pattern, text, re.IGNORECASE)
                for match in matches:
                    entities.append({
                        "type": "system_component",
                        "value": match.group().lower(),
                        "start": match.start(),
                        "end": match.end(),
                        "confidence": 0.8
                    })
            
            # Extract error codes
            for pattern in self.error_patterns:
                matches = re.finditer(pattern, text, re.IGNORECASE)
                for match in matches:
                    error_code = match.group(1) if match.groups() else match.group()
                    entities.append({
                        "type": "error_code",
                        "value": error_code,
                        "start": match.start(),
                        "end": match.end(),
                        "confidence": 0.9
                    })
            
            # Use spaCy for named entity recognition if available
            if self.nlp:
                doc = self.nlp(text)
                for ent in doc.ents:
                    if ent.label_ in ["ORG", "PRODUCT", "GPE", "DATE", "TIME"]:
                        entities.append({
                            "type": ent.label_.lower(),
                            "value": ent.text,
                            "start": ent.start_char,
                            "end": ent.end_char,
                            "confidence": 0.7
                        })
            
            # Remove duplicates and sort by confidence
            unique_entities = []
            seen = set()
            for entity in sorted(entities, key=lambda x: x["confidence"], reverse=True):
                key = (entity["type"], entity["value"].lower())
                if key not in seen:
                    unique_entities.append(entity)
                    seen.add(key)
            
            return unique_entities[:20]  # Limit to top 20 entities
            
        except Exception as e:
            logger.error(f"Entity extraction failed: {e}")
            return []
    
    async def _analyze_sentiment(self, text: str) -> Dict[str, float]:
        """Analyze sentiment of ticket text"""
        try:
            if self.sentiment_analyzer:
                results = self.sentiment_analyzer(text[:512])  # Limit text length
                
                # Convert to standardized format
                sentiment_scores = {"positive": 0.0, "negative": 0.0, "neutral": 0.0}
                
                for result in results[0]:  # First (and only) input
                    label = result["label"].lower()
                    score = result["score"]
                    
                    if "positive" in label or "pos" in label:
                        sentiment_scores["positive"] = score
                    elif "negative" in label or "neg" in label:
                        sentiment_scores["negative"] = score
                    else:
                        sentiment_scores["neutral"] = score
                
                return sentiment_scores
            else:
                # Basic sentiment analysis using keyword matching
                positive_words = ["good", "great", "excellent", "working", "solved", "fixed"]
                negative_words = ["bad", "broken", "failed", "error", "problem", "issue", "down", "not working"]
                
                words = text.lower().split()
                positive_count = sum(1 for word in words if any(pw in word for pw in positive_words))
                negative_count = sum(1 for word in words if any(nw in word for nw in negative_words))
                
                total = max(positive_count + negative_count, 1)
                
                return {
                    "positive": positive_count / total,
                    "negative": negative_count / total,
                    "neutral": max(0, 1 - (positive_count + negative_count) / total)
                }
                
        except Exception as e:
            logger.error(f"Sentiment analysis failed: {e}")
            return {"positive": 0.0, "negative": 0.0, "neutral": 1.0}
    
    async def _extract_key_phrases(self, text: str) -> List[str]:
        """Extract key phrases from ticket text"""
        try:
            if self.nlp:
                doc = self.nlp(text)
                
                # Extract noun phrases
                noun_phrases = [chunk.text.strip() for chunk in doc.noun_chunks 
                              if len(chunk.text.strip()) > 3 and len(chunk.text.strip().split()) <= 4]
                
                # Extract verb phrases and important patterns
                key_phrases = []
                for sent in doc.sents:
                    # Look for action phrases
                    for token in sent:
                        if token.pos_ == "VERB" and token.dep_ == "ROOT":
                            phrase = " ".join([child.text for child in token.children if child.dep_ in ["dobj", "prep"]])
                            if phrase:
                                key_phrases.append(f"{token.text} {phrase}".strip())
                
                # Combine and deduplicate
                all_phrases = noun_phrases + key_phrases
                unique_phrases = list(dict.fromkeys([phrase.lower() for phrase in all_phrases if len(phrase) > 3]))
                
                return unique_phrases[:10]  # Top 10 phrases
            else:
                return self._extract_basic_phrases(text)
                
        except Exception as e:
            logger.error(f"Key phrase extraction failed: {e}")
            return self._extract_basic_phrases(text)
    
    def _extract_basic_phrases(self, text: str) -> List[str]:
        """Basic phrase extraction using patterns"""
        # Common IT phrases
        phrases = []
        
        # Find quoted strings
        quoted = re.findall(r'"([^"]+)"', text)
        phrases.extend([q for q in quoted if len(q) > 3])
        
        # Find technical terms
        tech_patterns = [
            r'\b\w+\s+(?:server|database|application|system|service|network)\b',
            r'\b(?:error|exception|failure)\s+\w+\b',
            r'\b\w+\s+(?:not|cannot|unable)\s+\w+\b'
        ]
        
        for pattern in tech_patterns:
            matches = re.findall(pattern, text, re.IGNORECASE)
            phrases.extend([m.strip() for m in matches if len(m.strip()) > 3])
        
        return list(dict.fromkeys(phrases))[:10]
    
    async def _suggest_tags(self, text: str) -> List[str]:
        """Suggest relevant tags for the ticket"""
        tags = []
        text_lower = text.lower()
        
        # System-based tags
        system_tags = {
            'email': ['email', 'outlook', 'exchange', 'smtp', 'mail'],
            'network': ['network', 'connection', 'internet', 'wifi', 'lan', 'wan'],
            'database': ['database', 'sql', 'mysql', 'postgresql', 'oracle', 'data'],
            'security': ['security', 'virus', 'malware', 'antivirus', 'firewall', 'breach'],
            'hardware': ['hardware', 'disk', 'memory', 'cpu', 'printer', 'monitor'],
            'software': ['software', 'application', 'program', 'install', 'update'],
            'authentication': ['login', 'password', 'authentication', 'access', 'permission'],
            'performance': ['slow', 'performance', 'speed', 'lag', 'timeout'],
            'backup': ['backup', 'restore', 'recovery', 'archive']
        }
        
        for tag, keywords in system_tags.items():
            if any(keyword in text_lower for keyword in keywords):
                tags.append(tag)
        
        # Priority-based tags
        if any(word in text_lower for word in self.urgency_keywords['critical']):
            tags.append('critical')
        elif any(word in text_lower for word in self.urgency_keywords['high']):
            tags.append('high-priority')
        
        # Issue type tags
        if any(word in text_lower for word in ['question', 'how to', 'help']):
            tags.append('question')
        elif any(word in text_lower for word in ['request', 'need', 'want']):
            tags.append('request')
        else:
            tags.append('incident')
        
        return list(dict.fromkeys(tags))[:8]  # Max 8 tags
    
    async def _calculate_urgency_score(self, text: str) -> float:
        """Calculate urgency score based on text analysis"""
        try:
            text_lower = text.lower()
            score = 0.0
            
            # Check urgency keywords
            for level, keywords in self.urgency_keywords.items():
                weight = {'critical': 1.0, 'high': 0.7, 'medium': 0.4, 'low': 0.1}[level]
                matches = sum(1 for keyword in keywords if keyword in text_lower)
                score += matches * weight
            
            # Check for time indicators
            time_urgent = ['immediately', 'asap', 'urgent', 'emergency', 'now', 'today']
            if any(word in text_lower for word in time_urgent):
                score += 0.3
            
            # Check for business impact
            business_impact = ['production', 'customer', 'revenue', 'business', 'critical system']
            if any(phrase in text_lower for phrase in business_impact):
                score += 0.4
            
            # Check for user count
            many_users = ['all users', 'everyone', 'entire team', 'multiple users', 'company-wide']
            if any(phrase in text_lower for phrase in many_users):
                score += 0.3
            
            # Normalize score to 0-1 range
            return min(1.0, score / 2.0)
            
        except Exception as e:
            logger.error(f"Urgency calculation failed: {e}")
            return 0.5  # Default medium urgency
    
    async def _generate_insights(self, title: str, description: str) -> List[str]:
        """Generate AI insights about the ticket"""
        insights = []
        
        try:
            text = f"{title}. {description}".lower()
            
            # Pattern-based insights
            if 'password' in text and 'reset' in text:
                insights.append("This appears to be a password reset request - verify user identity before proceeding")
            
            if 'slow' in text or 'performance' in text:
                insights.append("Performance issue detected - consider checking system resources and recent changes")
            
            if 'multiple users' in text or 'everyone' in text:
                insights.append("Issue affects multiple users - potential system-wide problem requiring immediate attention")
            
            if any(word in text for word in ['production', 'critical', 'down']):
                insights.append("Critical system issue - escalate to senior support team and notify management")
            
            if 'new' in text and ('employee' in text or 'user' in text):
                insights.append("New user setup request - ensure all standard access permissions and tools are provided")
            
            if 'cannot access' in text or 'permission denied' in text:
                insights.append("Access permission issue - check user roles and group memberships")
            
            if len(description.split()) < 10:
                insights.append("Limited description provided - consider requesting more details from the user")
            
            # Technical insights
            error_codes = re.findall(r'\b(?:error|code)\s*:?\s*([A-Z0-9-_]+)\b', text, re.IGNORECASE)
            if error_codes:
                insights.append(f"Error code detected: {error_codes[0]} - check documentation for specific resolution steps")
            
            # Default insight if none found
            if not insights:
                insights.append("Standard support ticket - follow established troubleshooting procedures")
            
            return insights[:5]  # Max 5 insights
            
        except Exception as e:
            logger.error(f"Insight generation failed: {e}")
            return ["Ticket analysis completed - proceed with standard support process"]
    
    def _calculate_confidence(self, results: List[Any]) -> float:
        """Calculate overall confidence score for the analysis"""
        # Count successful vs failed operations
        successful = sum(1 for result in results if not isinstance(result, Exception))
        total = len(results)
        
        base_confidence = successful / total
        
        # Adjust based on model availability
        if self.nlp and self.sentiment_analyzer:
            return min(1.0, base_confidence + 0.2)  # Boost for full capabilities
        else:
            return max(0.3, base_confidence - 0.2)  # Reduce for limited capabilities