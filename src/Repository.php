<?php namespace SebastianBerc\Repositories;

use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as Eloquent;
use SebastianBerc\Repositories\Contracts\Repositorable;
use SebastianBerc\Repositories\Contracts\ShouldBeCached;
use SebastianBerc\Repositories\Exceptions\InvalidRepositoryModel;
use SebastianBerc\Repositories\Managers\CacheManager;
use SebastianBerc\Repositories\Managers\RepositoryManager;
use SebastianBerc\Repositories\Traits\HasCriteria;

/**
 * Class Repositories
 *
 * @author  Sebastian Berć <sebastian.berc@gmail.com>
 *
 * @package SebastianBerc\Repositories
 *
 * @method static applyCriteria()
 */
abstract class Repository implements Repositorable
{
    /**
     * Contains Laravel Application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Contains Eloquent model instance.
     *
     * @var Eloquent
     */
    protected $instance;

    /**
     * Create a new Repository instance.
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Return fully qualified model class name.
     *
     * @return string
     */
    abstract public function takeModel();

    /**
     * Return instance of Eloquent model.
     *
     * @return static
     */
    protected function makeModel()
    {
        $this->instance = $this->app->make($this->takeModel());

        if (!$this->instance instanceof Eloquent) {
            throw new InvalidRepositoryModel(get_class($this->instance), Eloquent::class);
        }

        return $this;
    }

    /**
     * Return a new RepositoryManager instance.
     *
     * @return RepositoryManager
     */
    protected function manager()
    {
        return new RepositoryManager($this->instance);
    }

    /**
     * Return a new CacheManager instance.
     *
     * @return CacheManager
     */
    protected function cache()
    {
        return new CacheManager($this->app, $this->instance);
    }

    /**
     * Determine if the repository should be cached.
     *
     * @return bool
     */
    protected function shouldBeCached()
    {
        if ($this instanceof ShouldBeCached) return true;

        return (new \ReflectionClass($this))->implementsInterface(
            'App\Infrastructure\Repository\Contracts\ShouldBeCached'
        );
    }

    /**
     * Determine if the repository uses an criteria.
     *
     * @return bool
     */
    protected function hasCriteria()
    {
        return in_array(HasCriteria::class, (new \ReflectionClass($this))->getTraitNames());
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
        $this->hasCriteria() ? $this->makeModel()->applyCriteria() : $this->makeModel();

        if ($this->shouldBeCached()) {
            return $this->cache()->all($columns);
        }

        return $this->manager()->all($columns);
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
        $this->hasCriteria() ? $this->makeModel()->applyCriteria() : $this->makeModel();

        if ($this->shouldBeCached()) {
            return $this->cache()->where($column, $operator, $value, $boolean);
        }

        return $this->manager()->where($column, $operator, $value, $boolean);
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
        $this->hasCriteria() ? $this->makeModel()->applyCriteria() : $this->makeModel();

        if ($this->shouldBeCached()) {
            return $this->cache()->paginate($perPage, $columns);
        }

        return $this->manager()->paginate($perPage, $columns);
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
        $this->hasCriteria() ? $this->makeModel()->applyCriteria() : $this->makeModel();

        if ($this->shouldBeCached()) {
            return $this->cache()->create($attributes);
        }

        return $this->manager()->create($attributes);
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
        $this->hasCriteria() ? $this->makeModel()->applyCriteria() : $this->makeModel();

        if ($this->shouldBeCached()) {
            return $this->cache()->update($identifier, $attributes);
        }

        return $this->manager()->update($identifier, $attributes);
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
        $this->hasCriteria() ? $this->makeModel()->applyCriteria() : $this->makeModel();

        if ($this->shouldBeCached()) {
            return $this->cache()->delete($identifier);
        }

        return $this->manager()->delete($identifier);
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
        $this->hasCriteria() ? $this->makeModel()->applyCriteria() : $this->makeModel();

        if ($this->shouldBeCached()) {
            return $this->cache()->find($identifier, $columns);
        }

        return $this->manager()->find($identifier, $columns);
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
        $this->hasCriteria() ? $this->makeModel()->applyCriteria() : $this->makeModel();

        if ($this->shouldBeCached()) {
            return $this->cache()->findBy($column, $value, $columns);
        }

        return $this->manager()->findBy($column, $value, $columns);
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
        $this->hasCriteria() ? $this->makeModel()->applyCriteria() : $this->makeModel();

        if ($this->shouldBeCached()) {
            return $this->cache()->findWhere($wheres, $columns);
        }

        return $this->manager()->findWhere($wheres, $columns);
    }
}
