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
    public function execute(Builder $query);
}
