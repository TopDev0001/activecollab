<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class ProjectTemplateElement extends BaseProjectTemplateElement
{
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['template_id'] = $this->getTemplateId();
        $result['position'] = $this->getPosition();

        foreach ($this->getElementProperties() as $property => $cast) {
            if ($cast === 'array') {
                $result[$property] = empty($this->getAdditionalProperty($property))
                    ? []
                    : (array) $this->getAdditionalProperty($property);

                continue;
            }

            $result[$property] = call_user_func($cast, $this->getAdditionalProperty($property));
        }

        return $result;
    }

    /**
     * Return array of element properties.
     *
     * Key is name of the property, and value is a casting method
     *
     * @return array
     */
    abstract public function getElementProperties();

    public function getRoutingContext(): string
    {
        return 'project_template_element';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'project_template_id' => $this->getTemplateId(),
            'project_template_element_id' => $this->getId(),
        ];
    }

    public function canView(User $user): bool
    {
        return Projects::canAdd($user);
    }

    /**
     * Validate before save.
     */
    public function validate(ValidationErrors & $errors)
    {
        if (!$this->validatePresenceOf('template_id')) {
            $errors->addError('Please select a template', 'template_id');
        }

        foreach ($this->getRequiredElementProperties() as $property) {
            if ($property == 'name' || $property == 'body') {
                if (!$this->validatePresenceOf($property)) {
                    $errors->addError("Element $property is required", $property);
                }
            } else {
                if (!$this->getAdditionalProperty($property)) {
                    $errors->addError("Element $property is required", $property);
                }
            }
        }

        $start_on = $this->getAdditionalProperty('start_on');
        $due_on = $this->getAdditionalProperty('due_on');

        if ($start_on && $due_on && $start_on > $due_on) {
            $errors->addError('Start on should be before due on', 'start_on');
        }

        $five_years = 5 * 365;

        if ($start_on && $start_on > $five_years) {
            $errors->addError('Invalid start on', 'start_on');
        }

        if ($due_on && $due_on > $five_years) {
            $errors->addError('Invalid due on', 'due_on');
        }

        parent::validate($errors);
    }

    /**
     * Return required element properties.
     *
     * @return array
     */
    public function getRequiredElementProperties()
    {
        return ['name'];
    }

    /**
     * Return template that's been used to create this project.
     *
     * @return ProjectTemplate
     */
    public function getTemplate()
    {
        $template = DataObjectPool::get(ProjectTemplate::class, $this->getTemplateId());

        if ($template instanceof ProjectTemplate) {
            return $template;
        }

        return null;
    }

    /**
     * Hide attachments from clients for template element.
     */
    public function hideOrShowAttachmentsFromClients(bool $is_hidden): void
    {
        DB::execute(
            'UPDATE `attachments` SET `is_hidden_from_clients` = ? WHERE `parent_type` = ? AND `parent_id` = ?',
            $is_hidden,
            $this->getType(),
            $this->getId()
        );
    }
}
