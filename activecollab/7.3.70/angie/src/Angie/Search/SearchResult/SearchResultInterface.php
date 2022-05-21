<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\SearchResult;

use Angie\Search\SearchResult\Hit\SearchResultHitInterface;
use JsonSerializable;

interface SearchResultInterface extends JsonSerializable
{
    /**
     * Return hits for the current page.
     *
     * @return SearchResultHitInterface[]
     */
    public function getHits();

    /**
     * Return current page.
     *
     * @return int
     */
    public function getPage();

    /**
     * Return number of documents per page.
     *
     * @return int
     */
    public function getDocumentsPerPage();

    /**
     * Return number of total documents that we found.
     *
     * @return int
     */
    public function getTotal();

    /**
     * Return search query execution time, in miliseconds.
     *
     * @return int
     */
    public function getExecTime();
}
