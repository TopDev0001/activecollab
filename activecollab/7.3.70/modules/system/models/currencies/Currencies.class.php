<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\Utils\DefaultCurrencyResolver\DefaultCurrencyResolverInterface;

class Currencies extends BaseCurrencies
{
    /**
     * Cached ID name map.
     *
     * @var array
     */
    private static $id_name_map = false;

    /**
     * Cached ID code map.
     *
     * @var array
     */
    private static $id_code_map = false;

    /**
     * Cached ID details map.
     *
     * @var array
     */
    private static $id_details_map = false;

    public static function canAdd(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Set $currency as default.
     *
     * @return Currency
     */
    public static function setDefault(Currency $currency)
    {
        if ($currency->getIsDefault()) {
            return $currency;
        }

        DB::transact(function () use ($currency) {
            DB::execute('UPDATE currencies SET is_default = ?, updated_on = UTC_TIMESTAMP()', false);
            DB::execute('UPDATE currencies SET is_default = ? WHERE id = ?', true, $currency->getId());

            AngieApplication::invalidateInitialSettingsCache();
        });

        Currencies::clearCache();

        return DataObjectPool::reload(Currency::class, $currency->getId());
    }

    public static function findByCode(string $code): ?Currency
    {
        return Currencies::findOne(
            [
                'conditions' => [
                    'code = ?',
                    $code,
                ],
            ]
        );
    }

    /**
     * Return ID name map of currencies.
     *
     * @return array
     */
    public static function getIdNameMap()
    {
        if (self::$id_name_map === false) {
            self::$id_name_map = AngieApplication::cache()->get(['models', 'currencies', 'id_name_map'], function () {
                $result = [];

                if ($rows = DB::execute('SELECT id, name FROM currencies ORDER BY name')) {
                    foreach ($rows as $row) {
                        $result[$row['id']] = $row['name'];
                    }
                }

                return empty($result) ? null : $result;
            });
        }

        return self::$id_name_map;
    }

    /**
     * Return ID code map of currencies.
     *
     * @return array
     */
    public static function getIdCodeMap()
    {
        if (self::$id_code_map === false) {
            self::$id_code_map = AngieApplication::cache()->get(['models', 'currencies', 'id_code_map'], function () {
                $result = [];

                if ($rows = DB::execute('SELECT id, code FROM currencies ORDER BY code')) {
                    foreach ($rows as $row) {
                        $result[(int) $row['id']] = $row['code'];
                    }
                }

                return empty($result) ? null : $result;
            });
        }

        return self::$id_code_map;
    }

    /**
     * Prepare and return ID details map.
     *
     * @return array
     */
    public static function getIdDetailsMap()
    {
        if (self::$id_details_map === false) {
            self::$id_details_map = AngieApplication::cache()->get(['models', 'currencies', 'id_details_map'], function () {
                $result = [];

                if ($rows = DB::execute('SELECT id, name, code, decimal_spaces, decimal_rounding FROM currencies ORDER BY name')) {
                    foreach ($rows as $row) {
                        $result[(int) $row['id']] = [
                            'name' => $row['name'],
                            'code' => $row['code'],
                            'decimal_spaces' => $row['decimal_spaces'],
                            'decimal_rounding' => $row['decimal_rounding'],
                        ];
                    }
                }

                return empty($result) ? null : $result;
            });
        }

        return self::$id_details_map;
    }

    /**
     * Get Number of Decimal spaces.
     *
     * @return int
     */
    public static function getDecimalSpaces(Currency $currency = null)
    {
        if ($currency instanceof Currency) {
            return $currency->getDecimalSpaces();
        }

        $default_currency = Currencies::getDefault();
        if ($default_currency instanceof Currency) {
            return $default_currency->getDecimalSpaces();
        }

        return 2;
    }

    public static function getDefault(): Currency
    {
        return AngieApplication::getContainer()
            ->get(DefaultCurrencyResolverInterface::class)
                ->getDefaultCurrency();
    }

    public static function getDefaultId(): int
    {
        return AngieApplication::getContainer()
            ->get(DefaultCurrencyResolverInterface::class)
                ->getDefaultCurrencyId();
    }

    /**
     * Perform Decimal Rounding.
     *
     * @param  float    $value
     * @param  Currency $currency
     * @return float
     */
    public static function roundDecimal($value, $currency)
    {
        if (!$currency->getDecimalRounding()) {
            return $value;
        }

        $rounding_step = 1 / $currency->getDecimalRounding();

        return round($value * $rounding_step) / $rounding_step;
    }

    /**
     * Return only used currencies.
     *
     * @return Currency[]|DBResult
     */
    public static function findUsedCurrencies()
    {
        $rows = DB::execute('select p.currency_id from projects p group by p.currency_id
               union
               select c.id from currencies c where c.is_default = 1
               union
               select i.currency_id from invoices i group by i.currency_id;');

        $ids = [];

        if ($rows) {
            foreach ($rows as $row) {
                $ids[] = $row['currency_id'];
            }
        }

        return self::findByIds($ids);
    }
}
