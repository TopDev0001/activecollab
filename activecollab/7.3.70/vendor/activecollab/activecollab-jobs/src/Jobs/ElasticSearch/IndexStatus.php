<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch;

use ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch\IndexStatus\IndexStatus as IndexStatusResult;
use ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch\IndexStatus\IndexStatusInterface as IndexStatusResultInterface;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use RuntimeException;

final class IndexStatus extends Job
{
    /**
     * @return IndexStatusResultInterface
     */
    public function execute()
    {
        $index_name = $this->getData('index');

        try {
            $index_count_data = $this->getClient()->count(
                [
                    'index' => $index_name,
                ]
            );

            $index_count = is_array($index_count_data) && array_key_exists('count', $index_count_data)
                ? (int) $index_count_data['count']
                : 0;

            $status = $this->getClient()->indices()->get(
                [
                    'index' => $index_name,
                ]
            );

            if (is_array($status)
                && !empty($status[$index_name]['settings']['index'])
                && array_key_exists('creation_date', $status[$index_name]['settings']['index'])
                && array_key_exists('number_of_shards', $status[$index_name]['settings']['index'])
                && array_key_exists('number_of_replicas', $status[$index_name]['settings']['index'])
            ) {
                return new IndexStatusResult(
                    $index_name,
                    true,
                    floor(((int) $status[$index_name]['settings']['index']['creation_date']) / 1000),
                    (int) $status[$index_name]['settings']['index']['number_of_shards'],
                    (int) $status[$index_name]['settings']['index']['number_of_replicas'],
                    $index_count
                );
            } else {
                $this->log->error(
                    'Index status response is not properly formatted.',
                    [
                        'status_response' => $status,
                    ]
                );

                throw new RuntimeException('Index status response is not properly formatted.');
            }
        } catch (Missing404Exception $e) {
            return new IndexStatusResult(
                $index_name,
                false
            );
        }
    }
}
