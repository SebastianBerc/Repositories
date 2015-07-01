<?php

namespace SebastianBerc\Repositories\Test;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use SebastianBerc\Repositories\Exceptions\InvalidRepositoryModel;
use SebastianBerc\Repositories\Exceptions\InvalidTransformer;
use SebastianBerc\Repositories\Repository;
use SebastianBerc\Repositories\Transformer;

/**
 * Class TransformRepositoryResultTest
 *
 * @author  Sebastian Berć <sebastian.berc@gmail.com>
 *
 * @package SebastianBerc\Repositories\Test
 */
class TransformRepositoryResultTest extends TestCase
{
    /**
     * @var RepositoryTransformStub
     */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository = new RepositoryTransformStub($this->app);
    }

    /** @test */
    public function itShouldReturnRepositoryInstance()
    {
        $this->assertEquals(RepositoryTransformStub::class, get_class(RepositoryTransformStub::instance()));
    }

    /** @test */
    public function itShouldReturnAllRecordsFromDatabase()
    {
        $this->factory()->times(5)->create(ModelTransformStub::class);

        $this->assertInstanceOf(Collection::class, $this->repository->all());
        $this->assertEquals(5, $this->repository->all()->count());
        $this->assertEquals('*****', $this->repository->all()->first()->password);
    }

    /** @test */
    public function isShouldPaginateRecordsFromDatabase()
    {
        $this->factory()->times(50)->create(ModelTransformStub::class);

        $paginator = $this->repository->paginate(10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals(1, $paginator->currentPage());
        $this->assertEquals(true, $paginator->hasMorePages());
        $this->assertEquals(50, $paginator->total());
        $this->assertEquals(5, $paginator->lastPage());
        $this->assertEquals('*****', $paginator->items()[0]['password']);
    }

    /** @test */
    public function isShouldFetchFirstCollectionPageFilteredByRelationFieldFromDatabase()
    {
        $this->factory()->times(50)->create(ModelTransformStub::class);

        $paginator = $this->repository->fetch(1, 5);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals(5, sizeof($paginator->items()));
        $this->assertEquals(50, $paginator->total());
        $this->assertEquals('*****', $paginator->items()[0]['password']);
    }

    /** @test */
    public function itShouldReturnSimplePaginatedRecordsInDatabaseAsCollection()
    {
        $this->factory()->times(15)->create(ModelTransformStub::class);

        $collection = $this->repository->simpleFetch(1, 5);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertEquals(5, $collection->count());
        $this->assertEquals(1, $collection->first()->getKey());
        $this->assertEquals(5, $collection->last()->getKey());
        $this->assertEquals('*****', $collection->first()->password);
    }

    /** @test */
    public function itShouldReturnSpecifiedRecordFromDatabase()
    {
        $model  = $this->factory()->create(ModelTransformStub::class);
        $finded = $this->repository->find($model->getKey());

        $this->assertInstanceOf(ModelTransformStub::class, $finded);
        $this->assertEquals('*****', $finded->password);
    }

    /** @test */
    public function itShouldCreateNewRecordInDatabase()
    {
        $created = $this->repository->create(['email' => $this->fake()->email, 'password' => 'secret']);

        $this->assertEquals(true, $created->exists);
        $this->assertEquals('*****', $created->password);
    }

    /** @test */
    public function itShouldUpdateSpecifiedRecordInDatabase()
    {
        $model   = $this->factory()->create(ModelTransformStub::class);
        $updated = $this->repository->update($model->getKey(), ['password' => 'terces']);

        $this->assertEquals('*****', $updated->password);
    }

    /** @test */
    public function itShouldDeleteSpecifiedRecordFromDatabase()
    {
        $model = $this->factory()->create(ModelTransformStub::class);

        $this->repository->delete($model->getKey());

        $this->assertNull($this->repository->find($model->getKey()));
    }

    /** @test */
    public function itShouldFindRecordByHisField()
    {
        $this->factory()->times(15)->create(ModelTransformStub::class);

        $model  = $this->factory()->create(ModelTransformStub::class);
        $finded = $this->repository->findBy('email', $model->email);

        $this->assertInstanceOf(ModelTransformStub::class, $finded);
        $this->assertEquals($model->email, $finded->email);
        $this->assertEquals('*****', $finded->password);
    }

    /** @test */
    public function itShouldFindRecordWhereGivenFieldsAreMatch()
    {
        $this->factory()->times(15)->create(ModelTransformStub::class);

        $model  = $this->factory()->create(ModelTransformStub::class);
        $finded = $this->repository->findWhere(['email' => $model->email, 'password' => 'secret']);

        $this->assertInstanceOf(ModelTransformStub::class, $finded);
        $this->assertEquals($model->email, $finded->email);
        $this->assertEquals('*****', $finded->password);
    }

    /** @test */
    public function itShouldFindRecordsWhereGivenFieldsAreMatch()
    {
        $this->factory()->times(17)->create(ModelTransformStub::class);

        /** @var Collection $finded */
        $finded = $this->repository->where(['password' => 'secret']);

        $this->assertInstanceOf(Collection::class, $finded);
        $this->assertEquals(17, $finded->count());
        $this->assertEquals('*****', $finded->first()->password);
    }

    /** @test */
    public function itShouldReturnTotalCountOfRecordsInDatabase()
    {
        $this->factory()->times(21)->create(ModelTransformStub::class);

        $this->assertEquals(21, $this->repository->count());
    }

    /** @test */
    public function itShouldTransforRepositoryResults()
    {
        $this->factory()->create(ModelTransformStub::class);

        $finded = $this->repository->setTransformer(ExampleEmail::class)->find(1);

        $this->assertInstanceOf(ModelTransformStub::class, $finded);
        $this->assertEquals('example@example.com', $finded->email);
    }

    /** @test */
    public function itShouldCallMethodOnModel()
    {
        $this->assertEquals('users', $this->repository->getTable());
    }

    /** @test */
    public function itShouldThrowAnExceptionWhenBadObjectIsGiven()
    {
        $this->setExpectedException(InvalidRepositoryModel::class);

        (new BadRepositoryTransformStub($this->app))->find(1);
    }

    /** @test */
    public function itShouldThrowAnExceptionWhenBadTransformerIsDeclared()
    {
        $this->factory()->create(ModelTransformStub::class);
        $repository = new RepositoryWithBadTransformStub($this->app);

        $this->setExpectedException(InvalidTransformer::class);
        $repository->find(1);
    }

    /** @test */
    public function itShouldThrowAnExceptionWhenBadTransformerIsGiven()
    {
        $this->setExpectedException(InvalidTransformer::class);

        $this->repository->setTransformer(ModelTransformStub::class);
    }

    /** @test */
    public function itShouldThrowExceptionWhenCallingBadMethod()
    {
        $this->setExpectedException(\BadMethodCallException::class);
        $this->repository->veryBadMethod();
    }
}

class RepositoryTransformStub extends Repository
{
    public $transformer = HidePassword::class;

    public function takeModel()
    {
        return ModelTransformStub::class;
    }
}

class HidePassword extends Transformer
{
    public function transform($item)
    {
        $item->password = '*****';

        return $item;
    }
}

class ExampleEmail extends Transformer
{
    public function transform($item)
    {
        $item->email = 'example@example.com';

        return $item;
    }
}

class ModelTransformStub extends Model
{
    protected $fillable = ['email', 'password'];

    protected $table = 'users';
}

class BadRepositoryTransformStub extends Repository
{
    public function takeModel()
    {
        return BadModelTransformStub::class;
    }
}

class RepositoryWithBadTransformStub extends Repository
{
    public $transformer = ModelTransformStub::class;

    public function takeModel()
    {
        return ModelTransformStub::class;
    }
}

class BadModelTransformStub
{
    protected $table = 'users';
}
