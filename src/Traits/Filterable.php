<?php

namespace SebastianBerc\Repositories\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Class Filterable.
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 */
trait Filterable
{
    /**
     * Append many column filters to query builder.
     *
     * @param array $columns
     *
     * @return $this
     */
    public function multiFilterBy($columns)
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

        $relation = camel_case($relation);

        $column = $this->repository->makeModel()->$relation()->getModel()->getTable() . '.' . $column;
        $like   = $this->repository->makeModel()->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $this->instance->whereHas(
            $relation,
            function (Builder $builder) use ($column, $value, $like) {
                if (in_array($value, ['true', 'false'])) {
                    $builder->where($column, $value === 'false' ? false : true);
                } else {
                    $builder->where($column, $like, "%$value%");
                }
            }
        );

        return $this;
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
        $column = $this->repository->makeModel()->getTable() . '.' . $column;
        $like   = $this->repository->makeModel()->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        if (in_array($value, ['true', 'false'])) {
            $this->instance->where($column, $value === 'false' ? false : true);
        } else {
            $this->instance->where($column, $like, "%$value%");
        }

        return $this;
    }
}
