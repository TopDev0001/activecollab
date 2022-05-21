<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

/**
 * Project template task.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class ProjectTemplateTask extends ProjectTemplateElement implements ILabels, IBody, IHiddenFromClients, IProjectTemplateTaskDependency
{
    use ILabelsImplementation;
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
            'task_list_id' => 'intval',
            'assignee_id' => 'intval',
            'job_type_id' => 'intval',
            'estimate' => 'floatval',
            'start_on' => 'intval',
            'due_on' => 'intval',
            'is_important' => 'boolval',
            'is_hidden_from_clients' => 'boolval',
            'label_id' => 'intval',
        ];
    }

    public function getLabelType(): string
    {
        return TaskLabel::class;
    }

    public function getTaskListId(): int
    {
        return (int) $this->getAdditionalProperty('task_list_id');
    }

    public function getIsHiddenFromClients()
    {
        return (bool) $this->getAdditionalProperty('is_hidden_from_clients');
    }

    public function getStartOn(): ?int
    {
        if ($this->getAdditionalProperty('start_on') !== null) {
            return (int) $this->getAdditionalProperty('start_on');
        }

        return null;
    }

    public function getDueOn(): ?int
    {
        if ($this->getAdditionalProperty('due_on') !== null) {
            return (int) $this->getAdditionalProperty('due_on');
        }

        return null;
    }

    public function delete($bulk = false)
    {
        try {
            DB::beginWork();

            $subtasks_to_delete_ids = [];

            $rows = DB::execute(
                'SELECT `id`, `raw_additional_properties` FROM `project_template_elements` WHERE `type` = ? AND `template_id` = ?',
                ProjectTemplateSubtask::class,
                $this->getTemplateId()
            );

            if ($rows) {
                foreach ($rows as $row) {
                    $properties = $row['raw_additional_properties'] ? unserialize($row['raw_additional_properties']) : [];

                    if (!empty($properties['task_id']) && $properties['task_id'] == $this->getId()) {
                        $subtasks_to_delete_ids[] = $row['id'];
                    }
                }
            }

            if (count($subtasks_to_delete_ids)) {
                foreach (ProjectTemplateElements::findByIds($subtasks_to_delete_ids) as $subtasks_template) {
                    $subtasks_template->delete();
                }
            }

            parent::delete($bulk);

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            AngieApplication::log()->error($e->getMessage(), [__METHOD__]);
        }
    }

    public function getChildDependencies(): array
    {
        return DataObjectPool::getByIds(ProjectTemplateElement::class, $this->getChildrenIds()) ?? [];
    }

    public function getParentDependencies(): array
    {
        return DataObjectPool::getByIds(ProjectTemplateElement::class, $this->getParentsIds()) ?? [];
    }

    private function getChildrenIds(): ?array
    {
        return DB::executeFirstColumn(
            'SELECT child_id FROM project_template_task_dependencies WHERE parent_id = ?',
            $this->getId()
        );
    }

    private function getParentsIds(): ?array
    {
        return DB::executeFirstColumn(
            'SELECT parent_id FROM project_template_task_dependencies WHERE child_id = ?',
            $this->getId()
        );
    }

    public function canEdit(User $user): bool
    {
        return Projects::canAdd($user);
    }
}
