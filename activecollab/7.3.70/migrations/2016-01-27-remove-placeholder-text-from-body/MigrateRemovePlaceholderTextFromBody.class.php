<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateRemovePlaceholderTextFromBody extends AngieModelMigration
{
    public function up()
    {
        $placeholders = [
            '<span>Write a description</span>',
            '<span>Verfasse eine Beschreibung.</span>',
            '<span>Escribir una descripción</span>',
            '<span>Écrivez une description</span>',
            '<span>What is this discussion about...</span>',
            '<span>Le thème de cette discussion...</span>',
            '<span>De qué trata esta discusión...</span>',
            '<span>Von was handelt die Diskussion...</span>',
            '<span>Write here...</span>',
            '<span>Hier schreiben...</span>',
            '<span>Escribir aquí...</span>',
            '<span>Écrire ici...</span>',
            '<span>Description</span>',
            '<span>Beschreibung</span>',
            '<span>Descripción</span>',
            '<span>Description</span>',
        ];

        $tables = $this->useTables('tasks', 'discussions', 'notes', 'project_template_elements');

        foreach ($tables as $table) {
            $this->execute("UPDATE $table SET body = ? WHERE body IN (?)", '', $placeholders);
        }

        $this->doneUsingTables();
    }
}
