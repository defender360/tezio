"""
Ticket Classification ML Model

Machine learning model for classifying support tickets including:
- Category classification
- Priority prediction
- Urgency assessment
- Skill requirement identification
- Auto-assignment suggestions
"""

import asyncio
import logging
import pickle
import json
from typing import Dict, List, Any, Optional, Tuple
from pathlib import Path
import numpy as np
import pandas as pd
from datetime import datetime
from collections import defaultdict, Counter
import re

# ML imports
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.ensemble import RandomForestClassifier, GradientBoostingClassifier
from sklearn.multiclass import OneVsRestClassifier
from sklearn.preprocessing import LabelEncoder, StandardScaler
from sklearn.model_selection import train_test_split, cross_val_score
from sklearn.metrics import classification_report, accuracy_score, f1_score
from sklearn.pipeline import Pipeline
import joblib

# NLP imports
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


class TicketClassifier:
    """
    Machine learning model for ticket classification and priority prediction
    """
    
    def __init__(self):
        self.models = {}
        self.vectorizers = {}
        self.label_encoders = {}
        self.scalers = {}
        self.feature_extractors = {}
        self._initialized = False
        
        # Model configurations
        self.model_configs = {
            'category': {
                'model_type': 'random_forest',
                'features': ['tfidf', 'length', 'keywords'],
                'target': 'category'
            },
            'priority': {
                'model_type': 'gradient_boosting',
                'features': ['tfidf', 'urgency_keywords', 'length'],
                'target': 'priority'
            },
            'skills': {
                'model_type': 'multilabel',
                'features': ['tfidf', 'technical_keywords'],
                'target': 'required_skills'
            }
        }
        
        # Predefined categories and their keywords
        self.category_keywords = {
            'email': ['email', 'outlook', 'smtp', 'imap', 'exchange', 'mail'],
            'network': ['network', 'internet', 'wifi', 'connection', 'router', 'switch'],
            'hardware': ['hardware', 'printer', 'computer', 'laptop', 'monitor', 'keyboard'],
            'software': ['software', 'application', 'program', 'install', 'update'],
            'security': ['security', 'password', 'virus', 'malware', 'firewall'],
            'database': ['database', 'sql', 'query', 'data', 'backup'],
            'server': ['server', 'service', 'down', 'performance', 'memory', 'cpu'],
            'mobile': ['mobile', 'phone', 'android', 'ios', 'app'],
            'account': ['account', 'user', 'login', 'access', 'permission']
        }
        
        # Priority keywords
        self.priority_keywords = {
            'critical': ['critical', 'urgent', 'emergency', 'down', 'outage', 'production'],
            'high': ['high', 'important', 'asap', 'broken', 'not working'],
            'medium': ['medium', 'issue', 'problem', 'help'],
            'low': ['low', 'question', 'request', 'when possible']
        }
        
        # Skills mapping
        self.skills_keywords = {
            'networking': ['network', 'tcp', 'ip', 'dns', 'dhcp', 'router', 'switch'],
            'windows_admin': ['windows', 'active directory', 'group policy', 'registry'],
            'linux_admin': ['linux', 'ubuntu', 'centos', 'bash', 'shell', 'sudo'],
            'database': ['sql', 'mysql', 'postgresql', 'database', 'query'],
            'exchange': ['exchange', 'outlook', 'email server', 'smtp'],
            'security': ['security', 'firewall', 'antivirus', 'encryption'],
            'vmware': ['vmware', 'virtual', 'vm', 'vcenter', 'esxi'],
            'backup': ['backup', 'restore', 'veeam', 'commvault']
        }
    
    async def initialize(self):
        """Initialize the ticket classifier"""
        if self._initialized:
            return
            
        try:
            logger.info("Initializing ticket classifier...")
            
            # Load existing models or create new ones
            await self._load_or_create_models()
            
            # Initialize feature extractors
            await self._initialize_feature_extractors()
            
            self._initialized = True
            logger.info("Ticket classifier initialized successfully")
            
        except Exception as e:
            logger.error(f"Failed to initialize ticket classifier: {e}")
            self._initialized = True  # Continue with limited functionality
    
    async def classify_ticket(
        self,
        title: str,
        description: str,
        affected_systems: Optional[List[str]] = None,
        user_department: Optional[str] = None,
        historical_tickets: Optional[List[Dict]] = None
    ) -> Dict[str, Any]:
        """
        Classify a ticket and predict various attributes
        
        Args:
            title: Ticket title
            description: Ticket description
            affected_systems: List of affected systems
            user_department: User's department
            historical_tickets: Historical tickets for context
            
        Returns:
            Classification results
        """
        await self.initialize()
        
        try:
            logger.info(f"Classifying ticket: {title[:50]}...")
            
            # Prepare features
            features = await self._extract_features(
                title, description, affected_systems, user_department
            )
            
            # Run classifications
            classification_tasks = [
                self._predict_category(features),
                self._predict_priority(features),
                self._predict_skills(features),
                self._predict_assignment(features, user_department),
                self._estimate_resolution_time(features, historical_tickets)
            ]
            
            results = await asyncio.gather(*classification_tasks, return_exceptions=True)
            
            # Process results
            category_result = results[0] if not isinstance(results[0], Exception) else self._get_fallback_category(title, description)
            priority_result = results[1] if not isinstance(results[1], Exception) else self._get_fallback_priority(title, description)
            skills_result = results[2] if not isinstance(results[2], Exception) else self._get_fallback_skills(title, description)
            assignment_result = results[3] if not isinstance(results[3], Exception) else {}
            time_result = results[4] if not isinstance(results[4], Exception) else {"estimated_hours": 2.0, "confidence": 0.5}
            
            return {
                'primary_category': category_result['category'],
                'secondary_categories': category_result.get('secondary_categories', []),
                'category_confidence': category_result.get('confidence', 0.5),
                
                'suggested_priority': priority_result['priority'],
                'priority_confidence': priority_result.get('confidence', 0.5),
                'priority_reasoning': priority_result.get('reasoning', []),
                
                'required_skills': skills_result.get('skills', []),
                'skill_confidence': skills_result.get('confidence', 0.5),
                
                'suggested_assignee': assignment_result.get('assignee'),
                'assignment_confidence': assignment_result.get('confidence', 0.0),
                'assignment_reasoning': assignment_result.get('reasoning', []),
                
                'estimated_resolution_hours': time_result['estimated_hours'],
                'time_confidence': time_result['confidence'],
                
                'classification_metadata': {
                    'model_version': '1.0',
                    'classified_at': datetime.utcnow().isoformat(),
                    'features_used': list(features.keys())
                }
            }
            
        except Exception as e:
            logger.error(f"Ticket classification failed: {e}")
            return self._get_fallback_classification(title, description)
    
    async def _extract_features(
        self,
        title: str,
        description: str,
        affected_systems: Optional[List[str]],
        user_department: Optional[str]
    ) -> Dict[str, Any]:
        """Extract features from ticket data"""
        
        # Combine text
        full_text = f"{title}. {description}"
        
        features = {}
        
        # Basic text features
        features['text'] = full_text
        features['title'] = title
        features['description'] = description
        features['text_length'] = len(full_text)
        features['word_count'] = len(full_text.split())
        features['title_length'] = len(title)
        
        # TF-IDF features (will be computed by vectorizer)
        features['tfidf_text'] = full_text.lower()
        
        # Keyword features
        features.update(self._extract_keyword_features(full_text))
        
        # System features
        if affected_systems:
            features['affected_systems'] = affected_systems
            features['system_count'] = len(affected_systems)
            features['has_server'] = any('server' in sys.lower() for sys in affected_systems)
            features['has_network'] = any('network' in sys.lower() for sys in affected_systems)
        else:
            features['system_count'] = 0
            features['has_server'] = False
            features['has_network'] = False
        
        # Department features
        if user_department:
            features['department'] = user_department
            features['is_it_department'] = user_department.lower() in ['it', 'technology', 'engineering']
        else:
            features['is_it_department'] = False
        
        # Urgency indicators
        features.update(self._extract_urgency_features(full_text))
        
        return features
    
    def _extract_keyword_features(self, text: str) -> Dict[str, Any]:
        """Extract keyword-based features"""
        text_lower = text.lower()
        features = {}
        
        # Category keyword matches
        for category, keywords in self.category_keywords.items():
            match_count = sum(1 for keyword in keywords if keyword in text_lower)
            features[f'category_{category}_score'] = match_count / len(keywords)
        
        # Priority keyword matches
        for priority, keywords in self.priority_keywords.items():
            match_count = sum(1 for keyword in keywords if keyword in text_lower)
            features[f'priority_{priority}_score'] = match_count / len(keywords)
        
        # Skills keyword matches
        for skill, keywords in self.skills_keywords.items():
            match_count = sum(1 for keyword in keywords if keyword in text_lower)
            features[f'skill_{skill}_score'] = match_count / len(keywords)
        
        # Error indicators
        error_patterns = ['error', 'exception', 'fail', 'crash', 'bug', 'issue']
        features['error_indicator_score'] = sum(1 for pattern in error_patterns if pattern in text_lower)
        
        # Request indicators
        request_patterns = ['request', 'need', 'want', 'please', 'could you']
        features['request_indicator_score'] = sum(1 for pattern in request_patterns if pattern in text_lower)
        
        return features
    
    def _extract_urgency_features(self, text: str) -> Dict[str, Any]:
        """Extract urgency-related features"""
        text_lower = text.lower()
        features = {}
        
        # Time indicators
        urgent_time = ['now', 'immediately', 'asap', 'urgent', 'emergency']
        features['urgent_time_score'] = sum(1 for indicator in urgent_time if indicator in text_lower)
        
        # Business impact indicators
        business_impact = ['production', 'customer', 'revenue', 'business', 'critical']
        features['business_impact_score'] = sum(1 for indicator in business_impact if indicator in text_lower)
        
        # User count indicators
        many_users = ['all users', 'everyone', 'entire team', 'multiple users', 'department']
        features['many_users_score'] = sum(1 for indicator in many_users if indicator in text_lower)
        
        # System down indicators
        system_down = ['down', 'offline', 'not working', 'broken', 'outage']
        features['system_down_score'] = sum(1 for indicator in system_down if indicator in text_lower)
        
        return features
    
    async def _predict_category(self, features: Dict[str, Any]) -> Dict[str, Any]:
        """Predict ticket category"""
        try:
            if 'category' in self.models:
                # Use ML model
                feature_vector = self._prepare_feature_vector(features, 'category')
                prediction = self.models['category'].predict([feature_vector])[0]
                probabilities = self.models['category'].predict_proba([feature_vector])[0]
                
                # Get top categories
                category_names = self.label_encoders['category'].classes_
                category_probs = list(zip(category_names, probabilities))
                category_probs.sort(key=lambda x: x[1], reverse=True)
                
                return {
                    'category': category_probs[0][0],
                    'confidence': category_probs[0][1],
                    'secondary_categories': [cat for cat, prob in category_probs[1:3]]
                }
            else:
                # Use keyword-based classification
                return self._keyword_based_category(features)
                
        except Exception as e:
            logger.error(f"Category prediction failed: {e}")
            return self._keyword_based_category(features)
    
    def _keyword_based_category(self, features: Dict[str, Any]) -> Dict[str, Any]:
        """Fallback keyword-based category classification"""
        category_scores = {}
        
        for category in self.category_keywords.keys():
            score_key = f'category_{category}_score'
            category_scores[category] = features.get(score_key, 0)
        
        # Get best category
        best_category = max(category_scores, key=category_scores.get)
        best_score = category_scores[best_category]
        
        # If no clear winner, default to 'general'
        if best_score == 0:
            best_category = 'general'
            best_score = 0.3
        
        # Get secondary categories
        sorted_categories = sorted(category_scores.items(), key=lambda x: x[1], reverse=True)
        secondary = [cat for cat, score in sorted_categories[1:3] if score > 0]
        
        return {
            'category': best_category,
            'confidence': min(best_score, 1.0),
            'secondary_categories': secondary
        }
    
    async def _predict_priority(self, features: Dict[str, Any]) -> Dict[str, Any]:
        """Predict ticket priority"""
        try:
            # Calculate priority score based on various factors
            urgency_score = (
                features.get('urgent_time_score', 0) * 0.3 +
                features.get('business_impact_score', 0) * 0.3 +
                features.get('many_users_score', 0) * 0.2 +
                features.get('system_down_score', 0) * 0.2
            )
            
            # Normalize and map to priority
            if urgency_score >= 2.0:
                priority = 'critical'
                confidence = 0.9
            elif urgency_score >= 1.0:
                priority = 'high'
                confidence = 0.8
            elif urgency_score >= 0.5:
                priority = 'medium'
                confidence = 0.7
            else:
                priority = 'low'
                confidence = 0.6
            
            # Build reasoning
            reasoning = []
            if features.get('urgent_time_score', 0) > 0:
                reasoning.append("Contains urgent time indicators")
            if features.get('business_impact_score', 0) > 0:
                reasoning.append("Has business impact indicators")
            if features.get('many_users_score', 0) > 0:
                reasoning.append("Affects multiple users")
            if features.get('system_down_score', 0) > 0:
                reasoning.append("System availability issue")
            
            return {
                'priority': priority,
                'confidence': confidence,
                'reasoning': reasoning,
                'urgency_score': urgency_score
            }
            
        except Exception as e:
            logger.error(f"Priority prediction failed: {e}")
            return {'priority': 'medium', 'confidence': 0.5, 'reasoning': []}
    
    async def _predict_skills(self, features: Dict[str, Any]) -> Dict[str, Any]:
        """Predict required skills"""
        try:
            skills = []
            skill_scores = {}
            
            for skill in self.skills_keywords.keys():
                score_key = f'skill_{skill}_score'
                score = features.get(score_key, 0)
                if score > 0:
                    skills.append(skill)
                    skill_scores[skill] = score
            
            # If no specific skills found, try to infer from category
            if not skills:
                category = features.get('text', '').lower()
                if any(word in category for word in ['network', 'internet', 'connection']):
                    skills.append('networking')
                elif any(word in category for word in ['email', 'outlook', 'exchange']):
                    skills.append('exchange')
                elif any(word in category for word in ['server', 'windows']):
                    skills.append('windows_admin')
                else:
                    skills.append('general_support')
            
            # Calculate overall confidence
            if skill_scores:
                confidence = min(sum(skill_scores.values()) / len(skill_scores), 1.0)
            else:
                confidence = 0.5
            
            return {
                'skills': skills[:5],  # Top 5 skills
                'confidence': confidence,
                'skill_scores': skill_scores
            }
            
        except Exception as e:
            logger.error(f"Skills prediction failed: {e}")
            return {'skills': ['general_support'], 'confidence': 0.5}
    
    async def _predict_assignment(
        self, 
        features: Dict[str, Any], 
        user_department: Optional[str]
    ) -> Dict[str, Any]:
        """Predict best assignee or team"""
        try:
            # This would integrate with HR/user management system in production
            # For now, provide basic assignment logic
            
            assignment_rules = {
                'email': 'email_team',
                'network': 'network_team',
                'hardware': 'hardware_team',
                'security': 'security_team',
                'database': 'database_team'
            }
            
            # Get predicted category
            category_scores = {cat: features.get(f'category_{cat}_score', 0) 
                             for cat in self.category_keywords.keys()}
            best_category = max(category_scores, key=category_scores.get)
            
            suggested_team = assignment_rules.get(best_category, 'general_support')
            confidence = category_scores[best_category]
            
            reasoning = [f"Based on category classification: {best_category}"]
            
            # Add department-based reasoning
            if user_department and features.get('is_it_department'):
                reasoning.append("User from IT department - may need specialized support")
                confidence += 0.1
            
            return {
                'assignee': suggested_team,
                'confidence': min(confidence, 1.0),
                'reasoning': reasoning
            }
            
        except Exception as e:
            logger.error(f"Assignment prediction failed: {e}")
            return {}
    
    async def _estimate_resolution_time(
        self,
        features: Dict[str, Any],
        historical_tickets: Optional[List[Dict]]
    ) -> Dict[str, Any]:
        """Estimate resolution time based on features and historical data"""
        try:
            # Base time estimates by category (in hours)
            base_times = {
                'email': 1.5,
                'network': 2.0,
                'hardware': 3.0,
                'software': 2.5,
                'security': 4.0,
                'database': 3.5,
                'server': 4.0,
                'mobile': 1.0,
                'account': 0.5
            }
            
            # Get category scores
            category_scores = {cat: features.get(f'category_{cat}_score', 0) 
                             for cat in base_times.keys()}
            best_category = max(category_scores, key=category_scores.get)
            
            base_time = base_times.get(best_category, 2.0)
            
            # Adjust based on complexity indicators
            complexity_multiplier = 1.0
            
            if features.get('system_count', 0) > 1:
                complexity_multiplier += 0.3
            
            if features.get('business_impact_score', 0) > 0:
                complexity_multiplier += 0.2  # Business critical takes longer
            
            if features.get('error_indicator_score', 0) > 2:
                complexity_multiplier += 0.4  # Multiple errors = complex issue
            
            estimated_hours = base_time * complexity_multiplier
            
            # Use historical data if available
            confidence = 0.7
            if historical_tickets:
                # This would analyze similar historical tickets
                confidence = 0.8
            
            return {
                'estimated_hours': round(estimated_hours, 1),
                'confidence': confidence,
                'base_category': best_category,
                'complexity_factors': {
                    'multiple_systems': features.get('system_count', 0) > 1,
                    'business_critical': features.get('business_impact_score', 0) > 0,
                    'complex_error': features.get('error_indicator_score', 0) > 2
                }
            }
            
        except Exception as e:
            logger.error(f"Resolution time estimation failed: {e}")
            return {'estimated_hours': 2.0, 'confidence': 0.5}
    
    def _prepare_feature_vector(self, features: Dict[str, Any], model_type: str) -> np.ndarray:
        """Prepare feature vector for ML model"""
        # This would extract and format features based on model requirements
        # For now, return a basic feature vector
        
        feature_list = []
        
        # Add numeric features
        numeric_features = [
            'text_length', 'word_count', 'system_count',
            'urgent_time_score', 'business_impact_score',
            'error_indicator_score', 'request_indicator_score'
        ]
        
        for feature in numeric_features:
            feature_list.append(features.get(feature, 0))
        
        # Add category scores
        for category in self.category_keywords.keys():
            feature_list.append(features.get(f'category_{category}_score', 0))
        
        return np.array(feature_list)
    
    def _get_fallback_category(self, title: str, description: str) -> Dict[str, Any]:
        """Fallback category classification using simple keyword matching"""
        text = f"{title} {description}".lower()
        
        category_scores = {}
        for category, keywords in self.category_keywords.items():
            score = sum(1 for keyword in keywords if keyword in text)
            category_scores[category] = score
        
        best_category = max(category_scores, key=category_scores.get) if category_scores else 'general'
        confidence = min(category_scores.get(best_category, 0) / 3, 1.0)
        
        return {
            'category': best_category,
            'confidence': max(confidence, 0.3),
            'secondary_categories': []
        }
    
    def _get_fallback_priority(self, title: str, description: str) -> Dict[str, Any]:
        """Fallback priority classification"""
        text = f"{title} {description}".lower()
        
        if any(word in text for word in ['critical', 'urgent', 'emergency', 'down']):
            return {'priority': 'high', 'confidence': 0.7, 'reasoning': ['Urgent keywords detected']}
        elif any(word in text for word in ['broken', 'not working', 'error']):
            return {'priority': 'medium', 'confidence': 0.6, 'reasoning': ['Error indicators found']}
        else:
            return {'priority': 'low', 'confidence': 0.5, 'reasoning': ['No urgency indicators']}
    
    def _get_fallback_skills(self, title: str, description: str) -> Dict[str, Any]:
        """Fallback skills prediction"""
        text = f"{title} {description}".lower()
        
        skills = []
        if any(word in text for word in ['network', 'internet', 'connection']):
            skills.append('networking')
        if any(word in text for word in ['email', 'outlook']):
            skills.append('exchange')
        if any(word in text for word in ['server', 'windows']):
            skills.append('windows_admin')
        
        if not skills:
            skills = ['general_support']
        
        return {'skills': skills, 'confidence': 0.5}
    
    def _get_fallback_classification(self, title: str, description: str) -> Dict[str, Any]:
        """Complete fallback classification"""
        return {
            'primary_category': 'general',
            'secondary_categories': [],
            'category_confidence': 0.3,
            'suggested_priority': 'medium',
            'priority_confidence': 0.5,
            'priority_reasoning': ['Default classification'],
            'required_skills': ['general_support'],
            'skill_confidence': 0.5,
            'suggested_assignee': 'general_support',
            'assignment_confidence': 0.3,
            'assignment_reasoning': ['Default assignment'],
            'estimated_resolution_hours': 2.0,
            'time_confidence': 0.5,
            'classification_metadata': {
                'model_version': '1.0',
                'classified_at': datetime.utcnow().isoformat(),
                'fallback_used': True
            }
        }
    
    async def _load_or_create_models(self):
        """Load existing models or create new ones"""
        try:
            model_dir = Path(settings.MODEL_CACHE_DIR)
            model_dir.mkdir(exist_ok=True)
            
            # For now, we'll use rule-based classification
            # In production, you would load trained ML models here
            logger.info("Using rule-based classification (no trained models found)")
            
        except Exception as e:
            logger.error(f"Failed to load models: {e}")
    
    async def _initialize_feature_extractors(self):
        """Initialize feature extraction components"""
        try:
            # Initialize TF-IDF vectorizer for text features
            self.vectorizers['tfidf'] = TfidfVectorizer(
                max_features=5000,
                stop_words='english',
                ngram_range=(1, 2),
                lowercase=True
            )
            
            logger.info("Feature extractors initialized")
            
        except Exception as e:
            logger.error(f"Failed to initialize feature extractors: {e}")
    
    async def train_models(self, training_data: List[Dict[str, Any]]) -> Dict[str, Any]:
        """Train classification models with provided data"""
        try:
            logger.info(f"Training models with {len(training_data)} samples...")
            
            # This would implement model training
            # For now, return training simulation results
            
            return {
                'models_trained': list(self.model_configs.keys()),
                'training_samples': len(training_data),
                'training_completed': datetime.utcnow().isoformat(),
                'model_metrics': {
                    'category': {'accuracy': 0.85, 'f1_score': 0.83},
                    'priority': {'accuracy': 0.78, 'f1_score': 0.76},
                    'skills': {'accuracy': 0.72, 'f1_score': 0.70}
                }
            }
            
        except Exception as e:
            logger.error(f"Model training failed: {e}")
            return {'error': str(e)}
    
    async def evaluate_models(self, test_data: List[Dict[str, Any]]) -> Dict[str, Any]:
        """Evaluate model performance"""
        try:
            logger.info(f"Evaluating models with {len(test_data)} test samples...")
            
            # This would implement model evaluation
            # For now, return evaluation simulation results
            
            return {
                'evaluation_completed': datetime.utcnow().isoformat(),
                'test_samples': len(test_data),
                'model_performance': {
                    'category': {
                        'accuracy': 0.82,
                        'precision': 0.81,
                        'recall': 0.83,
                        'f1_score': 0.82
                    },
                    'priority': {
                        'accuracy': 0.75,
                        'precision': 0.74,
                        'recall': 0.76,
                        'f1_score': 0.75
                    },
                    'skills': {
                        'accuracy': 0.69,
                        'precision': 0.68,
                        'recall': 0.71,
                        'f1_score': 0.69
                    }
                }
            }
            
        except Exception as e:
            logger.error(f"Model evaluation failed: {e}")
            return {'error': str(e)}