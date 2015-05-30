<?php namespace SebastianBerc\Repositories\Managers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Pagination\LengthAwarePaginator;
use SebastianBerc\Repositories\Contracts\Gridable;

/**
 * Class GridManager
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Managers
 */
class GridManager extends RepositoryManager implements Gridable
{
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
    public function fetch($page, $perPage = 15, array $filter = [], array $sort = [], array $columns = ['*'])
    {
        if (!empty($filter)) {
            $this->multiFilterBy($filter);
        }

        if (!empty($sort)) {
            $this->multiSortBy($sort);
        }

        $options = [
            'path'  => $this->app->make('request')->url(),
            'query' => compact('page', 'perPage')
        ];

        $count = $this->count();
        $items = $this->instance->forPage($page, $perPage)->get($columns);

        return (new LengthAwarePaginator($items, $count, $perPage, $page, $options));
    }

    /**
     * Returns total count of whole collection.
     *
     * @return int
     */
    public function count()
    {
        return $this->instance->count('*');
    }

    /**
     * Append column filter to query builder.
     *
     * @param string|array $column
     * @param string       $value
     *
     * @return $this
     */
    public function filterBy($column, $value = null)
    {
        $this->instance = $this->instance->where($column, 'like', "%$value%");

        return $this;
    }

    /**
     * Append relation column filter to query builder.
     *
     * @param string $column
     * @param string $value
     *
     * @return $this
     */
    public function filterByRelation($column, $value = null)
    {
        list($relation, $column) = explode('.', $column);

        $column = $this->instance->$relation()->getModel()->getTable() . '.' . $column;

        $this->instance = $this->instance->whereHas(
            $relation,
            function (Builder $builder) use ($column, $value) {
                $builder->where($column, "like", "%$value%");
            }
        );

        return $this;
    }

    /**
     * Append many column filters to query builder.
     *
     * @param array $columns
     *
     * @return $this
     */
    public function multiFilterBy(array $columns)
    {
        foreach ($columns as $column => $value) {
            if (strpos($column, '.')) {
                $this->filterByRelation($column, $value);
            } else {
                $this->filterBy($column, $value);
            }
        }

        return $this;
    }

    /**
     * Append column sorting to query builder.
     *
     * @param string|array $column
     * @param string       $direction
     *
     * @return $this
     */
    public function sortBy($column, $direction = 'ASC')
    {
        $this->instance = $this->instance->orderBy($column, $direction);

        return $this;
    }

    /**
     * Append relation column sorting to query builder.
     *
     * @param string|array $column
     * @param string       $direction
     *
     * @return $this
     */
    public function sortByRelation($column, $direction = 'ASC')
    {
        list($relation, $column) = explode('.', $column);

        /** @var HasOne $relationClass */
        $relationClass = $this->instance->$relation();

        $this->instance = $this->instance->with($relation)->join(
            $relationClass->getModel()->getTable() . ' as ' . $relation,
            $relation . '.' . $this->instance->getForeignKey(),
            '=',
            $relationClass->getQualifiedParentKeyName()
        )
            ->orderBy("{$relation}.{$column}", $direction)
            ->select($this->instance->getTable() . '.*');

        return $this;
    }

    /**
     * Append many column sorting to query builder.
     *
     * @param array $columns
     *
     * @return $this
     */
    public function multiSortBy(array $columns)
    {
        foreach ($columns as $column => $direction) {
            if (strpos($column, '.')) {
                $this->sortByRelation($column, $direction);
            } else {
                $this->sortBy($column, $direction);
            }
        }

        return $this;
    }
}
