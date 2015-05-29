<?php namespace SebastianBerc\Repositories\Managers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use SebastianBerc\Repositories\Contracts\Gridable;

/**
 * Class CacheGridManager
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Managers
 */
class CacheGridManager extends CacheRepositoryManager implements Gridable
{
    /**
     * Fetch collection ordered and filtrated by specified columns for specified page.
     *
     * @param int   $page
     * @param int   $perPage
     * @param array $filter
     * @param array $sort
     * @param array $columns
     *
     * @return LengthAwarePaginator
     */
    public function fetch($page, $perPage = 15, array $filter = [], array $sort = [], array $columns = ['*'])
    {
        // TODO: Implement fetch() method.
    }

    /**
     * Returns total count of whole collection.
     *
     * @return int
     */
    public function count()
    {
        // TODO: Implement count() method.
    }

    /**
     * Append column filter to query builder.
     *
     * @param string|array $column
     * @param string       $value
     *
     * @return $this
     */
    public function filterBy($column, $value = null)
    {
        // TODO: Implement filterBy() method.
    }

    /**
     * Append relation column filter to query builder.
     *
     * @param string $column
     * @param string $value
     *
     * @return $this
     */
    public function filterByRelation($column, $value = null)
    {
        // TODO: Implement filterByRelation() method.
    }

    /**
     * Append many column filters to query builder.
     *
     * @param array $columns
     *
     * @return $this
     */
    public function multiFilterBy(array $columns)
    {
        // TODO: Implement multiFilterBy() method.
    }

    /**
     * Append column sorting to query builder.
     *
     * @param string|array $column
     * @param string       $direction
     *
     * @return $this
     */
    public function sortBy($column, $direction = 'ASC')
    {
        // TODO: Implement sortBy() method.
    }

    /**
     * Append relation column sorting to query builder.
     *
     * @param string|array $column
     * @param string       $direction
     *
     * @return $this
     */
    public function sortByRelation($column, $direction = 'ASC')
    {
        // TODO: Implement sortByRelation() method.
    }

    /**
     * Append many column sorting to query builder.
     *
     * @param array $columns
     *
     * @return $this
     */
    public function multiSortBy(array $columns)
    {
        // TODO: Implement multiSortBy() method.
    }
}
