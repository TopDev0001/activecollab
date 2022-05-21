<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\History\Renderers\IsHiddenFromClientsHistoryFieldRenderer;
use ActiveCollab\Module\Discussions\Events\DataObjectLifeCycleEvents\DiscussionCreatedEvent;
use ActiveCollab\Module\Discussions\Events\DataObjectLifeCycleEvents\DiscussionMoveToTrashEvent;
use Angie\Search\SearchDocument\SearchDocumentInterface;

class Discussion extends BaseDiscussion
{
    public function getRoutingContext(): string
    {
        return 'discussion';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'project_id' => $this->getProjectId(),
            'discussion_id' => $this->getId(),
        ];
    }

    public function getHistoryFieldRenderers(): array
    {
        $renderers = parent::getHistoryFieldRenderers();

        $renderers['is_hidden_from_clients'] = new IsHiddenFromClientsHistoryFieldRenderer();

        return $renderers;
    }

    public function getSearchDocument(): SearchDocumentInterface
    {
        return new ProjectElementSearchDocument($this);
    }

    public function validate(ValidationErrors &$errors)
    {
        if (!$this->validatePresenceOf('name')) {
            $errors->addError('Summary is required', 'name');
        }

        parent::validate($errors);
    }

    public function canMoveToProject(User $user, Project $target_project)
    {
        $can_move = parent::canMoveToProject($user, $target_project);

        if ($user->isPowerClient(true)) {
            return $can_move && $this->isCreatedBy($user);
        } elseif ($user->isClient()) {
            return false;
        } else {
            return $can_move;
        }
    }

    public function canCopyToProject(User $user, Project $target_project)
    {
        $can_copy = parent::canCopyToProject($user, $target_project);

        if ($user->isPowerClient(true)) {
            return $can_copy && $this->isCreatedBy($user);
        } elseif ($user->isClient()) {
            return false;
        } else {
            return $can_copy;
        }
    }

    /**
     * Move this project element to project.
     *
     * @throws Exception
     */
    public function moveToProject(
        Project $project,
        User $by,
        callable $before_save = null,
        callable $after_save = null
    )
    {
        parent::moveToProject($project, $by, $before_save, $after_save);

        DataObjectPool::announce(new DiscussionCreatedEvent($this));
    }

    public function moveToTrash(User $by = null, $bulk = false)
    {
        try {
            DB::beginWork('Begin: move Discussion to trash @ ' . __CLASS__);

            Notifications::deleteByParent($this);
            DataObjectPool::announce(new DiscussionMoveToTrashEvent($this));
            parent::moveToTrash($by, $bulk);

            DB::commit('Done: move Discussion to trash @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: move Discussion to trash @ ' . __CLASS__);
            throw $e;
        }
    }
}
