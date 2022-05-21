<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAppliedTemplatesModel extends AngieModelMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `applied_project_templates` (
            `id` int unsigned NOT NULL auto_increment,
            `project_id` int unsigned NOT NULL DEFAULT 0,
            `template_id` int unsigned NOT NULL DEFAULT 0,
            `created_on` datetime  DEFAULT NULL,
            `created_by_id` int unsigned NULL DEFAULT NULL,
            `created_by_name` varchar(100)  DEFAULT NULL,
            `created_by_email` varchar(150)  DEFAULT NULL,
            PRIMARY KEY (`id`),
            INDEX `project_id` (`project_id`),
            INDEX `template_id` (`template_id`),
            INDEX `created_on` (`created_on`),
            INDEX `created_by_id` (`created_by_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

        $rows = $this->execute(
            'SELECT `id`, `template_id`, `created_on`, `created_by_id`, `created_by_name`, `created_by_email` FROM `projects` WHERE `template_id` > ?',
            0,
        );

        if (!empty($rows)) {
            $batch_insert = DB::batchInsert(
                'applied_project_templates',
                [
                    'project_id',
                    'template_id',
                    'created_on',
                    'created_by_id',
                    'created_by_name',
                    'created_by_email',
                ],
            );

            foreach ($rows as $row) {
                $batch_insert->insert(
                    $row['id'],
                    $row['template_id'],
                    $row['created_on'],
                    $row['created_by_id'],
                    $row['created_by_name'],
                    $row['created_by_email'],
                );
            }

            $batch_insert->done();
        }

        $this->useTableForAlter('projects')->dropColumn('template_id');
    }
}
