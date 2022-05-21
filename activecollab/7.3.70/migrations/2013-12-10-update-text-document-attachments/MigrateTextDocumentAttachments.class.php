<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Class MigrateTextDocumentAttachments.
 *
 * Fix type for attachments added to text documents
 *
 * @package activecollab.modules.files
 */
class MigrateTextDocumentAttachments extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->execute("UPDATE attachments SET type = 'Attachment' WHERE type = 'ProjectObjectAttachment' AND parent_type = 'TextDocument'");
    }
}
