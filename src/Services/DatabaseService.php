<?php

namespace SebastianBerc\Repositories\Services;

use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Pagination\LengthAwarePaginator;
use SebastianBerc\Repositories\Contracts\ServiceInterface;
use SebastianBerc\Repositories\Repository;
use SebastianBerc\Repositories\Traits\Filterable;
use SebastianBerc\Repositories\Traits\Sortable;

/**
 * Class DatabaseService.
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 */
class DatabaseService implements ServiceInterface
{
    use Filterable, Sortable;

    /**
     * Contains Laravel Application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Contains a repository instance.
     *
     * @var Repository
     */
    protected $repository;

    /**
     * Contains model instance for fetch, and simple fetch methods.
     *
     * @var mixed
     */
    protected $instance;

    /**
     * Create a new database service instance.
     *
     * @param Application $app
     * @param Repository  $repository
     */
    public function __construct(Application $app, Repository $repository)
    {
        $this->app        = $app;
        $this->repository = $repository;
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
        return $this->repository->makeQuery()->get($columns);
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
     * @return Collection
     */
    public function where($column, $operator = '=', $value = null, $boolean = 'and', array $columns = ['*'])
    {
        return $this->repository->makeQuery()->where($column, $operator, $value, $boolean, $columns)->get($columns);
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
        return $this->repository->makeQuery()->paginate($perPage, $columns);
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
        return $this->repository->makeModel()->create($attributes);
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
        $instance = $identifier instanceof Eloquent
            ? $identifier
            : $this->repository->makeQuery()->findOrFail($identifier);

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
     * @return bool|null
     */
    public function delete($identifier)
    {
        return $this->repository->makeQuery()
            ->findOrFail($identifier, [$this->repository->makeModel()->getKeyName()])
            ->delete();
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
        return $this->repository->makeQuery()->find($identifier, $columns);
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
     * Find a model by its specified columns and values presented as array.
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
     * Find a models by its primary key.
     *
     * @param array    $identifiers
     * @param string[] $columns
     *
     * @return Collection
     */
    public function findMany($identifiers, array $columns = ['*'])
    {
        return $this->repository->makeQuery()->findMany($identifiers, $columns);
    }

    /**
     * Search the models in search of words in a given phrase to the specified columns.
     *
     * @param string $search
     * @param array  $columns
     * @param float  $threshold
     *
     * @return Builder
     */
    public function search($search, array $columns = [], $threshold = null)
    {
        $searchable = empty($columns) ? $columns : $this->repository->getSearchableFields();
        $service    = new SearchService($this->repository->makeQuery(), $searchable, $threshold);

        return $service->search($search);
    }

    /**
     * Returns total count of whole collection.
     *
     * @return int
     */
    public function count()
    {
        $countBy = "{$this->repository->makeModel()->getTable()}.{$this->repository->makeModel()->getKeyName()}";

        return $this->repository->makeQuery()->count($countBy);
    }

    /**
     * Fetch collection ordered and filtrated by specified columns for specified page as paginator.
     *
     * @param int    $page
     * @param int    $perPage
     * @param array  $columns
     * @param array  $filter
     * @param array  $sort
     * @param string $search
     *
     * @return LengthAwarePaginator
     */
    public function fetch(
        $page = 1,
        $perPage = 15,
        array $columns = ['*'],
        array $filter = [],
        array $sort = [],
        $search = null
    ) {
        if (empty($search)) {
            $this->instance = $this->repository->makeQuery();
            $this->multiFilterBy($filter)->multiSortBy($sort);
        } else {
            $this->instance = $this->repository->search($search);
        }

        $this->parseAliases($this->instance);

        $count = $this->countResults($this->instance);
        $items = $this->instance->forPage($page, $perPage)->get($columns);

        $options = [
            'path'  => $this->app->make('request')->url(),
            'query' => compact('page', 'perPage'),
        ];

        return new LengthAwarePaginator($items, $count, $perPage, $page, $options);
    }

    /**
     * Fetch collection ordered and filtrated by specified columns for specified page.
     *
     * @param int    $page
     * @param int    $perPage
     * @param array  $columns
     * @param array  $filter
     * @param array  $sort
     * @param string $search
     *
     * @return Collection
     */
    public function simpleFetch(
        $page = 1,
        $perPage = 15,
        array $columns = ['*'],
        array $filter = [],
        array $sort = [],
        $search = null
    ) {
        if (is_null($search)) {
            $this->instance = $this->repository->makeQuery();
            $this->multiFilterBy($filter)->multiSortBy($sort);
        } else {
            $this->instance = $this->repository->search($search);
        }

        $this->parseAliases($this->instance);

        return $this->instance->forPage($page, $perPage)->get($columns);
    }

    /**
     * Counts results for given query.
     *
     * @param Builder $query
     *
     * @return int
     */
    protected function countResults(Builder $query)
    {
        $query = clone $query;

        $query->getQuery()->aggregate = ['function' => 'count', 'columns' => $columns = ['*']];
        $query->getQuery()->orders    = null;

        $results = $query->getQuery()->get($columns);

        if (isset($results[0])) {
            return array_change_key_case((array) $results[0])['aggregate'];
        }
    }

    /**
     * Replace alias name in where closure with sub-queries from select.
     *
     * Example:
     *
     * SELECT table.*, (SELECT 1 FROM other_table ot WHERE ot.id = table.id) AS exists
     * WHERE exists = 1;
     *
     * Will be converted to:
     *
     * SELECT table.*, (SELECT 1 FROM other_table ot WHERE ot.id = table.id) AS exists
     * WHERE (SELECT 1 FROM other_table ot WHERE ot.id = table.id) = 1;
     *
     * @param Builder $query
     *
     * @return Builder
     */
    protected function parseAliases(Builder $query)
    {
        $aliases = [];

        if (!empty($query->getQuery()->columns)) {
            $aliases = $this->getAliases($query);
        }

        if (!empty($aliases) && !empty($query->getQuery()->wheres)) {
            $this->replaceAliases($query, $aliases);
        }

        return $query;
    }

    /**
     * Get sub queries and aliases from select statement.
     *
     * @param Builder $query
     *
     * @return array
     */
    protected function getAliases(Builder $query)
    {
        $aliases = [];

        foreach ($query->getQuery()->columns as $column) {
            if (preg_match("~AS (\w+)~i", $column, $matches)) {
                $aliases[$matches[1]] = \DB::raw(str_replace($matches[0], '', $column));
            }
        }

        return $aliases;
    }

    /**
     * Replace aliases in where statement with sub queries.
     *
     * @param Builder $query
     * @param array   $aliases
     *
     * @return void
     */
    protected function replaceAliases(Builder $query, $aliases)
    {
        foreach ($query->getQuery()->wheres as $key => $value) {
            if (in_array($value['column'], array_keys($aliases))) {
                $query->getQuery()->wheres[$key]['column'] = $aliases[$value['column']];
            }
        }
    }
}
