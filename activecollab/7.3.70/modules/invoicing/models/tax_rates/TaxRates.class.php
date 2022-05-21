<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Globalization;

/**
 * TaxRates class.
 *
 * @package ActiveCollab.modules.invoicing
 * @subpackage models
 */
class TaxRates extends BaseTaxRates
{
    /**
     * Cached ID name map.
     *
     * @var array
     */
    private static $id_name_map = false;
    /**
     * Cached ID name map with percentage included.
     *
     * @var array
     */
    private static $id_name_map_with_percentage = false;

    /**
     * Get Default tax rate.
     *
     * @return TaxRate
     */
    public static function getDefault()
    {
        return DataObjectPool::get('TaxRate', self::getDefaultId());
    }

    /**
     * Return default tax rate ID.
     *
     * @return int|null
     */
    public static function getDefaultId()
    {
        return AngieApplication::cache()->get(['models', 'tax_rates', 'default_tax_rate_id'], function () {
            return DB::executeFirstCell('SELECT id FROM tax_rates WHERE is_default = ? LIMIT 0, 1', true);
        });
    }

    /**
     * Set default tax rate.
     *
     * @return TaxRate|bool
     */
    public static function setDefault(TaxRate $tax_rate = null)
    {
        if ($tax_rate && $tax_rate->getIsDefault()) {
            return $tax_rate;
        }

        DB::transact(function () use ($tax_rate) {
            DB::execute('UPDATE tax_rates SET is_default = ?', false);

            if ($tax_rate) {
                DB::execute('UPDATE tax_rates SET is_default = ? WHERE id = ?', true, $tax_rate->getId());
            }

            AngieApplication::invalidateInitialSettingsCache();
        });

        self::clearCache();

        return $tax_rate ? DataObjectPool::reload(TaxRate::class, $tax_rate->getId()) : true;
    }

    /**
     * Return ID name map.
     *
     * @param  bool  $include_percentage
     * @return array
     */
    public static function getIdNameMap($include_percentage = false)
    {
        if (self::$id_name_map === false && self::$id_name_map_with_percentage === false) {
            $tax_rates = DB::execute('SELECT id, name, percentage FROM ' . self::getTableName() . ' ORDER BY name');

            if ($tax_rates) {
                $tax_rates->setCasting(['percentage' => DBResult::CAST_FLOAT]);

                foreach ($tax_rates as $tax_rate) {
                    self::$id_name_map[$tax_rate['id']] = $tax_rate['name'];
                    self::$id_name_map_with_percentage[$tax_rate['id']] = $tax_rate['name'] . ' (' . Globalization::formatNumber($tax_rate['percentage'], null, 3) . ')%';
                }
            }
        }

        return $include_percentage ? self::$id_name_map_with_percentage : self::$id_name_map;
    }

    public static function canAdd(User $user): bool
    {
        return $user->isOwner();
    }
}
