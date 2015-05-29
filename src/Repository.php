<?php namespace SebastianBerc\Repositories;

use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Query\Builder;
use SebastianBerc\Repositories\Contracts\ShouldBeCached;
use SebastianBerc\Repositories\Contracts\MayHaveGrid;
use SebastianBerc\Repositories\Exceptions\InvalidRepositoryModel;
use SebastianBerc\Repositories\Managers\CacheGridManager;
use SebastianBerc\Repositories\Managers\CacheRepositoryManager;
use SebastianBerc\Repositories\Managers\GridManager;
use SebastianBerc\Repositories\Managers\RepositoryManager;

/**
 * Class Repositories
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories
 *
 * @see    RepositoryManager
 * @method static               applyCriteria()
 * @method Collection           all(array $columns = ['*'])
 * @method Builder              where($column, $operator = '=', $value = null, $boolean = 'and')
 * @method LengthAwarePaginator paginate($perPage = 15, array $columns = ['*'])
 * @method Eloquent             create(array $attributes = [])
 * @method Eloquent|null        update($identifier, array $attributes = [])
 * @method bool                 delete($identifier)
 * @method Eloquent             find($identifier, array $columns = ['*'])
 * @method Eloquent             findBy($column, $value, array $columns = ['*'])
 * @method Eloquent             findWhere(array $wheres, array $columns = ['*'])
 *
 * @see    GridManager
 * @method LengthAwarePaginator fetch($page, $perPage = 15, $filter = [], $sort = [], $columns = ['*'])
 * @method int                  count()
 */
abstract class Repository
{
    /**
     * Contains Laravel Application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Contains Eloquent model instance.
     *
     * @var Eloquent
     */
    protected $instance;

    /**
     * Create a new Repository instance.
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->makeModel();
    }

    /**
     * Return fully qualified model class name.
     *
     * @return string
     */
    abstract public function takeModel();

    /**
     * Return instance of Eloquent model.
     *
     * @return static
     */
    protected function makeModel()
    {
        $this->instance = $this->app->make($this->takeModel());

        if (!$this->instance instanceof Eloquent) {
            throw new InvalidRepositoryModel(get_class($this->instance), Eloquent::class);
        }

        return $this;
    }

    /**
     * Return a new RepositoryManager instance.
     *
     * @return RepositoryManager
     */
    protected function manager()
    {
        if ($this->mayHaveGrid()) {
            return new GridManager($this->app, $this->instance);
        }

        return new RepositoryManager($this->app, $this->instance);
    }

    /**
     * Return a new CacheRepositoryManager instance.
     *
     * @return CacheRepositoryManager
     */
    protected function cache()
    {
        if ($this->mayHaveGrid()) {
            return new CacheGridManager($this->app, $this->instance);
        }

        return new CacheRepositoryManager($this->app, $this->instance);
    }

    /**
     * Determine if the repository should be cached.
     *
     * @return bool
     */
    protected function shouldBeCached()
    {
        if ($this instanceof ShouldBeCached) {
            return true;
        }

        return (new \ReflectionClass($this))->implementsInterface(ShouldBeCached::class);
    }

    /**
     * Determine if the repository may have grid.
     *
     * @return bool
     */
    protected function mayHaveGrid()
    {
        if ($this instanceof MayHaveGrid) {
            return true;
        }

        return (new \ReflectionClass($this))->implementsInterface(MayHaveGrid::class);
    }

    /**
     * Dynamicly call methods on managers.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($method, $args)
    {
        if ($this->isManagerMethod($method)) {
            return $this->delegateToManager($method, $args);
        }

        throw new \BadMethodCallException();
    }

    /**
     * Determine if dynamicaly method belongs to managers.
     *
     * @param string $method
     *
     * @return bool
     */
    protected function isManagerMethod($method)
    {
        return method_exists($this->manager(), $method);
    }

    /**
     * Delegates method execution to proper manager.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    protected function delegateToManager($method, $args)
    {
        if ($this->shouldBeCached()) {
            return call_user_func_array([$this->cache(), $method], $args);
        }

        return call_user_func_array([$this->manager(), $method], $args);
    }
}
