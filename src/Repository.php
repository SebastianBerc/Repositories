<?php namespace SebastianBerc\Repositories;

use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Query\Builder;
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
 * @method static               applyCriteria()
 * @method Collection           all(array $columns = ['*'])
 * @method Builder              where($column, $operator = '=', $value = null, $boolean = 'and')
 * @method LengthAwarePaginator paginate($perPage = 15, array $columns = ['*'])
 * @method Eloquent             create(array $attributes = [])
 * @method Eloquent|null        update($identifier, array $attributes = [])
 * @method bool                 delete($identifier)
 * @method Eloquent             find($identifier, array $columns = ['*'])
 * @method Eloquent             findBy($column, $value, array $columns = ['*'])
 * @method Eloquent             findWhere(array $wheres, array $columns = ['*'])
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
        if ($this instanceof ShouldBeCached) {
            return true;
        }

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
     * Dynamicly call methods on managers.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($method, $args)
    {
        if (!method_exists($this->manager(), Repositorable::class)) {
            throw new \BadMethodCallException();
        }

        $this->hasCriteria() ? $this->makeModel()->applyCriteria() : $this->makeModel();

        if ($this->shouldBeCached()) {
            return call_user_func_array([$this->cache(), $method], $args);
        }

        return call_user_func_array([$this->manager(), $method], $args);
    }
}
