<?php

namespace SebastianBerc\Repositories\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class SearchService.
 *
 * @author  Sebastian Berć <sebastian.berc@gmail.com>
 */
class SearchService
{
    /**
     * Contains bindings for select query.
     *
     * @var array
     */
    private $bindings = [];

    /**
     * Contains a query builder instance.
     *
     * @var Builder
     */
    private $builder;

    /**
     * Contains searchable columns.
     *
     * @var array
     */
    private $columns = [];

    /**
     * Contains relations names for query joins.
     *
     * @var array
     */
    private $joins = [];

    /**
     * Contains selects for query.
     *
     * @var array
     */
    private $selects = [];

    /**
     * Contains total relevance count.
     *
     * @var int
     */
    private $relevanceCount = 0;

    /**
     * Contains value of threshold.
     *
     * @var float
     */
    private $threshold;

    /**
     * Create a new search service instance.
     *
     * @param Builder $builder
     * @param array   $searchable
     * @param float   $threshold
     */
    public function __construct(Builder $builder, array $searchable = [], $threshold = null)
    {
        $this->builder   = $builder;
        $this->columns   = $this->setSearchableColumns($searchable);
        $this->joins     = $this->getJoinsFromEagerLoad();
        $this->threshold = $threshold;
    }

    /**
     * Adds value to total value of relevance.
     *
     * @param int $value
     *
     * @return void
     */
    public function addToRelevanceCount($value)
    {
        $this->relevanceCount += $value;
    }

    /**
     * Returns total count of relevance.
     *
     * @return int
     */
    public function getRelevanceCount()
    {
        return $this->relevanceCount;
    }

    /**
     * Returns a value of threshold for query results.
     *
     * @return float
     */
    public function getThreshold()
    {
        if (!$this->threshold) {
            return $this->getRelevanceCount() / 4;
        }

        return $this->threshold;
    }

    /**
     * Creates the search scope.
     *
     * @param string $search
     *
     * @return Builder
     */
    public function search($search)
    {
        if (!empty($columns)) {
            $this->columns = $this->setSearchableColumns($columns);
        }

        $query = clone $this->builder;
        $query->select($query->getModel()->getTable() . '.*');
        $this->makeJoins($query);

        $words = $this->getSearchWords($search);

        foreach ($this->getColumns() as $column => $relevance) {
            $this->addToRelevanceCount($relevance);
            $this->getSearchQueriesForColumn($column, $relevance, $words);
        }

        $this->addSelectsToQuery($query);

        $this->filterQueryWithRelevance($query, $this->getThreshold());

        $this->makeGroupBy($query);

        $this->addBindingsToQuery($query);

        $this->mergeQueries($query, $this->builder);

        return $this->builder;
    }

    /**
     * Split a words from search phrase.
     *
     * @param string $phrase
     *
     * @return array
     */
    protected function getSearchWords($phrase)
    {
        return explode(' ', mb_strtolower(trim($phrase)));
    }

    /**
     * Adds the sql joins to the query.
     *
     * @param Builder $query
     *
     * @return void
     */
    protected function makeJoins(Builder $query)
    {
        foreach ($this->getJoins() as $relationName) {
            $relation = $query->getModel()->{$relationName}();

            if ($this->shouldJoin($relationName)) {
                $this->{'join' . class_basename($relation)}($relationName, $relation, $query);
            }
        }
    }

    /**
     * Checks if relation should be joined in query.
     *
     * We will join relation only in case when we searching
     * in his columns otherwise not because of performance.
     *
     * @param string $relationName
     *
     * @return bool
     */
    protected function shouldJoin($relationName)
    {
        foreach ($this->columns as $column => $relevance) {
            if (Str::contains($column, "{$relationName}.")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Join tables from belongs to many relation.
     *
     * @param string        $relationName
     * @param BelongsToMany $relation
     * @param Builder       $query
     *
     * @return void
     */
    protected function joinBelongsToMany($relationName, BelongsToMany $relation, Builder $query)
    {
        $query->leftJoin(
            "{$relation->getTable()} as {$relationName}",
            $relation->getForeignKey(),
            '=',
            "{$relationName}.{$this->builder->getModel()->getForeignKey()}"
        );
    }

    /**
     * Join tables from belongs to relation.
     *
     * @param string    $relationName
     * @param BelongsTo $relation
     * @param Builder   $query
     *
     * @return void
     */
    protected function joinBelongsTo($relationName, BelongsTo $relation, Builder $query)
    {
        $query->leftJoin(
            "{$relation->getRelated()->getTable()} as {$relationName}",
            "{$relation->getQualifiedForeignKey()}",
            '=',
            "{$relationName}.{$relation->getRelated()->getKeyName()}"
        );
    }

    /**
     * Join tables from has many relation.
     *
     * @param string  $relationName
     * @param HasMany $relation
     * @param Builder $query
     *
     * @return void
     */
    protected function joinHasMany($relationName, HasMany $relation, Builder $query)
    {
        $this->joinHasOneOrMany($relationName, $relation, $query);
    }

    /**
     * Join tables from has one relation.
     *
     * @param string  $relationName
     * @param HasOne  $relation
     * @param Builder $query
     *
     * @return void
     */
    protected function joinHasOne($relationName, HasOne $relation, Builder $query)
    {
        $this->joinHasOneOrMany($relationName, $relation, $query);
    }

    /**
     * Join tables from has one or many relation.
     *
     * @param string       $relationName
     * @param HasOneOrMany $relation
     * @param Builder      $query
     *
     * @return void
     */
    protected function joinHasOneOrMany($relationName, HasOneOrMany $relation, Builder $query)
    {
        $query->leftJoin(
            "{$relation->getRelated()->getTable()} as {$relationName}",
            $relation->getQualifiedParentKeyName(),
            '=',
            "{$relationName}.{$relation->getParent()->getForeignKey()}"
        );
    }

    /**
     * Returns join from eager load relations.
     *
     * @return array
     */
    private function getJoinsFromEagerLoad()
    {
        return array_keys($this->builder->getEagerLoads());
    }

    /**
     * Returns the search columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return $this->columns;
    }

    /**
     * Returns the columns from database schema.
     *
     * @return array
     */
    private function getColumnsFromSchema()
    {
        return DB::connection()->getSchemaBuilder()->getColumnListing($this->builder->getModel()->getTable());
    }

    /**
     * Returns the search queries for the specified column.
     *
     * @param string $column
     * @param float  $relevance
     * @param array  $words
     *
     * @return void
     */
    protected function getSearchQueriesForColumn($column, $relevance, array $words)
    {
        $this->selects[] = $this->getSearchQuery($column, $relevance, $words, 15);
        $this->selects[] = $this->getSearchQuery($column, $relevance, $words, 5, '', '%');
        $this->selects[] = $this->getSearchQuery($column, $relevance, $words, 1, '%', '%');
    }

    /**
     * Returns the sql string for the given parameters.
     *
     * @param string $column
     * @param string $relevance
     * @param array  $words
     * @param float  $multiplier
     * @param string $prefix
     * @param string $postfix
     *
     * @return string
     */
    protected function getSearchQuery(
        $column,
        $relevance,
        array $words,
        $multiplier,
        $prefix = '',
        $postfix = ''
    ) {
        $comparator = $this->getDatabaseDriver() == 'pgsql' ? 'ILIKE' : 'LIKE';
        $cases      = [];

        foreach ($words as $word) {
            $cases[] = $this->getCaseCompare($column, $comparator, $relevance * $multiplier);

            $this->bindings[] = $prefix . $word . $postfix;
        }

        return implode(' + ', $cases);
    }

    /**
     * Puts all the select clauses to the main query.
     *
     * @param Builder $query
     *
     * @return void
     */
    protected function addSelectsToQuery(Builder $query)
    {
        $query->addSelect(new Expression(implode(' + ', $this->selects) . ' as relevance'));
    }

    /**
     * Adds the relevance filter to the query.
     *
     * @param Builder $query
     * @param float   $relCount
     *
     * @return void
     */
    protected function filterQueryWithRelevance(Builder $query, $relCount)
    {
        $comparator = $this->getDatabaseDriver() != 'mysql' ? implode(' + ', $this->selects) : 'relevance';

        $query->havingRaw("$comparator > " . number_format($relCount, 2, '.', ''));
        $query->orderBy('relevance', 'desc');
    }

    /**
     * Makes the query not repeat the results.
     *
     * @param Builder $query
     *
     * @return void
     */
    protected function makeGroupBy(Builder $query)
    {
        if ($this->getDatabaseDriver() !== 'sqlsrv') {
            $id = $query->getModel()->getTable() . '.' . $query->getModel()->getKeyName();

            foreach ($this->getColumns() as $column => $relevance) {
                array_map(function ($join) use ($column, $query) {
                    if (Str::contains($column, $join)) {
                        $query->groupBy("$column");
                    }
                }, array_keys(($this->getJoins())));
            }

            $query->groupBy($id);
        }
    }

    /**
     * Adds the bindings to the query.
     *
     * @param Builder $query
     *
     * @return void
     */
    protected function addBindingsToQuery(Builder $query)
    {
        $count = $this->getDatabaseDriver() != 'mysql' ? 2 : 1;

        for ($i = 0; $i < $count; $i++) {
            foreach ($this->bindings as $binding) {
                $type = $i == 0 ? 'select' : 'having';
                $query->addBinding($binding, $type);
            }
        }
    }

    /**
     * Merge our cloned query builder with the original one.
     *
     * @param Builder $clone
     * @param Builder $original
     *
     * @return void
     */
    protected function mergeQueries(Builder $clone, Builder $original)
    {
        $original->from(DB::raw("({$clone->toSql()}) as `{$original->getModel()->getTable()}`"));
        $original->mergeBindings($clone->getQuery());
    }

    /**
     * Returns the tables that are to be joined.
     *
     * @return array
     */
    protected function getJoins()
    {
        return $this->joins;
    }

    /**
     * Returns database driver Ex: mysql, pgsql, sqlite.
     *
     * @return array
     */
    protected function getDatabaseDriver()
    {
        return DB::connection()->getDriverName();
    }

    /**
     * Returns the comparison string.
     *
     * @param string $column
     * @param string $compare
     * @param float  $relevance
     *
     * @return string
     */
    protected function getCaseCompare($column, $compare, $relevance)
    {
        $field = 'LOWER(`' . str_replace('.', '`.`', $column) . '`) ' . $compare . ' ?';

        return '(case when ' . $field . ' then ' . $relevance . ' else 0 end)';
    }

    /**
     * Sets columns as searchable and prefix them with table name if dont belongs to any relation.
     *
     * @param array $searchable
     *
     * @return array
     */
    private function setSearchableColumns(array $searchable)
    {
        if (empty($searchable)) {
            $relevance  = array_fill(0, count($columns = $this->getColumnsFromSchema()), 1);
            $searchable = array_combine($columns, $relevance);
        }

        $columns = array_map(function ($column) {
            if (!Str::contains($column, '.')) {
                $column = "{$this->builder->getModel()->getTable()}.{$column}";
            }

            return $column;
        }, array_keys($searchable));

        return array_combine($columns, array_values($searchable));
    }
}
