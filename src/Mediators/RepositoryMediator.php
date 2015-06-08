<?php namespace SebastianBerc\Repositories\Mediators;

use Illuminate\Contracts\Container\Container as Application;
use SebastianBerc\Repositories\Repository;
use SebastianBerc\Repositories\Services\CacheService;
use SebastianBerc\Repositories\Services\DatabaseService;

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
     * @var Repository
     */
    protected $repository;

    /**
     * @var DatabaseService
     */
    protected $database;

    /**
     * @var CacheService
     */
    protected $cache;

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
}
