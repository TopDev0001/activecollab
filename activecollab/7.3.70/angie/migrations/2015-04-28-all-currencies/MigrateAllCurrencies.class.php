<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Import all currencies from common currencies file.
 *
 * @package angie.migrations
 */
class MigrateAllCurrencies extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $currencies = $this->useTableForAlter('currencies');

        if ($currencies->getColumn('symbol') === null) {
            $currencies->addColumn(DBStringColumn::create('symbol', 5), 'code');
        }

        if ($currencies->getColumn('symbol_native') === null) {
            $currencies->addColumn(DBStringColumn::create('symbol_native', 5), 'symbol');
        }

        if ($currencies->getColumn('decimal_separator')) {
            $currencies->dropColumn('decimal_separator');
        }

        if ($currencies->getColumn('thousands_separator')) {
            $currencies->dropColumn('thousands_separator');
        }

        foreach (json_decode(file_get_contents(ANGIE_PATH . '/frameworks/environment/resources/Common-Currency.json'), true) as $currency_code => $currency_details) {
            $batch = new DBBatchInsert($currencies->getName(), ['name', 'code', 'symbol', 'symbol_native', 'decimal_spaces', 'decimal_rounding'], 50, DBBatchInsert::REPLACE_RECORDS);

            if ($currency_id = $this->executeFirstCell('SELECT id FROM ' . $currencies->getName() . ' WHERE code = ? LIMIT 0, 1', $currency_code)) {
                $this->execute('UPDATE ' . $currencies->getName() . ' SET name = ?, code = ?, symbol = ?, symbol_native = ? WHERE id = ?', $currency_details['name'], $currency_code, $currency_details['symbol'], $currency_details['symbol_native'], $currency_id);
            } else {
                $batch->insert($currency_details['name'], $currency_code, $currency_details['symbol'], $currency_details['symbol_native'], $currency_details['decimal_digits'], $currency_details['rounding']);
            }

            $batch->done();
        }

        $this->execute('UPDATE ' . $currencies->getName() . ' SET updated_on = UTC_TIMESTAMP()');

        $this->doneUsingTables();
    }
}
