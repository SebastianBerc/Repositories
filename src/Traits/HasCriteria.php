<?php namespace SebastianBerc\Repositories\Traits;

use SebastianBerc\Repositories\Managers\CriteriaManager;

/**
 * Trait HasCriteria
 *
 * @author  Sebastian Berć <sebastian.berc@gmail.com>
 *
 * @package SebastianBerc\Repositories\Traits
 *
 * @property \Illuminate\Database\Eloquent\Model $instance
 */
trait HasCriteria
{
    /**
     * @var CriteriaManager
     */
    protected $criterias;

    /**
     * @return CriteriaManager
     */
    public function criteria()
    {
        if (!$this->criterias instanceof CriteriaManager) {
            $this->criterias = new CriteriaManager($this->instance);
        }

        return $this->criterias;
    }

    /**
     * @return static
     */
    protected function applyCriteria()
    {
        $this->criteria()->execute();

        return $this;
    }
}
