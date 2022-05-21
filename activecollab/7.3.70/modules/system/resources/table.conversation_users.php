<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

return DB::createTable('conversation_users')
    ->addColumns(
        [
            new DBIdColumn(),
            DBFkColumn::create('conversation_id', 0, true),
            DBFkColumn::create('user_id', 0, true),
            new DBBoolColumn('is_admin'),
            new DBBoolColumn('is_muted'),
            new DBBoolColumn('is_original_muted'),
            new DBDateTimeColumn('new_messages_since'),
            new DBCreatedOnColumn(),
            new DBUpdatedOnColumn(),
        ]
    )->addIndices(
        [
            DBIndex::create(
                'conversation_user',
                DBIndex::UNIQUE,
                [
                    'conversation_id',
                    'user_id',
                ]
            ),
            DBIndex::create('user_id'),
        ]
    );
