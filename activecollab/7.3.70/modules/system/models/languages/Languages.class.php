<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class Languages extends BaseLanguages
{
    public static function canAdd(User $user): bool
    {
        return $user->isOwner();
    }

    public static function getIdNameMap(): array
    {
        $result = [
            lang('English (built in)'),
        ];

        if ($rows = DB::execute('SELECT `id`, `name` FROM `languages` ORDER BY `name`')) {
            foreach ($rows as $row) {
                $result[$row['id']] = $row['name'];
            }
        }

        return $result;
    }

    /**
     * Check if $locale is already defined in system.
     *
     * @param  string $locale
     * @return bool
     */
    public static function localeExists($locale)
    {
        return (bool) Languages::count(['locale = ?', $locale]);
    }

    /**
     * Check if $name is already used in system.
     *
     * @param  string $name
     * @return bool
     */
    public static function nameExists($name)
    {
        return (bool) Languages::count(['name = ?', $name]);
    }

    /**
     * Return default language.
     *
     * @return Language|DataObject|null
     */
    public static function findDefault()
    {
        return DataObjectPool::get(Language::class, Languages::getDefaultId());
    }

    /**
     * Return default language ID.
     *
     * @return int
     */
    public static function getDefaultId()
    {
        return AngieApplication::cache()->get(
            [
                'models',
                'languages',
                'default_language_id',
            ],
            function () {
                return DB::executeFirstCell('SELECT `id` FROM `languages` WHERE `is_default` = ? LIMIT 0, 1', true);
            }
        );
    }

    /**
     * Set $language as default.
     *
     * @return Language|DataObject
     */
    public static function setDefault(Language $language)
    {
        if ($language->getIsDefault()) {
            return $language;
        }

        DB::transact(
            function () use ($language) {
                DB::execute(
                    'UPDATE `languages` SET `is_default` = ?, `updated_on` = UTC_TIMESTAMP() WHERE `id` != ?',
                    false,
                    $language->getId()
                );
                DB::execute(
                    'UPDATE `languages` SET `is_default` = ? WHERE `id` = ?',
                    true,
                    $language->getId()
                );

                AngieApplication::invalidateInitialSettingsCache();
            }
        );

        Languages::clearCache();

        return DataObjectPool::reload(Language::class, $language->getId());
    }

    /**
     * Get built in language.
     *
     * @return Language
     */
    public static function getBuiltIn()
    {
        return Languages::findByLocale(BUILT_IN_LOCALE);
    }

    /**
     * Return language by locale.
     *
     * @param  string              $locale
     * @return Language|DataObject
     */
    public static function findByLocale($locale)
    {
        return Languages::findOne(
            [
                'conditions' => ['locale = ?', $locale],
            ]
        );
    }

    /**
     * Return locale code of locale.
     *
     * @param  string $locale
     * @return string
     */
    public static function getLocaleCode($locale)
    {
        $locale_code = explode('.', $locale);
        $locale_code = array_var($locale_code, 0, 'en-us');
        $locale_code = str_replace('_', '-', $locale_code);

        return strtolower($locale_code);
    }
}
