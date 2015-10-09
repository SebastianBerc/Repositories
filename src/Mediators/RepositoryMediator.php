<?php

namespace SebastianBerc\Repositories\Mediators;

use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use SebastianBerc\Repositories\Repository;
use SebastianBerc\Repositories\Services\CacheService;
use SebastianBerc\Repositories\Services\CriteriaService;
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
     * Contains an Criteria service.
     *
     * @var CriteriaService
     */
    protected $criteria;

    /**
     * Create a new Repositry Mediator instance.
     *
     * @param Application $app
     * @param Repository  $repository
     */
    public function __construct(Application $app, Repository $repository)
    {
        $this->app        = $app;
        $this->repository = $repository;
        $this->cache      = new CacheService($app, $repository);
        $this->database   = new DatabaseService($app, $repository);
        $this->transform  = new TransformService($app, $repository);
        $this->criteria   = new CriteriaService($app, $repository);
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
     * Execute method with parameters on database service.
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
        if (!$collection instanceof Collection) {
            // If given "collection" is an object we need to wrap it into an array, and pass into collection.
            $collection = new Collection(is_array($collection) ? $collection : [$collection]);
        }

        // If transform is declared or set, we can execute transform on his object.
        if ($this->hasTransformer()) {
            return $this->transform->executeOn($collection);
        }

        // Otherwise, we can return collection without transformation.
        return $collection;
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
        if (!$this->hasTransformer()) {
            return $paginator;
        }

        $items = $this->transform($paginator->items())->toArray();

        return new LengthAwarePaginator($items, $paginator->total(), $paginator->perPage(), $paginator->currentPage());
    }

    /**
     * Determinate if repository has a transformer.
     *
     * @return bool
     */
    public function hasTransformer()
    {
        return (bool) $this->repository->transformer;
    }

    /**
     * Returns criteria service on model query.
     *
     * @return CriteriaService
     */
    public function criteria()
    {
        return $this->criteria;
    }

    /**
     * Determinate if repository has an criteria.
     *
     * @return bool
     */
    public function hasCriteria()
    {
        return $this->criteria->hasCriteria();
    }
}
