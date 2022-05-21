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
class GoogleDriveUploadedFile extends RemoteUploadedFile
{
    use IGoogleDriveFileImplementation;

    /**
     * Override IAdditionalPropertiesImplementation result.
     *
     * @return array
     */
    public function getAdditionalProperties()
    {
        $additional_properties = parent::getAdditionalProperties();

        return [
            'url' => isset($additional_properties['url']) ? $additional_properties['url'] : null,
        ];
    }
}
