<?php

namespace SebastianBerc\Repositories\Test;

use Illuminate\Database\Eloquent\Builder;
use SebastianBerc\Repositories\Criteria;

/**
 * Class CriteriaTest
 *
 * @author  Sebastian Berć <sebastian.berc@gmail.com>
 * @package SebastianBerc\Repositories\Test
 */
class CriteriaTest extends TestCase
{
    /**
     * @var RepositoryStub
     */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
    }

    /** @test */
    public function itShouldNarrowRepositoryResults()
    {
        $this->factory()->times(20)->create(User::class);

        $this->assertEquals(1, (new RepositoryStub($this->app))->citeria(new CriteriaStub())->all()->count());
    }

    /** @test */
    public function itShouldNarrowRepositoryResultsFromCache()
    {
        $this->factory()->times(20)->create(User::class);

        $this->assertEquals(1, (new CacheRepositoryStub($this->app))->citeria(new CriteriaStub())->all()->count());
    }
}

class CriteriaStub extends Criteria
{
    public function execute(Builder $query)
    {
        return $query->where(['id' => 1]);
    }
}
