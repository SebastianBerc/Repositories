<?php namespace SebastianBerc\Repositories\Contracts;

use Illuminate\Contracts\Container\Container as Application;
use SebastianBerc\Repositories\Repository;

/**
 * Interface ServiceInterface
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Contracts
 */
interface ServiceInterface
{
    /**
     * Create a new instance of service.
     *
     * @param Application $app
     * @param Repository  $repository
     * @param array       $options
     */
    public function __construct(Application $app, Repository $repository, array $options = []);
}
