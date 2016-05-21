<?php

namespace Phroggyy\Discover\Contracts\Services;

use Phroggyy\Discover\Contracts\Searchable;

interface DiscoverService
{
    /**
     * Check if an index is actually a nested type.
     *
     * @param  string  $index
     * @return bool
     */
    public function indexIsNested($index);

    /**
     * Retrieve the index and nested type.
     *
     * @param  string  $index
     * @return array
     * @throws \Phroggyy\Discover\Contracts\Exceptions\ClassNotFoundException
     * @throws \Phroggyy\Discover\Contracts\Exceptions\NoNestedIndexException
     * @throws \Phroggyy\Discover\Contracts\Exceptions\NonSearchableClassException
     */
    public function retrieveNestedIndex($index);

    /**
     * Build the document search query.
     *
     * @param \Phroggyy\Discover\Contracts\Searchable $model
     * @param  array $query
     * @return array
     */
    public function buildSearchQuery(Searchable $model, array $query);

    /**
     * Search the model's related index.
     *
     * @param \Phroggyy\Discover\Contracts\Searchable $model
     * @param $query
     * @return mixed
     */
    public function search(Searchable $model, $query);

    /**
     * Save a model's document properties.
     * 
     * @param  \Phroggyy\Discover\Contracts\Searchable $model
     * @return  void
     */
    public function saveDocument(Searchable $model);
}