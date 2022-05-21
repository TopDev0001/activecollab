<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Discussions\Resources;

use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;
use ActiveCollab\Module\Discussions\DiscussionsModule;
use ActiveCollabModuleModel;
use DB;
use DBBodyColumn;
use DBBoolColumn;
use DBCreatedOnByColumn;
use DBIdColumn;
use DBIntegerColumn;
use DBNameColumn;
use DBTrashColumn;
use DBUpdatedOnByColumn;
use IHiddenFromClients;
use IProjectElement;
use IProjectElementImplementation;
use IWhoCanSeeThis;
use IWhoCanSeeThisImplementation;

require_once APPLICATION_PATH . '/resources/ActiveCollabModuleModel.class.php';

class DiscussionsModuleModel extends ActiveCollabModuleModel
{
    public function __construct(DiscussionsModule $parent)
    {
        parent::__construct($parent);

        $this->addModel(
            DB::createTable('discussions')->addColumns(
                [
                    new DBIdColumn(),
                    DBIntegerColumn::create('project_id', 10, 0)->setUnsigned(true),
                    new DBNameColumn(150),
                    new DBBodyColumn(),
                    new DBCreatedOnByColumn(),
                    new DBUpdatedOnByColumn(),
                    new DBBoolColumn('is_hidden_from_clients'),
                    new DBTrashColumn(true),
                ]
            )
        )
            ->implementComments(true, true)
            ->implementAttachments()
            ->implementHistory()
            ->implementAccessLog()
            ->implementSearch()
            ->implementTrash()
            ->implementActivityLog()
            ->addModelTrait(IHiddenFromClients::class)
            ->addModelTrait(IProjectElement::class, IProjectElementImplementation::class)
            ->addModelTrait(IWhoCanSeeThis::class, IWhoCanSeeThisImplementation::class)
            ->addModelTraitTweak('IProjectElementImplementation::canViewAccessLogs insteadof IAccessLogImplementation')
            ->addModelTraitTweak('IProjectElementImplementation::whatIsWorthRemembering insteadof IActivityLogImplementation')
            ->addModelTrait(RoutingContextInterface::class, RoutingContextImplementation::class);
    }

    public function loadInitialData()
    {
        foreach (['discussions', 'tasks', 'files', 'notes'] as $t) {
            DB::execute("ALTER TABLE $t ADD last_comment_on DATETIME NULL");
        }

        DB::execute('CREATE TRIGGER default_last_comment_on_for_discussions BEFORE INSERT ON discussions FOR EACH ROW SET NEW.last_comment_on = NEW.created_on');

        DB::execute("CREATE TRIGGER project_element_comment_inserted AFTER INSERT ON comments FOR EACH ROW
            BEGIN
                IF NEW.parent_type = 'Task' THEN
                    UPDATE tasks SET last_comment_on = NEW.created_on WHERE id = NEW.parent_id;
                ELSEIF NEW.parent_type = 'Discussion' THEN
                    UPDATE discussions SET last_comment_on = NEW.created_on WHERE id = NEW.parent_id;
                ELSEIF NEW.parent_type = 'File' THEN
                    UPDATE files SET last_comment_on = NEW.created_on WHERE id = NEW.parent_id;
                ELSEIF NEW.parent_type = 'Note' THEN
                    UPDATE notes SET last_comment_on = NEW.created_on WHERE id = NEW.parent_id;
                END IF;
            END");

        DB::execute("CREATE TRIGGER project_element_comment_updated AFTER UPDATE ON comments FOR EACH ROW
            BEGIN
                IF NEW.parent_id = OLD.parent_id AND NEW.is_trashed != OLD.is_trashed THEN
                    IF NEW.parent_type = 'Task' THEN
                        UPDATE tasks SET last_comment_on = (SELECT MAX(created_on) FROM comments WHERE parent_type = 'Task' AND parent_id = NEW.parent_id AND is_trashed = '0') WHERE id = NEW.parent_id;
                    ELSEIF NEW.parent_type = 'Discussion' THEN
                        SET @ref = (SELECT MAX(created_on) FROM comments WHERE parent_type = 'Discussion' AND parent_id = NEW.parent_id AND is_trashed = '0');
            
                        IF @ref IS NULL THEN
                            UPDATE discussions SET last_comment_on = created_on WHERE id = NEW.parent_id;
                        ELSE
                            UPDATE discussions SET last_comment_on = @ref WHERE id = NEW.parent_id;
                        END IF;
                    ELSEIF NEW.parent_type = 'File' THEN
                        UPDATE files SET last_comment_on = (SELECT MAX(created_on) FROM comments WHERE parent_type = 'File' AND parent_id = NEW.parent_id AND is_trashed = '0') WHERE id = NEW.parent_id;
                    ELSEIF NEW.parent_type = 'Note' THEN
                        UPDATE notes SET last_comment_on = (SELECT MAX(created_on) FROM comments WHERE parent_type = 'Note' AND parent_id = NEW.parent_id AND is_trashed = '0') WHERE id = NEW.parent_id;
                    END IF;
                END IF;
            END");

        DB::execute("CREATE TRIGGER project_element_comment_deleted AFTER DELETE ON comments FOR EACH ROW
            BEGIN
                IF OLD.parent_type = 'Task' THEN
                    UPDATE tasks SET last_comment_on = (SELECT MAX(created_on) FROM comments WHERE parent_type = 'Task' AND parent_id = OLD.parent_id AND is_trashed = '0') WHERE id = OLD.parent_id;
                ELSEIF OLD.parent_type = 'Discussion' THEN
                    SET @ref = (SELECT MAX(created_on) FROM comments WHERE parent_type = 'Discussion' AND parent_id = OLD.parent_id AND is_trashed = '0');
          
                    IF @ref IS NULL THEN
                        UPDATE discussions SET last_comment_on = created_on WHERE id = OLD.parent_id;
                    ELSE
                        UPDATE discussions SET last_comment_on = @ref WHERE id = OLD.parent_id;
                    END IF;
                ELSEIF OLD.parent_type = 'File' THEN
                    UPDATE files SET last_comment_on = (SELECT MAX(created_on) FROM comments WHERE parent_type = 'File' AND parent_id = OLD.parent_id AND is_trashed = '0') WHERE id = OLD.parent_id;
                ELSEIF OLD.parent_type = 'Note' THEN
                    UPDATE notes SET last_comment_on = (SELECT MAX(created_on) FROM comments WHERE parent_type = 'Note' AND parent_id = OLD.parent_id AND is_trashed = '0') WHERE id = OLD.parent_id;
                END IF;
            END");

        parent::loadInitialData();
    }
}
