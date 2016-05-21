<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Connection
    |--------------------------------------------------------------------------
    |
    | This value determines which of your connections is the default one to
    | connect to. This allows Discover to automatically set a predefined
    | connection for the Discover document client when being resolved.
    |
    */

    'defaultConnection' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Connections
    |--------------------------------------------------------------------------
    |
    | This array defines all available connections for the Discover client.
    | In turn, this allows you to have several available connections, a
    | feature that can be useful if you store data in several places.
    |
    */

    'connections' => [
        'default' => [

            /*
            |------------------------------------------------------------------
            | Hosts
            |------------------------------------------------------------------
            |
            | This array allows you to define which hosts the client should
            | connect to. You can either enter a single hostname, or set
            | an array if you run a cluster of ElasticSearch instances.
            |
            | This is the only configuration value that is mandatory.
            |
            | @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#_host_configuration
            |
            */

            'hosts' => [
                'localhost:9200'
            ],

            /*
            |------------------------------------------------------------------
            | SSL
            |------------------------------------------------------------------
            |
            | If your ElasticSearch instances use an outdated or self-signed
            | SSL certificate, you would set your certificate bundle here.
            | You should enter the path to the self-signed certificate.
            |
            | If you are using SSL and the certificates are up-to-date and
            | signed by a public certificate authority, this can remain
            | null and you can just use https in the host path above.
            |
            | @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_security.html#_ssl_encryption_2
            |
            */

            'ssl' => null,

            /*
            |------------------------------------------------------------------
            | Logging
            |------------------------------------------------------------------
            |
            | Logging is handled by passing through an instance of the Monolog
            | Logger (which coincidentally is Laravel's default Logger). If
            | enabled, both a logger path and log level must be provided.
            |
            | @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#enabling_logger
            |
            */

            'logging' => [
                'enabled' => false,
                'path'    => storage_path('logs/discover.log'),
                'level'   => 200 // This is equivalent to Monolog's INFO level
            ],

            /*
            |------------------------------------------------------------------
            | Retries
            |------------------------------------------------------------------
            |
            | This value sets the number of times the client should attempt
            | to retry the operation. By default the client will retry n
            | times, where n is the number of nodes in the ES cluster.
            |
            | @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#_set_retries
            |
            */

            'retries' => null,

            /*
            |------------------------------------------------------------------
            | The remainder of the options can almost always be left as is.
            |------------------------------------------------------------------
            */

            /*
            | Sniff On Start
            |
            | @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html
            |
            */

            'sniffOnStart' => false,

            /*
            | HTTP Handler
            |
            | @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#_configure_the_http_handler
            | @see http://ringphp.readthedocs.org/en/latest/client_handlers.html
            |
            */

            'httpHandler' => null,

            /*
            | Connection Pool
            |
            | @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#_setting_the_connection_pool
            | @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_connection_pool.html
            |
            */

            'connectionPool' => null,

            /*
            | Connection Selector
            |
            | @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#_setting_the_connection_selector
            | @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_selectors.html
            |
            */

            'connectionSelector' => null,

            /*
            | Serializer
            |
            | @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#_setting_the_serializer
            | @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_serializers.html
            |
            */

            'serializer' => null,

            /*
            | Connection Factory
            |
            | @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#_setting_a_custom_connectionfactory
            |
            */

            'connectionFactory' => null,

            /*
            | Endpoint
            |
            | @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#_set_the_endpoint_closure
            |
            */
            'endpoint' => null,
        ]
    ]
];