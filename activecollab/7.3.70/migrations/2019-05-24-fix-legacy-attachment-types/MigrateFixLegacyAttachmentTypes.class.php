<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateFixLegacyAttachmentTypes extends AngieModelMigration
{
    public function up()
    {
        if ($this->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `attachments` WHERE `type` = "ProjectRequestAttachment"')) {
            $this->execute('DELETE FROM `attachments` WHERE `type` = "ProjectRequestAttachment"');
        }

        if ($this->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `attachments` WHERE `type` = "ProjectObjectAttachment"')) {
            $this->execute('UPDATE `attachments` SET `type` = "LocalAttachment" WHERE `type` = "ProjectObjectAttachment"');
        }
    }
}
