<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Set job data field to be long text.
 *
 * @package angie.migrations
 */
class MigrateJobDataAsLongText extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->execute('ALTER TABLE `jobs_queue` CHANGE `data` `data` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL');
        $this->execute('ALTER TABLE `jobs_queue_failed` CHANGE `data` `data` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL');
    }
}
