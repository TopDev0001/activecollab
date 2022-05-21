<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateUpdateDefaultAttachmentType extends AngieModelMigration
{
    public function up()
    {
        $this->execute('UPDATE ' . $this->useTables('attachments')[0] . ' SET type = ? WHERE type = ?', 'LocalAttachment', 'Attachment');
    }
}
