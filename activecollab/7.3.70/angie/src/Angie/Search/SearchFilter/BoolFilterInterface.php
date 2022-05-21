<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\SearchFilter;

interface BoolFilterInterface
{
    /**
     * @return SearchCriterionInterface[]
     */
    public function getMust();

    /**
     * Add criterion to the filter.
     *
     * This method SHOULD raise an exception if criterion for the given field is already set.
     *
     * @return BoolFilterInterface
     */
    public function must(SearchCriterionInterface $criterion);

    /**
     * Replace criterion to the filter.
     *
     * This method SHOULD always set a criterion, even if criterion for the field is already set
     *
     * @return BoolFilterInterface
     */
    public function mustReplace(SearchCriterionInterface $criterion);

    /**
     * Convert criterions to a set of filters.
     *
     * @return array
     */
    public function serialize();
}
