<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddAutoConnectRepoConnectionField extends AngieModelMigration
{
    public function up()
    {
        $this->execute(
            'ALTER TABLE `vcs_connections` ADD `auto_connect_repositories` TINYINT(1) UNSIGNED NOT NULL DEFAULT "0" AFTER `is_enabled`'
        );
    }
}
