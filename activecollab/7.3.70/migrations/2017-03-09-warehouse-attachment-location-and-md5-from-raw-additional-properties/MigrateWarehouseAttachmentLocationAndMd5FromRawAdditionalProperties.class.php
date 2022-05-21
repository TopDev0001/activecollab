<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateWarehouseAttachmentLocationAndMd5FromRawAdditionalProperties extends AngieModelMigration
{
    public function __construct()
    {
        $this->executeAfter('MigrateRemoveLocationFieldUniquenessInAttachmentsTable');
    }

    public function up()
    {
        [$attachments_table] = $this->useTables('attachments');

        if ($rows = $this->execute("SELECT id, raw_additional_properties FROM $attachments_table WHERE type = 'WarehouseAttachment' AND location IS NULL AND md5 IS NULL")) {
            foreach ($rows as $row) {
                $properties = $row['raw_additional_properties'] ? unserialize($row['raw_additional_properties']) : [];

                $location = isset($properties['location']) ? $properties['location'] : null;
                $md5 = isset($properties['md5']) ? $properties['md5'] : null;

                unset($properties['location']);
                unset($properties['md5']);

                $this->execute("UPDATE $attachments_table SET location = ?, md5 = ?, raw_additional_properties = ? WHERE id = ?", $location, $md5, serialize($properties), $row['id']);
            }
        }

        $this->doneUsingTables();
    }
}
