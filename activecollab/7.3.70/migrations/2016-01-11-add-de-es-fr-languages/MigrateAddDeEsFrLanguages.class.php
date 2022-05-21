<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddDeEsFrLanguages extends AngieModelMigration
{
    public function up()
    {
        $old_locale_id_map = $this->getOldLocaleIdMap();
        $english_langauge_id = $this->addEnglish();

        $update_object_language_ids = [];

        $localization_file = dirname(dirname(__DIR__)) . '/localization/config.json';

        if (is_file($localization_file)) {
            $localization_config = json_decode(file_get_contents($localization_file), true);

            if (is_array($localization_config)) {
                foreach ($localization_config as $locale => $language_settings) {
                    if (empty($language_settings['is_stable'])) {
                        continue;
                    }

                    $is_rtl = !empty($language_settings['is_rtl']);
                    $is_community_translation = !empty($language_settings['is_community_translation']);

                    $this->execute('INSERT INTO languages (name, locale, decimal_separator, thousands_separator, is_rtl, is_community_translation) VALUES (?, ?, ?, ?, ?, ?)', $language_settings['name_localized'], $locale, $language_settings['decimal_separator'], $language_settings['thousands_separator'], $is_rtl, $is_community_translation);

                    $language_id = $this->lastInsertId();

                    // When we have a language that's in use, remember ID-s of objects that use it, so we can update them later on.
                    if (isset($old_locale_id_map[$locale])) {
                        $update_object_language_ids[$language_id] = [];

                        foreach ($this->fixLanguageIdInTables() as $table_name) {
                            if ($table_ids = $this->executeFirstColumn("SELECT id FROM $table_name WHERE language_id = ?", $old_locale_id_map[$locale])) {
                                $update_object_language_ids[$language_id][$table_name] = $table_ids;
                            }
                        }

                        if (empty($update_object_language_ids[$language_id])) {
                            unset($update_object_language_ids[$language_id]);
                        }
                    }
                }
            }
        }

        $this->execute('UPDATE languages SET updated_on = UTC_TIMESTAMP()');

        // Force English
        foreach ($this->fixLanguageIdInTables() as $table_name) {
            $this->execute("UPDATE $table_name SET language_id = ?", $english_langauge_id);
        }

        // Update records that used DE, ES or FR
        foreach ($update_object_language_ids as $new_language_id => $records_to_update) {
            foreach ($records_to_update as $table_name => $table_ids) {
                $this->execute("UPDATE $table_name SET language_id = ? WHERE id IN (?)", $new_language_id, $table_ids);
            }
        }

        // Remember that we transfered language settings
        $this->execute('INSERT INTO memories (`key`, `value`, `updated_on`) VALUES (?, ?, UTC_TIMESTAMP())', 'transfered_languages_to_feather', serialize(true));
    }

    /**
     * Return locale -> language ID map.
     *
     * @return array
     */
    private function getOldLocaleIdMap()
    {
        $result = [];

        if ($language_rows = $this->execute('SELECT id, locale FROM languages ORDER BY id')) {
            foreach ($language_rows as $language_row) {
                $result[$language_row['locale']] = $language_row['id'];
            }
        }

        return $result;
    }

    /**
     * Add English language.
     *
     * @return int
     */
    private function addEnglish()
    {
        $this->execute('TRUNCATE TABLE languages');
        $this->execute("INSERT INTO languages (name, locale, decimal_separator, thousands_separator, is_default) VALUES ('English', 'en_US.UTF-8', '.', ',', '1')");

        return $this->lastInsertId();
    }

    /**
     * Return a list of tables where we need to fix language_id field value.
     *
     * @return string[]
     */
    private function fixLanguageIdInTables()
    {
        return ['users', 'invoices', 'estimates', 'recurring_profiles'];
    }
}
