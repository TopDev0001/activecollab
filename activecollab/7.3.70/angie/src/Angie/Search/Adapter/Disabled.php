<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\Adapter;

use Angie\Search\SearchItem\SearchItemInterface;
use Angie\Search\SearchResult\SearchResult;
use User;

/**
 * Disabled search adapter (black hole).
 *
 * @package Angie\Search\Adapter
 */
final class Disabled extends Adapter
{
    public function indexStatus()
    {
    }

    public function createIndex($force = true)
    {
    }

    public function deleteIndex()
    {
    }

    public function deleteDocuments()
    {
    }

    /**
     * Return indexed record.
     *
     * @return array|null
     * @todo
     */
    public function get(SearchItemInterface $item)
    {
    }

    /**
     * Add an item to the index.
     *
     * @param bool $bulk
     */
    public function add(SearchItemInterface $item, $bulk = false)
    {
    }

    /**
     * Update an item.
     *
     * @param bool $bulk
     */
    public function update(SearchItemInterface $item, $bulk = false)
    {
    }

    /**
     * Remove an item.
     *
     * @param bool $bulk
     */
    public function remove(SearchItemInterface $item, $bulk = false)
    {
    }

    public function query($search_for, User $user, $criterions = null, $page = 1, $documents_per_page = 25)
    {
        return new SearchResult([], 1, 25, 0, 0);
    }

    protected function isReady()
    {
        return true;
    }
}
