<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Simplify languages model.
 *
 * @package angie.migrations
 */
class MigrateSimplifyLanguagesModel extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->dropTable('language_phrases');
        $this->dropTable('language_phrase_translations');
    }
}
