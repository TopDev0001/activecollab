<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Files\Resources;

use ActiveCollab\Module\Files\FilesModule;
use ActiveCollabModuleModel;
use IHiddenFromClients;
use IProjectElement;
use IProjectElementImplementation;

require_once APPLICATION_PATH . '/resources/ActiveCollabModuleModel.class.php';

class FilesModuleModel extends ActiveCollabModuleModel
{
    public function __construct(FilesModule $parent)
    {
        parent::__construct($parent);

        $this->addModelFromFile('files')
            ->setTypeFromField('type')
            ->implementHistory()
            ->implementAccessLog()
            ->implementSearch()
            ->implementTrash()
            ->implementActivityLog()
            ->addModelTrait(IHiddenFromClients::class)
            ->addModelTrait(IProjectElement::class, IProjectElementImplementation::class)
            ->addModelTraitTweak('IProjectElementImplementation::canViewAccessLogs insteadof IAccessLogImplementation')
            ->addModelTraitTweak('IProjectElementImplementation::whatIsWorthRemembering insteadof IActivityLogImplementation');
    }

    public function loadInitialData()
    {
        $this->addConfigOption('display_mode_project_files', 'grid');

        parent::loadInitialData();
    }
}
