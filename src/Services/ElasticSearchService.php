<?php

namespace Phroggyy\Discover\Services;

use Elasticsearch\Client;
use Phroggyy\Discover\Contracts\Searchable;
use Phroggyy\Discover\Contracts\Exceptions\ClassNotFoundException;
use Phroggyy\Discover\Discover\Contracts\Services\DiscoverService;
use Phroggyy\Discover\Contracts\Exceptions\NonSearchableClassException;

class ElasticSearchService implements DiscoverService
{
    protected $client;

    /**
     * ElasticSearchService constructor.
     *
     * @param $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function indexIsNested($index)
    {
        return strpos($this->getElasticIndex(), '/') !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveNestedIndex($index)
    {
        if (strpos($index, '/') === false) {
            throw new NoNestedIndexException;
        }

        list($class, $type) = explode('/', $index);

        if (! class_exists($class)) {
            throw new ClassNotFoundException;
        }

        if (! in_array(Searchable::class, class_implements($class))) {
            throw new NonSearchableClassException;
        }

        $model = new $class;

        return [$model->getSearchIndex(), $type];
    }

    /**
     * {@inheritdoc}
     */
    public function buildSearchQuery(Searchable $model, array $query)
    {
        return [
            'index' => $model->getSearchIndex(),
            'type'  => $model->getSearchType(),
            'body'  => [
                'query' => [
                    'match' => $query,
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function search(Searchable $model, $query)
    {
        if (! is_array($query) && $this->defaultSearchField) {
            $query = [$this->defaultSearchField => $query];
        }

        return $this->client->search($this->buildSearchQuery($query));
    }

}