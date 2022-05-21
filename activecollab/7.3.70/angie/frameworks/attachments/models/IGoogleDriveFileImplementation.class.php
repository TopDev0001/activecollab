<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

trait IGoogleDriveFileImplementation
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
     * Return google drive preview url.
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
     * Return google drive file permalink.
     *
     * @return string|null
     */
    public function getUrl()
    {
        return $this instanceof IAdditionalProperties ? $this->getAdditionalProperty('url') : null;
    }

    /**
     * Set google drive file permalink.
     *
     * @param string
     */
    public function setUrl($value)
    {
        if ($this instanceof IAdditionalProperties) {
            $this->setAdditionalProperty('url', $value);
        }
    }

    abstract public function getLocation();

    abstract public function getMd5();
}
