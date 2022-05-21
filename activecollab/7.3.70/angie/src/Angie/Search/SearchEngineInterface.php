<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search;

use Angie\Search\SearchBuilder\SearchBuilderInterface;
use Angie\Search\SearchFilter\TermCriterion;
use Angie\Search\SearchItem\SearchItemInterface;
use Angie\Search\SearchResult\SearchResultInterface;
use User;

interface SearchEngineInterface
{
    const DOCUMENT_TYPE = 'SearchDocument';

    /**
     * Return true if index is created.
     *
     * @return bool
     */
    public function doesIndexExists();

    /**
     * Create index.
     *
     * @param  bool  $force
     * @return mixed
     */
    public function createIndex($force = true);

    /**
     * Delete index.
     *
     * @return mixed
     */
    public function deleteIndex();

    /**
     * Delete documents for tenant.
     *
     * @return mixed
     */
    public function deleteDocuments();

    public function reset();

    /**
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
     * @return array|null
     */
    public function get(SearchItemInterface $item);

    /**
     * @param bool $bulk
     */
    public function add(SearchItemInterface $item, $bulk = false);

    /**
     * @param bool $bulk
     */
    public function update(SearchItemInterface $item, $bulk = false);

    /**
     * @param bool $bulk
     */
    public function remove(SearchItemInterface $item, $bulk = false);

    /**
     * Return adapter name (identifier).
     *
     * @return string
     */
    public function getAdapterName();

    /**
     * @return array
     */
    public function getHosts();

    /**
     * @return string
     */
    public function getIndexName();

    /**
     * @return string
     */
    public function getDocumentType();

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
     * Return fields that can be used to filter the results.
     *
     * Key is field name and value is field type
     *
     * @return array
     */
    public function getFilters();

    /**
     * Get criterions from request.
     *
     * @param  array      $input
     * @return array|null
     */
    public function getCriterionsFromRequest($input);

    /**
     * Return true if we have a valid filter value.
     *
     * @param  string $field_name
     * @return bool
     */
    public function isValidFilterField($field_name);

    /**
     * Return true if $value is valid filter value for $field_name.
     *
     * @param  string $field_name
     * @param  mixed  $value
     * @return bool
     */
    public function isValidFilterValue($field_name, $value);

    /**
     * @return SearchBuilderInterface[]
     */
    public function getBuilders(): array;
}
