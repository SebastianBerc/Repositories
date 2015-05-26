<?php namespace SebastianBerc\Repositories\Managers;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Collection;
use SebastianBerc\Repositories\Exceptions\InvalidRepositoryCriteria;

/**
 * Class CriteriaManager
 *
 * @author  Sebastian Berć <sebastian.berc@gmail.com>
 *
 * @package SebastianBerc\Repositories\Managers
 */
class CriteriaManager
{
    /**
     * @var Collection
     */
    protected $stack;

    /**
     * @var Eloquent
     */
    protected $instance;

    /**
     * Create the new CriteriaManager instance.
     */
    public function __construct(&$modelInstance)
    {
        $this->stack = new Collection();
        $this->instance = $modelInstance;
    }

    protected function isValidCriteria($criteriaName)
    {
        if (class_exists($criteriaName)) {
            return true;
        }

        if (class_exists($this->getCriteriaClass($criteriaName))) {
            return true;
        }

        return false;
    }

    protected function getCriteriaClass($criteriaName)
    {
        return str_replace(basename($this->instance), ucfirst($criteriaName), get_class($this->instance));
    }

    /**
     * Append criteria to stack.
     *
     * @param string $criteriaName
     *
     * @return static
     */
    public function append($criteriaName)
    {
        if ($this->isValidCriteria($criteriaName)) {
            $this->stack->push($criteriaName);

            return $this;
        }

        throw new InvalidRepositoryCriteria();
    }

    /**
     * Prepend criteria to stack.
     *
     * @param string $criteriaName
     *
     * @return static
     */
    public function prepend($criteriaName)
    {
        if ($this->isValidCriteria($criteriaName)) {
            $this->stack->prepend($criteriaName);

            return $this;
        }

        throw new InvalidRepositoryCriteria();
    }

    /**
     * Remove criteria from stack.
     *
     * @param string $criteriaName
     *
     * @return static
     */
    public function remove($criteriaName)
    {
        if (in_array($criteriaName, $this->stack->toArray())) {
            $this->stack->filter(function ($item) use ($criteriaName) {
                return $item !== $criteriaName;
            });
        }
    }

    /**
     * Check if criteria is on stack.
     *
     * @param string $criteriaName
     *
     * @return bool
     */
    public function has($criteriaName)
    {
        return in_array($criteriaName, $this->stack->toArray());
    }

    /**
     * Execute criteria stack on given model.
     *
     * @return void
     */
    public function execute()
    {

    }
}
