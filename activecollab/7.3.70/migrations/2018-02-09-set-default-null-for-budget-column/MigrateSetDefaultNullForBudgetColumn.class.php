<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateSetDefaultNullForBudgetColumn extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('projects')) {
            if (!empty($this->execute('SHOW COLUMNS FROM `projects` WHERE `field` = ? AND `Null` = ?', 'budget', 'NO'))) {
                $this->execute('ALTER TABLE `projects` MODIFY COLUMN `budget` decimal(13,3) unsigned DEFAULT NULL');
            }
        }
    }
}
