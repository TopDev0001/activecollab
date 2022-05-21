<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add fields and triggers for last comment on behavior.
 *
 * @package ActiveCollab.modules.system
 * @subpackage migrations
 */
class MigrateTriggersForLastCommentOn extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        [$discussions_table, $tasks_table, $files_table, $notes_table, $comments_table] = $this->useTables('discussions', 'tasks', 'files', 'notes', 'comments');

        foreach ([$discussions_table, $tasks_table, $files_table, $notes_table] as $t) {
            $this->execute("ALTER TABLE $t ADD last_comment_on DATETIME NULL");
        }

        $this->execute('DROP TRIGGER IF EXISTS default_last_comment_on_for_discussions');
        $this->execute("CREATE TRIGGER default_last_comment_on_for_discussions BEFORE INSERT ON $discussions_table FOR EACH ROW SET NEW.last_comment_on = NEW.created_on");

        $this->execute('DROP TRIGGER IF EXISTS project_element_comment_inserted');
        $this->execute("CREATE TRIGGER project_element_comment_inserted AFTER INSERT ON $comments_table FOR EACH ROW
      BEGIN
        IF NEW.parent_type = 'Task' THEN
          UPDATE $tasks_table SET last_comment_on = NEW.created_on WHERE id = NEW.parent_id;
        ELSEIF NEW.parent_type = 'Discussion' THEN
          UPDATE $discussions_table SET last_comment_on = NEW.created_on WHERE id = NEW.parent_id;
        ELSEIF NEW.parent_type = 'File' THEN
          UPDATE $files_table SET last_comment_on = NEW.created_on WHERE id = NEW.parent_id;
        ELSEIF NEW.parent_type = 'Note' THEN
          UPDATE $notes_table SET last_comment_on = NEW.created_on WHERE id = NEW.parent_id;
        END IF;
      END");

        $this->execute('DROP TRIGGER IF EXISTS project_element_comment_updated');
        $this->execute("CREATE TRIGGER project_element_comment_updated AFTER UPDATE ON $comments_table FOR EACH ROW
      BEGIN
        IF NEW.parent_id = OLD.parent_id AND NEW.is_trashed != OLD.is_trashed THEN
          IF NEW.parent_type = 'Task' THEN
            UPDATE $tasks_table SET last_comment_on = (SELECT MAX(created_on) FROM $comments_table WHERE parent_type = 'Task' AND parent_id = NEW.parent_id AND is_trashed = '0') WHERE id = NEW.parent_id;
          ELSEIF NEW.parent_type = 'Discussion' THEN
            SET @ref = (SELECT MAX(created_on) FROM $comments_table WHERE parent_type = 'Discussion' AND parent_id = NEW.parent_id AND is_trashed = '0');

            IF @ref IS NULL THEN
              UPDATE $discussions_table SET last_comment_on = created_on WHERE id = NEW.parent_id;
            ELSE
              UPDATE $discussions_table SET last_comment_on = @ref WHERE id = NEW.parent_id;
            END IF;
          ELSEIF NEW.parent_type = 'File' THEN
            UPDATE $files_table SET last_comment_on = (SELECT MAX(created_on) FROM $comments_table WHERE parent_type = 'File' AND parent_id = NEW.parent_id AND is_trashed = '0') WHERE id = NEW.parent_id;
          ELSEIF NEW.parent_type = 'Note' THEN
            UPDATE $notes_table SET last_comment_on = (SELECT MAX(created_on) FROM $comments_table WHERE parent_type = 'Note' AND parent_id = NEW.parent_id AND is_trashed = '0') WHERE id = NEW.parent_id;
          END IF;
        END IF;
      END");

        $this->execute('DROP TRIGGER IF EXISTS project_element_comment_deleted');
        $this->execute("CREATE TRIGGER project_element_comment_deleted AFTER DELETE ON $comments_table FOR EACH ROW
      BEGIN
        IF OLD.parent_type = 'Task' THEN
          UPDATE $tasks_table SET last_comment_on = (SELECT MAX(created_on) FROM $comments_table WHERE parent_type = 'Task' AND parent_id = OLD.parent_id AND is_trashed = '0') WHERE id = OLD.parent_id;
        ELSEIF OLD.parent_type = 'Discussion' THEN
          SET @ref = (SELECT MAX(created_on) FROM $comments_table WHERE parent_type = 'Discussion' AND parent_id = OLD.parent_id AND is_trashed = '0');

          IF @ref IS NULL THEN
            UPDATE $discussions_table SET last_comment_on = created_on WHERE id = OLD.parent_id;
          ELSE
            UPDATE $discussions_table SET last_comment_on = @ref WHERE id = OLD.parent_id;
          END IF;
        ELSEIF OLD.parent_type = 'File' THEN
          UPDATE $files_table SET last_comment_on = (SELECT MAX(created_on) FROM $comments_table WHERE parent_type = 'File' AND parent_id = OLD.parent_id AND is_trashed = '0') WHERE id = OLD.parent_id;
        ELSEIF OLD.parent_type = 'Note' THEN
          UPDATE $notes_table SET last_comment_on = (SELECT MAX(created_on) FROM $comments_table WHERE parent_type = 'Note' AND parent_id = OLD.parent_id AND is_trashed = '0') WHERE id = OLD.parent_id;
        END IF;
      END");
    }
}
