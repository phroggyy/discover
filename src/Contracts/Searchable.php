<?php

namespace Phroggyy\Discover\Contracts;

interface Searchable {
    /**
     * Retrieve the index to be searched.
     *
     * @return string
     */
    public function getSearchIndex();

    /**
     * Return the type to be searched.
     *
     * @return string
     */
    public function getSearchType();

    /**
     * Retrieve the default search field.
     *
     * @return string|null
     */
    public function getDefaultSearchField();

    /**
     * Retrieve the fields to be indexed.
     *
     * @return array
     */
    public function getDocumentFields();

    /**
     * Convert a model with properties to an array for indexing.
     *
     * @return array
     */
    public function documentToArray();

    /**
     * Search the document database by a query.
     *
     * @param  string|array  $query
     * @return mixed
     */
    public static function search($query);
}