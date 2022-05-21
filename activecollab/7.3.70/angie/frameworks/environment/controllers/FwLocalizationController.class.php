<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

/**
 * Localization controller.
 *
 * @package angie.framework.globalization
 * @subpackage controllers
 */
class FwLocalizationController extends AuthRequiredController
{
    /**
     * Return localization settings.
     *
     * @return mixed
     */
    public function show_settings() // @todo(petar) deprecated
    {
        return ConfigOptions::getValue(['time_timezone', 'format_date', 'format_time']);
    }

    /**
     * Show timezones.
     *
     * @return array
     */
    public function show_timezones()
    {
        $default_timezone = ConfigOptions::getValue('time_timezone');
        $identifiers = DateTimeZone::listIdentifiers();
        $result = [];

        foreach ($identifiers as $identifier) {
            $result[] = ['value' => $identifier, 'is_default' => $identifier == $default_timezone];
        }

        return $result;
    }

    /**
     * Show date formats.
     *
     * @return array
     */
    public function show_date_formats()
    {
        $default_format = ConfigOptions::getValue('format_date');
        $formats = [
            '%b %e. %Y',
            '%e. %b %Y',
        ];
        $result = [];

        foreach ($formats as $format) {
            $result[] = ['value' => $format, 'is_default' => $format == $default_format];
        }

        return $result;
    }

    /**
     * Show time formats.
     *
     * @return array
     */
    public function show_time_formats()
    {
        $default_format = ConfigOptions::getValue('format_time');
        $formats = [
            '%H:%M',
            '%I:%M %p',
        ];
        $result = [];

        foreach ($formats as $format) {
            $result[] = ['value' => $format, 'is_default' => $format == $default_format];
        }

        return $result;
    }

    /**
     * Save localization settings.
     *
     * @return array|int
     * @throws Exception
     */
    public function save_settings(Request $request, User $user)
    {
        if (!$user->isOwner()) {
            return Response::NOT_FOUND;
        }

        $put = $request->put();

        $workweek_data = [
            'time_timezone' => $put['time_timezone'],
            'format_date' => $put['format_date'],
            'format_time' => $put['format_time'],
        ];

        try {
            DB::beginWork('Saving localization settings @ ' . __CLASS__);

            ConfigOptions::setValue($workweek_data);

            $language_id = (int) $put['language'];
            if ($language_id) {
                /* @var Language $language */
                $language = DataObjectPool::get('Language', $language_id);
                Languages::setDefault($language);
            } else {
                /* @var Language $default_language */
                $default_language = DataObjectPool::get('Language', Languages::getDefaultId());
                $default_language->setIsDefault(false);
                $default_language->save();
            }

            $currency_id = (int) $put['currency'];
            $currency = DataObjectPool::get('Currency', $currency_id);

            if ($currency instanceof Currency) {
                $decimal_spaces = intval($put['decimal_spaces']);
                $decimal_rounding = floatval($put['decimal_rounding']);
                $currency->setDecimalRounding($decimal_rounding);
                $currency->setDecimalSpaces($decimal_spaces);
                $currency->save();

                Currencies::setDefault($currency);
            }

            DB::commit('Localization settings saved @ ' . __CLASS__);

            AngieApplication::cache()->clear();
        } catch (Exception $e) {
            DB::rollback('Failed to save localization settings @ ' . __CLASS__);
            throw $e;
        }

        return $workweek_data;
    }
}
