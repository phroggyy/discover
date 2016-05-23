<?php

use Elasticsearch\Client;
use Mockery as m;
use Phroggyy\Discover\Contracts\Searchable;
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
                        'match' => ['foo' => 'bar'],
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
                    'match' => $query,
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
    }
}

class NonSearchableFoo
{

}

class SearchableFoo implements Searchable
{
    use \Phroggyy\Discover\Discoverable;

    public function getSearchIndex()
    {
        return 'foo';
    }

    public function getSearchType()
    {
        return 'bar';
    }
}
