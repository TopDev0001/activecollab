<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Parent labels table definition.
 *
 * @package angie.frameworks.labels
 * @subpackage resources
 */

return DB::createTable('parents_labels')->addColumns([
    new DBParentColumn(false, false),
    DBIntegerColumn::create('label_id', DBColumn::NORMAL, 0)->setUnsigned(true),
])->addIndices([
    DBIndex::create('parents_label', DBIndex::PRIMARY, ['parent_type', 'parent_id', 'label_id']),
    DBIndex::create('label_id'),
]);
