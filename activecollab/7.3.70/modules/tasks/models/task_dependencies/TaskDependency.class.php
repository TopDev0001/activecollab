<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * TaskDependency class.
 *
 * @package ActiveCollab.modules.tasks
 * @subpackage models
 */
final class TaskDependency extends BaseTaskDependency
{
    public function jsonSerialize(): array
    {
        return [
            'parent_id' => $this->getParentId(),
            'child_id' => $this->getChildId(),
        ];
    }

    public function describeSingleForFeather(array & $result)
    {
        parent::describeSingleForFeather($result);

        $result['parent'] = Tasks::findById($this->getParentId());
        $result['child'] = Tasks::findById($this->getChildId());
    }
}
