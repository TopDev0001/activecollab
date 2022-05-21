<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class AttachmentsFrameworkModel extends AngieFrameworkModel
{
    public function __construct(AttachmentsFramework $parent)
    {
        parent::__construct($parent);

        $this
            ->addModelFromFile('attachments')
            ->setTypeFromField('type')
            ->setOrderBy('created_on, id');
    }
}
