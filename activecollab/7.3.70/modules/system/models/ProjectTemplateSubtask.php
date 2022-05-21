<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class ProjectTemplateSubtask extends ProjectTemplateElement
{
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['body'] = $this->getBody();

        return $result;
    }

    /**
     * Return task name (first few words from text).
     *
     * @return string
     */
    public function getName()
    {
        return trim($this->getBody());
    }

    /**
     * Return array of element properties.
     *
     * Key is name of the property, and value is a casting method
     *
     * @return array
     */
    public function getElementProperties()
    {
        return ['task_id' => 'intval', 'assignee_id' => 'intval'];
    }

    /**
     * Return required element properties.
     *
     * @return array
     */
    public function getRequiredElementProperties()
    {
        return ['body', 'task_id'];
    }

    public function getTaskId(): int
    {
        return (int) $this->getAdditionalProperty('task_id');
    }
}
