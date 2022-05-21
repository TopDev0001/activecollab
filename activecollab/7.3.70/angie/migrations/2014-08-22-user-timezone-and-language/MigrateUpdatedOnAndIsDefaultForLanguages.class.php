<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add updated_on and is_default fields to languages.
 *
 * @package angie.migrations
 */
class MigrateUpdatedOnAndIsDefaultForLanguages extends AngieModelMigration
{
    /**
     * Migreate up.
     */
    public function up()
    {
        $languages = $this->useTableForAlter('languages');

        $languages->alterColumn('last_updated_on', new DBUpdatedOnColumn());
        $languages->addColumn(
            new DBBoolColumn('is_default'),
            'thousands_separator'
        );

        if ($default_langauge_id = $this->whichLanguageShouldBeDefault($languages->getName())) {
            $this->execute('UPDATE ' . $languages->getName() . ' SET is_default = ? WHERE id = ?', true, $default_langauge_id);
        }

        $this->doneUsingTables();
    }

    /**
     * Return ID of language that should be set as default once is_default column is added.
     *
     * @param $languages_table
     * @return int|null
     */
    private function whichLanguageShouldBeDefault($languages_table)
    {
        $default_langauge_id = (int) $this->getConfigOptionValue('language');

        if ($default_langauge_id && $this->executeFirstCell("SELECT COUNT(id) AS 'row_count' FROM $languages_table WHERE id = ?", $default_langauge_id)) {
            return $default_langauge_id;
        } else {
            return $this->executeFirstCell("SELECT id FROM $languages_table ORDER BY id LIMIT 0, 1");
        }
    }
}
