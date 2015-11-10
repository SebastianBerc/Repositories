<?php

namespace SebastianBerc\Repositories\Exceptions;

use SebastianBerc\Repositories\Contracts\CriteriaInterface;
use SebastianBerc\Repositories\Criteria;

/**
 * Class InvalidCriteria.
 *
 * @author  Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 */
class InvalidCriteria extends \Exception
{
    /**
     * Contains criteria full qualified class path to abstract class.
     *
     * @var string
     */
    private $abstract = Criteria::class;

    /**
     * Contains criteria full qualified class path to interface.
     *
     * @var string
     */
    private $interface = CriteriaInterface::class;

    /**
     * Create a new invalid criteria exception instance.
     */
    public function __construct()
    {
        parent::__construct(sprintf(
            "Criteria must extends '%s' abstract class or implements '%s' interface.",
            $this->abstract,
            $this->interface
        ));
    }
}
