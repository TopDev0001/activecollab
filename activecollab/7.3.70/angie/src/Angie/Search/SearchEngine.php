<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search;

use Angie\Error;
use Angie\Events;
use Angie\Search\Adapter\AdapterInterface;
use Angie\Search\SearchItem\SearchItemInterface;
use DateValue;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermsQuery;
use Psr\Log\LoggerInterface;
use User;

final class SearchEngine implements SearchEngineInterface
{
    private AdapterInterface $adapter;
    private LoggerInterface $logger;
    private bool $is_on_demand;
    private bool $is_in_development;

    public function __construct(
        AdapterInterface $adapter,
        LoggerInterface $logger,
        bool $is_on_demand = false,
        bool $is_in_development = false
    )
    {
        $this->adapter = $adapter;
        $this->is_on_demand = $is_on_demand;
        $this->is_in_development = $is_in_development;
        $this->logger = $logger;
    }

    public function doesIndexExists()
    {
        return $this->adapter->indexStatus()->indexExists();
    }

    public function createIndex($force = true)
    {
        return $this->adapter->createIndex($force);
    }

    public function deleteIndex()
    {
        return $this->adapter->deleteIndex();
    }

    public function reset()
    {
        $this->deleteDocuments();
    }

    public function deleteDocuments()
    {
        $this->adapter->deleteDocuments();
    }

    public function query(
        $search_for,
        User $user,
        $criterions = null,
        $page = 1,
        $documents_per_page = 25
    )
    {
        return $this->adapter->query(
            $search_for,
            $user,
            $criterions,
            $page,
            $documents_per_page
        );
    }

    public function get(SearchItemInterface $item)
    {
        return $this->adapter->get($item);
    }

    public function add(SearchItemInterface $item, $bulk = false)
    {
        $this->adapter->add($item, $bulk);
    }

    public function update(SearchItemInterface $item, $bulk = false)
    {
        $this->adapter->update($item, $bulk);
    }

    public function remove(SearchItemInterface $item, $bulk = false)
    {
        $this->adapter->remove($item, $bulk);
    }

    /**
     * @return string
     */
    public function getAdapterName()
    {
        return get_class($this->adapter);
    }

    /**
     * @return array
     */
    public function getHosts()
    {
        return $this->adapter->getHosts();
    }

    /**
     * @return string
     */
    public function getIndexName()
    {
        return $this->adapter->getIndexName();
    }

    /**
     * @return string
     */
    public function getDocumentType()
    {
        return self::DOCUMENT_TYPE;
    }

    /**
     * @return array
     */
    public function getDocumentMapping()
    {
        return $this->adapter->getDocumentMapping();
    }

    /**
     * Return number of shareds.
     *
     * @return int
     * @deprecated
     */
    public function getNumberOfShards()
    {
        return $this->adapter->getNumberOfShards();
    }

    /**
     * Return number of replicas.
     *
     * @return int
     * @deprecated
     */
    public function getNumberOfReplicas()
    {
        return $this->adapter->getNumberOfReplicas();
    }

    /**
     * @var array
     */
    private $filters = false;

    /**
     * Return fields that can be used to filter the results.
     *
     * Key is field name and value is field type
     *
     * @return array
     */
    public function getFilters()
    {
        if ($this->filters === false) {
            $this->filters = [];
            Events::trigger('on_search_filters', [&$this->filters]);
        }

        return $this->filters;
    }

    /**
     * Get criterions from request.
     *
     * @param  array      $input
     * @return array|null
     */
    public function getCriterionsFromRequest($input)
    {
        $result = [];

        if ($input && is_foreachable($input)) {
            foreach ($this->getFilters() as $filter => $type) {
                if (empty($input[$filter])) {
                    continue;
                }

                if ($type === SearchItemInterface::FIELD_NUMERIC) {
                    $ids = [];

                    foreach (explode(',', $input[$filter]) as $id) {
                        $id = (int) $id;

                        if ($id) {
                            $ids[] = $id;
                        }
                    }

                    if (count($ids) > 1) {
                        $result[] = new TermsQuery($filter, $ids);
                    } elseif (count($ids) == 1) {
                        $result[] = new TermQuery($filter, $ids[0]);
                    }
                } elseif ($type === SearchItemInterface::FIELD_DATETIME) {
                    if (strpos($input[$filter], ':') === false) {
                        $date = DateValue::makeFromString($input[$filter]);

                        $params = [];
                        $params[RangeQuery::GTE] = $date->beginningOfDay()->toMySQL();
                        $params[RangeQuery::LTE] = $date->endOfDay()->toMySQL();
                        $result[] = new RangeQuery($filter, $params);
                    } else {
                        [$from, $to] = explode(':', $input[$filter]);

                        $params = [];
                        $params[RangeQuery::GTE] = DateValue::makeFromString($from)->beginningOfDay()->toMySQL();
                        $params[RangeQuery::LTE] = DateValue::makeFromString($to)->endOfDay()->toMySQL();
                        $result[] = new RangeQuery($filter, $params);
                    }
                } elseif ($type === SearchItemInterface::FIELD_STRING) {
                    $values = [];

                    foreach (explode(',', trim($input[$filter])) as $value) {
                        if ($value) {
                            $values[] = $value;
                        }
                    }

                    if (count($values) > 1) {
                        $result[] = new TermsQuery($filter, $values);
                    } elseif (count($values) === 1) {
                        $result[] = new TermQuery($filter, $values[0]);
                    }
                }
            }
        }

        return empty($result) ? null : $result;
    }

    /**
     * Return true if we have a valid filter value.
     *
     * @param  string $field_name
     * @return bool
     */
    public function isValidFilterField($field_name)
    {
        return !empty($this->getFilters()[$field_name]);
    }

    /**
     * Return true if $value is valid filter value for $field_name.
     *
     * @param  string $field_name
     * @param  mixed  $value
     * @return bool
     * @throws Error
     */
    public function isValidFilterValue($field_name, $value)
    {
        $filters = $this->getFilters();

        if (isset($filters[$field_name]) && $filters[$field_name]) {
            if (is_array($value) && empty($value)) {
                return false;
            }

            switch ($filters[$field_name]) {
                case SearchItemInterface::FIELD_NUMERIC:
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            if (!is_numeric($v)) {
                                return false;
                            }
                        }

                        return true;
                    } else {
                        return is_numeric($value);
                    }

                    break;
                case SearchItemInterface::FIELD_DATETIME:
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            if (!($v instanceof DateValue)) {
                                return false;
                            }
                        }

                        return true;
                    } else {
                        return $value instanceof DateValue;
                    }

                    break;
                default:
                    throw new Error('Currently we support numeric and date time filter columns');
            }
        }

        return false;
    }

    public function getBuilders(): array
    {
        $builders = [];

        Events::trigger(
            'on_search_rebuild_index',
            [
                $this,
                $this->logger,
                &$builders,
            ]
        );

        return $builders;
    }
}
