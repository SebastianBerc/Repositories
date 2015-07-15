<?php

namespace SebastianBerc\Repositories\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Interface CriteriaInterface
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Contracts
 */
interface CriteriaInterface
{
    /**
     * Execute criteria on given query builder.
     *
     * @param Builder $query
     *
     * @return mixed
     */
    public function execute(Builder $query);
}
