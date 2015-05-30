<?php namespace SebastianBerc\Repositories\Managers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use SebastianBerc\Repositories\Contracts\Gridable;

/**
 * Class CacheGridManager
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Managers
 */
class CacheGridManager extends CacheRepositoryManager
{
    /**
     * Return a new instance of RepositoryManager.
     *
     * @return GridManager
     */
    protected function manager()
    {
        return new GridManager($this->app, $this->instance);
    }

    /**
     * Fetch collection ordered and filtrated by specified columns for specified page.
     *
     * @param int   $page
     * @param int   $perPage
     * @param array $filter
     * @param array $sort
     * @param array $columns
     *
     * @return LengthAwarePaginator
     */
    public function fetch($page, $perPage = 15, array $filter = [], array $sort = [], array $columns = ['*'])
    {
        $cacheKey = $this->cacheKey(compact('page', 'perPage', 'filter', 'sort'));

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        return $this->cache->remember(
            $cacheKey,
            $this->lifetime,
            function () use ($page, $perPage, $filter, $sort, $columns) {
                return $this->manager()->fetch($page, $perPage, $filter, $sort, $columns);
            }
        );
    }

    /**
     * Dynamicly call methods on grid manager.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($method, $args)
    {
        if (method_exists($this->manager(), $method)) {
            return call_user_func_array([$this->manager(), $method], $args);
        }

        throw new \BadMethodCallException();
    }
}
