<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

trait IDropboxFileImplementation
{
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['thumbnail_url'] = $this->getThumbnailUrl('--WIDTH--', '--HEIGHT--');
        $result['download_url'] = $this->getDownloadUrl(true);
        $result['preview_url'] = $this->getPreviewUrl();

        return $result;
    }

    /**
     * Return warehouse thumbnail url.
     *
     * @param  int    $width
     * @param  int    $height
     * @param  string $scale
     * @return string
     */
    public function getThumbnailUrl($width = 60, $height = 60, $scale = Thumbnails::SCALE)
    {
        return null;
    }

    /**
     * Return warehouse preview url.
     *
     * @return string
     */
    public function getPreviewUrl()
    {
        return $this->getUrl();
    }

    /**
     * Return warehouse download url.
     *
     * @param  bool   $force
     * @return string
     */
    public function getDownloadUrl($force = false)
    {
        return $this->getPreviewUrl();
    }

    /**
     * Return public download url.
     *
     * @param  bool   $force
     * @return string
     */
    public function getPublicDownloadUrl($force = false)
    {
        return $this->getDownloadUrl($force);
    }

    /**
     * Return file link.
     *
     * @return string|null
     */
    public function getUrl()
    {
        if ($this instanceof IAdditionalProperties) {
            return $this->getAdditionalProperty('url');
        }

        return null;
    }

    /**
     * Set file link.
     *
     * @param  string      $value
     * @return string|null
     */
    public function setUrl($value)
    {
        if ($this instanceof IAdditionalProperties) {
            return $this->setAdditionalProperty('url', $value);
        }

        return null;
    }

    abstract public function getLocation();

    abstract public function getMd5();
}
