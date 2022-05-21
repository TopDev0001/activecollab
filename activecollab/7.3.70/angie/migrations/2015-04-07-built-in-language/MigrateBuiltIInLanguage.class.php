<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate built in language.
 *
 * @package angie.migrations
 */
class MigrateBuiltIInLanguage extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $languages = $this->useTables('languages')[0];

        $has_default = (bool) $this->executeFirstCell("SELECT COUNT(id) AS 'row_count' FROM $languages WHERE is_default = ?", true);

        if ($existing_english_id = $this->executeFirstCell("SELECT id FROM $languages WHERE name = 'English'")) {
            $this->execute("UPDATE $languages SET locale = 'en_US.UTF-8', decimal_separator = '.', thousands_separator = ',', is_default = ?, updated_on = UTC_TIMESTAMP() WHERE id = ?", !$has_default, $existing_english_id);
        } else {
            $this->execute("INSERT INTO $languages (name, locale, decimal_separator, thousands_separator, is_default, updated_on) VALUES ('English', 'en_US.UTF-8', '.', ',', ?, UTC_TIMESTAMP())", !$has_default);
        }
    }
}
