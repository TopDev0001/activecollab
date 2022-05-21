<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateIntroduceVcsRepositoriesModel extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('vcs_repositories')) {
            $this->execute("
                CREATE TABLE `vcs_repositories` (
                    `id` int unsigned NOT NULL auto_increment,
                    `type` varchar(191) NOT NULL DEFAULT 'ApplicationObject',
                    `connection_id` int unsigned NOT NULL DEFAULT 0,
                    `name` varchar(191) NOT NULL DEFAULT '',
                    `default_branch` varchar(191) NOT NULL DEFAULT 'master',
                    `is_fork` tinyint(1) unsigned NOT NULL DEFAULT '0',
                    `created_on` datetime  DEFAULT NULL,
                    `updated_on` datetime  DEFAULT NULL,
                    `raw_additional_properties` longtext ,
                    PRIMARY KEY (`id`),
                    INDEX `type` (`type`),
                    INDEX `connection_id` (`connection_id`),
                    INDEX `name` (`name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
            );
        }
    }
}
