<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add stored cards model.
 *
 * @package angie.migrations
 */
class MigrateAddStoredCardsModel extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->createTable(DB::createTable('stored_cards')->addColumns([
            new DBIdColumn(),
            DBFkColumn::create('payment_gateway_id', 0, true),
            DBStringColumn::create('gateway_card_id', 255, ''),
            new DBEnumColumn('brand', ['visa', 'amex', 'mastercard', 'discover', 'jcb', 'diners', 'other'], 'other'),
            DBStringColumn::create('last_four_digits', 4),
            DBIntegerColumn::create('expiration_month', DBColumn::NORMAL, 0)->setUnsigned(true),
            DBIntegerColumn::create('expiration_year', DBColumn::NORMAL, 0)->setUnsigned(true),
            new DBUserColumn('card_holder'),
            DBStringColumn::create('address_line_1'),
            DBStringColumn::create('address_line_2'),
            DBStringColumn::create('address_zip'),
            DBStringColumn::create('address_city'),
            DBStringColumn::create('address_country'),
        ])->addIndices([
            DBIndex::create('gateway_card_id', DBIndex::UNIQUE),
            DBIndex::create('expiration', DBIndex::KEY, ['expiration_month', 'expiration_year']),
        ]));
    }
}
