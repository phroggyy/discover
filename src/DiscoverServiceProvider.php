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
        $this->mergeConfigFrom(__DIR__.'/../config/discover.php', 'discover');

        $this->app->singleton(Manager::class, function ($app) {
            return new Manager($app, new Factory);
        });

        $this->app->bind(Client::class, function ($app) {
            return $app->make(Manager::class)->connection();
        });

        $this->app->bind(DiscoverService::class, function ($app) {
            return new ElasticSearchService($this->app->make(Client::class));
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/discover.php' => config_path('discover.php'),
        ]);
    }

}