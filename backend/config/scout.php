<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Search Engine
    |--------------------------------------------------------------------------
    |
    | This option controls the default search "engine" that will be used
    | when searching using Laravel Scout. You may set this to any of
    | the built-in engines or define your own custom engine.
    |
    */

    'driver' => env('SCOUT_DRIVER', 'elasticsearch'),

    /*
    |--------------------------------------------------------------------------
    | Index Prefix
    |--------------------------------------------------------------------------
    |
    | Here you may specify a prefix that will be applied to all search index
    | names used by Scout. This prefix may be useful if you have multiple
    | "tenants" or applications sharing the same search infrastructure.
    |
    */

    'prefix' => env('SCOUT_PREFIX', 'itsm_'),

    /*
    |--------------------------------------------------------------------------
    | Queue Data Syncing
    |--------------------------------------------------------------------------
    |
    | This option allows you to control if the operations that sync your data
    | with your search engines are queued. When this is set to "true" then
    | all automatic data syncing will get queued for better performance.
    |
    */

    'queue' => env('SCOUT_QUEUE', true),

    /*
    |--------------------------------------------------------------------------
    | Queue Connection
    |--------------------------------------------------------------------------
    |
    | Here you may configure the queue connection that should be used to
    | sync data to your search index. By default, the "sync" connection
    | will be used which performs the sync operations synchronously.
    |
    */

    'queue_connection' => env('SCOUT_QUEUE_CONNECTION', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Chunk Sizes
    |--------------------------------------------------------------------------
    |
    | These options allow you to control the maximum chunk size when you are
    | mass importing data into the search engine. This allows you to fine
    | tune each of these chunk sizes based on the power of the servers.
    |
    */

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | This option allows you to control whether to keep soft deleted records in
    | the search indexes. Maintaining soft deleted records can be useful
    | if your application still needs to search for the records later.
    |
    */

    'soft_delete' => true,

    /*
    |--------------------------------------------------------------------------
    | Identify User
    |--------------------------------------------------------------------------
    |
    | This option allows you to control whether to notify the search engine
    | of the user performing the search. This is sometimes useful if the
    | engine supports any analytics based on this information.
    |
    */

    'identify' => env('SCOUT_IDENTIFY', false),

    /*
    |--------------------------------------------------------------------------
    | Algolia Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Algolia settings. Algolia is a cloud hosted
    | search engine which works great with Scout out of the box. Just plug
    | in your application ID and admin API key to get started searching.
    |
    */

    'algolia' => [
        'id' => env('ALGOLIA_APP_ID', ''),
        'secret' => env('ALGOLIA_SECRET', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | MeiliSearch Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your MeiliSearch settings. MeiliSearch is an open
    | source search engine with minimal configuration. Below, you can state
    | the host and key information for your own MeiliSearch installation.
    |
    */

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            // 'users' => [
            //     'filterableAttributes'=> ['id', 'name', 'email'],
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Elasticsearch settings. You can specify
    | multiple hosts, enable authentication, configure SSL, and more.
    | The configuration follows the official Elasticsearch PHP client.
    |
    */

    'elasticsearch' => [
        'hosts' => [
            env('ELASTICSEARCH_HOST', 'localhost:9200'),
        ],
        'auth' => [
            'enabled' => env('ELASTICSEARCH_AUTH_ENABLED', false),
            'username' => env('ELASTICSEARCH_USERNAME', ''),
            'password' => env('ELASTICSEARCH_PASSWORD', ''),
        ],
        'ssl' => [
            'enabled' => env('ELASTICSEARCH_SSL_ENABLED', false),
            'verify' => env('ELASTICSEARCH_SSL_VERIFY', true),
        ],
        'index_settings' => [
            'number_of_shards' => env('ELASTICSEARCH_INDEX_SHARDS', 1),
            'number_of_replicas' => env('ELASTICSEARCH_INDEX_REPLICAS', 0),
        ],
        'body_size' => env('ELASTICSEARCH_BODY_SIZE', '10mb'),
        'retries' => env('ELASTICSEARCH_RETRIES', 3),
        'client' => [
            'timeout' => env('ELASTICSEARCH_TIMEOUT', 10),
            'connect_timeout' => env('ELASTICSEARCH_CONNECT_TIMEOUT', 10),
        ],
    ],

];