<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add 'decorator' column to outgoing messages table.
 *
 * @package angie.migrations
 */
class MigrateAddCustomMessageDecorator extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->execute("ALTER TABLE outgoing_messages ADD decorator VARCHAR (255) DEFAULT 'OutgoingMessageDecorator'");
    }
}
