<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\SearchResult;

final class SearchResult implements SearchResultInterface
{
    private $hits;
    private $page;
    private $documents_per_page;
    private $total;
    private $exec_time;

    /**
     * @param int $page
     * @param int $documents_per_page
     * @param int $total
     * @param int $exec_time
     */
    public function __construct(
        array $hits,
        $page,
        $documents_per_page,
        $total,
        $exec_time
    )
    {
        $this->hits = $hits;
        $this->total = $total;
        $this->page = $page;
        $this->documents_per_page = $documents_per_page;
        $this->exec_time = $exec_time;
    }

    public function getHits()
    {
        return $this->hits;
    }

    public function getPage()
    {
        return $this->page;
    }

    public function getDocumentsPerPage()
    {
        return $this->documents_per_page;
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function jsonSerialize(): array
    {
        return $this->hits;
    }

    public function getExecTime()
    {
        return $this->exec_time;
    }
}
