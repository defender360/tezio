# Elasticsearch Integration for ITSM Knowledge Base

This directory contains the Elasticsearch configuration and setup for the ITSM platform's Knowledge Base search functionality.

## Overview

The Knowledge Base uses Elasticsearch to provide:
- Full-text search with relevance scoring
- Autocomplete suggestions
- Faceted search (categories, tags, authors)
- Similar article recommendations
- Search analytics and trending topics
- Multi-language support with custom analyzers

## Architecture

### Components

1. **Elasticsearch Engine** (`app/Search/ElasticsearchEngine.php`)
   - Custom Laravel Scout driver for Elasticsearch
   - Handles indexing, searching, and document management

2. **Search Service** (`app/Services/SearchService.php`)
   - High-level search API
   - Advanced search features
   - Analytics and suggestions

3. **Knowledge Article Model** (`app/Models/KnowledgeArticle.php`)
   - Searchable model with custom field mappings
   - Automatic indexing on save/update

4. **API Endpoints** (`app/Http/Controllers/Api/V1/KnowledgeController.php`)
   - RESTful search endpoints
   - Autocomplete and suggestions
   - Search health monitoring

## Setup Instructions

### 1. Environment Configuration

Copy the Elasticsearch environment variables to your `.env` file:

```bash
# From backend/.env.elasticsearch
SCOUT_DRIVER=elasticsearch
ELASTICSEARCH_HOST=elasticsearch:9200
```

### 2. Start Elasticsearch Services

```bash
# Start Elasticsearch and Kibana
docker-compose up -d elasticsearch kibana
```

### 3. Initialize Elasticsearch Index

```bash
# Run from the backend directory
docker-compose exec backend php artisan elasticsearch:setup
```

This command will:
- Connect to Elasticsearch
- Create the knowledge_articles index with proper mappings
- Import existing articles (if any)

### 4. Verify Setup

```bash
# Check Elasticsearch health
curl http://localhost:9200/_cluster/health?pretty

# Check index mapping
curl http://localhost:9200/itsm_knowledge_articles/_mapping?pretty

# Access Kibana
open http://localhost:5601
```

## Index Mapping

The knowledge articles index uses custom analyzers for optimal search:

- **article_analyzer**: Main text analyzer with stemming and stop words
- **autocomplete_analyzer**: Edge n-gram analyzer for autocomplete
- **tag_analyzer**: Keyword analyzer for exact tag matching

Key fields:
- `title`: Boosted for relevance, includes autocomplete
- `content`: Full-text search with highlighting
- `tags`: Keyword field for faceted filtering
- `category`: Nested object with ID and name
- `suggest`: Completion suggester for autocomplete

## API Usage

### Search Articles

```bash
POST /api/v1/knowledge/search
{
  "query": "password reset",
  "category_id": "123",
  "tags": ["security", "authentication"],
  "sort": "relevance",
  "page": 1,
  "per_page": 20
}
```

### Autocomplete

```bash
GET /api/v1/knowledge/search/autocomplete?query=pass&limit=5
```

### Find Similar Articles

```bash
GET /api/v1/knowledge/articles/{id}/similar
```

### Reindex All Articles

```bash
POST /api/v1/knowledge/search/reindex
```

### Health Check

```bash
GET /api/v1/knowledge/search/health
```

## Search Features

### 1. Full-Text Search
- Multi-field search across title, content, tags, and metadata
- Fuzzy matching for typo tolerance
- Phrase matching with proximity scoring
- Field boosting for relevance tuning

### 2. Faceted Search
- Filter by category, tags, author, date range
- Aggregations for result counts
- Rating-based filtering

### 3. Autocomplete
- Real-time suggestions as you type
- Based on article titles and popular searches
- Fuzzy matching for typos

### 4. Similar Articles
- More-like-this queries
- Based on content similarity
- Considers tags and categories

### 5. Search Analytics
- Track popular searches
- Monitor search performance
- Identify content gaps

## Maintenance

### Reindexing

To reindex all articles after mapping changes:

```bash
docker-compose exec backend php artisan scout:import "App\Models\KnowledgeArticle"
```

### Clear Index

```bash
docker-compose exec backend php artisan scout:flush "App\Models\KnowledgeArticle"
```

### Monitor Performance

Access Kibana at http://localhost:5601 to:
- View index statistics
- Monitor search performance
- Analyze search queries
- Debug mapping issues

## Troubleshooting

### Connection Issues

```bash
# Check Elasticsearch is running
docker-compose ps elasticsearch

# Test connection
curl http://localhost:9200

# Check logs
docker-compose logs elasticsearch
```

### Indexing Issues

```bash
# Check Scout queue is running
docker-compose ps queue

# Process pending jobs
docker-compose exec backend php artisan queue:work --queue=scout
```

### Search Issues

```bash
# Enable debug logging
LOG_LEVEL=debug

# Check Elasticsearch logs
docker-compose logs -f elasticsearch

# Validate mapping
curl http://localhost:9200/itsm_knowledge_articles/_mapping?pretty
```

## Performance Optimization

1. **Index Settings**
   - Single shard for small datasets (<1GB)
   - No replicas in development
   - Adjust heap size for larger datasets

2. **Query Optimization**
   - Use filters instead of queries when possible
   - Limit aggregation buckets
   - Enable query caching

3. **Indexing Optimization**
   - Use bulk operations
   - Queue indexing jobs
   - Disable refresh during bulk imports

## Security Considerations

1. **Access Control**
   - Enable authentication in production
   - Use API keys for applications
   - Restrict network access

2. **Data Protection**
   - Enable TLS/SSL
   - Encrypt data at rest
   - Regular backups

3. **Monitoring**
   - Set up alerts for failures
   - Monitor resource usage
   - Track unauthorized access

## Resources

- [Elasticsearch Documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/index.html)
- [Laravel Scout Documentation](https://laravel.com/docs/scout)
- [Kibana User Guide](https://www.elastic.co/guide/en/kibana/current/index.html)