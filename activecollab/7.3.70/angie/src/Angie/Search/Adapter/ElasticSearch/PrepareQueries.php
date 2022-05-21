<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\Adapter\ElasticSearch;

use Angie\Events;
use Angie\Search\SearchFilter\BoolFilter;
use Angie\Search\SearchFilter\TermCriterion;
use DateTimeValue;
use Elastica\Filter\AbstractFilter;
use Elastica\Filter\BoolAnd;
use Elastica\Filter\BoolOr;
use Elastica\Filter\Range;
use Elastica\Filter\Term;
use Elastica\Filter\Terms;
use Elastica\Query;
use Elastica\Query\Filtered as FilteredQuery;
use Elastica\Query\QueryString;
use Elastica\Util;
use User;

trait PrepareQueries
{
    /**
     * Prepare search query.
     *
     * @param  string          $search_for
     * @param  TermCriterion[] $criterions
     * @param  int             $page
     * @return Query
     */
    private function prepareQuery($search_for, User $user, $criterions = null, $page = 1)
    {
        $query = new Query();
        $query->setSize(100);

        if ($page > 1) {
            $query->setFrom(($page - 1) * 100);
        }

        $query_string = new QueryString(Util::escapeTerm($search_for));
        $query_string->setDefaultOperator('AND');

        if (($filter = $this->prepareQueryFilter($user, $criterions)) instanceof AbstractFilter) {
            $filted_query = new FilteredQuery();

            $filted_query->setFilter($filter);
            $filted_query->setQuery($query_string);

            $query->setQuery($filted_query);
        } else {
            $query->setQuery($query_string);
        }

        $query->setHighlight([
            'pre_tags' => ['<em class="highlight">'],
            'post_tags' => ['</em>'],
            'fields' => [
                'name' => ['fragment_size' => 255, 'number_of_fragments' => 1],
                'body' => ['fragment_size' => 80, 'number_of_fragments' => 5],
            ],
        ]);

        return $query;
    }

    /**
     * Return query filter.
     *
     * @param  TermCriterion[]              $criterions
     * @return \Elastica\Filter\BoolOr|null
     */
    private function prepareQueryFilter(User $user, $criterions = null)
    {
        $access_filter = $this->prepareUserAccessFilter($user);

        $specified_filter = $criterions && is_foreachable($criterions) ? $this->prepareUserSpecifiedFilter($criterions) : null;

        if ($access_filter instanceof AbstractFilter && $specified_filter instanceof AbstractFilter) {
            $and = new BoolFilter();
            $and->addMust($access_filter);
            $and->addMust($specified_filter);

            return $and;
        } elseif ($access_filter instanceof AbstractFilter) {
            return $access_filter;
        } elseif ($specified_filter instanceof AbstractFilter) {
            return $specified_filter;
        } else {
            return null;
        }
    }

    /**
     * @var array
     */
    private $special_suggesters = false;

    /**
     * Return a list of special suggesters.
     *
     * @return array
     */
    private function getSpecialSuggesters()
    {
        if ($this->special_suggesters === false) {
            $this->special_suggesters = [];
            Events::trigger('on_search_special_suggesters', [&$this->special_suggesters]);
        }

        return $this->special_suggesters;
    }

    /**
     * Prepare filter based on user's access permissions.
     *
     * @return BoolOr|null
     */
    private function prepareUserAccessFilter(User $user)
    {
        if (!$user->isOwner()) {
            $filter = new BoolFilter();

            Events::trigger('on_user_access_search_filter', [&$user, &$filter]);

            if ($filter->hasRules()) {
                return $filter->getFilter();
            }
        }

        return null;
    }

    /**
     * @param  TermCriterion[] $criterions
     * @return BoolAnd|null
     */
    private function prepareUserSpecifiedFilter($criterions = null)
    {
        $filter = new BoolAnd();

        foreach ($criterions as $criterion) {
            $value = (array) $criterion->getValue();

            switch (count($value)) {
                case 0:
                    break;
                case 1:
                    $filter->addFilter(new Term([$criterion->getField() => first($value)]));
                    break;
                case 2:
                    if ($criterion->getOperator() == TermCriterion::FILTER_BETWEEN) {
                        $filter->addFilter(new Range($criterion->getField(), [
                            'gte' => $value[0] instanceof DateTimeValue ? $value[0]->toMySQL() : $value[0],
                            'lte' => $value[1] instanceof DateTimeValue ? $value[1]->toMySQL() : $value[1],
                        ]));
                    } else {
                        $filter->addFilter(new Terms($criterion->getField(), $value));
                    }

                    break;
                default:
                    $filter->addFilter(new Terms($criterion->getField(), $value));
            }
        }

        return count($filter->getFilters()) ? $filter : null;
    }
}
