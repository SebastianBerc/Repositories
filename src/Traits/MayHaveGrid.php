<?php namespace SebastianBerc\Repositories\Traits;

use SebastianBerc\Repositories\Managers\CacheGridManager;
use SebastianBerc\Repositories\Managers\GridManager;

/**
 * Class MayHaveGrid
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Traits
 */
trait MayHaveGrid
{
    /**
     * Return a new GridManager instance.
     *
     * @return GridManager
     */
    protected function gridManager()
    {
        return new GridManager($this->app, $this->instance);
    }

    /**
     * Return a new CacheGridManager instance.
     *
     * @return CacheGridManager
     */
    protected function gridCache()
    {
        return new CacheGridManager($this->app, $this->instance);
    }
}
