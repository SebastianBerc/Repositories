<?php namespace SebastianBerc\Repositories\Managers;

use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as Eloquent;
use SebastianBerc\Repositories\Contracts\Repositorable;

/**
 * Class CacheManager
 *
 * @author  Sebastian Berć <sebastian.berc@gmail.com>
 *
 * @package SebastianBerc\Repositories\Managers
 */
class CacheManager implements Repositorable
{
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
     * Create a new CacheManager instance.
     */
    public function __construct(Application $app, $modelInstance)
    {
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
        return new RepositoryManager($this->instance);
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

        return $this->cache->remember($this->cacheKey(), $this->lifetime, function () {
            return $this->manager()->all(['*']);
        });
    }

    /**
     * Create a new basic where query clause on model.
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     * @param string $boolean
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
        $cacheKey = $this->cacheKey("paginete.{$perPage}");

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        return $this->cache->remember($cacheKey, $this->lifetime, function () use ($perPage) {
            return $this->manager()->paginate($perPage, ['*']);
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
        $instance = $this->manager()->find($identifier);
        $instance->update($attributes);

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

        return $this->cache->remember($this->cacheKey($identifier), $this->lifetime, function () use ($identifier) {
            return $this->manager()->find($identifier, ['*']);
        });
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
        $cacheKey = $this->cacheKey(compact('column', 'value'));

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        return $this->cache->remember($cacheKey, $this->lifetime, function () use ($column, $value) {
            return $this->manager()->findBy($column, $value, ['*']);
        });
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
        $cacheKey = $this->cacheKey($wheres);

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        return $this->cache->remember($cacheKey, $this->lifetime, function () use ($wheres) {
            return $this->manager()->findWhere($wheres, ['*']);
        });
    }
}
