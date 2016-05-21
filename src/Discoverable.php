<?php

namespace Phroggyy\Discover;

use Illuminate\Support\Str;
use Illuminate\Foundation\Application;
use Phroggyy\Discover\Contracts\Searchable;
use Phroggyy\Discover\Contracts\Services\DiscoverService;

trait Discoverable
{
    /**
     * The name of the index for the model.
     *
     * @var string
     */
    protected $documentIndex;

    /**
     * The type of the model in the document.
     *
     * @var string
     */
    protected $documentType;

    /**
     * The field to query if none is provided.
     *
     * @var string
     */
    protected $defaultSearchField;

    /**
     * The fields to store in a document db.
     *
     * @var array
     */
    protected $documentFields;

    /**
     * The Discover service to use for search.
     *
     * @var \Phroggyy\Discover\Contracts\Services\DiscoverService
     */
    protected static $discoverer;

    /**
     * Boot the trait and setup the required event handler.
     */
    public static function bootDiscoverable()
    {
        static::$discoverer = Application::getInstance()->make(DiscoverService::class);

        static::saved(function (Searchable $model) {
            static::$discoverer->saveDocument($model);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchIndex()
    {
        return $this->documentIndex ?: $this->getTable();
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchType()
    {
        return $this->documentType ?: Str::singular($this->getTable());
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSearchField()
    {
        return $this->defaultSearchField;
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentFields()
    {
        return $this->documentFields;
    }
}