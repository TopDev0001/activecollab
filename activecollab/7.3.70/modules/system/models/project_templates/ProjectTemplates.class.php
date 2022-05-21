<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ProjectTemplateEvents\ProjectTemplateCreatedEvent;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ProjectTemplateEvents\ProjectTemplateUpdatedEvent;

/**
 * Project templates manager class.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class ProjectTemplates extends BaseProjectTemplates
{
    /**
     * Prepare Collection.
     *
     * @param  User|null       $user
     * @return ModelCollection
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        $collection = parent::prepareCollection($collection_name, $user);

        if ($collection_name == 'project_template_active') {
            self::prepareCollectionActiveProjectTemplates($collection);
        }

        $collection->setPreExecuteCallback(function () {
            ProjectTemplateElements::preloadCountByProjectTemplate();
        });

        return $collection;
    }

    /**
     * Prepare active project templates collection.
     *
     * @param ModelCollection $collection
     */
    private static function prepareCollectionActiveProjectTemplates($collection)
    {
        $collection->setConditions('is_trashed = ?', false);
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): ProjectTemplate
    {
        try {
            DB::beginWork('Creating project template @ ' . __CLASS__);

            $project_template = parent::create($attributes, $save, $announce); // @TODO Announcement should be sent after members are added

            if ($project_template instanceof ProjectTemplate && $project_template->isLoaded()) {
                $project_template->tryToAddMembersFrom($attributes);
            }

            DataObjectPool::announce(new ProjectTemplateCreatedEvent($project_template));

            DB::commit('Calendar project template @ ' . __CLASS__);

            return $project_template;
        } catch (Exception $e) {
            DB::rollback('Failed to create project template @ ' . __CLASS__);
            throw $e;
        }
    }

    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): ProjectTemplate
    {
        if ($instance instanceof ProjectTemplate) {
            $current_users = $instance->getMemberIds();

            try {
                DB::beginWork('Updating project template @ ' . __CLASS__);

                parent::update($instance, $attributes, $save);

                if ($save) {
                    $instance->setMembers([]);

                    if (isset($attributes['members']) && is_array($attributes['members']) && !empty($attributes['members'])) {
                        $instance->tryToAddMembersFrom($attributes);
                        $changed_users = $attributes['members'];
                    } else {
                        $changed_users = [];
                    }
                }

                if (!empty($current_users) && $current_users > $changed_users) {
                    $users_to_revoke = array_diff($current_users, $changed_users);
                    ProjectTemplateElements::revokeAssignee($users_to_revoke, $instance->getId());
                }

                DataObjectPool::announce(new ProjectTemplateUpdatedEvent($instance));

                DB::commit('Calendar project template @ ' . __CLASS__);
            } catch (Exception $e) {
                DB::rollback('Failed to update project template @ ' . __CLASS__);
                throw $e;
            }
        } else {
            throw new InvalidInstanceError('instance', $instance, 'ProjectTemplate');
        }

        return $instance;
    }

    /**
     * Revoke user from all templates where it is a member.
     *
     * @throws InsufficientPermissionsError
     */
    public static function revokeMember(User $user, User $by)
    {
        if (!$user->canChangeRole($by, false)) {
            throw new InsufficientPermissionsError();
        }

        /** @var ProjectTemplate[] $project_templates */
        if ($project_templates = self::findBySQL('SELECT p.* FROM project_templates AS p LEFT JOIN project_template_users AS u ON p.id = u.project_template_id WHERE u.user_id = ?', $user->getId())) {
            foreach ($project_templates as $project_template) {
                $project_template->removeMembers([$user]);
            }
        }
    }
}
