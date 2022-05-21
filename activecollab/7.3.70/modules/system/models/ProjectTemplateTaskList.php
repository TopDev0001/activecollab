<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class ProjectTemplateTaskList extends ProjectTemplateElement implements IBody
{
    use IBodyImplementation;

    /**
     * Return array of element properties.
     *
     * Key is name of the property, and value is a casting method
     *
     * @return array
     */
    public function getElementProperties()
    {
        return [
            'start_on' => 'intval',
            'due_on' => 'intval',
        ];
    }

    public function delete($bulk = false)
    {
        try {
            DB::beginWork();

            $tasks_to_delete_ids = [];

            if ($rows = DB::execute('SELECT id, raw_additional_properties FROM project_template_elements WHERE type = ? AND template_id = ?', ProjectTemplateTask::class, $this->getTemplateId())) {
                foreach ($rows as $row) {
                    $properties = $row['raw_additional_properties'] ? unserialize($row['raw_additional_properties']) : [];

                    if (!empty($properties['task_list_id']) && $properties['task_list_id'] == $this->getId()) {
                        $tasks_to_delete_ids[] = $row['id'];
                    }
                }
            }

            if (count($tasks_to_delete_ids)) {
                /** @var ProjectTemplateTask $task_template */
                foreach (ProjectTemplateElements::findByIds($tasks_to_delete_ids) as $task_template) {
                    $task_template->delete(true);
                }
            }

            parent::delete($bulk);

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
