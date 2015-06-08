<?php namespace SebastianBerc\Repositories;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Pagination\LengthAwarePaginator;
use SebastianBerc\Repositories\Contracts\RepositoryInterface;
use SebastianBerc\Repositories\Contracts\ShouldBeCached;
use SebastianBerc\Repositories\Contracts\TransformerInterface;
use SebastianBerc\Repositories\Exceptions\InvalidRepositoryModel;
use SebastianBerc\Repositories\Exceptions\InvalidTransformer;
use SebastianBerc\Repositories\Mediators\RepositoryMediator;
use SebastianBerc\Repositories\Traits\Filterable;
use SebastianBerc\Repositories\Traits\Sortable;

/**
 * Class Repositories
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories
 */
abstract class Repository implements RepositoryInterface
{
    use Filterable, Sortable;

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
    public $model;

    /**
     * Contains time of caching.
     *
     * @var int
     */
    public $lifetime;

    /**
     * Contains Transformer class instance.
     *
     * @var string
     */
    public $transformer;

    /**
     * Create a new RepositoryInterface instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app      = $app;
        $this->mediator = new RepositoryMediator($app, $this);
    }

    /**
     * Create a new RepositoryInterface instance.
     *
     * @return static
     */
    public static function instance()
    {
        $app = (function_exists('app') ? app() : Container::getInstance());

        return new static($app);
    }

    /**
     * Call an action on mediator.
     *
     * @param array $parameters
     *
     * @return mixed
     */
    public function mediator(array $parameters)
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]["function"];

        if ($this->shouldBeCached()) {
            return $this->mediator->cache($caller, $parameters);
        }

        return $this->mediator->database($caller, $parameters);
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
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function makeModel()
    {
        if (!($this->model = $this->app->make($this->takeModel())) instanceof Eloquent) {
            throw new InvalidRepositoryModel(get_class($this->model), Eloquent::class);
        }

        return $this->model;
    }

    /**
     * Return instance of query builder for Eloquent model.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function makeQuery()
    {
        return $this->makeModel()->query()->getQuery();
    }

    /**
     * Sets transformer to current repository instance.
     *
     * @param string $transformer
     *
     * @return static
     */
    public function setTransformer($transformer)
    {
        if (!(new \ReflectionClass($transformer))->implementsInterface(TransformerInterface::class)) {
            throw new InvalidTransformer();
        }

        $this->transformer = $transformer;

        return $this;
    }

    /**
     * Get all of the models from the database.
     *
     * @param string[] $columns
     *
     * @return Collection
     */
    public function all(array $columns = ['*'])
    {
        $collection = $this->mediator(func_get_args());

        return $this->transformer ? $this->mediator->transform($collection) : $collection;
    }

    /**
     * Create a new basic where query clause on model.
     *
     * @param string|array $column
     * @param string       $operator
     * @param mixed        $value
     * @param string       $boolean
     * @param string[]     $columns
     *
     * @return mixed
     */
    public function where($column, $operator = '=', $value = null, $boolean = 'and', array $columns = ['*'])
    {
        $collection = $this->mediator(func_get_args());

        return $this->transformer ? $this->mediator->transform($collection) : $collection;
    }

    /**
     * Paginate the given query.
     *
     * @param int      $perPage
     * @param string[] $columns
     *
     * @return LengthAwarePaginator
     */
    public function paginate($perPage = 15, array $columns = ['*'])
    {
        /** @var LengthAwarePaginator $paginator */
        $paginator = $this->mediator(func_get_args());

        return $this->transformer ? $this->mediator->transformPaginator($paginator) : $paginator;
    }

    /**
     * Save a new model and return the instance.
     *
     * @param array $attributes
     *
     * @return Eloquent
     */
    public function create(array $attributes = [])
    {
        $model = $this->mediator(func_get_args());

        return $this->transformer ? $this->mediator->transform($model)->first() : $model;
    }

    /**
     * Save or update the model in the database.
     *
     * @param mixed $identifier
     * @param array $attributes
     *
     * @return Eloquent
     */
    public function update($identifier, array $attributes = [])
    {
        $model = $this->mediator(func_get_args());

        return $this->transformer ? $this->mediator->transform($model)->first() : $model;
    }

    /**
     * Delete the model from the database.
     *
     * @param int $identifier
     *
     * @return boolean|null
     */
    public function delete($identifier)
    {
        return $this->mediator(func_get_args());
    }

    /**
     * Find a model by its primary key.
     *
     * @param int      $identifier
     * @param string[] $columns
     *
     * @return Eloquent
     */
    public function find($identifier, array $columns = ['*'])
    {
        $model = $this->mediator(func_get_args());

        return $this->transformer ? $this->mediator->transform($model)->first() : $model;
    }

    /**
     * Find a model by its specified column and value.
     *
     * @param mixed    $column
     * @param mixed    $value
     * @param string[] $columns
     *
     * @return Eloquent
     */
    public function findBy($column, $value, array $columns = ['*'])
    {
        $model = $this->mediator(func_get_args());

        return $this->transformer ? $this->mediator->transform($model)->first() : $model;
    }

    /**
     * Find a model by its specified columns and values.
     *
     * @param array    $wheres
     * @param string[] $columns
     *
     * @return Eloquent
     */
    public function findWhere(array $wheres, array $columns = ['*'])
    {
        $model = $this->mediator(func_get_args());

        return $this->transformer ? $this->mediator->transform($model)->first() : $model;
    }

    /**
     * Returns total count of whole collection.
     *
     * @return int
     */
    public function count()
    {
        return $this->mediator(func_get_args());
    }

    /**
     * Fetch collection ordered and filtrated by specified columns for specified page.
     *
     * @param int   $page
     * @param int   $perPage
     * @param array $filter
     * @param array $sort
     * @param array $columns
     *
     * @return LengthAwarePaginator
     */
    public function fetch($page = 1, $perPage = 15, array $columns = ['*'], array $filter = [], array $sort = [])
    {
        /** @var LengthAwarePaginator $paginator */
        $paginator = $this->mediator(func_get_args());

        return $this->transformer ? $this->mediator->transformPaginator($paginator) : $paginator;
    }

    /**
     * @param int   $page
     * @param int   $perPage
     * @param array $columns
     * @param array $filter
     * @param array $sort
     *
     * @return Collection
     */
    public function simpleFetch($page = 1, $perPage = 15, array $columns = ['*'], array $filter = [], array $sort = [])
    {
        $collection = $this->mediator(func_get_args());

        return $this->transformer ? $this->mediator->transform($collection) : $collection;
    }

    /**
     *
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
     * Dynamicaly calls method on model instance.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     * @throws InvalidRepositoryModel
     */
    public function __call($method, $parameters)
    {
        if (method_exists($model = $this->makeModel(), $method)) {
            return call_user_func_array([$model, $method], $parameters);
        }

        throw new \BadMethodCallException();
    }
}
