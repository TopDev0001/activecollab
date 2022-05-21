<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('availability_types')->addColumns(
    [
        new DBIdColumn(),
        DBStringColumn::create('name', 100),
        new DBEnumColumn(
            'level',
            [
                'available',
                'not_available',
            ],
            'not_available'
        ),
        new DBCreatedOnColumn(),
        new DBUpdatedOnColumn(),
    ]
)->addIndices(
    [
        DBIndex::create('name', DBIndex::UNIQUE),
    ]
)->addModelTrait(
    AvailabilityTypeInterface::class
);
