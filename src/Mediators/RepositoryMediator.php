<?php namespace SebastianBerc\Repositories\Mediators;

use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use SebastianBerc\Repositories\Contracts\TransformerInterface;
use SebastianBerc\Repositories\Repository;
use SebastianBerc\Repositories\Services\CacheService;
use SebastianBerc\Repositories\Services\DatabaseService;
use SebastianBerc\Repositories\Services\TransformService;

/**
 * Class RepositoryMediator
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Mediators
 */
class RepositoryMediator
{
    /**
     * Contains Laravel Application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Contains a repository instance.
     *
     * @var Repository
     */
    protected $repository;

    /**
     * Contains a database service.
     *
     * @var DatabaseService
     */
    protected $database;

    /**
     * Contains an cache service.
     *
     * @var CacheService
     */
    protected $cache;

    /**
     * Contains a transform service.
     *
     * @var TransformService
     */
    protected $transform;

    /**
     * Create a new Repositry Mediator instance.
     *
     * @param Repository $repository
     */
    public function __construct(Application $app, Repository $repository)
    {
        $this->app        = $app;
        $this->repository = $repository;
        $this->cache      = new CacheService($app, $repository, ['lifetime' => $repository->lifetime ?: 30]);
        $this->database   = new DatabaseService($app, $repository);
        $this->transform  = new TransformService($app, $repository);
    }

    /**
     * Retrieve data from cache storage, or execute method
     * on database and store result then return it.
     *
     * @param string $caller
     * @param array  $parameters
     *
     * @return mixed
     */
    public function cache($caller, array $parameters = [])
    {
        return call_user_func_array([$this->cache, $caller], $parameters);
    }

    /**
     * Execute method with parameters od database service.
     *
     * @param string $caller
     * @param array  $parameters
     *
     * @return mixed
     */
    public function database($caller, array $parameters = [])
    {
        return call_user_func_array([$this->database, $caller], $parameters);
    }

    /**
     * Execute transform method on specified transformer.
     *
     * @param mixed $collection
     *
     * @return Collection
     */
    public function transform($collection)
    {
        if (empty($collection)) {
            return new Collection();
        }

        if (!$collection instanceof Collection) {
            $collection = new Collection(is_array($collection) ? $collection : [$collection]);
        }

        return $this->transform->executeOn($collection);
    }

    /**
     * Execute transform method on paginator items with specified transformer.
     *
     * @param LengthAwarePaginator $paginator
     *
     * @return LengthAwarePaginator
     */
    public function transformPaginator(LengthAwarePaginator $paginator)
    {
        $items = $this->transform($paginator->items())->toArray();

        return new LengthAwarePaginator($items, $paginator->total(), $paginator->perPage(), $paginator->currentPage());
    }
}
