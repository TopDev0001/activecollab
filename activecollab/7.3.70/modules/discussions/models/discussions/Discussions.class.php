<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\Discussions\Events\DataObjectLifeCycleEvents\DiscussionCreatedEvent;
use ActiveCollab\Module\Discussions\Events\DataObjectLifeCycleEvents\DiscussionUpdatedEvent;
use ActiveCollab\Module\Discussions\Utils\DiscussionToTaskConverter\DiscussionToTaskConverterInterface;

class Discussions extends BaseDiscussions
{
    use IProjectElementsImplementation;

    /**
     * Return new collection.
     *
     * @param  User|null                                    $user
     * @return ModelCollection|ProjectDiscussionsCollection
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        if (!str_starts_with($collection_name, 'discussions_in_project')) {
            throw new InvalidParamError('collection_name', $collection_name, 'Invalid collection name');
        }

        $bits = explode('_', $collection_name);

        $page = (int) array_pop($bits);
        array_pop($bits); // Remove _page_

        $project = DataObjectPool::get(Project::class, array_pop($bits));

        if (!$project instanceof Project) {
            throw new InvalidParamError('collection_name', $collection_name, 'Invalid collection name');
        }

        $collection = new ProjectDiscussionsCollection($collection_name);
        $collection->setPagination($page, 30);

        $collection->setWhosAsking($user);
        $collection->setProject($project);

        return $collection;
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): Discussion
    {
        $notify_subscribers = array_var($attributes, 'notify_subscribers', true, true);

        $discussion = parent::create($attributes, $save, false);

        if ($discussion instanceof Discussion && $discussion->isLoaded()) {
            /** @var Discussion $discussion */
            $discussion = self::autoSubscribeProjectLeader($discussion);

            if ($notify_subscribers) {
                AngieApplication::notifications()
                    ->notifyAbout('discussions/new_discussion', $discussion, $discussion->getCreatedBy())
                    ->sendToSubscribers();
            }

            if ($announce) {
                DataObjectPool::announce(new DiscussionCreatedEvent($discussion));
            }
        }

        return $discussion;
    }

    public static function promoteToTask(Discussion $discussion, User $by): Task
    {
        return AngieApplication::getContainer()
            ->get(DiscussionToTaskConverterInterface::class)
                ->convertToTask($discussion, $by);
    }

    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): Discussion
    {
        $discussion = parent::update($instance, $attributes, $save);

        if ($discussion instanceof Discussion) {
            DataObjectPool::announce(new DiscussionUpdatedEvent($discussion));
        }

        return $discussion;
    }

    // ---------------------------------------------------
    //  Permissions
    // ---------------------------------------------------

    /**
     * Returns true if $user can add discussions to $project.
     *
     * @return bool
     */
    public static function canAdd(User $user, Project $project)
    {
        return $user->isOwner() || $project->isMember($user);
    }

    // ---------------------------------------------------
    //  Utilities
    // ---------------------------------------------------

    /**
     * Return read status for discussions in a project.
     *
     * @return array
     */
    public static function getReadStatusInProject(User $user, Project $project)
    {
        $result = [];

        if ($user instanceof Client) {
            $conditions = ['project_id = ? AND is_hidden_from_clients = ? AND is_trashed = ?', $project->getId(), false, false];
        } else {
            $conditions = ['project_id = ? AND is_trashed = ?', $project->getId(), false];
        }

        /** @var Discussion[] $discussions */
        if ($discussions = self::find(['conditions' => $conditions])) {
            foreach ($discussions as $discussion) {
                $result[$discussion->getId()] = $discussion->isRead($user);
            }
        }

        return $result;
    }

    public static function whatIsWorthRemembering(): array
    {
        return [
            'project_id',
            'name',
            'body',
            'is_trashed',
        ];
    }
}
