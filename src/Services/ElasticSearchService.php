<?php

namespace Phroggyy\Discover\Services;

use Elasticsearch\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
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
        $index = $model->getSearchIndex();
        $query = $this->structureMatches($model, $query);
        $type = null;

        if ($this->indexIsNested($index)) {
            $type = $this->retrieveParentType($index);
            list($index, $key) = $this->retrieveNestedIndex($index);

            $query = [
                'nested' => [
                    'path' => $key,
                    'query' => [
                        'bool' => [
                            'must' => $query,
                        ],
                    ],
                ],
            ];
        }

        if (! $type) {
            $type = $model->getSearchType();
        }

        return [
            'index' => $index,
            'type'  => $type,
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => $query
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function search(Searchable $model, $query)
    {
        if (! is_array($query) && $field = $model->getDefaultSearchField()) {
            $query = [$field => $query];
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

        $children = Collection::make($parentData[$type]);

        if ($child = $children->first(function ($child) use ($model) {
            return $child[$model->getKeyName()] == $model->getKey();
        })) {
            $newChildren = $children->map(function ($child) use ($model) {
                if ($child[$model->getKeyName()] == $model->getKey()) {
                    $child = $model->documentToArray();
                    if (! isset($document[$model->getKeyName()])) {
                        $child[$model->getKeyName()] = $model->getKey();
                    }
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

    /**
     * Retrieve the search type of the parent.
     *
     * @param  $index
     * @return string
     */
    private function retrieveParentType($index)
    {
        $class = $this->retrieveParentClass($index);

        return (new $class)->getSearchType();
    }

    /**
     * Structure the matches array.
     *
     * @param  \Phroggyy\Discover\Contracts\Searchable $model
     * @param  array $query
     * @return array|string
     */
    private function structureMatches(Searchable $model, $query)
    {
        // If the query is an array of arrays, we assume the
        // developer knows exactly what they're doing and
        // are providing a complete match query for us.
        if (is_array($query) && ! Arr::isAssoc($query)) return $query;

        $key = '';

        // If the search index turns out to actually be nested, we
        // want to make sure we use the name of the subdocument
        // when such a query is performed. This is necessary.
        if ($this->indexIsNested($model->getSearchIndex())) {
            $key = $this->retrieveNestedIndex($model->getSearchIndex())[1].'.';
        }

        // If the query is just a string, we assume the user
        // intends to just do a simple match query on the
        // default search field defined in their model.
        if (is_string($query)) {
            return [[
                'match' => [$key.$model->getDefaultSearchField() => $query],
            ]];
        }

        $query = Collection::make($query);

        return $query->map(function ($constraint, $property) use ($key) {
            if (strpos($property, '.') === false) {
                $property = $key.$property;
            }

            return ['match' => [$property => $constraint]];
        })->values()->all();
    }

}