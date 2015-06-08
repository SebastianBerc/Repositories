<?php namespace SebastianBerc\Repositories\Services;

use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Support\Collection;
use SebastianBerc\Repositories\Contracts\TransformerInterface;
use SebastianBerc\Repositories\Exceptions\InvalidTransformer;
use SebastianBerc\Repositories\Repository;

/**
 * Class TransformService
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Services
 */
class TransformService
{
    /**
     * Create a new transform service instance.
     *
     * @param Application $app
     * @param Repository  $repository
     */
    public function __construct(Application $app, Repository $repository)
    {
        $this->app        = $app;
        $this->repository = $repository;
    }

    /**
     * Execute transform method on specified transformer.
     *
     * @return \Illuminate\Support\Collection
     */
    public function executeOn(Collection $collection)
    {
        $transformer = $this->repository->transformer;

        if (!(new \ReflectionClass($transformer))->implementsInterface(TransformerInterface::class)) {
            throw new InvalidTransformer();
        }

        /** @var TransformerInterface $transformer */
        $transformer = new $transformer($collection);

        return $transformer->execute();
    }
}
