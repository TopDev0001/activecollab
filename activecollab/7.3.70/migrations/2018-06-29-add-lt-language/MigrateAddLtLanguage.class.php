<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddLtLanguage extends AngieModelMigration
{
    public function up()
    {
        $localization_file = dirname(dirname(__DIR__)) . '/localization/config.json';

        if (is_file($localization_file)) {
            $localization_config = json_decode(file_get_contents($localization_file), true);

            if (is_array($localization_config)) {
                foreach ($localization_config as $locale => $language_settings) {
                    if (empty($language_settings['is_stable'])) {
                        continue;
                    }

                    if (!$this->executeFirstCell('SELECT COUNT(id) AS "row_count" FROM languages WHERE locale = ?', $locale)) {
                        $is_rtl = !empty($language_settings['is_rtl']);
                        $is_community_translation = !empty($language_settings['is_community_translation']);

                        $this->execute(
                            'INSERT INTO languages (name, locale, decimal_separator, thousands_separator, is_rtl, is_community_translation) VALUES (?, ?, ?, ?, ?, ?)',
                            $language_settings['name_localized'],
                            $locale,
                            $language_settings['decimal_separator'],
                            $language_settings['thousands_separator'],
                            $is_rtl,
                            $is_community_translation
                        );
                    }
                }
            }
        }

        $this->execute('UPDATE languages SET updated_on = UTC_TIMESTAMP()');
    }
}
