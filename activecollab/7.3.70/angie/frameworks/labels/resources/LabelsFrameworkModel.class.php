<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;

class LabelsFrameworkModel extends AngieFrameworkModel
{
    public function __construct(LabelsFramework $parent)
    {
        parent::__construct($parent);

        $this
            ->addModelFromFile('labels')
            ->setTypeFromField('type')
            ->setOrderBy('position')
            ->setObjectIsAbstract(true)
            ->addModelTrait(null, IResetInitialSettingsTimestamp::class)
            ->addModelTrait(RoutingContextInterface::class, RoutingContextImplementation::class);

        $this->addTableFromFile('parents_labels');
    }
}
