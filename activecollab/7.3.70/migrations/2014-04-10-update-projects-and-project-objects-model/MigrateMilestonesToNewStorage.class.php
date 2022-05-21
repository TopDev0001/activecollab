<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateMilestonesToNewStorage extends AngieModelMigration
{
    public function __construct()
    {
        $this->executeAfter('MigrateUpdateProjectUsersRelation');
    }

    public function up()
    {
        $this->createTable(
            DB::createTable('milestones')->addColumns(
                [
                    new DBIdColumn(),
                    DBIntegerColumn::create('project_id', 10, '0')->setUnsigned(true),
                    new DBNameColumn(150),
                    new DBDateColumn('start_on'),
                    new DBDateColumn('due_on'),
                    new DBActionOnByColumn('completed', true),
                    new DBCreatedOnByColumn(true, true),
                    new DBUpdatedOnByColumn(),
                    new DBTrashColumn(true),
                    DBIntegerColumn::create('position', 10)->setUnsigned(true),
                ]
            )->addIndices(
                [
                    DBIndex::create('project_id'),
                    DBIndex::create('span', DBIndex::KEY, ['start_on', 'due_on']),
                    DBIndex::create('due_on'),
                ]
            )
        );

        $project_objects = $this->useTables('project_objects')[0];

        if ($rows = $this->execute("SELECT id, project_id, name, completed_on, completed_by_id, completed_by_name, completed_by_email, created_on, created_by_id, created_by_name, created_by_email, updated_on, updated_by_id, updated_by_name, updated_by_email, date_field_1 AS 'start_on', due_on, state, position FROM $project_objects WHERE type = 'Milestone'")) {
            $batch = new DBBatchInsert('milestones', ['id', 'project_id', 'name', 'completed_on', 'completed_by_id', 'completed_by_name', 'completed_by_email', 'created_on', 'created_by_id', 'created_by_name', 'created_by_email', 'updated_on', 'updated_by_id', 'updated_by_name', 'updated_by_email', 'start_on', 'due_on', 'is_trashed', 'original_is_trashed', 'trashed_on', 'position']);

            $now = DateTimeValue::now()->toMySQL();
            defined('STATE_TRASHED') or define('STATE_TRASHED', 1);

            $milestone_ids = [];

            foreach ($rows as $row) {
                $milestone_ids[] = $row['id'];

                if ($row['state'] == STATE_TRASHED) {
                    $is_trashed = true;
                    $original_is_trashed = false;
                    $trashed_on = $now;
                } else {
                    $is_trashed = false;
                    $original_is_trashed = false;
                    $trashed_on = null;
                }

                $position = (int) $row['position'];
                if ($position < 1) {
                    $position = 1;
                }

                $batch->insert($row['id'], $row['project_id'], $row['name'], $row['completed_on'], $row['completed_by_id'], $row['completed_by_name'], $row['completed_by_email'], $row['created_on'], $row['created_by_id'], $row['created_by_name'], $row['created_by_email'], $row['updated_on'], $row['updated_by_id'], $row['updated_by_name'], $row['updated_by_email'], $row['start_on'], $row['due_on'], $is_trashed, $original_is_trashed, $trashed_on, $position);
            }

            $batch->done();

            $this->execute("DELETE FROM $project_objects WHERE type = 'Milestone'");

            $comment_ids_by_type = $this->cleanUpComments();
            $this->cleanUpAttachments($milestone_ids, $comment_ids_by_type);
        }

        $this->doneUsingTables();
    }

    /**
     * Clean up milestone comments.
     *
     * @return array
     */
    private function cleanUpComments()
    {
        $comments = $this->useTables('comments')[0];

        $comment_ids_by_type = [];

        if ($milestone_comments = $this->execute("SELECT id, type FROM $comments WHERE parent_type = 'Milestone'")) {
            foreach ($milestone_comments as $milestone_comment) {
                if (empty($comment_ids_by_type[$milestone_comment['type']])) {
                    $comment_ids_by_type[$milestone_comment['type']] = [];
                }

                $comment_ids_by_type[$milestone_comment['type']][] = $milestone_comment['id'];
            }

            $this->execute("DELETE FROM $comments WHERE parent_type = 'Milestone'");

            $comment_conditions = [];

            foreach ($comment_ids_by_type as $parent_type => $parent_ids) {
                $comment_conditions[] = DB::prepare('(parent_type = ? AND parent_id IN (?))', $parent_type, $parent_ids);
            }
        }

        return $comment_ids_by_type;
    }

    /**
     * Clean up attachments from milestones and deleted milestone comments.
     *
     * @param array $milestone_ids
     * @param array $comment_ids_by_type
     */
    private function cleanUpAttachments($milestone_ids, $comment_ids_by_type)
    {
        $attachment_parents = count($comment_ids_by_type) ? $comment_ids_by_type : [];
        $attachment_parents['Milestone'] = $milestone_ids;

        $attachment_conditions = [];

        foreach ($attachment_parents as $parent_type => $parent_ids) {
            $attachment_conditions[] = DB::prepare('(parent_type = ? AND parent_id IN (?))', $parent_type, $parent_ids);
        }

        $attachments = $this->useTables('attachments')[0];

        if ($rows = $this->execute("SELECT id, location FROM $attachments WHERE " . implode(' OR ', $attachment_conditions))) {
            $attachment_ids = [];

            foreach ($rows as $row) {
                if ($row['location'] && is_file(UPLOAD_PATH . '/' . $row['location'])) {
                    @unlink(UPLOAD_PATH . '/' . $row['location']);
                }

                $attachment_ids[] = $row['id'];
            }

            $this->execute("DELETE FROM $attachments WHERE id IN (?)", $attachment_ids);
        }
    }
}
