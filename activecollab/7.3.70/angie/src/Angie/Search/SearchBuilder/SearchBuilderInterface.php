<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\SearchBuilder;

interface SearchBuilderInterface
{
    /**
     * Return builder's name.
     *
     * @return string
     */
    public function getName();

    public function build(callable $communicate_progress = null, $communicate_progress_every_n_records = 100);
}
