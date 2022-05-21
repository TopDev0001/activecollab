<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

defined('DB_AUTO_RECONNECT') or define('DB_AUTO_RECONNECT', 3); // Number of reconnection times if server drops connection in the middle of request
defined('DB_DEADLOCK_RETRIES') or define('DB_DEADLOCK_RETRIES', 3);
defined('DB_DEADLOCK_SLEEP') or define('DB_DEADLOCK_SLEEP', 0.5);
defined('DB_INTERFACE') or define('DB_INTERFACE', 'mysql');

// Data object
const DATA_TYPE_NONE = 'NONE';
const DATA_TYPE_INTEGER = 'INTEGER';
const DATA_TYPE_STRING = 'STRING';
const DATA_TYPE_FLOAT = 'FLOAT';
const DATA_TYPE_BOOLEAN = 'BOOLEAN';
const DATA_TYPE_ARRAY = 'ARRAY';
const DATA_TYPE_RESOURCE = 'RESOURCE';
const DATA_TYPE_OBJECT = 'OBJECT';

AngieApplication::setForAutoload(
    [
        DB::class => ANGIE_PATH . '/classes/database/DB.class.php',
        DBConnection::class => ANGIE_PATH . '/classes/database/DBConnection.class.php',
        DBResult::class => ANGIE_PATH . '/classes/database/DBResult.class.php',
        DBResultIterator::class => ANGIE_PATH . '/classes/database/DBResultIterator.class.php',

        MySQLDBConnection::class => ANGIE_PATH . '/classes/database/mysql/MySQLDBConnection.class.php',
        MySQLDBResult::class => ANGIE_PATH . '/classes/database/mysql/MySQLDBResult.class.php',
        MySQLDBTable::class => ANGIE_PATH . '/classes/database/mysql/MySQLDBTable.class.php',

        IEtag::class => ANGIE_PATH . '/classes/database/etag/IEtag.class.php',
        IEtagImplementation::class => ANGIE_PATH . '/classes/database/etag/IEtagImplementation.class.php',

        DataObject::class => ANGIE_PATH . '/classes/database/DataObject.class.php',
        DataManager::class => ANGIE_PATH . '/classes/database/DataManager.class.php',
        DataView::class => ANGIE_PATH . '/classes/database/DataView.class.php',
        DataObjectPool::class => ANGIE_PATH . '/classes/database/DataObjectPool.class.php',

        DataObjectCollection::class => ANGIE_PATH . '/classes/database/collections/DataObjectCollection.class.php',
        ModelCollection::class => ANGIE_PATH . '/classes/database/collections/ModelCollection.class.php',
        CompositeCollection::class => ANGIE_PATH . '/classes/database/collections/CompositeCollection.class.php',
        CursorModelCollection::class => ANGIE_PATH . '/classes/database/collections/CursorModelCollection.class.php',
        RelativeCursorModelCollection::class => ANGIE_PATH . '/classes/database/collections/RelativeCursorModelCollection.class.php',
        CompositeCursorCollection::class => ANGIE_PATH . '/classes/database/collections/CompositeCursorCollection.class.php',
        CursorCollectionInterface::class => ANGIE_PATH . '/classes/database/collections/CursorCollectionInterface.php',
        RelativeCursorCollectionInterface::class => ANGIE_PATH . '/classes/database/collections/RelativeCursorCollectionInterface.php',

        // Utilities
        DBBatchInsert::class => ANGIE_PATH . '/classes/database/DBBatchInsert.class.php',
        DBResultPager::class => ANGIE_PATH . '/classes/database/DBResultPager.class.php',

        // Database engineer
        DBTable::class => ANGIE_PATH . '/classes/database/engineer/DBTable.class.php',
        DBColumn::class => ANGIE_PATH . '/classes/database/engineer/DBColumn.class.php',
        DBIndex::class => ANGIE_PATH . '/classes/database/engineer/DBIndex.class.php',

        DBIndexPrimary::class => ANGIE_PATH . '/classes/database/engineer/indexes/DBIndexPrimary.class.php',

        DBNumericColumn::class => ANGIE_PATH . '/classes/database/engineer/columns/DBNumericColumn.class.php',
        DBBinaryColumn::class => ANGIE_PATH . '/classes/database/engineer/columns/DBBinaryColumn.class.php',
        DBBoolColumn::class => ANGIE_PATH . '/classes/database/engineer/columns/DBBoolColumn.class.php',
        DBDateColumn::class => ANGIE_PATH . '/classes/database/engineer/columns/DBDateColumn.class.php',
        DBDateTimeColumn::class => ANGIE_PATH . '/classes/database/engineer/columns/DBDateTimeColumn.class.php',
        DBEnumColumn::class => ANGIE_PATH . '/classes/database/engineer/columns/DBEnumColumn.class.php',
        DBFloatColumn::class => ANGIE_PATH . '/classes/database/engineer/columns/DBFloatColumn.class.php',
        DBJsonColumn::class => ANGIE_PATH . '/classes/database/engineer/columns/DBJsonColumn.class.php',
        DBDecimalColumn::class => ANGIE_PATH . '/classes/database/engineer/columns/DBDecimalColumn.class.php',
        DBMoneyColumn::class => ANGIE_PATH . '/classes/database/engineer/columns/DBMoneyColumn.class.php',
        DBIntegerColumn::class => ANGIE_PATH . '/classes/database/engineer/columns/DBIntegerColumn.class.php',
        DBSetColumn::class => ANGIE_PATH . '/classes/database/engineer/columns/DBSetColumn.class.php',
        DBStringColumn::class => ANGIE_PATH . '/classes/database/engineer/columns/DBStringColumn.class.php',
        DBTextColumn::class => ANGIE_PATH . '/classes/database/engineer/columns/DBTextColumn.class.php',
        DBTimeColumn::class => ANGIE_PATH . '/classes/database/engineer/columns/DBTimeColumn.class.php',
        DBIpAddressColumn::class => ANGIE_PATH . '/classes/database/engineer/columns/DBIpAddressColumn.class.php',

        // Special columns
        DBIdColumn::class => ANGIE_PATH . '/classes/database/engineer/columns_special/DBIdColumn.class.php',
        DBFkColumn::class => ANGIE_PATH . '/classes/database/engineer/columns_special/DBFkColumn.class.php',
        DBNameColumn::class => ANGIE_PATH . '/classes/database/engineer/columns_special/DBNameColumn.class.php',
        DBBodyColumn::class => ANGIE_PATH . '/classes/database/engineer/columns_special/DBBodyColumn.class.php',
        DBTypeColumn::class => ANGIE_PATH . '/classes/database/engineer/columns_special/DBTypeColumn.class.php',
        DBCreatedOnColumn::class => ANGIE_PATH . '/classes/database/engineer/columns_special/DBCreatedOnColumn.class.php',
        DBUpdatedOnColumn::class => ANGIE_PATH . '/classes/database/engineer/columns_special/DBUpdatedOnColumn.class.php',
        DBAdditionalPropertiesColumn::class => ANGIE_PATH . '/classes/database/engineer/columns_special/DBAdditionalPropertiesColumn.class.php',

        // Composite columns
        DBCompositeColumn::class => ANGIE_PATH . '/classes/database/engineer/columns_composite/DBCompositeColumn.class.php',
        DBActionOnByColumn::class => ANGIE_PATH . '/classes/database/engineer/columns_composite/DBActionOnByColumn.class.php',
        DBCreatedOnByColumn::class => ANGIE_PATH . '/classes/database/engineer/columns_composite/DBCreatedOnByColumn.class.php',
        DBUpdatedOnByColumn::class => ANGIE_PATH . '/classes/database/engineer/columns_composite/DBUpdatedOnByColumn.class.php',
        DBRelatedObjectColumn::class => ANGIE_PATH . '/classes/database/engineer/columns_composite/DBRelatedObjectColumn.class.php',
        DBParentColumn::class => ANGIE_PATH . '/classes/database/engineer/columns_composite/DBParentColumn.class.php',
        DBStateColumn::class => ANGIE_PATH . '/classes/database/engineer/columns_composite/DBStateColumn.class.php',
        DBUserColumn::class => ANGIE_PATH . '/classes/database/engineer/columns_composite/DBUserColumn.class.php',
        DBArchiveColumn::class => ANGIE_PATH . '/classes/database/engineer/columns_composite/DBArchiveColumn.class.php',
        DBTrashColumn::class => ANGIE_PATH . '/classes/database/engineer/columns_composite/DBTrashColumn.class.php',
        DBFileMetaColumn::class => ANGIE_PATH . '/classes/database/engineer/columns_composite/DBFileMetaColumn.class.php',

        // Errors
        DBError::class => ANGIE_PATH . '/classes/database/errors/.class.php',
        DBConnectError::class => ANGIE_PATH . '/classes/database/errors/DBConnectError.class.php',
        DBQueryError::class => ANGIE_PATH . '/classes/database/errors/DBQueryError.class.php',
        ValidationErrors::class => ANGIE_PATH . '/classes/database/errors/ValidationErrors.class.php',
        DBNotConnectedError::class => ANGIE_PATH . '/classes/database/errors/DBNotConnectedError.class.php',
        DBReconnectError::class => ANGIE_PATH . '/classes/database/errors/DBReconnectError.class.php',
        ImpossibleCollectionError::class => ANGIE_PATH . '/classes/database/errors/ImpossibleCollectionError.class.php',
    ]
);
