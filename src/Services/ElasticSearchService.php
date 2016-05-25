<?php

namespace Phroggyy\Discover\Services;

use Elasticsearch\Client;
use Phroggyy\Discover\Contracts\Searchable;
use Phroggyy\Discover\Contracts\Services\DiscoverService;
use Phroggyy\Discover\Contracts\Exceptions\ClassNotFoundException;
use Phroggyy\Discover\Contracts\Exceptions\NoNestedIndexException;
use Phroggyy\Discover\Contracts\Exceptions\NonSearchableClassException;

class ElasticSearchService implements DiscoverService
{
    /**
     * The ElasticSearch client.
     *
     * @var \Elasticsearch\Client
     */
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
        return strpos($index, '/') !== false;
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

        return $this->client->search($this->buildSearchQuery($model, $query));
    }

    /**
     * {@inheritdoc}
     */
    public function saveDocument(Searchable $model)
    {
        $document = $this->constructDocument($model);

        if (! $this->indexIsNested($model->getSearchIndex())) {
            $this->client->index($document);

            return;
        }

        list($index, $type) = $this->retrieveNestedIndex($model->getSearchIndex());
        $class = $this->retrieveParentClass($model->getSearchIndex());

        $parent = $model->belongsTo($class, null, null, class_basename($class))->getResults();

        $parentData = $this->client->get([
            'id'    => $parent->getKey(),
            'type'  => $parent->getSearchType(),
            'index' => $parent->getSearchIndex(),
        ])['_source'];

        if (! isset($parentData[$type])) {
            $parentData[$type] = [];
        }

        $children = collect($parentData[$type]);

        if ($child = $children->first(function ($child) use ($model) {
            return $child['id'] == $model->getKey();
        })) {
            $newChildren = $children->map(function ($child) use ($model) {
                if ($child['id'] == $model->getKey()) {
                    return $model->toArray();
                }

                return $child;
            });
        } else {
            $newChildren = $children->push($model->documentToArray());
        }

        $this->client->update([
            'id'    => $parent->getKey(),
            'type'  => $parent->getSearchType(),
            'index' => $parent->getSearchIndex(),
            'body'  => [
                'doc' => [
                    $type => $newChildren,
                ],
            ],
        ]);
    }

    /**
     * Construct the document to save.
     *
     * @param  \Phroggyy\Discover\Contracts\Searchable $model
     * @return array
     */
    private function constructDocument(Searchable $model)
    {
        $elasticDocument = [
            'index' => $model->getSearchIndex(),
            'type'  => $model->getSearchType(),
            'id'    => $model->id,
            'body'  => [],
        ];

        foreach ($model->getDocumentFields() as $elasticField) {
            $field = $model->{$elasticField};
            if ($field instanceof Carbon || $field instanceof \DateTime) {
                $field = $field->format('Y-m-d H:i:s');
            }
            $elasticDocument['body'][$elasticField] = $field;
        }

        return $elasticDocument;
    }

    /**
     * Retrieve the parent class of the index.
     *
     * @param  string  $getSearchIndex
     * @return string
     */
    private function retrieveParentClass($getSearchIndex)
    {
        return explode('/', $getSearchIndex)[0];
    }

}