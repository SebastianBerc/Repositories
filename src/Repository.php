<?php

namespace SebastianBerc\Repositories;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Pagination\LengthAwarePaginator;
use SebastianBerc\Repositories\Contracts\CriteriaInterface;
use SebastianBerc\Repositories\Contracts\RepositoryInterface;
use SebastianBerc\Repositories\Contracts\ShouldCache;
use SebastianBerc\Repositories\Contracts\TransformerInterface;
use SebastianBerc\Repositories\Exceptions\InvalidCriteria;
use SebastianBerc\Repositories\Exceptions\InvalidRepositoryModel;
use SebastianBerc\Repositories\Exceptions\InvalidTransformer;
use SebastianBerc\Repositories\Mediators\RepositoryMediator;
use SebastianBerc\Repositories\Services\CriteriaService;
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
     * Contains relations to eager load.
     *
     * @var array
     */
    public $with = [];

    /**
     * Contains time of caching.
     *
     * @var int
     */
    public $lifetime;

    /**
     * Contains transformer instance.
     *
     * @var string
     */
    public $transformer;

    /**
     * Contains repository mediator instance.
     *
     * @var RepositoryMediator
     */
    public $mediator;

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
        return new static(function_exists('app') ? app() : Container::getInstance());
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

        if ($this->shouldCache()) {
            return $this->mediator->cache($caller, $parameters);
        }

        return $this->mediator->database($caller, $parameters);
    }

    /**
     * Add criteria to repository query or return criteria service.
     *
     * @param CriteriaInterface|null $criteria
     *
     * @return CriteriaService|$this
     * @throws InvalidCriteria
     */
    public function criteria($criteria = null)
    {
        if (is_null($criteria)) {
            return $this->mediator->criteria();
        }

        if (!is_null($criteria) && !is_a($criteria, CriteriaInterface::class)) {
            throw new InvalidCriteria;
        }

        $this->mediator->criteria()->addCriteria($criteria);

        return $this;
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
     * @return Eloquent
     * @throws InvalidRepositoryModel
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
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function makeQuery()
    {
        $query = $this->makeModel()->query();

        if ($this->mediator->hasCriteria()) {
            $query = $this->mediator->criteria()->executeOn($this->makeModel()->query());
        }

        return empty($this->with) ? $query : $query->with($this->with);
    }

    /**
     * Adds relation to eager loads.
     *
     * @param string|string[] $relation
     *
     * @return static
     */
    public function with($relation)
    {
        if (func_num_args() == 1) {
            $this->with = array_merge($this->with, is_array($relation) ? $relation : [$relation]);
        } else {
            $this->with = array_merge($this->with, func_get_args());
        }

        return $this;
    }

    /**
     * Sets transformer to current repository instance.
     *
     * @param string $transformer
     *
     * @return static
     * @throws InvalidTransformer
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
        return $this->mediator->transform($this->mediator(func_get_args()));
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
        return $this->mediator->transform($this->mediator(func_get_args()));
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
        return $this->mediator->transformPaginator($this->mediator(func_get_args()));
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
        return $this->mediator->transform($this->mediator(func_get_args()))->first();
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
        return $this->mediator->transform($this->mediator(func_get_args()))->first();
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
        return $this->mediator->transform($this->mediator(func_get_args()))->first();
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
        return $this->mediator->transform($this->mediator(func_get_args()))->first();
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
        return $this->mediator->transform($this->mediator(func_get_args()))->first();
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
        return $this->mediator->transformPaginator($this->mediator(func_get_args()));
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
        return $this->mediator->transform($this->mediator(func_get_args()));
    }

    /**
     * Determinate if repository should be cached.
     *
     * @return bool
     */
    protected function shouldCache()
    {
        if ($this instanceof ShouldCache) {
            return true;
        }

        return (new \ReflectionClass($this))->implementsInterface(ShouldCache::class);
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
