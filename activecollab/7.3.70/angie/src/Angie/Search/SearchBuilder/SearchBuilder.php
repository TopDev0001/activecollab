<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\SearchBuilder;

use Angie\Search\SearchEngineInterface;
use Angie\Search\SearchItem\SearchItemInterface;
use Psr\Log\LoggerInterface;

abstract class SearchBuilder implements SearchBuilderInterface
{
    /**
     * @var SearchEngineInterface
     */
    private $search_engine;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(SearchEngineInterface $search_engine, LoggerInterface $logger)
    {
        $this->search_engine = $search_engine;
        $this->logger = $logger;
    }

    public function build(callable $communicate_progress = null, $communicate_progress_every_n_records = 100)
    {
        if ($records = $this->getRecordsToAdd()) {
            $iteration = 0;
            $total_records = count($records);

            foreach ($records as $record) {
                if ($record instanceof SearchItemInterface) {
                    $iteration++;

                    $this->getSearchEngine()->add($record);

                    if ($communicate_progress && ($iteration % $communicate_progress_every_n_records) === 0) {
                        call_user_func($communicate_progress, $iteration, $total_records);
                    }
                } else {
                    $this->logger->warning(
                        'Search  record in {builder} is not a valid SearchItemInterface instance. We got a {record_type}.',
                        [
                            'builder' => get_class($this),
                            'record_type' => is_object($record) ? get_class($record) . ' instance' : gettype($record),
                            'record_value' => is_object($record) ? '--object--' : $record,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Return a list of records that we want to add to the index.
     *
     * @return SearchItemInterface[]
     */
    protected function getRecordsToAdd()
    {
        return [];
    }

    /**
     * @return SearchEngineInterface
     */
    protected function getSearchEngine()
    {
        return $this->search_engine;
    }
}
