<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Project template note.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class ProjectTemplateNote extends ProjectTemplateElement implements IBody, IHiddenFromClients
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
            'note_group_id' => 'intval',
            'is_hidden_from_clients' => 'boolval',
        ];
    }

    public function getIsHiddenFromClients()
    {
        return (bool) $this->getAdditionalProperty('is_hidden_from_clients');
    }

    public function getNoteGroupId(): int
    {
        return (int) $this->getAdditionalProperty('note_group_id');
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
     * Include plain text version of body in the JSON response.
     *
     * @return bool
     */
    protected function includePlainTextBodyInJson()
    {
        return true;
    }
}
