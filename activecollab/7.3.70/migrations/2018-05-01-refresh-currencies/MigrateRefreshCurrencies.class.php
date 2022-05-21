<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateRefreshCurrencies extends AngieModelMigration
{
    public function up()
    {
        $currencies = $this->useTableForAlter('currencies');

        $currencies->alterColumn('symbol', DBStringColumn::create('symbol', 15));
        $currencies->alterColumn('symbol_native', DBStringColumn::create('symbol_native', 15));

        foreach ($this->loadCommonCurrencies() as $currency_code => $currency_details) {
            if ($this->currencyExists($currency_code)) {
                $this->execute(
                    'UPDATE `currencies`
                        SET `name` = ?, `symbol` = ?, `symbol_native` = ?, `decimal_spaces` = ?, `decimal_rounding` = ?
                        WHERE `code` = ?',
                    $currency_details['name'],
                    $currency_details['symbol'],
                    $currency_details['symbol_native'],
                    $currency_details['decimal_digits'],
                    $currency_details['rounding'],
                    $currency_code
                );
            } else {
                $this->execute(
                    'INSERT INTO `currencies`
                        (`name`, `code`, `symbol`, `symbol_native`, `decimal_spaces`, `decimal_rounding`)
                        VALUES (?, ?, ?, ?, ?, ?)',
                    $currency_details['name'],
                    $currency_code,
                    $currency_details['symbol'],
                    $currency_details['symbol_native'],
                    $currency_details['decimal_digits'],
                    $currency_details['rounding']
                );
            }
        }
    }

    private function loadCommonCurrencies(): array
    {
        $common_currencies = json_decode(
            file_get_contents(dirname(dirname(__DIR__)) . '/angie/frameworks/environment/resources/Common-Currency.json'),
            true
        );

        if (empty($common_currencies) || !is_array($common_currencies)) {
            $common_currencies = [];
        }

        return $common_currencies;
    }

    private function currencyExists(string $currency_code): bool
    {
        return (bool) $this->executeFirstCell(
            'SELECT COUNT(`id`) AS "row_count" FROM `currencies` WHERE `code` = ?',
            $currency_code
        );
    }
}
