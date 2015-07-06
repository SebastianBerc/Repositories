<?php

namespace SebastianBerc\Repositories\Contracts;

use Illuminate\Contracts\Container\Container as Application;
use SebastianBerc\Repositories\Repository;

/**
 * Interface ServiceInterface
 *
 * @author  Sebastian Berć <sebastian.berc@gmail.com>
 * @package SebastianBerc\Repositories\Contracts
 */
interface ServiceInterface
{
    /**
     * Create a new service instance.
     *
     * @param Application $app
     * @param Repository  $repository
     * @param int         $lifetime
     */
    public function __construct(Application $app, Repository $repository);
}
