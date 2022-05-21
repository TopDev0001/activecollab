<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Framework level uploaded file instance.
 *
 * @package angie.frameworks.environment
 * @subpackage models
 */
abstract class FwUploadedFile extends BaseUploadedFile
{
    /**
     * @return string
     */
    public function getShareHash()
    {
        return $this->getAdditionalProperty('share_hash');
    }

    /**
     * @return string
     */
    public function getTikaData()
    {
        return $this->getAdditionalProperty('tika_data');
    }

    /**
     * Check is uploaded file from warehouse.
     *
     * @return bool
     */
    private function shouldUseWarehouse()
    {
        return $this->getType() === 'WarehouseUploadedFile' && Integrations::findFirstByType(WarehouseIntegration::class)->isInUse();
    }

    /**
     * Serialize to JSON.
     */
    public function jsonSerialize(): array
    {
        if ($this->shouldUseWarehouse()) {
            $thumbnail_url = Integrations::findFirstByType(WarehouseIntegration::class)->prepareFileThumbnailUrl(
                $this->getLocation(),
                $this->getMd5(),
                '--WIDTH--',
                '--HEIGHT--'
            );
        } else {
            $thumbnail_url = $this->getThumbnailUrl('--WIDTH--', '--HEIGHT--', '--SCALE--');
        }

        return [
            'class' => get_class($this),
            'code' => $this->getCode(),
            'name' => $this->getName(),
            'mime_type' => $this->getMimeType(),
            'size' => $this->getSize(),
            'thumbnail_url' => $thumbnail_url,
        ];
    }

    /**
     * Validate before save.
     */
    public function validate(ValidationErrors & $errors)
    {
        $this->validatePresenceOf('name') or $errors->addError('Name is required', 'name');

        if ($this->validatePresenceOf('location')) {
            if (!$this->validateUniquenessOf('location')) {
                $errors->addError('Location needs to be unique', 'location');
            }
        } else {
            $errors->addError('Location is required', 'location');
        }

        if ($this->validatePresenceOf('code')) {
            if (!$this->validateUniquenessOf('code')) {
                $errors->addError('File code needs to be unique', 'code');
            }
        } else {
            $errors->addError('File code is required', 'code');
        }
    }

    /**
     * Save to database.
     */
    public function save()
    {
        if (!$this->getCode()) {
            $this->setCode(UploadedFiles::getAvailableCode());
        }

        parent::save();
    }
}
