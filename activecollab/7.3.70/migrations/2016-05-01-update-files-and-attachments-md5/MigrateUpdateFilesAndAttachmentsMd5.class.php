<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateUpdateFilesAndAttachmentsMd5 extends AngieModelMigration
{
    public function up()
    {
        $updates = 0;
        $missing_files = 0;
        $failed_hashes = 0;

        foreach (['files' => 'LocalFile', 'attachments' => 'LocalAttachment'] as $table_name => $file_type) {
            if ($rows = $this->execute("SELECT id, location FROM $table_name WHERE type = ? AND (md5 = ? OR md5 IS NULL)", $file_type, '')) {
                foreach ($rows as $row) {
                    $file_location = AngieApplication::fileLocationToPath($row['location']);

                    $log_arguments = ['type' => $file_type, 'file_id' => $row['id']];

                    if (is_file($file_location)) {
                        if ($md5_hash = md5_file($file_location)) {
                            $this->execute("UPDATE $table_name SET md5 = ? WHERE id = ?", md5_file($file_location), $row['id']);

                            $log_arguments['md5_hash'] = $md5_hash;
                            $log_arguments['update_num'] = ++$updates;

                            AngieApplication::log()->debug('Updated {type} #{file_id} md5', $log_arguments);
                        } else {
                            $log_arguments['hash_failed_num'] = ++$failed_hashes;
                            AngieApplication::log()->error('Failed to update {type} #{file_id} md5, md5_file() returned FALSE', $log_arguments);
                        }
                    } else {
                        $log_arguments['missing_file_num'] = ++$missing_files;
                        AngieApplication::log()->error('Failed to update {type} #{file_id} md5, md5_file() returned FALSE', $log_arguments);
                    }
                }
            }
        }

        AngieApplication::log()->info('Local file rehashing done', ['updates' => $updates, 'missing_files' => $missing_files, 'failed_hashes' => $failed_hashes]);
    }
}
