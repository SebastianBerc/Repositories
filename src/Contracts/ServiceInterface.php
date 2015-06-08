<?php namespace SebastianBerc\Repositories\Contracts;

/**
 * Interface ServiceInterface
 *
 * @author  Sebastian Berć <sebastian.berc@gmail.com>
 *
 * @package SebastianBerc\Repositories\Contracts
 */
interface ServiceInterface
{
    public function __construct(RepositoryInterface $repository);
}
