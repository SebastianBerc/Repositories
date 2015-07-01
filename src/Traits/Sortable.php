<?php

namespace SebastianBerc\Repositories\Traits;

/**
 * Class Sortable
 *
 * @author  Sebastian Berć <sebastian.berc@gmail.com>
 *
 * @package SebastianBerc\Repositories\Traits
 */
trait Sortable
{
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
}
