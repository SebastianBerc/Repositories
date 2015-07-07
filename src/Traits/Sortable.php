<?php

namespace SebastianBerc\Repositories\Traits;

use DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class Sortable
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Traits
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

        /** @var BelongsTo|HasOne $relationClass */
        $relationClass = $this->repository->makeModel()->$relation();

        switch (get_class($relationClass)) {
            case BelongsTo::class:
                $this->instance = $this->joinBelongsTo($relationClass);
                break;
            case HasOne::class:
                $this->instance = $this->joinHasOne($relationClass);
                break;
        }

        $this->instance = $this->instance->select(DB::raw("{$relationClass->getParent()->getTable()}.*"))
            ->orderBy("{$relationClass->getRelated()->getTable()}.{$column}", $direction);

        return $this;
    }

    /**
     * Join a belongs to relationship.
     *
     * @param BelongsTo $relation
     *
     * @return mixed
     */
    protected function joinBelongsTo(BelongsTo $relation)
    {
        return $this->instance->join(
            $relation->getRelated()->getTable(),
            $relation->getQualifiedOtherKeyName(),
            '=',
            $relation->getQualifiedForeignKey()
        );
    }

    /**
     * Join a has one relationship.
     *
     * @param HasOne $relation
     *
     * @return mixed
     */
    protected function joinHasOne(HasOne $relation)
    {
        return $this->instance->join(
            $relation->getRelated()->getTable(),
            $relation->getRelated()->getTable() . '.' . $relation->getParent()->getForeignKey(),
            '=',
            $relation->getParent()->getTable() . '.' . $relation->getParent()->getKeyName()
        );
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
