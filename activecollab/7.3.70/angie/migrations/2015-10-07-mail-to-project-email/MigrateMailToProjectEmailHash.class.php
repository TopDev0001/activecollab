<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove mail_to_project_email field, and add project_hash field in projects table. Update existing projects.
 *
 * @package angie.migrations
 */
class MigrateMailToProjectEmailHash extends AngieModelMigration
{
    /* @var array */
    private $issued_hashes = [];

    /**
     * Migrate up.
     */
    public function up()
    {
        $projects_table = $this->useTableForAlter('projects');
        $projects_table->dropColumn('mail_to_project_email');
        if ($projects_table->getColumn('projects_hash') === null) {
            $projects_table->addColumn(DBStringColumn::create('project_hash'));
        }

        if ($projects = $this->execute('SELECT id FROM projects')) {
            foreach ($projects as $project) {
                $this->execute('UPDATE projects SET project_hash = ? WHERE id = ?', $this->getHash(), $project['id']);
            }
        }
        $this->doneUsingTables();
    }

    /**
     * Generate hash.
     *
     * @return string
     */
    private function getHash()
    {
        do {
            $hash = make_string(10, 'abcdefghijklmnopqrstuvwxyz1234567890');
        } while (in_array($hash, $this->issued_hashes));

        $this->issued_hashes[] = $hash;

        return $hash;
    }
}
