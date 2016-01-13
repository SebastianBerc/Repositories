<?php

namespace SebastianBerc\Repositories\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
     * @param array  $column
     * @param string $value
     *
     * @return $this
     */
    public function filterByRelation($column, $value = null)
    {
        $relations = explode('.', $column);
        $column    = $this->getColumn($column = array_pop($relations), $relations);

        $relations = array_map(function ($relation) {
            return camel_case($relation);
        }, $relations);

        $this->instance->whereHas(implode('.', $relations), function (Builder $builder) use ($column, $value) {
            $value = in_array($value, ['true', 'false']) ? ($value === 'false' ? false : true) : $value;

            is_bool($value)
                ? $builder->where($column, $value)
                : $builder->where($column, $this->getLikeOperator(), "%$value%");
        });

        return $this;
    }

    /**
     * Returns column name prefixed with table name.
     *
     * @param string $column
     * @param array  $relations
     *
     * @return string
     */
    protected function getColumn($column, array $relations = [])
    {
        /** @var \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\Relation $model */
        $model = $this->repository->makeModel();

        if (empty($relations)) {
            return "{$model->getTable()}.{$column}";
        }

        foreach ($relations as $relation) {
            $model = $model instanceof Model
                ? $model->{camel_case($relation)}()
                : $model->getRelated()->{camel_case($relation)}();
        }

        return "{$model->getRelated()->getTable()}.{$column}";
    }

    /**
     * Returns ilike for pgsql instead of like.
     *
     * @return string
     */
    protected function getLikeOperator()
    {
        return $this->repository->makeModel()->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
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
        $value = in_array($value, ['true', 'false']) ? ($value === 'false' ? false : true) : $value;

        is_bool($value)
            ? $this->instance->where($column, $value)
            : $this->instance->where($column, $this->getLikeOperator(), "%$value%");

        return $this;
    }
}
