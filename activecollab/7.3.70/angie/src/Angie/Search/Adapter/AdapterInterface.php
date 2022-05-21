<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\Adapter;

use ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch\IndexStatus\IndexStatusInterface;
use ActiveCollab\JobsQueue\JobsDispatcherInterface;
use Angie\Search\SearchFilter\TermCriterion;
use Angie\Search\SearchItem\SearchItemInterface;
use Angie\Search\SearchResult\SearchResultInterface;
use User;

/**
 * @package Angie\Search\Adapter
 */
interface AdapterInterface
{
    /**
     * Return index status.
     *
     * @return IndexStatusInterface|null
     */
    public function indexStatus();

    /**
     * Create index.
     *
     * @param  bool       $force
     * @return array|null
     */
    public function createIndex($force = true);

    /**
     * Delete index.
     *
     * @return array|null
     */
    public function deleteIndex();

    /**
     * Delete all documents from tenant id.
     *
     * @return array|null
     */
    public function deleteDocuments();

    /**
     * Return indexed record.
     *
     * @return array|null
     */
    public function get(SearchItemInterface $item);

    /**
     * Add an item to the index.
     *
     * @param bool $bulk
     */
    public function add(SearchItemInterface $item, $bulk = false);

    /**
     * Update an item.
     *
     * @param bool $bulk
     */
    public function update(SearchItemInterface $item, $bulk = false);

    /**
     * Remove an item.
     *
     * @param bool $bulk
     */
    public function remove(SearchItemInterface $item, $bulk = false);

    /**
     * Query the index.
     *
     * @param  string                $search_for
     * @param  TermCriterion[]       $criterions
     * @param  int                   $page
     * @param  int                   $documents_per_page
     * @return SearchResultInterface
     */
    public function query(
        $search_for,
        User $user,
        $criterions = null,
        $page = 1,
        $documents_per_page = 25
    );

    /**
     * @return array
     */
    public function getHosts();

    /**
     * Return index name.
     *
     * @return string
     */
    public function getIndexName();

    /**
     * Return document type.
     *
     * @return string
     */
    public function getDocumentType();

    /**
     * @return int
     */
    public function getTenantId();

    /**
     * @return array
     */
    public function getDocumentMapping();

    /**
     * Return number of shareds.
     *
     * @return int
     */
    public function getNumberOfShards();

    /**
     * Return number of replicas.
     *
     * @return int
     */
    public function getNumberOfReplicas();

    /**
     * @return JobsDispatcherInterface
     */
    public function getJobsDispatcher();
}
