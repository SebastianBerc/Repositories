<?php

namespace SebastianBerc\Repositories;

use Illuminate\Support\Collection;
use SebastianBerc\Repositories\Contracts\TransformerInterface;

/**
 * Class Transformer
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories
 */
abstract class Transformer implements TransformerInterface
{
    /**
     * Contains collection for transform.
     *
     * @var Collection
     */
    protected $collection;

    /**
     * Create new transformer instance with given collection of items.
     *
     * @param Collection $collection
     */
    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Transform given item.
     *
     * @param mixed $item
     *
     * @return mixed
     */
    abstract public function transform($item);

    /**
     * Execute transform on each collection element given in contructor.
     *
     * @return Collection
     */
    public function execute()
    {
        return $this->collection->transform(function ($item) {
            return $this->transform($item);
        });
    }
}
