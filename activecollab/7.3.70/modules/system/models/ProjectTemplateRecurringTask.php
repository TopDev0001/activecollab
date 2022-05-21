<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class ProjectTemplateRecurringTask extends ProjectTemplateElement implements ILabels, IBody
{
    use ILabelsImplementation;
    use IBodyImplementation;

    public function getElementProperties()
    {
        return [
            'task_list_id' => 'intval',
            'assignee_id' => 'intval',
            'is_important' => 'boolval',
            'is_hidden_from_clients' => 'boolval',
            'label_id' => 'intval',
            'subtasks' => 'array',
            'repeat_frequency' => 'strval',
            'repeat_amount' => 'intval',
            'repeat_amount_extended' => 'intval',
            'start_in' => 'strval',
            'due_in' => 'strval',
            'estimate' => 'strval',
            'job_type_id' => 'intval',
        ];
    }

    public function getLabelType(): string
    {
        return TaskLabel::class;
    }

    public function getSubtasks(): array
    {
        return $this->getAdditionalProperty('subtasks');
    }
}
