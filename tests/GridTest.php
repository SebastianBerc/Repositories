<?php

namespace SebastianBerc\Repositories\Test;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use SebastianBerc\Repositories\Exceptions\InvalidRepositoryModel;
use SebastianBerc\Repositories\Repository;

/**
 * Class GridTest
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Test
 */
class GridTest extends TestCase
{
    /**
     * @var RepositoryStub
     */
    protected $repository;

    /**
     * @var OtherGridRepositoryStub
     */
    protected $otherRepository;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository      = new GridRepositoryStub($this->app);
        $this->otherRepository = new OtherGridRepositoryStub($this->app);
    }

    /** @test */
    public function itShouldReturnRepositoryInstance()
    {
        $this->assertEquals(GridRepositoryStub::class, get_class(GridRepositoryStub::instance()));
    }

    /** @test */
    public function itShouldFetchFirstCollectionPageFromDatabase()
    {
        $this->factory()->times(20)->create(User::class);

        $paginator = $this->repository->fetch(1, 5);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals(5, sizeof($paginator->items()));
    }

    /** @test */
    public function itShouldFetchFirstCollectionPageSortedDescendingByIdsFromDatabase()
    {
        $this->factory()->times(20)->create(User::class);

        $paginator = $this->repository->fetch(1, 5, ['*'], [], ['id' => 'desc']);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals(5, sizeof($paginator->items()));
        $this->assertEquals(20, current($paginator->items())->getKey());
        $this->assertEquals(16, last($paginator->items())->getKey());
    }

    /** @test */
    public function itShouldFetchFirstCollectionPageSortedDescendingByRelationFieldFromDatabase()
    {
        $this->factory()->times(3)->create(PasswordReset::class);
        $this->factory()->times(1)->create(PasswordReset::class, [
            'user_id' => $this->factory()->times(1)->create(User::class, ['email' => '00000@gmail.com'])->getKey()
        ]);
        $this->factory()->times(1)->create(PasswordReset::class, ['token' => '000a0a0ea0813aef2f6c6dfd3a49c546']);

        $paginator = $this->repository->fetch(1, 5, ['*'], [], ['password.token' => 'ASC']);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals(5, sizeof($paginator->items()));
        $this->assertEquals(5, current($paginator->items())->getKey());

        $paginator = $this->otherRepository->fetch(1, 5, ['*'], [], ['user.email' => 'DESC']);

        $this->assertEquals(5, sizeof($paginator->items()));
        $this->assertEquals(4, last($paginator->items())->getKey());
    }

    /** @test */
    public function isShouldFetchFirstCollectionPageFilteredByFieldFromDatabase()
    {
        $this->factory()->times(20)->create(User::class);
        $this->factory()->times(5)->create(User::class, ['password' => 'notSecret']);

        $paginator = $this->repository->fetch(1, 5, ['*'], ['password' => 'not']);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals(5, sizeof($paginator->items()));
        $this->assertEquals(5, $paginator->total());
    }

    /** @test */
    public function isShouldFetchFirstCollectionPageFilteredByRelationFieldFromDatabase()
    {
        $this->factory()->times(20)->create(PasswordReset::class);
        $this->factory()->times(5)->create(PasswordReset::class, ['token' => $token = md5('token')]);

        $paginator = $this->repository->fetch(1, 5, ['*'], ['password.token' => $token]);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals(5, sizeof($paginator->items()));
        $this->assertEquals(5, $paginator->total());
    }

    /** @test */
    public function itShouldReturnSimplePaginatedRecordsInDatabaseAsCollection()
    {
        $this->factory()->times(15)->create(User::class);

        $collection = $this->repository->simpleFetch(1, 5);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertEquals(5, $collection->count());
        $this->assertEquals(1, $collection->first()->getKey());
        $this->assertEquals(5, $collection->last()->getKey());
    }

    /** @test */
    public function itShouldThrowAnExceptionWhenBadObjectIsGiven()
    {
        $this->setExpectedException(InvalidRepositoryModel::class);

        (new BadRepositoryStub($this->app))->find(1);
    }

    /** @test */
    public function itShouldThrowExceptionWhenCallingBadMethod()
    {
        $this->setExpectedException(\BadMethodCallException::class);
        $this->repository->veryBadMethod();
    }
}

class GridRepositoryStub extends Repository
{
    public function takeModel()
    {
        return User::class;
    }
}

class OtherGridRepositoryStub extends Repository
{
    public function takeModel()
    {
        return PasswordReset::class;
    }
}

class User extends Model
{
    protected $fillable = ['email', 'password'];

    protected $table = 'users';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function password()
    {
        return $this->hasOne(PasswordReset::class, 'id');
    }
}

class PasswordReset extends Model
{
    protected $fillable = ['user_id', 'token'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

class BadGridRepositoryStub extends Repository
{
    public function takeModel()
    {
        return BadModelStub::class;
    }
}

class BadGridModelStub
{
    protected $table = 'users';
}
