<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate text documents to notes.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateTextDocumentsToNotes extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->renameConfigOption('notification_new_text_document', 'notification_new_note');
        $this->renameConfigOption('notification_text_document_name_body_update', 'notification_note_name_body_update');

        $text_documents = $this->useTableForAlter('text_documents');

        $text_documents->alterColumn('text_document_id', DBFkColumn::create('note_id'));

        if ($text_documents->indexExists('text_document_id')) {
            $text_documents->dropIndex('text_document_id');
        }

        $text_documents->addIndex(DBIndex::create('note_id'));

        foreach ($this->useTables('subscriptions', 'attachments', 'favorites', 'comments') as $table) {
            $this->execute("UPDATE $table SET parent_type = 'Note' WHERE parent_type IN ('TextDocument', 'ProjectTextDocument')");
        }

        $this->doneUsingTables();

        $this->renameTable('text_documents', 'notes');
    }
}
