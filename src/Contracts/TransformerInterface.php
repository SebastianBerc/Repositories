<?php

namespace SebastianBerc\Repositories\Contracts;

use Illuminate\Support\Collection;

/**
 * Interface TransformerInterface
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Contracts
 */
interface TransformerInterface
{
    /**
     * Create new transformer instance with given collection of items.
     *
     * @param Collection $collection
     */
    public function __construct(Collection $collection);

    /**
     * Transform given item.
     *
     * @param mixed $item
     *
     * @return mixed
     */
    public function transform($item);

    /**
     * Execute transform on each collection element given in contructor.
     *
     * @return Collection
     */
    public function execute();
}
