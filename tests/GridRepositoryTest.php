<?php namespace SebastianBerc\Repositories\Test;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use SebastianBerc\Repositories\Contracts\MayHaveGrid;
use SebastianBerc\Repositories\Exceptions\InvalidRepositoryModel;
use SebastianBerc\Repositories\Repository;

/**
 * Class GridRepositoryTest
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Test
 */
class GridRepositoryTest extends TestCase
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

        $this->repository = new GridRepositoryStub($this->app);
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

        $paginator = $this->repository->fetch(1, 5, [], ['id' => 'desc']);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals(5, sizeof($paginator->items()));
        $this->assertEquals(20, current($paginator->items())->getKey());
        $this->assertEquals(16, last($paginator->items())->getKey());
    }

    /** @test */
    public function itShouldFetchFirstCollectionPageSortedDescendingByRelationFieldFromDatabase()
    {
        $this->factory()->times(4)->create(PasswordReset::class);
        $this->factory()->times(1)->create(PasswordReset::class, ['token' => '000a0a0ea0813aef2f6c6dfd3a49c546']);

        $paginator = $this->repository->fetch(1, 5, [], ['password.token' => 'ASC']);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals(5, sizeof($paginator->items()));
        $this->assertEquals(5, current($paginator->items())->getKey());

        $paginator = $this->repository->fetch(1, 5, [], ['password.token' => 'DESC']);

        $this->assertEquals(5, sizeof($paginator->items()));
        $this->assertEquals(5, last($paginator->items())->getKey());
    }

    /** @test */
    public function isShouldFetchFirstCollectionPageFilteredByFieldFromDatabase()
    {
        $this->factory()->times(20)->create(User::class);
        $this->factory()->times(5)->create(User::class, ['password' => 'notSecret']);

        $paginator = $this->repository->fetch(1, 5, ['password' => 'not']);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals(5, sizeof($paginator->items()));
        $this->assertEquals(5, $paginator->total());
    }

    /** @test */
    public function isShouldFetchFirstCollectionPageFilteredByRelationFieldFromDatabase()
    {
        $this->factory()->times(20)->create(PasswordReset::class);
        $this->factory()->times(5)->create(PasswordReset::class, ['token' => $token = md5('token')]);

        $paginator = $this->repository->fetch(1, 5, ['password.token' => $token]);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals(5, sizeof($paginator->items()));
        $this->assertEquals(5, $paginator->total());
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

class GridRepositoryStub extends Repository implements MayHaveGrid
{
    public function takeModel()
    {
        return User::class;
    }
}

class User extends Model
{
    protected $fillable = ['email', 'password'];

    protected $table = 'users';

    public function password()
    {
        return $this->hasOne(PasswordReset::class, 'id');
    }
}

class PasswordReset extends Model
{
    protected $fillable = ['user_id', 'token'];
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
