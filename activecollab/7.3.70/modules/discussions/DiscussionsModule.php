<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Discussions;

use ActiveCollab\Module\Discussions\Events\DataObjectLifeCycleEvents\DiscussionLifeCycleEventInterface;
use AngieApplication;
use AngieModule;
use DataObjectPool;
use Discussion;
use Discussions;
use NewDiscussionNotification;
use ProjectDiscussionsCollection;

class DiscussionsModule extends AngieModule
{
    const NAME = 'discussions';

    protected string $name = 'discussions';
    protected string $version = '5.0';

    public function init()
    {
        parent::init();

        DataObjectPool::registerTypeLoader(
            Discussion::class,
            function (array $ids): ?iterable
            {
                return Discussions::findByIds($ids);
            }
        );
    }

    public function defineClasses()
    {
        require_once __DIR__ . '/resources/autoload_model.php';

        AngieApplication::setForAutoload(
            [
                ProjectDiscussionsCollection::class => __DIR__ . '/models/ProjectDiscussionsCollection.php',
                NewDiscussionNotification::class => __DIR__ . '/notifications/NewDiscussionNotification.class.php',
            ]
        );
    }

    public function defineHandlers()
    {
        $this->listen('on_rebuild_activity_logs');
        $this->listen('on_object_from_notification_context');
        $this->listen('on_trash_sections');
        $this->listen('on_reset_manager_states');
        $this->listen('on_discussion_updated');
    }

    public function defineListeners(): array
    {
        return [
            DiscussionLifeCycleEventInterface::class => function ($event) {
                AngieApplication::socketsDispatcher()->dispatch(
                    $event,
                    $event->getWebhookEventType(),
                );
            },
        ];
    }
}
