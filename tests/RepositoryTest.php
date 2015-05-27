<?php namespace SebastianBerc\Repositories\Test;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use SebastianBerc\Repositories\Repository;

/**
 * Class RepositoryTest
 *
 * @author  Sebastian Berć <sebastian.berc@gmail.com>
 *
 * @package SebastianBerc\Repositories\Test
 */
class RepositoryTest extends TestCase
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

        $this->repository = new RepositoryStub($this->app);
    }

    /** @test */
    public function itShouldReturnAllRecordsFromDatabase()
    {
        $this->factory()->times(5)->create(ModelStub::class);

        $this->assertInstanceOf(Collection::class, $this->repository->all());
        $this->assertEquals(5, $this->repository->all()->count());
    }

    /** @test */
    public function isShouldPaginateRecordsFromDatabase()
    {
        $this->factory()->times(50)->create(ModelStub::class);

        $this->assertInstanceOf(LengthAwarePaginator::class, $this->repository->paginate(10));
        $this->assertEquals(1, $this->repository->paginate(10)->currentPage());
        $this->assertEquals(true, $this->repository->paginate(10)->hasMorePages());
        $this->assertEquals(50, $this->repository->paginate(10)->total());
        $this->assertEquals(5, $this->repository->paginate(10)->lastPage());
    }

    /** @test */
    public function itShouldReturnSpecifiedRecordFromDatabase()
    {
        $model  = $this->factory()->create(ModelStub::class);
        $finded = $this->repository->find($model->getKey());

        $this->assertInstanceOf(ModelStub::class, $finded);
        $this->assertEquals(
            array_only($finded->toArray(), ['email', 'password']),
            array_only($model->toArray(), ['email', 'password'])
        );
    }

    /** @test */
    public function itShouldUpdateSpecifiedRecordInDatabase()
    {
        $model = $this->factory()->create(ModelStub::class);

        $this->repository->update($model->getKey(), ['password' => 'terces']);

        $this->assertEquals('terces', $this->repository->find($model->getKey())->password);
    }

    /** @test */
    public function itShouldDeleteSpecifiedRecordFromDatabase()
    {
        $model = $this->factory()->create(ModelStub::class);

        $this->repository->delete($model->getKey());

        $this->assertNull($this->repository->find($model->getKey()));
    }

    /** @test */
    public function itShouldFindRecordByHisField()
    {
        $this->factory()->times(15)->create(ModelStub::class);

        $model  = $this->factory()->create(ModelStub::class);
        $finded = $this->repository->findBy('email', $model->email);

        $this->assertInstanceOf(ModelStub::class, $finded);
        $this->assertEquals(
            array_only($finded->toArray(), ['email', 'password']),
            array_only($model->toArray(), ['email', 'password'])
        );
    }

    /** @test */
    public function itShouldFindRecordWhereGivenFieldsAreMatch()
    {
        $this->factory()->times(15)->create(ModelStub::class);

        $model  = $this->factory()->create(ModelStub::class);
        $finded = $this->repository->findWhere(['email' => $model->email, 'password' => 'secret']);

        $this->assertInstanceOf(ModelStub::class, $finded);
        $this->assertEquals(
            array_only($finded->toArray(), ['email', 'password']),
            array_only($model->toArray(), ['email', 'password'])
        );
    }

    /** @test */
    public function itShouldFindRecordsWhereGivenFieldsAreMatch()
    {
        $this->factory()->times(17)->create(ModelStub::class);

        /** @var Collection $finded */
        $finded = $this->repository->where(['password' => 'secret'])->get();

        $this->assertInstanceOf(Collection::class, $finded);
        $this->assertEquals(17, $finded->count());
    }

    /** @test */
    public function itShouldThrowExceptionWhenCallingBadMethod()
    {
        $this->setExpectedException(\BadMethodCallException::class);
        $this->repository->veryBadMethod();
    }
}

class RepositoryStub extends Repository
{
    public function takeModel()
    {
        return 'SebastianBerc\Repositories\Test\ModelStub';
    }
}

class ModelStub extends Model
{
    protected $fillable = ['email', 'password'];

    protected $table = 'users';
}
