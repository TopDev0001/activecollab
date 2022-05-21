<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate last file comment excerpt.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateLastFileCommentCache extends AngieModelMigration
{
    /**
     * Construct migration.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateTriggersForLastCommentOn');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        [$files, $comments] = $this->useTables('files', 'comments');

        if ($rows = DB::execute("SELECT c1.parent_id AS 'file_id', c1.created_on AS 'last_comment_on' FROM $comments c1 LEFT JOIN $comments c2 ON (c1.parent_type = c2.parent_type AND c1.parent_id = c2.parent_id AND c1.id < c2.id) WHERE c1.parent_type = 'File' AND c1.is_trashed = ? AND c2.id IS NULL", false)) {
            foreach ($rows as $row) {
                $this->execute("UPDATE $files SET last_comment_on = ? WHERE id = ?", $row['last_comment_on'], $row['file_id']);
            }
        }

        $this->doneUsingTables();
    }
}
