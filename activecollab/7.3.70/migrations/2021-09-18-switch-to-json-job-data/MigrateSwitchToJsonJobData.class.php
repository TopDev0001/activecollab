<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateSwitchToJsonJobData extends AngieModelMigration
{
    public function up()
    {
        if (!class_exists('DBJsonColumn', false)) {
            require_once dirname(__DIR__, 2) . '/angie/classes/database/engineer/columns/DBJsonColumn.class.php';
        }

        $jobs_queue = $this->useTableForAlter('jobs_queue');

        $jobs_queue->alterColumn('data', new DBJsonColumn('data'));

        $this->useTableForAlter('jobs_queue_failed')
            ->alterColumn('data', new DBJsonColumn('data'));

        $extractions_to_delete = [
            'priority',
            'instance_id',
            'webhook_id',
        ];

        foreach ($extractions_to_delete as $extraction_to_delete) {
            if ($jobs_queue->getIndex($extraction_to_delete)) {
                $jobs_queue->dropIndex($extraction_to_delete);
            }

            if ($jobs_queue->getColumn($extraction_to_delete)) {
                $jobs_queue->dropColumn($extraction_to_delete);
            }
        }

        $int_extractors_to_add = [
            'priority',
            'instance_id',
            'webhook_id',
        ];

        $after_column = 'data';

        foreach ($int_extractors_to_add as $int_extractor_to_add) {
            $this->execute(
                sprintf(
                    "ALTER TABLE `jobs_queue` ADD `%s`INT UNSIGNED GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.%s'))) STORED AFTER `%s`",
                    $int_extractor_to_add,
                    $int_extractor_to_add,
                    $after_column
                )
            );

            $jobs_queue->addIndex(new DBIndex($int_extractor_to_add));

            $after_column = $int_extractor_to_add;
        }
    }
}
