<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\SearchFilter;

interface SearchCriterionInterface
{
    /**
     * @return string
     */
    public function getField();

    /**
     * @return mixed
     */
    public function getValue();

    /**
     * @return array
     */
    public function serialize();
}
