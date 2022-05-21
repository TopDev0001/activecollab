<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class TaskLabel extends Label implements TaskLabelInterface
{
    public function canEdit(User $user): bool
    {
        if (!$this->getIsDefault() && self::countProjectsByTaskLabelForUser($user->getId())) {
            return true;
        }

        return parent::canEdit($user);
    }

    public function canDelete(User $user): bool
    {
        return $this->canEdit($user);
    }

    protected function countProjectsByTaskLabelForUser(int $user_id = null): int
    {
        return (int) DB::executeFirstCell(
            'SELECT COUNT(id) FROM projects as p, (SELECT t.project_id FROM tasks as t LEFT JOIN parents_labels as l ON l.parent_id = t.id WHERE l.label_id = ? AND l.parent_type = ?) as x WHERE x.project_id = p.id AND p.leader_id = ?',
            $this->getId(),
            Task::class,
            $user_id
        );
    }

    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'is_global' => $this->getIsGlobal(),
            ]
        );
    }

    public function save()
    {
        $is_new = $this->isNew();

        parent::save();

        if ($is_new) {
            Projects::clearCache();
        }
    }

    public function delete($bulk = false)
    {
        parent::delete($bulk);

        if (!$bulk) {
            Tasks::clearCache();
        }
    }
}
