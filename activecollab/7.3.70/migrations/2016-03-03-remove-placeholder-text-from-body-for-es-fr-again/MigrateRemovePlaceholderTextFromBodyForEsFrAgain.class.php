<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateRemovePlaceholderTextFromBodyForEsFrAgain extends AngieModelMigration
{
    public function up()
    {
        $placeholders = [
            '<span>Escribir una descripción</span>',
            '<span>Écrivez une description</span>',
            '<span>Le thème de cette discussion...</span>',
            '<span>De qué trata esta discusión...</span>',
            '<span>Escribir aquí...</span>',
            '<span>Écrire ici...</span>',
            '<span>Descripción</span>',
        ];

        $tables = $this->useTables('tasks', 'discussions', 'notes', 'project_template_elements');

        foreach ($tables as $table) {
            $this->execute("UPDATE $table SET body = ? WHERE body IN (?)", '', $placeholders);
        }

        $this->doneUsingTables();
    }
}
