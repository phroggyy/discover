<?php

use Elasticsearch\Client;
use Mockery as m;
use Phroggyy\Discover\Contracts\Searchable;
use Phroggyy\Discover\Discoverable;
use Phroggyy\Discover\Services\ElasticSearchService;

class ElasticSearchServiceTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ElasticSearchService
     */
    protected $elasticService;

    public function setUp()
    {
        $client = m::mock(Client::class);
        $client->shouldReceive('search')
            ->with([
                'index' => 'foo',
                'type'  => 'bar',
                'body'  => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                [
                                    'match' => ['foo' => 'bar']
                                ],
                            ],
                        ],
                    ],
                ],
            ])->andReturn([
                ['foo' => 'bar']
            ]);

        $this->elasticService = new ElasticSearchService($client);
    }

    public function testItFindsNestedIndices()
    {
        $this->assertTrue($this->elasticService->indexIsNested('Foo/bar'));
    }

    /**
     * @expectedException \Phroggyy\Discover\Contracts\Exceptions\NoNestedIndexException
     */
    public function testItFailsOnNonNestedIndices()
    {
        $this->elasticService->retrieveNestedIndex('Foo');
    }

    /**
     * @expectedException \Phroggyy\Discover\Contracts\Exceptions\ClassNotFoundException
     */
    public function testItFailsOnInvalidNestedIndices()
    {
        $this->elasticService->retrieveNestedIndex('Foo/bar');
    }

    /**
     * @expectedException \Phroggyy\Discover\Contracts\Exceptions\NonSearchableClassException
     */
    public function testItDoesNotAcceptNonSearchableClasses()
    {
        $this->elasticService->retrieveNestedIndex('NonSearchableFoo/bar');
    }

    public function testItRetrievesANestedIndex()
    {
        $result = $this->elasticService->retrieveNestedIndex('SearchableFoo/bar');
        $this->assertEquals(['foo', 'bar'], $result);
    }

    public function testItBuildsACorrectSearchQuery()
    {
        $query = ['foo' => 'bar'];

        $truth = [
            'index' => 'foo',
            'type'  => 'bar',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'match' => $query
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($truth, $this->elasticService->buildSearchQuery(new SearchableFoo, $query));
    }

    public function testItPerformsASearch()
    {
        $this->assertEquals([
            ['foo' => 'bar']
        ], $this->elasticService->search(new SearchableFoo, ['foo' => 'bar']));
        $this->assertEquals([
            ['foo' => 'bar'],
        ], $this->elasticService->search(new SearchableFoo, 'bar'));
    }

    public function testItBuildsANestedSearchQuery()
    {
        $truth = [
            'index' => 'foo',
            'type'  => 'bar',
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'nested' => [
                                'path' => 'foobar',
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            [
                                                'match' => ['foobar.foo' => 'bar'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($truth, $this->elasticService->buildSearchQuery(new SearchableFooBar, ['foo' => 'bar']));
    }
}

class NonSearchableFoo
{

}

class SearchableFoo implements Searchable
{
    use Discoverable;

    protected $documentIndex = 'foo';

    protected $documentType = 'bar';

    protected $defaultSearchField = 'foo';
}

class SearchableFooBar implements Searchable
{
    use Discoverable;

    protected $documentIndex = SearchableFoo::class.'/foobar';

}