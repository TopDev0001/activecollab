<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Organize notes into groups.
 *
 * @package ActiveCollab.migrations
 */
class MigrateNotesToGroups extends AngieModelMigration
{
    /**
     * Make sure that this migration is executed after given list of migrations.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateAddNoteGroupsTable');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        $note_groups = $this->useTables('note_groups')[0];
        $notes = $this->useTableForAlter('notes');

        if (!$notes->getColumn('note_group_id')) {
            $notes->addColumn(DBIntegerColumn::create('note_group_id', 10, 0)->setUnsigned(true), 'project_id');
        }

        if ($notes->indexExists('note_position')) {
            $notes->dropIndex('note_position');
        }

        if ($notes->indexExists('note_id')) {
            $notes->dropIndex('note_id');
        }

        if ($rows = $this->execute("SELECT note_id, project_id FROM {$notes->getName()} WHERE note_id != ? GROUP BY note_id, project_id;", 0)) {
            $note_ids = [];

            for ($i = 0; $i < $rows->count(); ++$i) {
                $note_ids[] = $rows[$i]['note_id'];
                $note_id = $rows[$i]['note_id'];
                $project_id = $rows[$i]['project_id'];

                $this->execute("INSERT INTO $note_groups (project_id, position) VALUES (?, ?)", $project_id, $i + 1);
                $note_group_id = $this->lastInsertId();

                $this->execute("UPDATE {$notes->getName()} SET note_group_id = ? WHERE (id = ? OR note_id = ?) AND project_id = ?", $note_group_id, $note_id, $note_id, $project_id);
            }
        }

        $notes->dropColumn('note_id');

        $this->doneUsingTables();
    }
}
