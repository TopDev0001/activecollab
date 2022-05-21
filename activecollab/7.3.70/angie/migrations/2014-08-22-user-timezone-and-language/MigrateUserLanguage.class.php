<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate user time zone and language.
 *
 * @package angie.migrations
 */
class MigrateUserLanguage extends AngieModelMigration
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateUpdatedOnAndIsDefaultForLanguages');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        $users = $this->useTableForAlter('users');

        [$languages, $config_option_values] = $this->useTables('languages', 'config_option_values');

        $users->addColumn(DBFkColumn::create('language_id'), 'id');

        $default_language_id = (int) $this->executeFirstCell("SELECT id FROM $languages WHERE is_default = ? LIMIT 0, 1", true);

        if ($default_language_id) {
            $this->execute('UPDATE ' . $users->getName() . ' SET language_id = ?', $default_language_id);
        }

        if ($rows = $this->execute("SELECT parent_id, value FROM $config_option_values WHERE parent_type = 'User' AND name = 'language'")) {
            $language_users_map = [];

            foreach ($rows as $row) {
                $language_id = (int) unserialize($row['value']);

                if ($language_id && $language_id !== $default_language_id) {
                    if (empty($language_users_map[$language_id])) {
                        $language_users_map[$language_id] = [];
                    }

                    $language_users_map[$language_id][] = $row['parent_id'];
                }
            }

            foreach ($language_users_map as $language_id => $user_ids) {
                $this->execute('UPDATE ' . $users->getName() . ' SET language_id = ? WHERE id IN (?)', $language_id, $user_ids);
            }
        }

        $this->removeConfigOption('language');

        $this->doneUsingTables();
    }
}
