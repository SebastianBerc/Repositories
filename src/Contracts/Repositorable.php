<?php namespace SebastianBerc\Repositories\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Interface Repositorable
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Contracts
 */
interface Repositorable
{
    /**
     * Get all of the models from the database.
     *
     * @param array $columns
     *
     * @return Collection
     */
    public function all(array $columns = ['*']);

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
    public function where($column, $operator = '=', $value = null, $boolean = 'and');

    /**
     * Paginate the given query.
     *
     * @param int   $perPage
     * @param array $columns
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 15, array $columns = ['*']);

    /**
     * Save a new model and return the instance.
     *
     * @param array $attributes
     *
     * @return Eloquent
     */
    public function create(array $attributes = []);

    /**
     * Save or update the model in the database.
     *
     * @param mixed $identifier
     * @param array $attributes
     *
     * @return Eloquent|null
     */
    public function update($identifier, array $attributes = []);

    /**
     * Delete the model from the database.
     *
     * @param int $identifier
     *
     * @return bool
     */
    public function delete($identifier);

    /**
     * Find a model by its primary key.
     *
     * @param int   $identifier
     * @param array $columns
     *
     * @return Eloquent
     */
    public function find($identifier, array $columns = ['*']);

    /**
     * Find a model by its specified column and value.
     *
     * @param mixed $column
     * @param mixed $value
     * @param array $columns
     *
     * @return Eloquent
     */
    public function findBy($column, $value, array $columns = ['*']);

    /**
     * Find a model by its specified columns and values.
     *
     * @param array $wheres
     * @param array $columns
     *
     * @return Eloquent
     */
    public function findWhere(array $wheres, array $columns = ['*']);
}
