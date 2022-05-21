<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\HTML;

class MigrateLongProjectDescription extends AngieModelMigration
{
    public function up()
    {
        $projects = $this->useTableForAlter('projects');

        $rows = $this->execute('SELECT id, body, created_on, created_by_id, created_by_name, created_by_email FROM ' . $projects->getName() . " WHERE body != ''");

        if ($rows) {
            foreach ($rows as $row) {
                $body['raw'] = trim($row['body']);
                $body['clean'] = str_replace("\n\n", ' ', HTML::toPlainText($body['raw']));

                if (mb_strlen($body['clean']) > 191) {
                    $position = $this->executeFirstCell('SELECT MAX(position) FROM notes WHERE project_id = ?', $row['id']) + 1;

                    $default_name = 'Project description';

                    $query = implode(
                        ' ',
                        [
                            'INSERT INTO',
                            'notes',
                            '(' . implode(', ', ['project_id', 'name', 'body', 'created_on', 'created_by_id', 'created_by_name', 'created_by_email', 'position']) . ')',
                            'VALUES',
                            '(' . implode(', ', ['?', '?', '?', '?', '?', '?', '?', '?']) . ')',
                        ]
                    );

                    $this->execute(
                        $query,
                        $row['id'],
                        $default_name,
                        $body['raw'],
                        $row['created_on'],
                        $row['created_by_id'],
                        $row['created_by_name'],
                        $row['created_by_email'],
                        $position
                    );

                    $body['clean'] = mb_substr($body['clean'], 0, 191);
                }

                $this->execute('UPDATE projects SET body = ? WHERE id = ?', $body['clean'], $row['id']);
            }
        }

        $projects->alterColumn('body', DBStringColumn::create('body'));
    }
}
