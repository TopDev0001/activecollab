<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\HTML;

/**
 * Rename project overview field to body, for consistency reasons.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateProjectOverviewToBody extends AngieModelMigration
{
    /**
     * Execute after given migration.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateDropProjectState');
    }

    /**
     * Rename project overview to body and convert HTML to plain text.
     */
    public function up()
    {
        $projects = $this->useTableForAlter('projects');
        $text_documents = $this->useTables('text_documents')[0];

        $projects->alterColumn('overview', new DBTextColumn('body'));

        if ($rows = $this->execute('SELECT id, body, created_on, created_by_id, created_by_name, created_by_email FROM ' . $projects->getName() . " WHERE body != ''")) {
            foreach ($rows as $row) {
                $body['raw'] = trim($row['body']);
                $body['clean'] = str_replace("\n\n", ' ', HTML::toPlainText($body['raw']));

                if (mb_strlen($body['clean']) > 191) {
                    $this->execute('INSERT INTO ' . $text_documents . ' (type, parent_type, parent_id, name, body, created_on, created_by_id, created_by_name, created_by_email, visibility, position) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', 'ProjectTextDocument', 'Project', $row['id'], 'Project description', $body['raw'], $row['created_on'], $row['created_by_id'], $row['created_by_name'], $row['created_by_email'], 1, $this->getNextTextDocumentPositionInProject($text_documents, $row['id']));
                    $body['clean'] = mb_substr($body['clean'], 0, 191);
                }

                $this->execute('UPDATE ' . $projects->getName() . ' SET body = ? WHERE id = ?', $body['clean'], $row['id']);
            }
        }

        $this->doneUsingTables();
    }

    /**
     * Return next text document position for the given project.
     *
     * @param  string $text_documents_table
     * @param  int    $project_id
     * @return int
     */
    private function getNextTextDocumentPositionInProject($text_documents_table, $project_id)
    {
        return $this->executeFirstCell("SELECT MAX(position) FROM $text_documents_table WHERE parent_type = ? AND parent_id = ?", 'Project', $project_id) + 1;
    }
}
