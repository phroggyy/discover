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
}
