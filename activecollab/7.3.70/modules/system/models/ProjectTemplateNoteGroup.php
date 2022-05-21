<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Project template note group.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class ProjectTemplateNoteGroup extends ProjectTemplateElement implements IBody
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
        return [];
    }

    /**
     * Return required element properties.
     *
     * @return array
     */
    public function getRequiredElementProperties()
    {
        return [];
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
