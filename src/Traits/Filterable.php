<?php

namespace SebastianBerc\Repositories\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Class Filterable
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Traits
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

        $column = $this->repository->makeModel()->$relation()->getModel()->getTable() . '.' . $column;

        $this->instance = $this->instance->whereHas(
            $relation,
            function (Builder $builder) use ($column, $value) {
                $builder->where($column, "like", "%$value%");
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

        $this->instance = $this->instance->where($column, 'like', "%$value%");

        return $this;
    }
}
