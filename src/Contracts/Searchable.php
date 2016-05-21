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
}