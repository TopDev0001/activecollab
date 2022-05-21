<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate discussions to the new storage.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateDiscussionsToNewStorage extends AngieModelMigration
{
    /**
     * Execute after.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateMilestonesToNewStorage');
    }

    /**
     * Migrate discussions to the new storage.
     */
    public function up()
    {
        $this->createTable(
            DB::createTable('discussions')->addColumns(
                [
                    new DBIdColumn(),
                    DBIntegerColumn::create('project_id', 10, 0)->setUnsigned(true),
                    new DBNameColumn(150),
                    (new DBTextColumn('body'))->setSize(DBTextColumn::BIG),
                    new DBCreatedOnByColumn(),
                    new DBUpdatedOnByColumn(),
                    new DBBoolColumn('is_hidden_from_clients'),
                    new DBTrashColumn(true),
                ]
            )
        );

        [$project_objects, $categories] = $this->useTables('project_objects', 'categories');

        if ($rows = $this->execute("SELECT id, project_id, name, body, created_on, created_by_id, created_by_name, created_by_email, updated_on, updated_by_id, updated_by_name, updated_by_email, state, visibility FROM $project_objects WHERE type = 'Discussion'")) {
            $batch = new DBBatchInsert(
                'discussions',
                [
                    'id',
                    'project_id',
                    'name',
                    'body',
                    'created_on',
                    'created_by_id',
                    'created_by_name',
                    'created_by_email',
                    'updated_on',
                    'updated_by_id',
                    'updated_by_name',
                    'updated_by_email',
                    'is_hidden_from_clients',
                    'is_trashed',
                    'original_is_trashed',
                    'trashed_on',
                ]
            );

            $now = DateTimeValue::now()->toMySQL();
            defined('VISIBILITY_PRIVATE') or define('VISIBILITY_PRIVATE', 0);

            foreach ($rows as $row) {
                if ($row['state'] == STATE_TRASHED) {
                    $is_trashed = true;
                    $original_is_trashed = false;
                    $trashed_on = $now;
                } else {
                    $is_trashed = false;
                    $original_is_trashed = false;
                    $trashed_on = null;
                }

                $is_hidden_from_clients = $row['visibility'] == VISIBILITY_PRIVATE;

                $batch->insert(
                    $row['id'],
                    $row['project_id'],
                    $row['name'],
                    $row['body'],
                    $row['created_on'],
                    $row['created_by_id'],
                    $row['created_by_name'],
                    $row['created_by_email'],
                    $row['updated_on'],
                    $row['updated_by_id'],
                    $row['updated_by_name'],
                    $row['updated_by_email'],
                    $is_hidden_from_clients,
                    $is_trashed,
                    $original_is_trashed,
                    $trashed_on
                );
            }

            $batch->done();

            $this->execute("DELETE FROM $project_objects WHERE type = 'Discussion'");
        }

        $this->removeConfigOption('discussion_categories');
        $this->execute("DELETE FROM $categories WHERE type = 'DiscussionCategory'");

        $this->doneUsingTables();
    }
}
