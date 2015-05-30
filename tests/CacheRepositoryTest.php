<?php namespace SebastianBerc\Repositories\Test;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use SebastianBerc\Repositories\Contracts\ShouldBeCached;
use SebastianBerc\Repositories\Exceptions\InvalidRepositoryModel;
use SebastianBerc\Repositories\Repository;

/**
 * Class CacheRepositoryTest
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Test
 */
class CacheRepositoryTest extends TestCase
{
    /**
     * @var BadCacheRepositoryStub
     */
    protected $repository;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->cache      = $this->app->make('cache.store');
        $this->repository = new CacheRepositoryStub($this->app);
    }

    /** @test */
    public function itShouldReturnRepositoryInstance()
    {
        $this->assertEquals(CacheRepositoryStub::class, get_class(CacheRepositoryStub::instance()));
    }

    /** @test */
    public function itShouldReturnAllRecordsFromCache()
    {
        $this->factory()->times(5)->create(ModelStub::class);
        $this->repository->all();

        $this->assertTrue($this->cache->has('users'));
        $this->assertEquals($this->repository->all(), $this->cache->get('users'));
    }

    /** @test */
    public function isShouldPaginateRecordsFromCache()
    {
        $this->factory()->times(50)->create(ModelStub::class);
        $this->repository->paginate(10);

        $cacheKey = "users.paginate.10";

        $this->assertTrue($this->cache->has($cacheKey));
        $this->assertEquals($this->repository->paginate(10), $this->cache->get($cacheKey));
    }

    /** @test */
    public function itShouldReturnSpecifiedRecordFromCache()
    {
        $this->factory()->create(ModelStub::class);

        $finded = $this->repository->find(1);

        $this->assertTrue($this->cache->has('users.1'));
        $this->assertEquals($finded, $this->cache->get('users.1'));
    }

    /** @test */
    public function itShouldCreateNewRecordInCache()
    {
        $this->repository->create(['email' => $this->fake()->email, 'password' => 'secret']);

        $this->assertTrue($this->cache->has('users.1'));
        $this->assertEquals($this->repository->find(1), $this->cache->get('users.1'));
    }

    /** @test */
    public function itShouldUpdateSpecifiedRecordInCache()
    {
        $model = $this->factory()->create(ModelStub::class);

        $this->repository->update($model->getKey(), ['password' => 'terces']);

        $this->assertTrue($this->cache->has("users.{$model->getKey()}"));
        $this->assertEquals($this->repository->find($model->getKey()), $this->cache->get("users.{$model->getKey()}"));
    }

    /** @test */
    public function itShouldDeleteSpecifiedRecordFromCache()
    {
        $model = $this->factory()->create(ModelStub::class);

        $this->repository->delete($model->getKey());

        $this->assertFalse($this->cache->has("users.{$model->getKey()}"));
        $this->assertNull($this->cache->get("users.{$model->getKey()}"));
    }

    /** @test */
    public function itShouldFindRecordByHisField()
    {
        $this->factory()->times(15)->create(ModelStub::class);
        $model = $this->factory()->create(ModelStub::class);
        $this->repository->findBy('email', $model->email);

        $finded   = $this->repository->findBy('email', $model->email);
        $wheres   = [
            'column'   => ['email' => $model->email],
            'operator' => '=',
            'value'    => null,
            'boolean'  => 'and',
            'columns'  => ['*']
        ];
        $cacheKey = 'users.' . md5(serialize($wheres));

        $this->assertTrue($this->cache->has($cacheKey));

        // In cache we will hold collection with one object (or more),
        // and repository is responsible to fetch first one.
        $this->assertEquals($finded, $this->cache->get($cacheKey)->first());
    }

    /** @test */
    public function itShouldFindRecordWhereGivenFieldsAreMatch()
    {
        $this->factory()->times(15)->create(ModelStub::class);
        $model = $this->factory()->create(ModelStub::class);
        $this->repository->findWhere($wheres = ['email' => $model->email, 'password' => 'secret']);

        $finded   = $this->repository->findWhere($wheres = ['email' => $model->email, 'password' => 'secret']);
        $wheres   = ['column' => $wheres, 'operator' => '=', 'value' => null, 'boolean' => 'and', 'columns' => ['*']];
        $cacheKey = 'users.' . md5(serialize($wheres));

        $this->assertTrue($this->cache->has($cacheKey));

        // In cache we will hold collection with one object (or more),
        // and repository is responsible to fetch first one.
        $this->assertEquals($finded, $this->cache->get($cacheKey)->first());
    }

    /** @test */
    public function itShouldFindRecordsWhereGivenFieldsAreMatch()
    {
        $this->factory()->times(17)->create(ModelStub::class);
        $this->repository->where('password', '=', 'secret', 'and');

        /** @var Collection $finded */
        $finded   = $this->repository->where('password', '=', 'secret', 'and');
        $wheres   = [
            'column'   => 'password',
            'operator' => '=',
            'value'    => 'secret',
            'boolean'  => 'and',
            'columns'  => ['*']
        ];
        $cacheKey = 'users.' . md5(serialize($wheres));

        $this->assertTrue($this->cache->has($cacheKey));
        $this->assertEquals($finded, $this->cache->get($cacheKey));
    }

    /** @test */
    public function itShouldThrowAnExceptionWhenBadObjectIsGiven()
    {
        $this->setExpectedException(InvalidRepositoryModel::class);

        (new BadCacheRepositoryStub($this->app))->find(1);
    }

    /** @test */
    public function itShouldThrowExceptionWhenCallingBadMethod()
    {
        $this->setExpectedException(\BadMethodCallException::class);

        $this->repository->veryBadMethod();
    }
}

class CacheRepositoryStub extends Repository implements ShouldBeCached
{
    public function takeModel()
    {
        return CacheModelStub::class;
    }
}

class CacheModelStub extends Model
{
    protected $fillable = ['email', 'password'];

    protected $table = 'users';
}

class BadCacheRepositoryStub extends Repository implements ShouldBeCached
{
    public function takeModel()
    {
        return BadCacheModelStub::class;
    }
}

class BadCacheModelStub
{
    protected $table = 'users';
}
