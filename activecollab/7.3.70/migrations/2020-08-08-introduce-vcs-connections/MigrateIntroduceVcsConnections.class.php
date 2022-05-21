<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateIntroduceVcsConnections extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('vcs_connections')) {
            $this->execute(
                "CREATE TABLE `vcs_connections` (
                    `id` int unsigned NOT NULL auto_increment,
                    `type` varchar(191) NOT NULL DEFAULT 'ApplicationObject',
                    `name` varchar(191) NOT NULL DEFAULT '',
                    `is_enabled` tinyint(1) unsigned NOT NULL DEFAULT '0',
                    `created_on` datetime  DEFAULT NULL,
                    `created_by_id` int unsigned NULL DEFAULT NULL,
                    `created_by_name` varchar(100)  DEFAULT NULL,
                    `created_by_email` varchar(150)  DEFAULT NULL,
                    `updated_on` datetime  DEFAULT NULL,
                    `raw_additional_properties` longtext ,
                    PRIMARY KEY (`id`),
                    INDEX `type` (`type`),
                    INDEX `name` (`name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
            );
        }

        if (!$this->tableExists('vcs_webhook_logs')) {
            $this->execute(
                'CREATE TABLE `vcs_webhook_logs` (
                    `id` int unsigned NOT NULL auto_increment,
                    `connection_id` int unsigned NOT NULL DEFAULT 0,
                    `payload` text ,
                    `created_on` datetime  DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    INDEX `connection_id` (`connection_id`),
                    INDEX `created_on` (`created_on`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
            );
        }
    }
}
