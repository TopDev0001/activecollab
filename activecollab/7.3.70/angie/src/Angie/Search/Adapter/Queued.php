<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\Adapter;

use ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch\CreateIndex;
use ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch\DeleteDocument;
use ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch\DeleteDocuments;
use ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch\DeleteIndex;
use ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch\IndexDocument;
use ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch\IndexStatus;
use ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch\QueryIndex;
use ActiveCollab\JobsQueue\Jobs\Job;
use ActiveCollab\JobsQueue\JobsDispatcherInterface;
use Angie\Search\SearchDocument\SearchDocumentInterface;
use Angie\Search\SearchItem\SearchItemInterface;
use Angie\Search\SearchQueryResolver\DeleteDocumentsQueryResolver;
use Angie\Search\SearchQueryResolver\ElasticSearchQueryResolver;
use Angie\Search\SearchResult\Hit\SearchResultHit;
use Angie\Search\SearchResult\SearchResult;
use DataObject;
use DataObjectPool;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use SearchIntegration;
use User;

/**
 * Queued search adapter.
 *
 * @package Angie\Search\Adapter
 */
final class Queued extends Adapter
{
    use ElasticSearch\PrepareQueries;

    public function __construct(
        array $hosts,
        $shards,
        $replicas,
        $index_name,
        $document_type,
        $tenant_id,
        JobsDispatcherInterface $jobs,
        LoggerInterface $logger,
        $is_on_demand = false
    )
    {
        if (empty($hosts)) {
            throw new InvalidArgumentException('Queued search adapter requires one or more ElasticSearch hosts.');
        }
        if (empty($shards)) {
            throw new InvalidArgumentException('Queued search adapter requires number of shards.');
        }
        if (empty($replicas)) {
            throw new InvalidArgumentException('Queued search adapter requires number of replicas.');
        }

        parent::__construct(
            $hosts,
            $shards,
            $replicas,
            $index_name,
            $document_type,
            $tenant_id,
            $jobs,
            $logger,
            $is_on_demand
        );
    }

    public function indexStatus()
    {
        $job = new IndexStatus(
            [
                'hosts' => $this->getHosts(),
                'index' => $this->getIndexName(),
                'instance_id' => $this->getTenantId(),
            ]
        );

        if ($this->isAngieInTest()) {
            return $this->getJobsDispatcher()->dispatch(
                $job,
                SearchIntegration::JOBS_QUEUE_CHANNEL
            );
        } else {
            return $this->getJobsDispatcher()->execute($job, false);
        }
    }

    public function createIndex($force = true)
    {
        $job = new CreateIndex(
            $this->prepareJobData(
                [
                    'number_of_shards' => $this->getNumberOfShards(),
                    'number_of_replicas' => $this->getNumberOfReplicas(),
                    'document_type' => $this->getDocumentType(),
                    'document_mapping' => $this->getDocumentMapping(),
                    'force' => $force,
                ]
            )
        );

        if ($this->isAngieInTest()) {
            return $this->getJobsDispatcher()->dispatch(
                $job,
                SearchIntegration::JOBS_QUEUE_CHANNEL
            );
        } else {
            return $this->getJobsDispatcher()->execute($job, false);
        }
    }

    public function deleteIndex()
    {
        $job = new DeleteIndex($this->prepareJobData([]));

        if ($this->isAngieInTest()) {
            return $this->getJobsDispatcher()->dispatch(
                $job,
                SearchIntegration::JOBS_QUEUE_CHANNEL
            );
        } else {
            return $this->getJobsDispatcher()->execute($job, false);
        }
    }

    public function deleteDocuments()
    {
        $query_resolver = new DeleteDocumentsQueryResolver($this->getTenantId());

        $job = new DeleteDocuments(
            $this->prepareJobData(
                [
                    'type' => $this->getDocumentType(),
                    'body' => $query_resolver->getQuery(),
                    'priority' => Job::HAS_HIGHEST_PRIORITY,
                ]
            )
        );

        if ($this->isAngieInTest()) {
            return $this->getJobsDispatcher()->dispatch(
                $job,
                SearchIntegration::JOBS_QUEUE_CHANNEL
            );
        } else {
            return $this->getJobsDispatcher()->execute($job, false);
        }
    }

    /**
     * Return indexed record.
     *
     * @return array|null
     */
    public function get(SearchItemInterface $item)
    {
        return null;
    }

    /**
     * Add an item to the index.
     *
     * @param bool $bulk
     */
    public function add(SearchItemInterface $item, $bulk = false)
    {
        $this->update($item, $bulk);
    }

    /**
     * Update an item.
     *
     * @param bool $bulk
     */
    public function update(SearchItemInterface $item, $bulk = false)
    {
        if (method_exists($item, 'getSearchDocument')) {
            /** @var SearchDocumentInterface $document */
            $document = $item->getSearchDocument();

            $this->getJobsDispatcher()->dispatch(
                new IndexDocument(
                    $this->prepareDocumentJobData(
                        $document,
                        [
                            'document_payload' => array_merge(
                                $document->getDocumentPayload(),
                                [
                                    'context' => $document->getDocumentContext(),
                                    'tenant_id' => $this->getTenantId(),
                                ]
                            ),
                            'attempts' => 5,
                            'delay' => 60,
                            'first_attempt_delay' => 0,
                        ]
                    )
                ),
                SearchIntegration::JOBS_QUEUE_CHANNEL
            );
        }
    }

    /**
     * Remove an item.
     *
     * @param bool $bulk
     */
    public function remove(SearchItemInterface $item, $bulk = false)
    {
        if (method_exists($item, 'getSearchDocument')) {
            /** @var SearchDocumentInterface $document */
            $document = $item->getSearchDocument();

            $this->getJobsDispatcher()->dispatch(
                new DeleteDocument(
                    $this->prepareDocumentJobData(
                        $document,
                        [
                            'attempts' => 5,
                            'delay' => 60,
                            'first_attempt_delay' => 0,
                        ]
                    )
                ),
                SearchIntegration::JOBS_QUEUE_CHANNEL
            );
        }
    }

    public function query($search_for, User $user, $criterions = null, $page = 1, $documents_per_page = 25)
    {
        if (!$this->isReady()) {
            $this->returnEmptySearchResult($page, $documents_per_page);
        }

        $query_resolver = new ElasticSearchQueryResolver($search_for, $this->getTenantId(), $user, $criterions);

        $response = $this->getJobsDispatcher()->execute(
            new QueryIndex(
                $this->prepareJobData(
                    [
                        'query' => $query_resolver->getQuery(),
                        'page' => $page,
                        'documents_per_page' => $documents_per_page,
                        'default_operator' => 'AND',
                        'highlight' => [
                            '<em class="highlight">',
                            '</em>',
                        ],
                        'type' => $this->getDocumentType(),
                        'tenant_id' => $this->getTenantId(),
                    ]
                )
            ),
            false
        );

        if (empty($response['hits'])) {
            return $this->returnEmptySearchResult($page, $documents_per_page);
        }

        $total_hits = (int) $response['hits']['total'];

        $search_result_hits = [];

        if ($total_hits && !empty($response['hits']['hits'])) {
            foreach ($response['hits']['hits'] as $hit) {
                if ($this->shouldIncludeInSearchResult($hit)) {
                    $object = $this->hydrateObject($hit);

                    if ($object instanceof DataObject) {
                        [$name_highlight, $body_highlight] = $this->extractHighlights($hit);

                        $search_result_hits[] = new SearchResultHit(
                            $object,
                            $hit['_score'],
                            $name_highlight,
                            $body_highlight
                        );
                    }
                }
            }
        }

        if ($total_hits != count($search_result_hits)) {
            $this->logger->warning(
                'Search for {query_string} for tenant #{tenant_id} in index {index} return {total_hits}, but we filtered out {removed_hits}.',
                [
                    'query_string' => $search_for,
                    'tenant_id' => $this->getTenantId(),
                    'index' => $this->getIndexName(),
                    'total_hits' => $total_hits,
                    'removed_hits' => $total_hits - count($search_result_hits),
                ]
            );
        }

        $execution_time = (int) $response['took'];

        $this->logger->info(
            'Search for "{query_string}" return {documents_returned} of {total_documents} in {exec_time} miliseconds.',
            [
                'query_string' => $search_for,
                'documents_returned' => count($search_result_hits),
                'total_documents' => $total_hits,
                'exec_time' => $execution_time,
                'page' => $page,
                'documents_per_page' => $documents_per_page,
            ]
        );

        return new SearchResult(
            $search_result_hits,
            $page,
            $documents_per_page,
            $total_hits,
            $execution_time
        );
    }

    private function returnEmptySearchResult($page, $documents_per_page)
    {
        return new SearchResult([], $page, $documents_per_page, 0, 0);
    }

    private function shouldIncludeInSearchResult(array $hit)
    {
        if ($hit['_index'] !== $this->getIndexName()) {
            $this->logger->error(
                'Got a document from {other_index} instead of {expected_index}.',
                [
                    'expected_index' => $this->getIndexName(),
                    'other_index' => $hit['_index'],
                    'hit' => $hit,
                ]
            );

            return false;
        }

        if (empty($hit['_source']['tenant_id'])) {
            $this->logger->error(
                'Tenant ID not found for {document_type} #{document_id}.',
                [
                    'document_type' => $hit['type'],
                    'document_id' => $hit['id'],
                    'hit' => $hit,
                ]
            );

            return false;
        }

        if ($hit['_source']['tenant_id'] != $this->getTenantId()) {
            $this->logger->critical(
                'Excepted documents for {expected_tenant_id}, but got {document_type} #{document_id} which belongs to {other_tenant_id}.',
                [
                    'expected_tenant_id' => $this->getTenantId(),
                    'other_tenant_id' => $hit['_source']['tenant_id'],
                    'document_type' => $hit['type'],
                    'document_id' => $hit['id'],
                    'hit' => $hit,
                ]
            );

            return false;
        }

        return true;
    }

    private function hydrateObject(array $hit)
    {
        $object = !empty($hit['_source']['type']) && !empty($hit['_source']['id'])
            ? DataObjectPool::get($hit['_source']['type'], $hit['_source']['id'])
            : null;

        if ($object instanceof DataObject) {
            return $object;
        } else {
            $this->logger->error(
                'Search return {document_type} #{document_id}, but we could not load the object.',
                [
                    'document_type' => $hit['type'] ?? '--undefined--',
                    'document_id' => $hit['id'] ?? '--undefined--',
                    'hit' => $hit,
                ]
            );

            return null;
        }
    }

    /**
     * Extract name and body highlights from hit.
     *
     * @return array
     */
    private function extractHighlights(array $hit)
    {
        $name_highlight = [];
        $body_highlight = [];

        if (!empty($hit['highlight']['name'])) {
            $name_highlight = $hit['highlight']['name'];
        }

        if (!empty($hit['highlight']['body'])) {
            $body_highlight = $hit['highlight']['body'];
        }

        if (!empty($hit['highlight']['body_extensions'])) {
            $body_highlight = array_merge(
                $body_highlight,
                $hit['highlight']['body_extensions']
            );
        }

        return [$name_highlight, $body_highlight];
    }

    private function prepareJobData(array $job_data)
    {
        return array_merge(
            [
                'instance_id' => $this->getTenantId(),
                'hosts' => $this->getHosts(),
                'index' => $this->getIndexName(),
            ],
            $job_data
        );
    }

    private function prepareDocumentJobData(SearchDocumentInterface $search_document, array $job_data)
    {
        return array_merge(
            [
                'type' => $this->getDocumentType(),
                'document_id' => $search_document->getDocumentId(),
                'tenant_id' => $this->getTenantId(),
            ],
            $this->prepareJobData($job_data)
        );
    }

    public function getDocumentMapping()
    {
        return [
            'properties' => [
                'tenant_id' => [
                    'type' => 'integer',
                ],
                'context' => [
                    'type' => 'keyword',
                    'index' => true,
                ],
                'type' => [
                    'type' => 'keyword',
                    'index' => true,
                ],
                'id' => [
                    'type' => 'integer',
                ],
                'project_id' => [
                    'type' => 'integer',
                ],
                'timestamps' => [
                    'type' => 'date',
                    'format' => 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis',
                ],
                'created_by_id' => [
                    'type' => 'integer',
                ],
                'assignee_id' => [
                    'type' => 'integer',
                ],
                'label_id' => [
                    'type' => 'integer',
                ],
                'name' => [
                    'type' => 'text',
                ],
                'body' => [
                    'type' => 'text',
                ],
                'body_extensions' => [
                    'type' => 'text',
                ],
                'is_hidden_from_clients' => [
                    'type' => 'boolean',
                ],
            ],
        ];
    }

    protected function isReady()
    {
        return $this->isOnDemand() || !empty($this->getHosts());
    }

    private function isAngieInTest(): bool
    {
        return defined('ANGIE_IN_TEST') && ANGIE_IN_TEST;
    }
}
