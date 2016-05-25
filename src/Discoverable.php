<?php

namespace Phroggyy\Discover;

use Illuminate\Support\Str;
use Illuminate\Foundation\Application;
use Phroggyy\Discover\Contracts\Searchable;
use Phroggyy\Discover\Contracts\Services\DiscoverService;

trait Discoverable
{
    /**
     * The field to query if none is provided.
     *
     * @var string
     */
    protected $defaultSearchField;

    /**
     * Boot the trait and setup the required event handler.
     */
    public static function bootDiscoverable()
    {
        static::saved(function (Searchable $model) {
            app(DiscoverService::class)->saveDocument($model);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchIndex()
    {
        if (property_exists($this, 'documentIndex')) {
            return $this->documentIndex;
        }

        return $this->getTable();
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchType()
    {
        if (property_exists($this, 'documentType')) {
            return $this->documentType;
        }

        return Str::singular($this->getTable());
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSearchField()
    {
        if (! property_exists($this, 'defaultSearchField')) {
            return null;
        }

        return $this->defaultSearchField;
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentFields()
    {
        if (property_exists($this, 'documentFields')) {
            return $this->documentFields;
        }

        // If the user has not explicitly stated which properties
        // of the model they want discoverable to index, it is
        // assumed they wish to index all of them, so we do.
        return array_keys($this->attributesToArray());
    }

    /**
     * {@inheritdoc}
     */
    public function documentToArray()
    {
        return collect($this->toArray())
            ->filter(function ($value, $property) {
                return in_array($property, $this->getDocumentFields());
            })->toArray();
    }
}