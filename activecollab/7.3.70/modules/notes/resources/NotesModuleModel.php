<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\Notes\Resources;

use ActiveCollab\Module\Notes\NotesModule;
use ActiveCollabModuleModel;
use DB;
use DBAdditionalPropertiesColumn;
use DBBodyColumn;
use DBBoolColumn;
use DBCreatedOnByColumn;
use DBIdColumn;
use DBIndex;
use DBIntegerColumn;
use DBNameColumn;
use DBTrashColumn;
use DBUpdatedOnByColumn;
use IWhoCanSeeThis;
use IWhoCanSeeThisImplementation;

require_once APPLICATION_PATH . '/resources/ActiveCollabModuleModel.class.php';

class NotesModuleModel extends ActiveCollabModuleModel
{
    public function __construct(NotesModule $parent)
    {
        parent::__construct($parent);

        $this->addModel(
            DB::createTable('note_groups')->addColumns(
                [
                    new DBIdColumn(),
                    DBIntegerColumn::create('project_id', 10, 0)->setUnsigned(true),
                    DBIntegerColumn::create('position', DBIntegerColumn::NORMAL, 0)->setUnsigned(true),
                ]
            )->addIndices(
                [
                    DBIndex::create('position'),
                ]
            )
        )
            ->setOrderBy('position');

        $this->addModel(
            DB::createTable('notes')->addColumns(
                [
                    new DBIdColumn(),
                    new DBNameColumn(255),
                    DBIntegerColumn::create('project_id', 10, 0)->setUnsigned(true),
                    DBIntegerColumn::create('note_group_id', 10, 0)->setUnsigned(true),
                    new DBBodyColumn(),
                    DBIntegerColumn::create('position', DBIntegerColumn::NORMAL, 0)->setUnsigned(true),
                    new DBBoolColumn('is_hidden_from_clients'),
                    new DBTrashColumn(true),
                    new DBCreatedOnByColumn(true, true),
                    new DBUpdatedOnByColumn(),
                    new DBAdditionalPropertiesColumn(),
                ]
            )->addIndices(
                [
                    DBIndex::create('position'),
                ]
            )
        )
            ->setOrderBy('position')
            ->implementSearch()
            ->implementComments(true, true)
            ->implementAttachments()
            ->implementHistory()
            ->implementAccessLog()
            ->implementTrash()
            ->implementActivityLog()
            ->addModelTrait('IHiddenFromClients')
            ->addModelTrait('IProjectElement', 'IProjectElementImplementation')
            ->addModelTrait(IWhoCanSeeThis::class, IWhoCanSeeThisImplementation::class)
            ->addModelTraitTweak('IProjectElementImplementation::canViewAccessLogs insteadof IAccessLogImplementation')
            ->addModelTraitTweak('IProjectElementImplementation::whatIsWorthRemembering insteadof IActivityLogImplementation');
    }

    /**
     * Load initial framework data.
     */
    public function loadInitialData()
    {
        $this->addConfigOption('notifications_user_send_email_note_name_body_update', false);

        $this->addConfigOption('sort_mode_project_notes', 'recently_updated');

        parent::loadInitialData();
    }
}
