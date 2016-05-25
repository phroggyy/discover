<?php

namespace Phroggyy\Discover;

use Carbon\Carbon;
use Elasticsearch\Client;
use Phroggyy\Discover\Contracts\Searchable;

trait MigratesElasticSearchIndices
{
    /**
     * Migrate a new version of an index.
     *
     * @param \Phroggyy\Discover\Contracts\Searchable $model
     * @param  int $version
     * @param  array $properties
     * @param  int $shards
     * @param  int $replicas
     */
    public function migrateIndex(Searchable $model, int $version, array $properties, $shards = 2, $replicas = 1)
    {
        $client = app(Client::class);

        $alias = $model->getSearchIndex();
        $type = $model->getSearchType();

        $index = $alias.'-'.$version;

        $currentIndex = $alias.'-'.$version-1;

        $description = [
            'index' => $index,
            'body' => [
                'settings' => [
                    'index' => [
                        'number_of_shards' => $shards,
                        'number_of_replicas' => $replicas,
                    ],
                ],
                'mappings' => [
                    $type => [
                        'properties' => $properties,
                    ],
                ],
            ],
        ];

        $client->indices()->create($description);

        // Reindex the existing data if we're migrating


        if ($version > 1) {
            $timestamp = null;

            while (true) {

                if ($timestamp) {
                    $query = [
                        'filtered' => [
                            'query' => [
                                'match_all' => [],
                            ],
                            'filter' => [
                                'range' => [
                                    'created_at' => [
                                        'gte' => $timestamp,
                                    ],
                                ],
                            ],
                        ],
                    ];
                } else {
                    $query = ['match_all' => []];
                }
                $timestamp = Carbon::now()->format('Y-m-d H:i:s');

                $search = $client->search([
                    'search_type' => 'scan',
                    'scroll'      => '1m',
                    'size'        => 1000,
                    'index'       => $currentIndex,
                    'sort'        => ['_doc'],
                    'body'        => [
                        'query' => [
                            $query
                        ]
                    ]
                ]);

                $scrollId = $search['_scroll_id'];

                while (true) {
                    $response = $client->scroll([
                        'scroll_id' => $scrollId,
                        'scroll' => '1m',
                    ]);

                    if (! count($response['hits']['hits'])) {
                        break;
                    }

                    $scrollId = $response['_scroll_id'];

                    $results = array_map(function ($result) {
                        return ['create' => $result['_source']];
                    }, $response['hits']['hits']);


                    $client->bulk([
                        'index' => $index,
                        'type'  => $type,
                        'body'  => $results,
                    ]);
                }

                if ($scrollId == $search['_scroll_id']) {
                    break;
                }
            }

            $client->indices()->deleteAlias([
                'index' => $currentIndex,
                'name'  => $alias,
            ]);
        }

        $client->indices()->putAlias([
            'index' => $index,
            'name'  => $alias,
        ]);
    }
}