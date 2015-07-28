<?php

namespace SebastianBerc\Repositories\Services;

use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as Eloquent;
use SebastianBerc\Repositories\Contracts\ServiceInterface;
use SebastianBerc\Repositories\Repository;

/**
 * Class CacheService
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Services
 */
class CacheService implements ServiceInterface
{
    /**
     * Contains instance of repository.
     *
     * @var Repository
     */
    protected $repository;

    /**
     * Contains cache repository.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Contains tag for repository model.
     *
     * @var string
     */
    protected $tag;

    /**
     * Contains cache life time.
     *
     * @var int
     */
    protected $lifetime;

    /**
     * Contains cache key for given action.
     *
     * @var string
     */
    protected $cacheKey;

    /**
     * Create a new cache service instance.
     *
     * @param Application $app
     * @param Repository  $repository
     */
    public function __construct(Application $app, Repository $repository)
    {
        $this->repository = $repository;
        $this->lifetime   = $repository->lifetime ?: 30;
        $this->tag        = $repository->makeModel()->getTable();
        $this->cache      = $app->make('cache.store');
    }

    /**
     * Execute refresh on cache service and update action on database service.
     *
     * @param int   $identifier
     * @param array $attributes
     *
     * @return mixed
     */
    public function update($identifier, array $attributes = [])
    {
        $this->forget('all', ['columns' => ['*']]);

        return $this->refresh('update', compact('identifier', 'attributes'));
    }

    /**
     * Forget, and store new data into cache.
     *
     * @param string $caller
     * @param array  $parameters
     *
     * @return mixed
     */
    public function refresh($caller, array $parameters = [])
    {
        $cacheKey = $this->cacheKey($caller, $parameters);

        $this->cache()->forget($cacheKey);

        return $this->store($caller, $parameters);
    }

    /**
     * Generate and return cache key for caller with specified parameters.
     *
     * @param string $caller
     * @param array  $parameters
     *
     * @return string
     */
    public function cacheKey($caller, array $parameters = [])
    {
        $parameters = compact('caller', 'parameters');

        if ($this->repository->model instanceof Eloquent || $this->repository->model instanceof Builder) {
            $parameters['eager'] = md5(serialize($this->repository->with));
        }

        if ($this->repository->mediator->criteria()->hasCriteria()) {
            $parameters['criteria'] = md5(serialize($this->repository->mediator->criteria()->getCriterias()));
        }

        return md5(serialize($parameters));
    }

    /**
     * Initialize cache repository with specified tag for given model.
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function cache()
    {
        return method_exists($this->cache, 'tags') ? $this->cache->tags($this->tag) : $this->cache;
    }

    /**
     * Store data in cache behind caller with specified parameters.
     *
     * @param string $caller
     * @param array  $parameters
     *
     * @return mixed
     */
    public function store($caller, array $parameters = [])
    {
        $this->forget('all', ['columns' => ['*']]);

        $cacheKey = $this->cacheKey($caller, $parameters);

        return $this->cache()->remember($cacheKey, $this->lifetime, function () use ($caller, $parameters) {
            return call_user_func_array([$this->repository->mediator, 'database'], [$caller, $parameters]);
        });
    }

    /**
     * Execute forget on cache service and delete action on database services.
     *
     * @param int $identifier
     *
     * @return bool
     */
    public function delete($identifier)
    {
        $this->forget('all', ['columns' => ['*']]);

        return $this->forget('delete', compact('identifier'));
    }

    /**
     * Forget data in cache behind caller with specified parameters.
     *
     * @param string $caller
     * @param array  $parameters
     *
     * @return bool
     */
    public function forget($caller, array $parameters = [])
    {
        $cacheKey = $this->cacheKey($caller, $parameters);

        if ($caller == 'delete') {
            $this->repository->mediator->database($caller, $parameters);
        }

        return $this->cache()->forget($cacheKey);
    }

    /**
     * Dynamicly call method on cache service.
     *
     * @param string $caller
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($caller, array $parameters = [])
    {
        return $this->retrieveOrStore($caller, $parameters);
    }

    /**
     * Retrieve or store and return data from cache.
     *
     * @param string $caller
     * @param array  $parameters
     *
     * @return mixed
     */
    public function retrieveOrStore($caller, array $parameters = [])
    {
        $cacheKey = $this->cacheKey($caller, $parameters);

        return $this->retrieve($cacheKey) ?: $this->store($caller, $parameters);
    }

    /**
     * Return data for given cache key.
     *
     * @param string $cacheKey
     *
     * @return bool
     */
    public function retrieve($cacheKey)
    {
        if ($this->has($cacheKey)) {
            return $this->cache()->get($cacheKey);
        }

        return false;
    }

    /**
     * Check if specified cache key exists and has data.
     *
     * @param string $cacheKey
     *
     * @return bool
     */
    public function has($cacheKey)
    {
        return $this->cache()->has($cacheKey);
    }
}
