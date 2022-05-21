<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add unique key for note position.
 *
 * @package ActiveCollab.modules.system
 * @subpackage migrations
 */
class MigratePositionKeyForNotes extends AngieModelMigration
{
    private $reindexed_projects = [];
    private $reindexed_notes = [];

    /**
     * Migrate up.
     */
    public function up()
    {
        $notes = $this->useTableForAlter('notes');

        if ($rows = $this->execute('SELECT COUNT(id) AS "row_count", project_id, note_id, position FROM ' . $notes->getName() . ' GROUP BY project_id, note_id, position HAVING row_count > 1')) {
            $notes_table_name = $notes->getName();

            foreach ($rows as $row) {
                if ($row['note_id']) {
                    $this->reindexSubnotes($row['note_id'], $notes_table_name);
                } else {
                    $this->reindexProjectNotes($row['project_id'], $notes_table_name);
                }
            }
        }

        $notes->addIndex(DBIndex::create('note_position', DBIndex::UNIQUE, ['project_id', 'note_id', 'position']));
        $this->doneUsingTables();
    }

    /**
     * @param  int               $note_id
     * @param  string            $notes_table
     * @throws InvalidParamError
     */
    private function reindexSubnotes($note_id, $notes_table)
    {
        if (empty($this->reindexed_notes[$note_id])) {
            $counter = 1;

            foreach ($this->executeFirstColumn("SELECT id FROM $notes_table WHERE note_id = ? ORDER BY position, id", $note_id) as $subnote_id) {
                $this->execute("UPDATE $notes_table SET position = ? WHERE id = ?", $counter++, $subnote_id);
            }

            $this->reindexed_notes[$note_id] = true;
        }
    }

    /**
     * @param  int               $project_id
     * @param  string            $notes_table
     * @throws InvalidParamError
     */
    private function reindexProjectNotes($project_id, $notes_table)
    {
        if (empty($this->reindexed_projects[$project_id])) {
            $counter = 1;

            foreach ($this->executeFirstColumn("SELECT id FROM $notes_table WHERE project_id = ? AND note_id = ? ORDER BY position, id", $project_id, 0) as $note_id) {
                $this->execute("UPDATE $notes_table SET position = ? WHERE id = ?", $counter++, $note_id);
            }

            $this->reindexed_projects[$project_id] = true;
        }
    }
}
