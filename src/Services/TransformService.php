<?php

namespace SebastianBerc\Repositories\Services;

use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Support\Collection;
use SebastianBerc\Repositories\Contracts\ServiceInterface;
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
class TransformService implements ServiceInterface
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
     * @param Collection $collection
     *
     * @return Collection
     * @throws InvalidTransformer
     */
    public function executeOn(Collection $collection)
    {
        if (empty($collection) || $collection->first() === null) {
            return $collection;
        }

        $transformer = $this->repository->transformer;

        if (!(new \ReflectionClass($transformer))->implementsInterface(TransformerInterface::class)) {
            throw new InvalidTransformer();
        }

        /** @var TransformerInterface $transformer */
        $transformer = new $transformer($collection);

        return $transformer->execute();
    }
}
