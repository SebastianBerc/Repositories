<?php

namespace SebastianBerc\Repositories\Traits;

use DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Class Sortable.
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
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
     * @param array  $column
     * @param string $direction
     *
     * @return $this
     */
    public function sortByRelation($column, $direction = 'ASC')
    {
        $relations = explode('.', $column);
        $column    = array_pop($relations);

        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = $this->repository->makeModel();

        $this->instance->getQuery()->orders = [];

        foreach ($relations as $relation) {
            /* @var Relation $relationClass */
            $this->joinRelation($relationClass = $model->{camel_case($relation)}());

            $model = $relationClass->getRelated();
        }

        $this->instance->select(DB::raw("{$this->repository->makeModel()->getTable()}.*"))
            ->orderBy("{$model->getTable()}.{$column}", $direction);

        return $this;
    }

    /**
     * Add joining the tables to query based on the type of relationship.
     *
     * @param Relation $relationClass
     */
    protected function joinRelation(Relation $relationClass)
    {
        switch (get_class($relationClass)) {
            case BelongsToMany::class:
                /* @var BelongsToMany $relationClass */
                $this->instance = $this->joinBelongsToMany($relationClass);
                break;
            case BelongsTo::class:
                /* @var BelongsTo $relationClass */
                $this->instance = $this->joinBelongsTo($relationClass);
                break;
            case HasOneOrMany::class:
                /* @var HasOneOrMany $relationClass */
                $this->instance = $this->joinHasOne($relationClass);
                break;
        }
    }

    /**
     * Join a belongs to many relationship.
     *
     * @param BelongsToMany $relation
     *
     * @return mixed
     */
    protected function joinBelongsToMany(BelongsToMany $relation)
    {
        return $this->instance->join($relation->getTable(),
            $relation->getParent()->getTable() . '.' . $relation->getParent()->getKeyName(), '=',
            $relation->getForeignKey())->join($relation->getRelated()->getTable(),
            $relation->getRelated()->getTable() . '.' . $relation->getRelated()->getKeyName(), '=',
            $relation->getOtherKey());
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
        return $this->instance->join($relation->getRelated()->getTable(), $relation->getQualifiedOtherKeyName(), '=',
            $relation->getQualifiedForeignKey());
    }

    /**
     * Join a has one relationship.
     *
     * @param HasOneOrMany $relation
     *
     * @return mixed
     */
    protected function joinHasOne(HasOneOrMany $relation)
    {
        return $this->instance->join($relation->getRelated()->getTable(),
            $relation->getRelated()->getTable() . '.' . $relation->getParent()->getForeignKey(), '=',
            $relation->getParent()->getTable() . '.' . $relation->getParent()->getKeyName());
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
