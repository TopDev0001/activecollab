<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * ProjectTemplateTaskDependency class.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
final class ProjectTemplateTaskDependency extends BaseProjectTemplateTaskDependency
{
    public function jsonSerialize(): array
    {
        return [
            'parent_id' => $this->getParentId(),
            'child_id' => $this->getChildId(),
        ];
    }

    public function describeSingleForFeather(array &$result)
    {
        parent::describeSingleForFeather($result);

        $result['parent'] = ProjectTemplateElements::findById($this->getParentId());
        $result['child'] = ProjectTemplateElements::findById($this->getChildId());
    }
}
