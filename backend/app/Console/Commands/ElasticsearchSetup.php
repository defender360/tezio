<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KnowledgeArticle;
use App\Search\ElasticsearchEngine;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticsearchSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:setup {--force : Force recreation of index}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up Elasticsearch indices and mappings for the knowledge base';

    /**
     * The Elasticsearch client.
     *
     * @var Client
     */
    protected $client;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up Elasticsearch for Knowledge Base...');
        
        // Initialize Elasticsearch client
        $config = config('scout.elasticsearch', []);
        $hosts = $config['hosts'] ?? [env('ELASTICSEARCH_HOST', 'localhost:9200')];
        
        $clientBuilder = ClientBuilder::create()->setHosts($hosts);
        
        if ($config['auth']['enabled'] ?? false) {
            $clientBuilder->setBasicAuthentication(
                $config['auth']['username'],
                $config['auth']['password']
            );
        }
        
        $this->client = $clientBuilder->build();
        
        // Check Elasticsearch connection
        if (!$this->checkConnection()) {
            $this->error('Failed to connect to Elasticsearch. Please check your configuration.');
            return 1;
        }
        
        // Set up knowledge articles index
        $this->setupKnowledgeArticlesIndex();
        
        // Import existing articles
        if ($this->confirm('Do you want to import existing knowledge articles?')) {
            $this->importExistingArticles();
        }
        
        $this->info('Elasticsearch setup completed successfully!');
        return 0;
    }

    /**
     * Check Elasticsearch connection.
     */
    protected function checkConnection(): bool
    {
        try {
            $response = $this->client->info();
            $this->info('Connected to Elasticsearch ' . $response['version']['number']);
            return true;
        } catch (\Exception $e) {
            $this->error('Connection error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Set up knowledge articles index.
     */
    protected function setupKnowledgeArticlesIndex()
    {
        $indexName = config('scout.prefix', '') . 'knowledge_articles';
        
        try {
            // Check if index exists
            $exists = $this->client->indices()->exists(['index' => $indexName])->asBool();
            
            if ($exists) {
                if ($this->option('force') || $this->confirm("Index '{$indexName}' already exists. Do you want to recreate it?")) {
                    $this->info("Deleting existing index '{$indexName}'...");
                    $this->client->indices()->delete(['index' => $indexName]);
                } else {
                    $this->info("Keeping existing index '{$indexName}'.");
                    return;
                }
            }
            
            // Load mapping from file
            $mappingFile = database_path('elasticsearch/knowledge_articles_mapping.json');
            if (!file_exists($mappingFile)) {
                $this->error("Mapping file not found: {$mappingFile}");
                return;
            }
            
            $mapping = json_decode(file_get_contents($mappingFile), true);
            
            $this->info("Creating index '{$indexName}'...");
            
            // Create index with mapping
            $this->client->indices()->create([
                'index' => $indexName,
                'body' => $mapping
            ]);
            
            $this->info("Index '{$indexName}' created successfully!");
            
            // Verify index
            $this->verifyIndex($indexName);
            
        } catch (\Exception $e) {
            $this->error('Error setting up index: ' . $e->getMessage());
        }
    }

    /**
     * Verify index setup.
     */
    protected function verifyIndex(string $indexName)
    {
        try {
            $mapping = $this->client->indices()->getMapping(['index' => $indexName]);
            $this->info("Index mapping verified for '{$indexName}'");
            
            // Display field mappings
            if ($this->option('verbose')) {
                $this->table(
                    ['Field', 'Type'],
                    collect($mapping[$indexName]['mappings']['properties'] ?? [])
                        ->map(function ($props, $field) {
                            return [$field, $props['type'] ?? 'object'];
                        })
                        ->toArray()
                );
            }
        } catch (\Exception $e) {
            $this->error('Error verifying index: ' . $e->getMessage());
        }
    }

    /**
     * Import existing articles to Elasticsearch.
     */
    protected function importExistingArticles()
    {
        $this->info('Importing existing knowledge articles...');
        
        $progressBar = $this->output->createProgressBar(KnowledgeArticle::count());
        $progressBar->start();
        
        $imported = 0;
        $failed = 0;
        
        KnowledgeArticle::chunk(100, function ($articles) use (&$imported, &$failed, $progressBar) {
            foreach ($articles as $article) {
                try {
                    if ($article->shouldBeSearchable()) {
                        $article->searchable();
                        $imported++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $this->error("\nFailed to import article ID {$article->id}: " . $e->getMessage());
                }
                $progressBar->advance();
            }
        });
        
        $progressBar->finish();
        $this->newLine();
        
        $this->info("Import completed: {$imported} articles imported, {$failed} failed.");
    }
}