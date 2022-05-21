<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class ProjectTemplateFile extends ProjectTemplateElement implements IFile, IBody, IHiddenFromClients
{
    use IFileImplementation;
    use IBodyImplementation;

    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        if (isset($result['type'])) {
            if ($result['type'] == WarehouseFile::class) {
                /** @var WarehouseIntegration $warehouse_integration */
                $warehouse_integration = Integrations::findFirstByType(WarehouseIntegration::class);

                $result['thumbnail_url'] = $warehouse_integration->prepareFileThumbnailUrl(
                    $this->getLocation(),
                    $this->getMd5(),
                    '--WIDTH--',
                    '--HEIGHT--'
                );
                $result['download_url'] = $warehouse_integration->prepareFileDownloadUrl(
                    $this->getLocation(),
                    $this->getMd5(),
                    false
                );
                $result['preview_url'] = $warehouse_integration->prepareFilePreviewUrl(
                    $this->getLocation(),
                    $this->getMd5()
                );
            } elseif ($result['type'] == GoogleDriveFile::class || $result['type'] == DropboxFile::class) {
                $result['thumbnail_url'] = null;
                $result['download_url'] = null;
                $result['preview_url'] = $result['url'];
            }
        }

        unset($result['body']);
        unset($result['body_formatted']);
        unset($result['attachments']);
        unset($result['url']);

        return $result;
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
        return [
            'type' => 'trim',
            'location' => 'trim',
            'size' => 'intval',
            'mime_type' => 'trim',
            'md5' => 'trim',
            'share_hash' => 'trim',
            'url' => 'trim',
            'is_hidden_from_clients' => 'boolval',
        ];
    }

    /**
     * Return file type, so IFileImplementation knows what to delete.
     */
    public function getFileType(): string
    {
        return $this->getAdditionalProperty('type');
    }

    /**
     * Return file size, in bytes.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->getAdditionalProperty('size');
    }

    /**
     * Set file size, in bytes.
     *
     * @param  int $value
     * @return int
     */
    public function setSize($value)
    {
        return $this->setAdditionalProperty('size', $value);
    }

    /**
     * Return name of the file in /upload folder.
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->getAdditionalProperty('location');
    }

    /**
     * Set file location.
     *
     * @param  string $value
     * @return string
     */
    public function setLocation($value)
    {
        return $this->setAdditionalProperty('location', $value);
    }

    /**
     * Return file MIME type.
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->getAdditionalProperty('mime_type');
    }

    /**
     * Set file MIME type.
     *
     * @param  string $value
     * @return string
     */
    public function setMimeType($value)
    {
        return $this->setAdditionalProperty('mime_type', $value);
    }

    /**
     * Return file hash.
     *
     * @return string
     */
    public function getMd5()
    {
        return $this->getAdditionalProperty('md5');
    }

    /**
     * Set file hash.
     *
     * @param  string $value
     * @return string
     */
    public function setMd5($value)
    {
        return $this->setAdditionalProperty('md5', $value);
    }

    /**
     * Return file share hash.
     *
     * @return string
     */
    public function getShareHash()
    {
        return $this->getAdditionalProperty('share_hash');
    }

    /**
     * Set file share hash.
     *
     * @param  string $value
     * @return string
     */
    public function setShareHash($value)
    {
        return $this->setAdditionalProperty('share_hash', $value);
    }

    /**
     * Return file url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->getAdditionalProperty('url');
    }

    /**
     * Set file url.
     *
     * @param  string $value
     * @return string
     */
    public function setUrl($value)
    {
        return $this->setAdditionalProperty('url', $value);
    }

    /**
     * Return true if this file is hidden from clients.
     *
     * @return bool
     */
    public function getIsHiddenFromClients()
    {
        return (bool) $this->getAdditionalProperty('is_hidden_from_clients');
    }
}
