<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Modules\AngieFramework;

class SubscriptionsFramework extends AngieFramework
{
    const NAME = 'subscriptions';

    protected string $name = 'subscriptions';

    public function defineClasses()
    {
        AngieApplication::setForAutoload(
            [
                ISubscriptions::class => __DIR__ . '/models/ISubscriptions.class.php',
                ISubscriptionsImplementation::class => __DIR__ . '/models/ISubscriptionsImplementation.class.php',

                FwSubscription::class => __DIR__ . '/models/subscriptions/FwSubscription.class.php',
                FwSubscriptions::class => __DIR__ . '/models/subscriptions/FwSubscriptions.class.php',
            ]
        );
    }

    public function defineHandlers()
    {
        $this->listen('on_handle_public_unsubscribe');
    }
}
