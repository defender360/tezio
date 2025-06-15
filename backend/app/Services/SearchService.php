<?php

namespace App\Services;

use App\Models\KnowledgeArticle;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Scout\Builder;

class SearchService
{
    /**
     * The Elasticsearch client instance.
     *
     * @var Client
     */
    protected $client;

    /**
     * The index prefix.
     *
     * @var string
     */
    protected $prefix;

    /**
     * Create a new search service instance.
     */
    public function __construct()
    {
        $this->prefix = config('scout.prefix', '');
        
        $config = config('scout.elasticsearch', []);
        $hosts = $config['hosts'] ?? [env('ELASTICSEARCH_HOST', 'localhost:9200')];
        
        $clientBuilder = ClientBuilder::create()
            ->setHosts($hosts)
            ->setRetries($config['retries'] ?? 3);

        if ($config['auth']['enabled'] ?? false) {
            $clientBuilder->setBasicAuthentication(
                $config['auth']['username'],
                $config['auth']['password']
            );
        }

        if ($config['ssl']['enabled'] ?? false) {
            $clientBuilder->setSSLVerification($config['ssl']['verify'] ?? true);
        }

        $this->client = $clientBuilder->build();
    }

    /**
     * Perform an advanced search with multiple options.
     *
     * @param  string  $query
     * @param  array  $options
     * @return array
     */
    public function advancedSearch(string $query, array $options = []): array
    {
        $indexName = $this->prefix . 'knowledge_articles';
        
        $params = [
            'index' => $indexName,
            'body' => [
                'query' => $this->buildAdvancedQuery($query, $options),
                'from' => $options['from'] ?? 0,
                'size' => $options['size'] ?? 10,
                'highlight' => $this->getHighlightConfig(),
                'aggs' => $this->getAggregationsConfig(),
                'suggest' => $this->getSuggestConfig($query),
            ],
        ];

        // Add sorting
        if (isset($options['sort'])) {
            $params['body']['sort'] = $this->buildSortConfig($options['sort']);
        } else {
            // Default relevance + recency sorting
            $params['body']['sort'] = [
                '_score',
                ['published_at' => ['order' => 'desc', 'missing' => '_last']],
            ];
        }

        try {
            $response = $this->client->search($params);
            $results = $response->asArray();
            
            return $this->formatSearchResults($results, $query);
        } catch (\Exception $e) {
            Log::error('Advanced search failed: ' . $e->getMessage(), [
                'query' => $query,
                'options' => $options,
            ]);
            
            return $this->emptySearchResult();
        }
    }

    /**
     * Perform autocomplete search.
     *
     * @param  string  $query
     * @param  int  $limit
     * @return array
     */
    public function autocomplete(string $query, int $limit = 5): array
    {
        $cacheKey = 'autocomplete_' . md5($query);
        
        return Cache::remember($cacheKey, 300, function () use ($query, $limit) {
            $indexName = $this->prefix . 'knowledge_articles';
            
            $params = [
                'index' => $indexName,
                'body' => [
                    'suggest' => [
                        'article-suggest' => [
                            'prefix' => $query,
                            'completion' => [
                                'field' => 'suggest',
                                'size' => $limit,
                                'skip_duplicates' => true,
                                'fuzzy' => [
                                    'fuzziness' => 'AUTO',
                                ],
                            ],
                        ],
                    ],
                    '_source' => ['title', 'slug', 'excerpt'],
                ],
            ];

            try {
                $response = $this->client->search($params);
                $results = $response->asArray();
                
                return $this->formatAutocompleteResults($results);
            } catch (\Exception $e) {
                Log::error('Autocomplete search failed: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Search for similar articles.
     *
     * @param  KnowledgeArticle  $article
     * @param  int  $limit
     * @return Collection
     */
    public function findSimilar(KnowledgeArticle $article, int $limit = 5): Collection
    {
        $indexName = $this->prefix . 'knowledge_articles';
        
        $params = [
            'index' => $indexName,
            'body' => [
                'query' => [
                    'bool' => [
                        'must_not' => [
                            ['term' => ['id' => $article->id]],
                        ],
                        'should' => [
                            [
                                'more_like_this' => [
                                    'fields' => ['title', 'content', 'tags'],
                                    'like' => [
                                        [
                                            '_index' => $indexName,
                                            '_id' => $article->id,
                                        ],
                                    ],
                                    'min_term_freq' => 1,
                                    'max_query_terms' => 12,
                                    'min_doc_freq' => 1,
                                ],
                            ],
                        ],
                        'filter' => [
                            ['term' => ['status' => 'published']],
                        ],
                    ],
                ],
                'size' => $limit,
            ],
        ];

        // Add tenant filter if applicable
        if ($article->tenant_id) {
            $params['body']['query']['bool']['filter'][] = ['term' => ['tenant_id' => $article->tenant_id]];
        }

        try {
            $response = $this->client->search($params);
            $results = $response->asArray();
            
            $articleIds = collect($results['hits']['hits'])->pluck('_id')->toArray();
            
            return KnowledgeArticle::whereIn('id', $articleIds)
                ->orderByRaw("FIELD(id, " . implode(',', $articleIds) . ")")
                ->get();
        } catch (\Exception $e) {
            Log::error('Similar articles search failed: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get trending searches.
     *
     * @param  int  $days
     * @param  int  $limit
     * @return array
     */
    public function getTrendingSearches(int $days = 7, int $limit = 10): array
    {
        return Cache::remember("trending_searches_{$days}", 3600, function () use ($days, $limit) {
            // This would typically query a search logs table
            // For now, return mock data
            return [
                'password reset',
                'vpn setup',
                'email configuration',
                'printer issues',
                'software installation',
            ];
        });
    }

    /**
     * Get search suggestions based on user history.
     *
     * @param  int  $userId
     * @param  int  $limit
     * @return array
     */
    public function getPersonalizedSuggestions(int $userId, int $limit = 5): array
    {
        // This would analyze user's search history and viewed articles
        // For now, return general popular articles
        return KnowledgeArticle::published()
            ->orderBy('view_count', 'desc')
            ->orderBy('helpful_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'excerpt' => $article->excerpt,
                    'category' => $article->category?->name,
                ];
            })
            ->toArray();
    }

    /**
     * Index health check.
     *
     * @return array
     */
    public function healthCheck(): array
    {
        try {
            $indexName = $this->prefix . 'knowledge_articles';
            
            // Check if index exists
            $indexExists = $this->client->indices()->exists(['index' => $indexName])->asBool();
            
            if (!$indexExists) {
                return [
                    'healthy' => false,
                    'message' => 'Index does not exist',
                ];
            }

            // Get index stats
            $stats = $this->client->indices()->stats(['index' => $indexName])->asArray();
            
            // Get cluster health
            $health = $this->client->cluster()->health()->asArray();
            
            return [
                'healthy' => $health['status'] !== 'red',
                'status' => $health['status'],
                'index' => [
                    'name' => $indexName,
                    'documents' => $stats['indices'][$indexName]['primaries']['docs']['count'] ?? 0,
                    'size' => $stats['indices'][$indexName]['primaries']['store']['size_in_bytes'] ?? 0,
                ],
                'cluster' => [
                    'name' => $health['cluster_name'],
                    'nodes' => $health['number_of_nodes'],
                ],
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Health check failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Reindex all knowledge articles.
     *
     * @return array
     */
    public function reindexAll(): array
    {
        try {
            $indexName = $this->prefix . 'knowledge_articles';
            
            // Delete existing index
            if ($this->client->indices()->exists(['index' => $indexName])->asBool()) {
                $this->client->indices()->delete(['index' => $indexName]);
            }

            // Create new index with mapping
            $mappingFile = database_path('elasticsearch/knowledge_articles_mapping.json');
            if (file_exists($mappingFile)) {
                $mapping = json_decode(file_get_contents($mappingFile), true);
                $this->client->indices()->create([
                    'index' => $indexName,
                    'body' => $mapping,
                ]);
            }

            // Reindex all articles
            $count = 0;
            KnowledgeArticle::chunk(100, function ($articles) use (&$count) {
                foreach ($articles as $article) {
                    if ($article->shouldBeSearchable()) {
                        $article->searchable();
                        $count++;
                    }
                }
            });

            return [
                'success' => true,
                'indexed' => $count,
                'message' => "Successfully reindexed {$count} articles",
            ];
        } catch (\Exception $e) {
            Log::error('Reindex failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Reindex failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build advanced query.
     *
     * @param  string  $query
     * @param  array  $options
     * @return array
     */
    protected function buildAdvancedQuery(string $query, array $options): array
    {
        $must = [];
        $filter = [];
        $should = [];

        // Main search query
        if ($query) {
            $must[] = [
                'multi_match' => [
                    'query' => $query,
                    'fields' => [
                        'title^4',
                        'title.autocomplete^3',
                        'content^2',
                        'excerpt^3',
                        'tags^3',
                        'category.name^2',
                        'meta_description',
                    ],
                    'type' => 'best_fields',
                    'operator' => 'or',
                    'fuzziness' => 'AUTO',
                    'prefix_length' => 2,
                    'max_expansions' => 50,
                ],
            ];

            // Boost exact matches
            $should[] = [
                'match_phrase' => [
                    'title' => [
                        'query' => $query,
                        'boost' => 5,
                    ],
                ],
            ];
        }

        // Status filter
        $filter[] = ['term' => ['status' => $options['status'] ?? 'published']];

        // Category filter
        if (isset($options['category_id'])) {
            $filter[] = ['term' => ['category.id' => $options['category_id']]];
        }

        // Tags filter
        if (isset($options['tags']) && is_array($options['tags'])) {
            $filter[] = ['terms' => ['tags' => $options['tags']]];
        }

        // Author filter
        if (isset($options['author_id'])) {
            $filter[] = ['term' => ['author.id' => $options['author_id']]];
        }

        // Date range filter
        if (isset($options['date_from']) || isset($options['date_to'])) {
            $dateFilter = ['range' => ['published_at' => []]];
            
            if (isset($options['date_from'])) {
                $dateFilter['range']['published_at']['gte'] = $options['date_from'];
            }
            
            if (isset($options['date_to'])) {
                $dateFilter['range']['published_at']['lte'] = $options['date_to'];
            }
            
            $filter[] = $dateFilter;
        }

        // Featured filter
        if (isset($options['featured'])) {
            $filter[] = ['term' => ['featured' => $options['featured']]];
        }

        // Tenant filter
        if (isset($options['tenant_id'])) {
            $filter[] = ['term' => ['tenant_id' => $options['tenant_id']]];
        }

        $query = ['bool' => []];
        
        if (!empty($must)) {
            $query['bool']['must'] = $must;
        }
        
        if (!empty($filter)) {
            $query['bool']['filter'] = $filter;
        }
        
        if (!empty($should)) {
            $query['bool']['should'] = $should;
            $query['bool']['minimum_should_match'] = 0;
        }

        // If no query provided, match all
        if (empty($must) && empty($should)) {
            $query = ['match_all' => new \stdClass()];
        }

        return $query;
    }

    /**
     * Get highlight configuration.
     *
     * @return array
     */
    protected function getHighlightConfig(): array
    {
        return [
            'fields' => [
                'title' => [
                    'number_of_fragments' => 0,
                    'pre_tags' => ['<mark>'],
                    'post_tags' => ['</mark>'],
                ],
                'content' => [
                    'fragment_size' => 150,
                    'number_of_fragments' => 3,
                    'no_match_size' => 150,
                    'pre_tags' => ['<mark>'],
                    'post_tags' => ['</mark>'],
                ],
                'excerpt' => [
                    'number_of_fragments' => 0,
                    'pre_tags' => ['<mark>'],
                    'post_tags' => ['</mark>'],
                ],
            ],
            'encoder' => 'html',
        ];
    }

    /**
     * Get aggregations configuration.
     *
     * @return array
     */
    protected function getAggregationsConfig(): array
    {
        return [
            'categories' => [
                'terms' => [
                    'field' => 'category.id',
                    'size' => 20,
                ],
            ],
            'tags' => [
                'terms' => [
                    'field' => 'tags',
                    'size' => 50,
                ],
            ],
            'authors' => [
                'terms' => [
                    'field' => 'author.id',
                    'size' => 20,
                ],
            ],
            'date_histogram' => [
                'date_histogram' => [
                    'field' => 'published_at',
                    'calendar_interval' => 'month',
                    'format' => 'yyyy-MM',
                    'min_doc_count' => 1,
                ],
            ],
            'rating_ranges' => [
                'range' => [
                    'field' => 'average_rating',
                    'ranges' => [
                        ['key' => '4-5', 'from' => 4],
                        ['key' => '3-4', 'from' => 3, 'to' => 4],
                        ['key' => '2-3', 'from' => 2, 'to' => 3],
                        ['key' => '0-2', 'to' => 2],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get suggest configuration.
     *
     * @param  string  $query
     * @return array
     */
    protected function getSuggestConfig(string $query): array
    {
        return [
            'text' => $query,
            'simple_phrase' => [
                'phrase' => [
                    'field' => 'title',
                    'size' => 3,
                    'gram_size' => 2,
                    'confidence' => 1.0,
                    'max_errors' => 2,
                    'highlight' => [
                        'pre_tag' => '<em>',
                        'post_tag' => '</em>',
                    ],
                ],
            ],
        ];
    }

    /**
     * Build sort configuration.
     *
     * @param  string  $sort
     * @return array
     */
    protected function buildSortConfig(string $sort): array
    {
        switch ($sort) {
            case 'newest':
                return [['published_at' => ['order' => 'desc']]];
                
            case 'oldest':
                return [['published_at' => ['order' => 'asc']]];
                
            case 'popular':
                return [['view_count' => ['order' => 'desc']]];
                
            case 'helpful':
                return [['helpful_count' => ['order' => 'desc']]];
                
            case 'rating':
                return [['average_rating' => ['order' => 'desc']]];
                
            case 'title':
                return [['title.keyword' => ['order' => 'asc']]];
                
            default:
                return ['_score'];
        }
    }

    /**
     * Format search results.
     *
     * @param  array  $results
     * @param  string  $query
     * @return array
     */
    protected function formatSearchResults(array $results, string $query): array
    {
        $hits = $results['hits']['hits'] ?? [];
        $total = $results['hits']['total']['value'] ?? 0;
        
        $articles = collect($hits)->map(function ($hit) {
            $article = $hit['_source'];
            $article['_score'] = $hit['_score'];
            
            // Add highlights
            if (isset($hit['highlight'])) {
                $article['highlights'] = $hit['highlight'];
            }
            
            return $article;
        });

        // Get facets
        $facets = [
            'categories' => $this->formatBuckets($results['aggregations']['categories']['buckets'] ?? []),
            'tags' => $this->formatBuckets($results['aggregations']['tags']['buckets'] ?? []),
            'authors' => $this->formatBuckets($results['aggregations']['authors']['buckets'] ?? []),
            'dates' => $this->formatBuckets($results['aggregations']['date_histogram']['buckets'] ?? []),
            'ratings' => $this->formatBuckets($results['aggregations']['rating_ranges']['buckets'] ?? []),
        ];

        // Get suggestions
        $suggestions = [];
        if (isset($results['suggest']['simple_phrase'])) {
            foreach ($results['suggest']['simple_phrase'] as $suggestion) {
                if (!empty($suggestion['options'])) {
                    $suggestions[] = $suggestion['options'][0]['text'];
                }
            }
        }

        return [
            'query' => $query,
            'total' => $total,
            'articles' => $articles,
            'facets' => $facets,
            'suggestions' => $suggestions,
            'took' => $results['took'] ?? 0,
        ];
    }

    /**
     * Format autocomplete results.
     *
     * @param  array  $results
     * @return array
     */
    protected function formatAutocompleteResults(array $results): array
    {
        $suggestions = [];
        
        if (isset($results['suggest']['article-suggest'])) {
            foreach ($results['suggest']['article-suggest'] as $suggest) {
                foreach ($suggest['options'] as $option) {
                    $suggestions[] = [
                        'text' => $option['text'],
                        'title' => $option['_source']['title'] ?? $option['text'],
                        'slug' => $option['_source']['slug'] ?? '',
                        'excerpt' => $option['_source']['excerpt'] ?? '',
                    ];
                }
            }
        }
        
        return $suggestions;
    }

    /**
     * Format aggregation buckets.
     *
     * @param  array  $buckets
     * @return array
     */
    protected function formatBuckets(array $buckets): array
    {
        return collect($buckets)->map(function ($bucket) {
            return [
                'key' => $bucket['key'],
                'count' => $bucket['doc_count'],
            ];
        })->toArray();
    }

    /**
     * Get empty search result.
     *
     * @return array
     */
    protected function emptySearchResult(): array
    {
        return [
            'query' => '',
            'total' => 0,
            'articles' => [],
            'facets' => [
                'categories' => [],
                'tags' => [],
                'authors' => [],
                'dates' => [],
                'ratings' => [],
            ],
            'suggestions' => [],
            'took' => 0,
        ];
    }
}