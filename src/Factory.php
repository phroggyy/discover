<?php

namespace Phroggyy\Discover;

use Elasticsearch\ClientBuilder;

class Factory
{
    /**
     * Build the Elasticsearch client for the given configuration.
     *
     * @param  array  $config
     * @return \Elasticsearch\Client|mixed
     */
    public function make(array $config)
    {
        return $this->buildClient($config);
    }

    /**
     * Build and configure an Elasticsearch client.
     *
     * @param array $config
     * @return \Elasticsearch\Client
     */
    protected function buildClient(array $config)
    {
        $clientBuilder = ClientBuilder::create();
        $clientBuilder->setHosts($config['hosts']);

        if ($config['sslVerification'] !== null) {
            $clientBuilder->setSSLVerification($config['sslVerification']);
        }

        if ($config['logging']) {
                $path = $config['logging']['path'];
                $level = $config['logging']['level'];
                $logger = ClientBuilder::defaultLogger($path, $level);
                $clientBuilder->setLogger($logger);
        }

        if ($config['sniffOnStart'] !== null) {
            $clientBuilder->setSniffOnStart($config['sniffOnStart']);
        }

        if ($config['retries'] !== null) {
            $clientBuilder->setRetries($config['retries']);
        }

        if ($config['httpHandler'] !== null) {
            $clientBuilder->setHandler($config['httpHandler']);
        }

        if ($config['connectionPool'] !== null) {
            $clientBuilder->setConnectionPool($config['connectionPool']);
        }

        if ($config['connectionSelector'] !== null) {
            $clientBuilder->setSelector($config['connectionSelector']);
        }

        if ($config['serializer'] !== null) {
            $clientBuilder->setSerializer($config['serializer']);
        }

        if ($config['connectionFactory'] !== null) {
            $clientBuilder->setConnectionFactory($config['connectionFactory']);
        }

        if ($config['endpoint'] !== null) {
            $clientBuilder->setEndpoint($config['endpoint']);
        }

        return $clientBuilder->build();
    }

}