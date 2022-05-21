<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateUpdateDecimalAndThousandSeparators extends AngieModelMigration
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

                    $language_id = $this->executeFirstCell(
                        'SELECT `id` FROM `languages` WHERE `locale` = ? AND (`decimal_separator` != ? OR `thousands_separator` != ?)',
                        $locale,
                        $language_settings['decimal_separator'],
                        $language_settings['thousands_separator']
                    );

                    if ($language_id) {
                        $this->execute(
                            'UPDATE `languages` SET `decimal_separator` = ?, `thousands_separator` = ?, `updated_on` = UTC_TIMESTAMP() WHERE `id` = ?',
                            $language_settings['decimal_separator'],
                            $language_settings['thousands_separator'],
                            $language_id
                        );
                    }
                }
            }
        }
    }
}
