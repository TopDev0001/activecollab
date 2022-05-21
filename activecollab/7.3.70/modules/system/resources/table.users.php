<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

return DB::createTable('users')
    ->addColumns(
        [
            new DBIdColumn(),
            new DBTypeColumn('User'),
            DBFkColumn::create('company_id', 0, true),
            DBFkColumn::create('language_id'),
            DBStringColumn::create('first_name', 50),
            DBStringColumn::create('last_name', 50),
            DBStringColumn::create('title'),
            DBStringColumn::create('email', 150),
            DBStringColumn::create('phone'),
            DBStringColumn::create('im_type'),
            DBStringColumn::create('im_handle'),
            DBStringColumn::create('password', DBStringColumn::MAX_LENGTH, ''),
            new DBEnumColumn('password_hashed_with', ['php', 'pbkdf2'], 'php'),
            DBStringColumn::create('password_reset_key', 20),
            new DBDateTimeColumn('password_reset_on'),
            DBStringColumn::create('avatar_location', DBStringColumn::MAX_LENGTH),
            DBDecimalColumn::create('daily_capacity', 12, 2),
            new DBCreatedOnByColumn(),
            new DBUpdatedOnColumn(),
            new DBArchiveColumn(true, true),
            new DBTrashColumn(true),
            new DBAdditionalPropertiesColumn(),
            new DBBoolColumn('is_eligible_for_covid_discount'),
            new DBDateTimeColumn('first_login_on'),
            new DBDateTimeColumn('paid_on'),
            new DBEnumColumn('policy_version', ['january_2019'], 'january_2019'),
            new DBDateTimeColumn('policy_accepted_on'),
        ]
    )->addIndices(
        [
            DBIndex::create('email', DBIndex::UNIQUE),
        ]
    );
