<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class ProjectTemplateDiscussion extends ProjectTemplateElement implements IBody, IHiddenFromClients
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
            'is_hidden_from_clients' => 'boolval',
        ];
    }

    public function getIsHiddenFromClients()
    {
        return (bool) $this->getAdditionalProperty('is_hidden_from_clients');
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
}
