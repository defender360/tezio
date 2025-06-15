"""
Resolution Suggestion Service

Provides intelligent resolution suggestions for support tickets by:
- Searching knowledge base for similar issues
- Analyzing historical ticket resolutions
- Generating step-by-step solutions
- Ranking suggestions by relevance and success rate
- Learning from resolution outcomes
"""

import asyncio
import logging
from typing import Dict, List, Any, Optional, Tuple
from datetime import datetime, timedelta
import json
from collections import defaultdict
import numpy as np
from sentence_transformers import SentenceTransformer
from sklearn.metrics.pairwise import cosine_similarity
import aiohttp

from app.core.config import settings
from app.core.logging import get_logger

logger = get_logger(__name__)


class ResolutionSuggester:
    """
    AI-powered resolution suggestion service
    """
    
    def __init__(self):
        self.embedding_model = None
        self.knowledge_embeddings = {}
        self.resolution_patterns = defaultdict(list)
        self._initialized = False
        
        # Common resolution templates
        self.resolution_templates = {
            'password_reset': [
                "Verify user identity using security questions or manager approval",
                "Reset password in Active Directory or user management system",
                "Send temporary password via secure channel (not email)",
                "Instruct user to change password on first login",
                "Update password policy compliance if needed"
            ],
            'network_connectivity': [
                "Check physical network connections (cables, switches)",
                "Verify IP configuration and DNS settings",
                "Test connectivity to gateway and external sites",
                "Check for IP conflicts or DHCP issues",
                "Review firewall rules and network policies",
                "Restart network services if necessary"
            ],
            'software_installation': [
                "Verify system requirements and compatibility",
                "Check for existing installations or conflicts",
                "Run installer with administrator privileges",
                "Configure application settings and permissions",
                "Test application functionality",
                "Document installation for future reference"
            ],
            'email_issues': [
                "Check email server status and connectivity",
                "Verify email account configuration (SMTP/IMAP settings)",
                "Test sending and receiving with different clients",
                "Check spam filters and mail routing rules",
                "Review mailbox quotas and storage limits",
                "Clear email cache and rebuild profiles if needed"
            ],
            'hardware_failure': [
                "Identify specific hardware component causing issues",
                "Run diagnostic tests to confirm failure",
                "Check warranty status and support options",
                "Order replacement parts if covered",
                "Schedule maintenance window for replacement",
                "Update hardware inventory and documentation"
            ],
            'performance_issues': [
                "Monitor system resources (CPU, memory, disk I/O)",
                "Identify resource-intensive processes or applications",
                "Check for malware or unwanted software",
                "Review system logs for errors or warnings",
                "Optimize system settings and configurations",
                "Consider hardware upgrades if consistently slow"
            ]
        }
        
        # Resolution confidence factors
        self.confidence_factors = {
            'exact_match': 0.95,
            'similar_issue': 0.85,
            'category_match': 0.70,
            'keyword_match': 0.60,
            'template_match': 0.50,
            'generic_advice': 0.30
        }
    
    async def initialize(self):
        """Initialize the resolution suggester"""
        if self._initialized:
            return
            
        try:
            logger.info("Initializing resolution suggester...")
            
            # Load sentence transformer for semantic similarity
            self.embedding_model = SentenceTransformer(settings.EMBEDDING_MODEL)
            
            # Load knowledge base embeddings (would be from database in production)
            await self._load_knowledge_base()
            
            # Load historical resolution patterns
            await self._load_resolution_patterns()
            
            self._initialized = True
            logger.info("Resolution suggester initialized successfully")
            
        except Exception as e:
            logger.error(f"Failed to initialize resolution suggester: {e}")
            self._initialized = True  # Continue with limited functionality
    
    async def suggest_resolutions(
        self,
        title: str,
        description: str,
        category: Optional[str] = None,
        affected_systems: Optional[List[str]] = None,
        limit: int = 5
    ) -> List[Dict[str, Any]]:
        """
        Generate resolution suggestions for a ticket
        
        Args:
            title: Ticket title
            description: Ticket description  
            category: Ticket category if known
            affected_systems: List of affected systems
            limit: Maximum number of suggestions
            
        Returns:
            List of resolution suggestions with confidence scores
        """
        await self.initialize()
        
        try:
            logger.info(f"Generating resolution suggestions for: {title[:50]}...")
            
            # Combine ticket information
            ticket_text = f"{title}. {description}"
            
            # Generate suggestions from multiple sources
            suggestion_tasks = [
                self._get_knowledge_base_suggestions(ticket_text, category, limit),
                self._get_historical_suggestions(ticket_text, category, limit),
                self._get_template_suggestions(ticket_text, category),
                self._get_ai_generated_suggestions(title, description, category)
            ]
            
            results = await asyncio.gather(*suggestion_tasks, return_exceptions=True)
            
            # Combine and rank all suggestions
            all_suggestions = []
            for result in results:
                if not isinstance(result, Exception) and result:
                    all_suggestions.extend(result)
            
            # Remove duplicates and rank by confidence
            unique_suggestions = self._deduplicate_suggestions(all_suggestions)
            ranked_suggestions = sorted(unique_suggestions, key=lambda x: x['confidence'], reverse=True)
            
            return ranked_suggestions[:limit]
            
        except Exception as e:
            logger.error(f"Resolution suggestion failed: {e}")
            return self._get_fallback_suggestions(title, description)
    
    async def _get_knowledge_base_suggestions(
        self, 
        ticket_text: str, 
        category: Optional[str], 
        limit: int
    ) -> List[Dict[str, Any]]:
        """Get suggestions from knowledge base using semantic similarity"""
        try:
            if not self.embedding_model:
                return []
            
            # Get embedding for ticket text
            ticket_embedding = self.embedding_model.encode([ticket_text])
            
            suggestions = []
            
            # Search through knowledge base embeddings
            for kb_id, kb_data in self.knowledge_embeddings.items():
                similarity = cosine_similarity(ticket_embedding, [kb_data['embedding']])[0][0]
                
                if similarity > 0.5:  # Minimum similarity threshold
                    suggestions.append({
                        'type': 'knowledge_base',
                        'title': kb_data['title'],
                        'steps': kb_data['steps'],
                        'confidence': similarity * self.confidence_factors['similar_issue'],
                        'source': f"Knowledge Base Article #{kb_id}",
                        'success_rate': kb_data.get('success_rate', 0.8),
                        'estimated_time': kb_data.get('estimated_time', '30-60 minutes')
                    })
            
            return sorted(suggestions, key=lambda x: x['confidence'], reverse=True)[:limit]
            
        except Exception as e:
            logger.error(f"Knowledge base search failed: {e}")
            return []
    
    async def _get_historical_suggestions(
        self, 
        ticket_text: str, 
        category: Optional[str], 
        limit: int
    ) -> List[Dict[str, Any]]:
        """Get suggestions based on historical ticket resolutions"""
        try:
            suggestions = []
            
            # Search resolution patterns
            for pattern_key, resolutions in self.resolution_patterns.items():
                if self._text_matches_pattern(ticket_text, pattern_key):
                    for resolution in resolutions[:3]:  # Top 3 for each pattern
                        suggestions.append({
                            'type': 'historical',
                            'title': f"Similar Issue Resolution - {pattern_key.replace('_', ' ').title()}",
                            'steps': resolution['steps'],
                            'confidence': resolution['success_rate'] * self.confidence_factors['similar_issue'],
                            'source': f"Historical Data ({resolution['usage_count']} similar cases)",
                            'success_rate': resolution['success_rate'],
                            'estimated_time': resolution['avg_resolution_time']
                        })
            
            return sorted(suggestions, key=lambda x: x['confidence'], reverse=True)[:limit]
            
        except Exception as e:
            logger.error(f"Historical suggestion search failed: {e}")
            return []
    
    async def _get_template_suggestions(
        self, 
        ticket_text: str, 
        category: Optional[str]
    ) -> List[Dict[str, Any]]:
        """Get suggestions from resolution templates"""
        try:
            suggestions = []
            text_lower = ticket_text.lower()
            
            # Match against template patterns
            template_matches = {
                'password_reset': ['password', 'reset', 'forgot', 'login', 'account locked'],
                'network_connectivity': ['network', 'connection', 'internet', 'wifi', 'connectivity'],
                'software_installation': ['install', 'software', 'application', 'program', 'setup'],
                'email_issues': ['email', 'outlook', 'mail', 'smtp', 'exchange'],
                'hardware_failure': ['hardware', 'printer', 'monitor', 'keyboard', 'mouse', 'disk'],
                'performance_issues': ['slow', 'performance', 'lag', 'freeze', 'hang', 'speed']
            }
            
            for template_name, keywords in template_matches.items():
                if any(keyword in text_lower for keyword in keywords):
                    if template_name in self.resolution_templates:
                        suggestions.append({
                            'type': 'template',
                            'title': f"Standard Resolution - {template_name.replace('_', ' ').title()}",
                            'steps': self.resolution_templates[template_name],
                            'confidence': self.confidence_factors['template_match'],
                            'source': 'Standard Resolution Template',
                            'success_rate': 0.75,
                            'estimated_time': '30-90 minutes'
                        })
            
            return suggestions
            
        except Exception as e:
            logger.error(f"Template suggestion generation failed: {e}")
            return []
    
    async def _get_ai_generated_suggestions(
        self, 
        title: str, 
        description: str, 
        category: Optional[str]
    ) -> List[Dict[str, Any]]:
        """Generate AI-powered custom suggestions"""
        try:
            # This would integrate with Claude/OpenAI API in production
            # For now, generate suggestions based on patterns
            
            suggestions = []
            combined_text = f"{title}. {description}".lower()
            
            # Generate contextual suggestions
            if 'error' in combined_text or 'failed' in combined_text:
                suggestions.append({
                    'type': 'ai_generated',
                    'title': 'Error Analysis and Resolution',
                    'steps': [
                        'Identify the specific error message or code',
                        'Check system logs for additional context',
                        'Research the error in knowledge base and forums',
                        'Test potential solutions in a safe environment',
                        'Implement the most appropriate fix',
                        'Verify the issue is resolved and document the solution'
                    ],
                    'confidence': self.confidence_factors['keyword_match'],
                    'source': 'AI Analysis',
                    'success_rate': 0.70,
                    'estimated_time': '45-120 minutes'
                })
            
            if 'access' in combined_text and 'denied' in combined_text:
                suggestions.append({
                    'type': 'ai_generated',
                    'title': 'Access Permission Resolution',
                    'steps': [
                        'Verify user identity and authorization for requested access',
                        'Check user group memberships and role assignments',
                        'Review resource permissions and access control lists',
                        'Test access with similar user accounts to isolate issue',
                        'Update permissions or escalate to system administrator',
                        'Confirm access is working and document changes'
                    ],
                    'confidence': self.confidence_factors['keyword_match'],
                    'source': 'AI Analysis',
                    'success_rate': 0.80,
                    'estimated_time': '20-60 minutes'
                })
            
            return suggestions
            
        except Exception as e:
            logger.error(f"AI suggestion generation failed: {e}")
            return []
    
    def _deduplicate_suggestions(self, suggestions: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """Remove duplicate suggestions based on similarity"""
        if not suggestions:
            return []
        
        unique_suggestions = []
        seen_titles = set()
        
        for suggestion in suggestions:
            title_key = suggestion['title'].lower().strip()
            if title_key not in seen_titles:
                unique_suggestions.append(suggestion)
                seen_titles.add(title_key)
        
        return unique_suggestions
    
    def _text_matches_pattern(self, text: str, pattern_key: str) -> bool:
        """Check if text matches a resolution pattern"""
        text_lower = text.lower()
        
        pattern_keywords = {
            'password_reset': ['password', 'reset', 'login', 'account'],
            'network_issue': ['network', 'connection', 'internet', 'connectivity'],
            'software_issue': ['software', 'application', 'program', 'install'],
            'email_issue': ['email', 'mail', 'outlook', 'exchange'],
            'hardware_issue': ['hardware', 'printer', 'device', 'equipment'],
            'performance_issue': ['slow', 'performance', 'speed', 'lag']
        }
        
        keywords = pattern_keywords.get(pattern_key, [])
        return any(keyword in text_lower for keyword in keywords)
    
    def _get_fallback_suggestions(self, title: str, description: str) -> List[Dict[str, Any]]:
        """Generate basic fallback suggestions when other methods fail"""
        return [
            {
                'type': 'fallback',
                'title': 'General Troubleshooting Steps',
                'steps': [
                    'Gather detailed information about the issue',
                    'Check for recent changes or updates',
                    'Review system logs and error messages',
                    'Test with different users or systems',
                    'Consult documentation and knowledge base',
                    'Escalate to specialized team if needed'
                ],
                'confidence': self.confidence_factors['generic_advice'],
                'source': 'Standard Troubleshooting Guide',
                'success_rate': 0.60,
                'estimated_time': '60-180 minutes'
            },
            {
                'type': 'fallback',
                'title': 'Basic System Checks',
                'steps': [
                    'Verify system status and availability',
                    'Check network connectivity and access',
                    'Restart affected services or applications',
                    'Clear cache and temporary files',
                    'Update software and drivers if needed',
                    'Run system diagnostics and health checks'
                ],
                'confidence': self.confidence_factors['generic_advice'],
                'source': 'System Maintenance Guide',
                'success_rate': 0.50,
                'estimated_time': '30-90 minutes'
            }
        ]
    
    async def _load_knowledge_base(self):
        """Load knowledge base articles and their embeddings"""
        try:
            # In production, this would load from database
            # For now, create sample data
            sample_kb = {
                '1': {
                    'title': 'Email Server Configuration Issues',
                    'content': 'Steps to resolve email server connectivity and configuration problems',
                    'steps': [
                        'Check email server status and services',
                        'Verify DNS and MX record configuration',
                        'Test SMTP and IMAP connections',
                        'Review server logs for errors',
                        'Update email client configurations'
                    ],
                    'success_rate': 0.85,
                    'estimated_time': '45-90 minutes'
                },
                '2': {
                    'title': 'Network Connectivity Troubleshooting',
                    'content': 'Comprehensive guide for diagnosing and fixing network connectivity issues',
                    'steps': [
                        'Check physical network connections',
                        'Verify IP configuration and DNS settings',
                        'Test connectivity to gateway and internet',
                        'Check firewall and security settings',
                        'Restart network services and adapters'
                    ],
                    'success_rate': 0.80,
                    'estimated_time': '30-60 minutes'
                }
            }
            
            # Generate embeddings for sample data
            if self.embedding_model:
                for kb_id, kb_data in sample_kb.items():
                    embedding = self.embedding_model.encode([kb_data['content']])
                    self.knowledge_embeddings[kb_id] = {
                        **kb_data,
                        'embedding': embedding[0]
                    }
            
            logger.info(f"Loaded {len(self.knowledge_embeddings)} knowledge base articles")
            
        except Exception as e:
            logger.error(f"Failed to load knowledge base: {e}")
    
    async def _load_resolution_patterns(self):
        """Load historical resolution patterns"""
        try:
            # In production, this would analyze historical ticket data
            # For now, create sample patterns
            sample_patterns = {
                'password_reset': [
                    {
                        'steps': [
                            'Verify user identity through security questions',
                            'Reset password in Active Directory',
                            'Force password change on next login',
                            'Notify user of password reset completion'
                        ],
                        'success_rate': 0.95,
                        'usage_count': 150,
                        'avg_resolution_time': '15-30 minutes'
                    }
                ],
                'network_issue': [
                    {
                        'steps': [
                            'Check network cable connections',
                            'Restart network adapter drivers',
                            'Flush DNS cache and renew IP address',
                            'Test connectivity to local and remote resources'
                        ],
                        'success_rate': 0.80,
                        'usage_count': 89,
                        'avg_resolution_time': '30-45 minutes'
                    }
                ]
            }
            
            self.resolution_patterns.update(sample_patterns)
            
            logger.info(f"Loaded {len(self.resolution_patterns)} resolution patterns")
            
        except Exception as e:
            logger.error(f"Failed to load resolution patterns: {e}")
    
    async def learn_from_resolution(
        self, 
        ticket_id: int, 
        resolution_steps: List[str], 
        success: bool, 
        resolution_time: float
    ):
        """Learn from resolution outcomes to improve future suggestions"""
        try:
            logger.info(f"Learning from resolution of ticket {ticket_id}")
            
            # In production, this would update the ML models and knowledge base
            # based on the success/failure of suggested resolutions
            
            # Update success rates and patterns based on outcomes
            # This is where reinforcement learning could be applied
            
            logger.info(f"Resolution outcome recorded: success={success}, time={resolution_time}min")
            
        except Exception as e:
            logger.error(f"Failed to learn from resolution: {e}")
    
    async def get_resolution_feedback(
        self, 
        suggestion_id: str, 
        helpful: bool, 
        feedback: Optional[str] = None
    ):
        """Collect feedback on resolution suggestions to improve quality"""
        try:
            logger.info(f"Received feedback for suggestion {suggestion_id}: helpful={helpful}")
            
            # In production, this would update suggestion ranking algorithms
            # and retrain models based on user feedback
            
            if feedback:
                logger.info(f"Additional feedback: {feedback}")
            
        except Exception as e:
            logger.error(f"Failed to process resolution feedback: {e}")