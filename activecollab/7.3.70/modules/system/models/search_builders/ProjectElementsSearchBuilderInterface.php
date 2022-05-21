<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Search\SearchBuilder\SearchBuilderInterface;

interface ProjectElementsSearchBuilderInterface extends SearchBuilderInterface
{
    /**
     * @return string
     */
    public function getProjectElements();
}
