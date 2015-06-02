<?php namespace SebastianBerc\Repositories;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as Eloquent;
use SebastianBerc\Repositories\Contracts\RepositoryInterface;
use SebastianBerc\Repositories\Exceptions\InvalidRepositoryModel;
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
    protected $instance;

    /**
     * Create a new Repository instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Create a new Repository instance.
     *
     * @return static
     */
    public static function instance()
    {
        $app = (function_exists('app') ? app() : Container::getInstance());

        return new static($app);
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
    protected function makeModel()
    {
        if (!($this->instance = $this->app->make($this->takeModel())) instanceof Eloquent) {
            throw new InvalidRepositoryModel(get_class($this->instance), Eloquent::class);
        }

        return $this->instance;
    }

    /**
     * Return instance of query builder for Eloquent model.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function makeQuery()
    {
        return $this->makeModel()->query()->getQuery();
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
        return $this->makeModel()->all($columns);
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
        return $this->makeModel()->query()->where($column, $operator, $value, $boolean, $columns);
    }

    /**
     * Paginate the given query.
     *
     * @param int      $perPage
     * @param string[] $columns
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 15, array $columns = ['*'])
    {
        return $this->makeModel()->query()->paginate($perPage, $columns);
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
        return $this->makeModel()->create($attributes);
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
        $instance = ($identifier instanceof Eloquent ? $identifier : $this->find($identifier));

        $instance->fill($attributes);

        if ($instance->isDirty()) {
            $instance->save();
        }

        return $instance;
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
        return $this->find($identifier, [$this->makeModel()->getKeyName()])->delete();
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
        return $this->makeModel()->find($identifier, $columns);
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
        return $this->where([$column => $value], '=', null, 'and', $columns)->first();
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
        return $this->where($wheres, '=', null, 'and', $columns)->first();
    }

    /**
     * Returns total count of whole collection.
     *
     * @return int
     */
    public function count()
    {
        $countBy = "{$this->makeModel()->getTable()}.{$this->makeModel()->getKeyName()}";

        return $this->makeQuery()->count($countBy);
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
        $this->instance = $this->makeModel();

        $this->multiFilterBy($filter)->multiSortBy($sort);

        $count   = $this->instance->count();
        $items   = $this->instance->forPage($page, $perPage)->get($columns);

        $options = [
            'path'  => $this->app->make('request')->url(),
            'query' => compact('page', 'perPage')
        ];

        return (new LengthAwarePaginator($items, $count, $perPage, $page, $options));
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
        $this->instance = $this->makeModel();

        $this->multiFilterBy($filter)->multiSortBy($sort);

        return $this->instance->forPage($page, $perPage)->get($columns);
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
