<?php

namespace App\Search;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;

class ElasticsearchEngine extends Engine
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
     * Create a new engine instance.
     *
     * @param  array  $config
     * @return void
     */
    public function __construct(array $config = [])
    {
        $this->prefix = config('scout.prefix', '');
        
        $hosts = $config['hosts'] ?? [env('ELASTICSEARCH_HOST', 'localhost:9200')];
        
        $clientBuilder = ClientBuilder::create()
            ->setHosts($hosts)
            ->setRetries($config['retries'] ?? 3);

        // Configure authentication if enabled
        if ($config['auth']['enabled'] ?? false) {
            $clientBuilder->setBasicAuthentication(
                $config['auth']['username'],
                $config['auth']['password']
            );
        }

        // Configure SSL if enabled
        if ($config['ssl']['enabled'] ?? false) {
            $clientBuilder->setSSLVerification($config['ssl']['verify'] ?? true);
        }

        $this->client = $clientBuilder->build();
    }

    /**
     * Update the given model in the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @return void
     */
    public function update($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $params = ['body' => []];

        $models->each(function ($model) use (&$params) {
            $params['body'][] = [
                'index' => [
                    '_index' => $this->getIndexName($model),
                    '_id' => $model->getScoutKey(),
                ]
            ];

            $params['body'][] = array_merge(
                $model->toSearchableArray(),
                [
                    'suggest' => $this->getSuggestions($model),
                    '__class_name' => get_class($model),
                ]
            );
        });

        try {
            $response = $this->client->bulk($params);
            
            if ($response['errors'] ?? false) {
                Log::error('Elasticsearch bulk update errors', [
                    'errors' => $response['items']
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Elasticsearch update failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Remove the given model from the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @return void
     */
    public function delete($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $params = ['body' => []];

        $models->each(function ($model) use (&$params) {
            $params['body'][] = [
                'delete' => [
                    '_index' => $this->getIndexName($model),
                    '_id' => $model->getScoutKey(),
                ]
            ];
        });

        try {
            $this->client->bulk($params);
        } catch (\Exception $e) {
            Log::error('Elasticsearch delete failed: ' . $e->getMessage());
        }
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @return mixed
     */
    public function search(Builder $builder)
    {
        return $this->performSearch($builder, [
            'numericFilters' => $this->filters($builder),
            'size' => $builder->limit,
        ]);
    }

    /**
     * Perform the given search on the engine for pagination.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  int  $perPage
     * @param  int  $page
     * @return mixed
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        return $this->performSearch($builder, [
            'numericFilters' => $this->filters($builder),
            'from' => ($page - 1) * $perPage,
            'size' => $perPage,
        ]);
    }

    /**
     * Perform the search.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  array  $options
     * @return array
     */
    protected function performSearch(Builder $builder, array $options = [])
    {
        $model = $builder->model;
        $indexName = $this->getIndexName($model);

        $params = [
            'index' => $indexName,
            'body' => [
                'query' => $this->buildQuery($builder),
            ],
        ];

        // Add pagination
        if (isset($options['from'])) {
            $params['body']['from'] = $options['from'];
        }

        if (isset($options['size'])) {
            $params['body']['size'] = $options['size'];
        }

        // Add sorting
        if (!empty($builder->orders)) {
            $params['body']['sort'] = collect($builder->orders)->map(function ($order) {
                return [$order['column'] => $order['direction']];
            })->toArray();
        }

        // Add highlighting
        if ($builder->query) {
            $params['body']['highlight'] = [
                'fields' => [
                    'title' => ['number_of_fragments' => 0],
                    'content' => [
                        'fragment_size' => 150,
                        'number_of_fragments' => 3,
                        'no_match_size' => 150,
                    ],
                    'excerpt' => ['number_of_fragments' => 0],
                ],
                'pre_tags' => ['<mark>'],
                'post_tags' => ['</mark>'],
            ];
        }

        // Add aggregations for faceted search
        $params['body']['aggs'] = [
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
            'status' => [
                'terms' => [
                    'field' => 'status',
                ],
            ],
        ];

        try {
            $response = $this->client->search($params);
            return $response->asArray();
        } catch (\Exception $e) {
            Log::error('Elasticsearch search failed: ' . $e->getMessage());
            return [
                'hits' => [
                    'total' => ['value' => 0],
                    'hits' => [],
                ],
            ];
        }
    }

    /**
     * Build the query for the search.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @return array
     */
    protected function buildQuery(Builder $builder)
    {
        $query = ['bool' => ['must' => [], 'filter' => []]];

        // Main search query
        if ($builder->query) {
            $query['bool']['must'][] = [
                'multi_match' => [
                    'query' => $builder->query,
                    'fields' => [
                        'title^3',
                        'title.autocomplete^2',
                        'content',
                        'excerpt^2',
                        'tags^2',
                        'category.name',
                        'meta_description',
                    ],
                    'type' => 'best_fields',
                    'operator' => 'or',
                    'fuzziness' => 'AUTO',
                ],
            ];
        } else {
            // Match all if no query
            $query['bool']['must'][] = ['match_all' => new \stdClass()];
        }

        // Apply filters
        foreach ($this->filters($builder) as $field => $value) {
            if (is_array($value)) {
                $query['bool']['filter'][] = ['terms' => [$field => $value]];
            } else {
                $query['bool']['filter'][] = ['term' => [$field => $value]];
            }
        }

        // Apply where clauses
        foreach ($builder->wheres as $field => $value) {
            if (is_array($value)) {
                $query['bool']['filter'][] = ['terms' => [$field => $value]];
            } else {
                $query['bool']['filter'][] = ['term' => [$field => $value]];
            }
        }

        // Apply where-in clauses
        foreach ($builder->whereIns as $field => $values) {
            $query['bool']['filter'][] = ['terms' => [$field => $values]];
        }

        // Add tenant filter if applicable
        if (method_exists($builder->model, 'getTenantId') && $builder->model->getTenantId()) {
            $query['bool']['filter'][] = ['term' => ['tenant_id' => $builder->model->getTenantId()]];
        }

        return $query;
    }

    /**
     * Get the filter array for the query.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @return array
     */
    protected function filters(Builder $builder)
    {
        return collect($builder->wheres)->map(function ($value, $key) {
            return [$key => $value];
        })->collapse()->all();
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  mixed  $results
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function map(Builder $builder, $results, $model)
    {
        if (($results['hits']['total']['value'] ?? 0) === 0) {
            return $model->newCollection();
        }

        $objectIds = collect($results['hits']['hits'])->pluck('_id')->values()->all();
        $objectIdPositions = array_flip($objectIds);

        return $model->getScoutModelsByIds(
            $builder, $objectIds
        )->filter(function ($model) use ($objectIds) {
            return in_array($model->getScoutKey(), $objectIds);
        })->sortBy(function ($model) use ($objectIdPositions) {
            return $objectIdPositions[$model->getScoutKey()];
        })->values();
    }

    /**
     * Map the given results to instances of the given model via a lazy collection.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  mixed  $results
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Support\LazyCollection
     */
    public function lazyMap(Builder $builder, $results, $model)
    {
        if (($results['hits']['total']['value'] ?? 0) === 0) {
            return LazyCollection::empty();
        }

        $objectIds = collect($results['hits']['hits'])->pluck('_id')->values()->all();
        $objectIdPositions = array_flip($objectIds);

        return $model->queryScoutModelsByIds(
            $builder, $objectIds
        )->cursor()->filter(function ($model) use ($objectIds) {
            return in_array($model->getScoutKey(), $objectIds);
        })->sortBy(function ($model) use ($objectIdPositions) {
            return $objectIdPositions[$model->getScoutKey()];
        })->values();
    }

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param  mixed  $results
     * @return int
     */
    public function getTotalCount($results)
    {
        return $results['hits']['total']['value'] ?? 0;
    }

    /**
     * Flush all of the model's records from the engine.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function flush($model)
    {
        $indexName = $this->getIndexName($model);

        try {
            $this->client->deleteByQuery([
                'index' => $indexName,
                'body' => [
                    'query' => [
                        'match_all' => new \stdClass(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Elasticsearch flush failed: ' . $e->getMessage());
        }
    }

    /**
     * Create the index for the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array  $settings
     * @return void
     */
    public function createIndex($model, array $settings = [])
    {
        $indexName = $this->getIndexName($model);

        try {
            // Check if index exists
            if ($this->client->indices()->exists(['index' => $indexName])->asBool()) {
                return;
            }

            // Load mapping from file if exists
            $mappingFile = database_path("elasticsearch/{$model->getTable()}_mapping.json");
            if (file_exists($mappingFile)) {
                $settings = json_decode(file_get_contents($mappingFile), true);
            }

            // Create index with settings and mappings
            $this->client->indices()->create([
                'index' => $indexName,
                'body' => $settings,
            ]);

            Log::info("Elasticsearch index created: {$indexName}");
        } catch (\Exception $e) {
            Log::error('Elasticsearch create index failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete the index for the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function deleteIndex($model)
    {
        $indexName = $this->getIndexName($model);

        try {
            if ($this->client->indices()->exists(['index' => $indexName])->asBool()) {
                $this->client->indices()->delete(['index' => $indexName]);
                Log::info("Elasticsearch index deleted: {$indexName}");
            }
        } catch (\Exception $e) {
            Log::error('Elasticsearch delete index failed: ' . $e->getMessage());
        }
    }

    /**
     * Get the index name for the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return string
     */
    protected function getIndexName($model)
    {
        return $this->prefix . $model->searchableAs();
    }

    /**
     * Get suggestions for the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return array
     */
    protected function getSuggestions($model)
    {
        $suggestions = [];

        if (method_exists($model, 'toSearchableSuggestions')) {
            return $model->toSearchableSuggestions();
        }

        // Default suggestions based on title
        if (isset($model->title)) {
            $suggestions[] = $model->title;
        }

        return $suggestions;
    }
}