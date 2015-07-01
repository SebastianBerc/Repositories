<?php

namespace SebastianBerc\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Interface RepositoryInterface
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Contracts
 */
interface RepositoryInterface
{
    /**
     * Return fully qualified model class name.
     *
     * @return string
     */
    public function takeModel();

    /**
     * Return instance of Eloquent model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function makeModel();

    /**
     * Return instance of query builder for Eloquent model.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function makeQuery();

    /**
     * Get all of the models from the database.
     *
     * @param string[] $columns
     *
     * @return Collection
     */
    public function all(array $columns = ['*']);

    /**
     * Returns total count of whole collection.
     *
     * @return int
     */
    public function count();

    /**
     * Paginate the given query.
     *
     * @param int      $perPage
     * @param string[] $columns
     *
     * @return LengthAwarePaginator
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
     * @param int      $identifier
     * @param string[] $columns
     *
     * @return Eloquent
     */
    public function find($identifier, array $columns = ['*']);

    /**
     * Create a new basic where query clause on model.
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     * @param string $boolean
     *
     * @return mixed
     */
    public function where($column, $operator = '=', $value = null, $boolean = 'and');

    /**
     * Find a model by its specified column and value.
     *
     * @param mixed    $column
     * @param mixed    $value
     * @param string[] $columns
     *
     * @return Eloquent
     */
    public function findBy($column, $value, array $columns = ['*']);

    /**
     * Find a model by its specified columns and values.
     *
     * @param array    $wheres
     * @param string[] $columns
     *
     * @return Eloquent
     */
    public function findWhere(array $wheres, array $columns = ['*']);

    /**
     * Fetch collection ordered and filtrated by specified columns for specified page contained in paginator.
     *
     * @param int      $page
     * @param int      $perPage
     * @param array    $filter
     * @param array    $sort
     * @param string[] $columns
     *
     * @return LengthAwarePaginator
     */
    public function fetch($page = 1, $perPage = 15, array $columns = ['*'], array $filter = [], array $sort = []);

    /**
     * Fetch collection ordered and filtrated by specified columns for specified page.
     *
     * @param int      $page
     * @param int      $perPage
     * @param array    $filter
     * @param array    $sort
     * @param string[] $columns
     *
     * @return Collection
     */
    public function simpleFetch($page = 1, $perPage = 15, array $columns = ['*'], array $filter = [], array $sort = []);
}
