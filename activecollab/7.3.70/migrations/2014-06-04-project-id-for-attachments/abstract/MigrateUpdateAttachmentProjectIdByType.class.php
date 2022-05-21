<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Reusable code that updates attachment parent_id and is_hidden_from_client by project element type.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
abstract class MigrateUpdateAttachmentProjectIdByType extends AngieModelMigration
{
    /**
     * @var string
     */
    protected $type_table;
    protected $type_class;

    /**
     * Construct the migration instance.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateProjectIdForAttachments');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        [$type_table, $comments_table, $attachments_table] = $this->useTables($this->type_table, 'comments', 'attachments');

        // ---------------------------------------------------
        //  Updated project ID-s
        // ---------------------------------------------------

        if ($rows = $this->execute("SELECT id, project_id FROM $type_table")) {
            $elements_by_project = [];

            foreach ($rows as $row) {
                if (empty($elements_by_project[$row['project_id']])) {
                    $elements_by_project[$row['project_id']] = [];
                }

                $elements_by_project[$row['project_id']][] = $row['id'];
            }

            foreach ($elements_by_project as $project_id => $element_ids) {
                $comment_ids = $this->executeFirstColumn("SELECT id FROM $comments_table WHERE parent_type = '$this->type_class' AND parent_id IN (?)", $element_ids);

                if ($comment_ids) {
                    $this->execute("UPDATE $attachments_table SET project_id = ? WHERE (parent_type = '$this->type_class' AND parent_id IN (?)) OR (parent_type = 'Comment' AND parent_id IN (?))", $project_id, $element_ids, $comment_ids);
                } else {
                    $this->execute("UPDATE $attachments_table SET project_id = ? WHERE parent_type = '$this->type_class' AND parent_id IN (?)", $project_id, $element_ids);
                }
            }
        }

        // ---------------------------------------------------
        //  Update is_hidden_from_clients
        // ---------------------------------------------------

        if ($element_ids = $this->executeFirstColumn("SELECT id FROM $type_table WHERE is_hidden_from_clients = ?", true)) {
            $comment_ids = $this->executeFirstColumn("SELECT id FROM $comments_table WHERE parent_type = '$this->type_class' AND parent_id IN (?)", $element_ids);

            if ($comment_ids) {
                $this->execute("UPDATE $attachments_table SET is_hidden_from_clients = ? WHERE (parent_type = '$this->type_class' AND parent_id IN (?)) OR (parent_type = 'Comment' AND parent_id IN (?))", true, $element_ids, $comment_ids);
            } else {
                $this->execute("UPDATE $attachments_table SET is_hidden_from_clients = ? WHERE parent_type = '$this->type_class' AND parent_id IN (?)", true, $element_ids);
            }
        }
    }
}
