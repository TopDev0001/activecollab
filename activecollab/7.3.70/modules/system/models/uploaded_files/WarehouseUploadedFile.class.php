<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class WarehouseUploadedFile extends RemoteUploadedFile
{
    use IWarehouseFileImplementation;

    /**
     * Override IAdditionalPropertiesImplementation result.
     *
     * @return array
     */
    public function getAdditionalProperties()
    {
        $additional_properties = parent::getAdditionalProperties();

        return [
            'shared_hash' => isset($additional_properties['shared_hash']) ? $additional_properties['shared_hash'] : null,
        ];
    }
}
