<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateExpandCodeFiledForUploadedFilesTable extends AngieModelMigration
{
    public function up()
    {
        if ($field_details = $this->execute("SHOW COLUMNS FROM `uploaded_files` LIKE 'code'")) {
            if (!empty($field_details) && $field_details[0]['Type'] != 'varchar(50)') {
                $this->execute('ALTER TABLE `uploaded_files` CHANGE COLUMN `code` `code` VARCHAR(50);');
            }
        }
    }
}
