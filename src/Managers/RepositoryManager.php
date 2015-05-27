<?php namespace SebastianBerc\Repositories\Managers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as Eloquent;
use SebastianBerc\Repositories\Contracts\Repositorable;

/**
 * Class RepositoryManager
 *
 * @author  Sebastian Berć <sebastian.berc@gmail.com>
 *
 * @package SebastianBerc\Repositories\Managers
 */
class RepositoryManager implements Repositorable
{
    /**
     * Contains Eloquent model instance.
     *
     * @var Eloquent|Builder Model instance.
     */
    private $instance;

    /**
     * Create a new RepositoryManager instance.
     * @param Eloquent $modelInstance
     */
    public function __construct($modelInstance)
    {
        $this->instance = $modelInstance;
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
        return $this->instance->all($columns);
    }

    /**
     * Create a new basic where query clause on model.
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     * @param string $boolean
     *
     * @return Builder
     */
    public function where($column, $operator = '=', $value = null, $boolean = 'and')
    {
        return $this->instance->where($column, $operator, $value, $boolean);
    }

    /**
     * Paginate the given query.
     *
     * @param int   $perPage
     * @param string[] $columns
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 15, array $columns = ['*'])
    {
        return $this->instance->paginate($perPage, $columns);
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
        return $this->instance->create($attributes);
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
        $instance = $this->find($identifier);
        $instance->update($attributes);

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
        return $this->find($identifier, [$this->instance->getKeyName()])->delete();
    }

    /**
     * Find a model by its primary key.
     *
     * @param int   $identifier
     * @param string[] $columns
     *
     * @return Eloquent
     */
    public function find($identifier, array $columns = ['*'])
    {
        return $this->instance->find($identifier, $columns);
    }

    /**
     * Find a model by its specified column and value.
     *
     * @param mixed $column
     * @param mixed $value
     * @param string[] $columns
     *
     * @return Eloquent
     */
    public function findBy($column, $value, array $columns = ['*'])
    {
        return $this->instance->where($column, $value)->first($columns);
    }

    /**
     * Find a model by its specified columns and values.
     *
     * @param array $wheres
     * @param string[] $columns
     *
     * @return Eloquent
     */
    public function findWhere(array $wheres, array $columns = ['*'])
    {
        return $this->instance->where($wheres)->first($columns);
    }
}
