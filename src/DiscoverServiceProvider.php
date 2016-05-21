<?php

namespace Phroggyy\Discover;

use Elasticsearch\Client;
use Illuminate\Support\ServiceProvider;
use Phroggyy\Discover\Services\ElasticSearchService;
use Phroggyy\Discover\Contracts\Services\DiscoverService;

class DiscoverServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(Client::class, function ($app) {
            return new Manager($app);
        });

        $this->app->bind(DiscoverService::class, function ($app) {
            return new ElasticSearchService($this->app->make(Client::class));
        });
    }

}