<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Notifications\NotificationInterface;

class NotificationsFrameworkModel extends AngieFrameworkModel
{
    public function __construct(NotificationsFramework $parent)
    {
        parent::__construct($parent);

        $this
            ->addModelFromFile('notifications')
            ->setTypeFromField('type')
            ->setObjectIsAbstract(true)
            ->addModelTrait(NotificationInterface::class)
            ->setOrderBy('created_on DESC, id DESC');

        $this
            ->addModelFromFile('notification_recipients')
            ->addModelTrait(IWhoCanSeeThis::class);
    }

    public function loadInitialData()
    {
        $this->addConfigOption('notifications_notify_email_sender', true);

        parent::loadInitialData();
    }
}
