<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Pager class.
 *
 * Every instance is used to describe a state of paginated result - number of
 * total pages, current page and how many projects are listed per page
 */
final class DBResultPager
{
    /**
     * @var int
     */
    private $total_items = 0;
    private $items_per_page = 10;
    private $current_page = 1;

    /**
     * Cached last page value.
     *
     * If null the value will be calculated by the getLastPage() function and saved
     *
     * @var int
     */
    private $last_page = null;

    /**
     * Construct pager object.
     *
     * @param int $total_items
     * @param int $current_page
     * @param int $per_page
     */
    public function __construct($total_items, $current_page, $per_page)
    {
        $this->current_page = $current_page;
        $this->total_items = $total_items;
        $this->items_per_page = $per_page;
    }

    // ---------------------------------------------------
    //  Logic
    // ---------------------------------------------------

    /**
     * Check if specific page is first page. If $page is null function will use
     * current page.
     *
     * @param  int  $page Page that need to be checked. If null function will
     *                    use current page
     * @return bool
     */
    public function isFirst($page = null)
    {
        $page = is_null($page) ? $this->getCurrentPage() : (int) $page;

        return $page == 1;
    }

    /**
     * Return current page value.
     *
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->current_page;
    }

    /**
     * Check if specific page is last page. If $page is null function will use
     * current page.
     *
     * @param  int  $page Page that need to be checked. If null function will
     *                    use current page
     * @return bool
     */
    public function isLast($page = null)
    {
        $page = is_null($page) ? $this->getCurrentPage() : (int) $page;
        if (is_null($last = $this->getLastPage())) {
            return false;
        }

        return $page == $last;
    }

    /**
     * Return num of last page.
     *
     * @return int
     */
    public function getLastPage()
    {
        if ($this->last_page === null) {
            if (($this->getItemsPerPage() < 1) || ($this->getTotalItems() < 1)) {
                $this->last_page = 1;
            } else {
                if (($this->getTotalItems() % $this->getItemsPerPage()) == 0) {
                    $this->last_page = (int) ($this->getTotalItems() / $this->getItemsPerPage());
                } else {
                    $this->last_page = (int) ($this->getTotalItems() / $this->getItemsPerPage()) + 1;
                }
            }
        }

        return $this->last_page;
    }

    /**
     * Return items per page value.
     *
     * @return int
     */
    public function getItemsPerPage()
    {
        return $this->items_per_page;
    }

    // ---------------------------------------------------
    //  Getters and setters
    // ---------------------------------------------------

    /**
     * Return total items value.
     *
     * @return int
     */
    public function getTotalItems()
    {
        return $this->total_items;
    }

    /**
     * Check if specific page has next page. If $page is null function will use
     * current page.
     *
     * @param  int  $page Page that need to be checked. If null function will
     *                    use current page
     * @return bool
     */
    public function hasNext($page = null)
    {
        $page = is_null($page) ? $this->getCurrentPage() : (int) $page;
        if (is_null($last = $this->getLastPage())) {
            return false;
        }

        return $page < $last;
    }

    /**
     * Returns num of last page... If there is no last page (we are on it) return NULL.
     *
     * @return int
     */
    public function getNextPage()
    {
        return $this->current_page < $this->getLastPage() ? $this->current_page + 1 : $this->getLastPage();
    }
}
