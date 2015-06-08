<?php namespace SebastianBerc\Repositories\Services;

use SebastianBerc\Repositories\Repository;

/**
 * Class CacheService
 *
 * @author  Sebastian Berć <sebastian.berc@gmail.com>
 *
 * @package SebastianBerc\Repositories\Services
 */
class CacheService
{
    /**
     * @var Repository
     */
    protected $repository;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * @var string
     */
    protected $tag;

    /**
     * @var int
     */
    protected $lifetime;

    /**
     * @var string
     */
    protected $cacheKey;

    /**
     * @param Repository $repository
     * @param int        $lifetime
     */
    public function __construct(Repository $repository, $lifetime = 30)
    {
        $this->repository = $repository;
        $this->lifetime   = $lifetime;
        $this->tag        = $repository->makeModel()->getTable();
        $this->cache      = app('cache.store');
    }

    /**
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function cache()
    {
        return $this->cache->tags($this->tag);
    }

    /**
     * @param string $caller
     * @param array  $parameters
     *
     * @return string
     */
    public function cacheKey($caller, array $parameters = [])
    {
        $parameters = compact('caller', 'parameters');

        return md5(serialize($parameters));
    }

    /**
     * @param string $cacheKey
     *
     * @return bool
     */
    public function has($cacheKey)
    {
        return $this->cache()->has($cacheKey);
    }

    /**
     * @param string $caller
     * @param array  $parameters
     * @param string $cacheKey
     *
     * @return mixed
     */
    public function store($caller, array $parameters = [])
    {
        $cacheKey = $this->cacheKey($caller, $parameters);

        return $this->cache()->remember($cacheKey, $this->lifetime, function () use ($caller, $parameters) {
            return call_user_func_array([$this->repository->mediator, 'database'], [$caller, $parameters]);
        });
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
     * @param string $caller
     * @param array  $parameters
     *
     * @return bool
     */
    public function forget($caller, array $parameters = [])
    {
        $cacheKey = $this->cacheKey($caller, $parameters);

        $this->cache()->forget($cacheKey);

        return $this->repository->mediator->database($caller, $parameters);
    }

    /**
     * @param int   $identifier
     * @param array $attributes
     *
     * @return mixed
     */
    public function update($identifier, array $attributes = [])
    {
        return $this->refresh('update', compact('identifier', 'attributes'));
    }

    /**
     * @param int $identifier
     *
     * @return bool
     */
    public function delete($identifier)
    {
        return $this->forget('delete', compact('identifier'));
    }

    /**
     * @param string $caller
     * @param array  $parameters
     */
    public function __call($caller, array $parameters = [])
    {
        return $this->retrieveOrStore($caller, $parameters);
    }
}
