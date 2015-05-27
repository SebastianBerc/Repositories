<?php namespace SebastianBerc\Repositories\Exceptions;

/**
 * Class InvalidRepositoryModel
 *
 * @author  Sebastian Berć <sebastian.berc@gmail.com>
 *
 * @package SebastianBerc\Repositories\Exceptions
 */
class InvalidRepositoryModel extends \Exception
{
    /**
     * Create a new InvalidRepositoryModel instance.
     */
    public function __construct($model, $interface)
    {
        parent::__construct("Class '{$model}' must be an instance of '{$interface}'.");
    }
}
