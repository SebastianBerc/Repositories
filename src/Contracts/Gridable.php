<?php namespace SebastianBerc\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface Gridable
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Contracts
 */
interface Gridable
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
    public function fetch($page, $perPage = 15, array $filter = [], array $sort = [], array $columns = ['*']);

    /**
     * Returns total count of whole collection.
     *
     * @return int
     */
    public function count();

    /**
     * Append column filter to query builder.
     *
     * @param string|array $column
     * @param string       $value
     *
     * @return $this
     */
    public function filterBy($column, $value = null);

    /**
     * Append relation column filter to query builder.
     *
     * @param string $column
     * @param string $value
     *
     * @return $this
     */
    public function filterByRelation($column, $value = null);

    /**
     * Append many column filters to query builder.
     *
     * @param array $columns
     *
     * @return $this
     */
    public function multiFilterBy(array $columns);

    /**
     * Append column sorting to query builder.
     *
     * @param string|array $column
     * @param string       $direction
     *
     * @return $this
     */
    public function sortBy($column, $direction = 'ASC');

    /**
     * Append relation column sorting to query builder.
     *
     * @param string|array $column
     * @param string       $direction
     *
     * @return $this
     */
    public function sortByRelation($column, $direction = 'ASC');

    /**
     * Append many column sorting to query builder.
     *
     * @param array $columns
     *
     * @return $this
     */
    public function multiSortBy(array $columns);
}
