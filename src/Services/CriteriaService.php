<?php

namespace SebastianBerc\Repositories\Services;

use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use SebastianBerc\Repositories\Contracts\CriteriaInterface;
use SebastianBerc\Repositories\Contracts\ServiceInterface;
use SebastianBerc\Repositories\Repository;

/**
 * Class CriteriaService
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Services
 */
class CriteriaService implements ServiceInterface
{
    /**
     * Contains application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Contains repository instance.
     *
     * @var Repository
     */
    protected $repository;

    /**
     * Contains stack of criterias.
     *
     * @var Collection
     */
    protected $stack;

    /**
     * Create a new criteria service instance.
     *
     * @param Application $app
     * @param Repository  $repository
     */
    public function __construct(Application $app, Repository $repository)
    {
        $this->app        = $app;
        $this->repository = $repository;
        $this->stack      = new Collection();
    }

    /**
     * Add criteria to stack.
     *
     * @param CriteriaInterface $criteria
     *
     * @return $this
     */
    public function addCriteria(CriteriaInterface $criteria)
    {
        $this->stack->push($criteria);

        return $this;
    }

    /**
     * Determinate if service stack has any criteria.
     *
     * @return bool
     */
    public function hasCriteria()
    {
        return !$this->stack->isEmpty();
    }

    /**
     * Returns all criteria from stack.
     *
     * @return array
     */
    public function getCriterias()
    {
        return $this->stack->all();
    }

    /**
     * Remove one or all criteria from stack.
     *
     * @param string $criteriaName
     *
     * @return bool
     */
    public function removeCriteria($criteriaName = '')
    {
        if (!empty($criteriaName)) {
            $this->stack = $this->stack->filter(function (CriteriaInterface $criteria) use ($criteriaName) {
                return !is_a($criteria, $criteriaName);
            });

            return true;
        }

        $this->stack = new Collection();

        return true;
    }

    /**
     * Execute an criterias from the stack on given query builder.
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function executeOn(Builder $query)
    {
        $this->stack->each(function (CriteriaInterface $criteria) use ($query) {
            $criteria->execute($query);
        });

        return $query;
    }
}
