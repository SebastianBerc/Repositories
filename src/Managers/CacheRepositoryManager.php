<?php namespace SebastianBerc\Repositories\Managers;

use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as Eloquent;
use SebastianBerc\Repositories\Contracts\Repositorable;

/**
 * Class CacheRepositoryManager
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Managers
 */
class CacheRepositoryManager implements Repositorable
{
    /**
     * Contains Laravel Application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Contains the Eloquent model instance.
     *
     * @var Eloquent Model instance.
     */
    protected $instance;

    /**
     * Contains the Laravel CacheRepository instance.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Contains the lifetime of cache.
     *
     * @var int
     */
    protected $lifetime;

    /**
     * Create a new CacheRepositoryManager instance.
     *
     * @param Application $app
     * @param Eloquent    $modelInstance
     */
    public function __construct(Application $app, $modelInstance)
    {
        $this->app      = $app;
        $this->instance = $modelInstance;
        $this->cache    = $app->make('cache.store');
        $this->lifetime = $app->make('config')['cache.lifetime'] ?: 30;
    }

    /**
     * Return cache key for specified credentials.
     *
     * @param mixed $suffix
     *
     * @return string
     */
    protected function cacheKey($suffix = null)
    {
        $key = $this->instance->getTable();

        if (is_array($suffix)) {
            $key .= '.' . md5(serialize($suffix));
        }

        if (is_scalar($suffix)) {
            $key .= '.' . $suffix;
        }

        return $key;
    }

    /**
     * Return a new instance of RepositoryManager.
     *
     * @return RepositoryManager
     */
    protected function manager()
    {
        return new RepositoryManager($this->app, $this->instance);
    }

    /**
     * Get all of the models from the database.
     *
     * @param array $columns
     *
     * @return Collection
     */
    public function all(array $columns = ['*'])
    {
        if ($this->cache->has($this->cacheKey())) {
            return $this->cache->get($this->cacheKey());
        }

        return $this->cache->remember($this->cacheKey(), $this->lifetime, function () use ($columns) {
            return $this->manager()->all($columns);
        });
    }

    /**
     * Create a new basic where query clause on model.
     *
     * @param string|array $column
     * @param string       $operator
     * @param mixed        $value
     * @param string       $boolean
     *
     * @return Builder
     */
    public function where($column, $operator = '=', $value = null, $boolean = 'and')
    {
        $cacheKey = $this->cacheKey(compact('column', 'operator', 'value', 'boolean'));

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        return $this->cache->remember(
            $cacheKey,
            $this->lifetime,
            function () use ($column, $operator, $value, $boolean) {
                return $this->manager()->where($column, $operator, $value, $boolean);
            }
        );
    }

    /**
     * Paginate the given query.
     *
     * @param int   $perPage
     * @param array $columns
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 15, array $columns = ['*'])
    {
        $cacheKey = $this->cacheKey("paginate.{$perPage}");

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        return $this->cache->remember($cacheKey, $this->lifetime, function () use ($perPage, $columns) {
            return $this->manager()->paginate($perPage, $columns);
        });
    }

    /**
     * Save a new model and return the instance.
     *
     * @param array $attributes
     *
     * @return Eloquent
     */
    public function create(array $attributes = [])
    {
        $instance = $this->manager()->create($attributes);
        $cacheKey = $this->cacheKey($instance->getKey());

        return $this->cache->remember($cacheKey, $this->lifetime, function () use ($instance) {
            return $instance;
        });
    }

    /**
     * Save or update the model in the database.
     *
     * @param mixed $identifier
     * @param array $attributes
     *
     * @return Eloquent|null
     */
    public function update($identifier, array $attributes = [])
    {
        $instance = $this->manager()->update($identifier, $attributes);

        $cacheKey = $this->cacheKey($instance->getKey());

        $this->cache->forget($cacheKey);

        return $this->cache->remember($cacheKey, $this->lifetime, function () use ($instance) {
            return $instance;
        });
    }

    /**
     * Delete the model from the database.
     *
     * @param int $identifier
     *
     * @return bool
     */
    public function delete($identifier)
    {
        $instance = $this->manager()->find($identifier);
        $cacheKey = $this->cacheKey($instance->getKey());

        $this->cache->forget($cacheKey);

        return $instance->delete();
    }

    /**
     * Find a model by its primary key.
     *
     * @param int   $identifier
     * @param array $columns
     *
     * @return Eloquent
     */
    public function find($identifier, array $columns = ['*'])
    {
        if ($this->cache->has($this->cacheKey($identifier))) {
            return $this->cache->get($this->cacheKey($identifier));
        }

        return $this->cache->remember(
            $this->cacheKey($identifier), $this->lifetime,
            function () use ($identifier, $columns) {
                return $this->manager()->find($identifier, $columns);
            }
        );
    }

    /**
     * Find a model by its specified column and value.
     *
     * @param mixed $column
     * @param mixed $value
     * @param array $columns
     *
     * @return Eloquent
     */
    public function findBy($column, $value, array $columns = ['*'])
    {
        return $this->where([$column => $value])->first($columns);
    }

    /**
     * Find a model by its specified columns and values.
     *
     * @param array $wheres
     * @param array $columns
     *
     * @return Eloquent
     */
    public function findWhere(array $wheres, array $columns = ['*'])
    {
        return $this->where($wheres)->first($columns);
    }
}
