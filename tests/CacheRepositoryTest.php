<?php namespace SebastianBerc\Repositories\Test;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
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
        $this->factory()->times(5)->create(CacheModelStub::class);
        $collection = $this->repository->all();

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertEquals($this->repository->all(), $collection);
    }

    /** @test */
    public function isShouldPaginateRecordsFromCache()
    {
        $this->factory()->times(50)->create(CacheModelStub::class);
        $paginator = $this->repository->paginate(10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals($this->repository->paginate(10), $paginator);
    }

    /** @test */
    public function itShouldReturnSpecifiedRecordFromCache()
    {
        $model = $this->factory()->create(CacheModelStub::class);

        $this->assertInstanceOf(CacheModelStub::class, $model);
        $this->assertEquals($this->repository->find($model->getKey()), $model);
    }

    /** @test */
    public function itShouldCreateNewRecordInCache()
    {
        $model = $this->repository->create([
            'email' => $this->fake()->email,
            'password' => 'secret',
            'remember_token' => md5(str_random())
        ]);

        $this->assertEquals($this->repository->find($model->getKey()), $model);
    }

    /** @test */
    public function itShouldUpdateSpecifiedRecordInCache()
    {
        $model   = $this->factory()->create(CacheModelStub::class);
        $updated = $this->repository->update($model->getKey(), ['password' => 'terces']);

        $this->assertEquals($this->repository->find($model->getKey()), $updated);
    }

    /** @test */
    public function itShouldDeleteSpecifiedRecordFromCache()
    {
        $model = $this->factory()->create(CacheModelStub::class);

        $this->repository->delete($model->getKey());

        $this->assertNull($this->repository->find($model->getKey()));
    }

    /** @test */
    public function itShouldFindRecordByHisField()
    {
        $this->factory()->times(15)->create(CacheModelStub::class);
        $model = $this->factory()->create(CacheModelStub::class);

        $finded = $this->repository->findBy('email', $model->email);

        $this->assertEquals($this->repository->findBy('email', $model->email), $finded);
    }

    /** @test */
    public function itShouldFindRecordWhereGivenFieldsAreMatch()
    {
        $this->factory()->times(15)->create(CacheModelStub::class);
        $model = $this->factory()->create(CacheModelStub::class);
        $this->repository->findWhere($wheres = ['email' => $model->email, 'password' => 'secret']);

        $finded = $this->repository->findWhere($wheres);

        $this->assertEquals($finded, $this->repository->findWhere($wheres));
    }

    /** @test */
    public function itShouldFindRecordsWhereGivenFieldsAreMatch()
    {
        $this->factory()->times(17)->create(CacheModelStub::class);

        $finded = $this->repository->where('password', 'secret');

        $this->assertEquals($this->repository->where('password', 'secret'), $finded);
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
    protected $fillable = ['email', 'password', 'remember_token'];

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
