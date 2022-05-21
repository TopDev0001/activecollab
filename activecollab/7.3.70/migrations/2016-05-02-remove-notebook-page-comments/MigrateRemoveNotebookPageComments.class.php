<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateRemoveNotebookPageComments extends AngieModelMigration
{
    public function up()
    {
        if ($comment_ids = $this->executeFirstCell('SELECT id FROM comments WHERE parent_type = ?', 'NotebookPage')) {
            AngieApplication::log()->info(self::class . ' migration found {notebook_page_comments_num} notebook page comments in account {account_id}', [
                'notebook_page_comments_num' => count($comment_ids),
            ]);

            if ($attachment_rows = $this->execute('SELECT id, type, location FROM attachments WHERE parent_type = ? AND parent_id IN (?)', Comment::class, $comment_ids)) {
                $attachment_ids = [];
                $to_unlink = [];

                foreach ($attachment_rows as $attachment_row) {
                    if ($attachment_row['type'] == LocalAttachment::class && file_exists(UPLOAD_PATH . '/' . $attachment_rows['location'])) {
                        $attachment_file_path = AngieApplication::fileLocationToPath($attachment_rows['location']);

                        if (file_exists($attachment_file_path)) {
                            $to_unlink[] = $attachment_file_path;
                        }
                    }

                    $attachment_ids[] = $attachment_row['id'];
                }

                $this->execute('DELETE FROM attachments WHERE id IN (?)', $attachment_ids);

                if (!empty($to_unlink)) {
                    AngieApplication::log()->info(self::class . ' migration found {attachment_files_to_unlink_num} attachment files to unlink in account {account_id}', [
                        'attachment_files_to_unlink_num' => count($to_unlink),
                    ]);

                    foreach ($to_unlink as $path) {
                        unlink($path);
                    }
                }
            }
        }

        $this->execute('DELETE FROM comments WHERE parent_type = ?', 'NotebookPage');
        $this->execute('DELETE FROM subscriptions WHERE parent_type = ?', 'NotebookPage');
    }
}
