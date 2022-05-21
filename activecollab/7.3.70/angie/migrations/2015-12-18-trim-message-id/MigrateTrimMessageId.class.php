<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Trim "<" and ">" from message ids in email_log table.
 *
 * @package angie.migrations
 */
class MigrateTrimMessageId extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->execute("UPDATE email_log SET message_id = REPLACE(REPLACE(message_id, '<', ''), '>', '')");
    }
}
