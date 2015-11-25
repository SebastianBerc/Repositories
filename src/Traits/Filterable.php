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
     * @param array  $column
     * @param string $value
     *
     * @return $this
     */
    public function filterByRelation($column, $value = null)
    {
        $relations = explode('.', $column);
        $column    = $this->getColumn($column = array_pop($relations), $relations);

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
            $model = $model instanceof \Illuminate\Database\Eloquent\Model
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
