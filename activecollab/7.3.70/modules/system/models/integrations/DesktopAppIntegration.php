<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class DesktopAppIntegration extends Integration
{
    /**
     * @var string
     */
    private $shepherd_prefix = 'https://activecollab.com';

    public function isSingleton(): bool
    {
        return true;
    }

    public function isInUse(User $user = null): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Desktop Apps (Beta)';
    }

    public function getShortName(): string
    {
        return 'desktop-app';
    }

    public function getDescription()
    {
        return lang('Run ActiveCollab as an app on your Mac or Windows computer.');
    }

    /**
     * @return string
     */
    public function getWindowsDownloadUrl()
    {
        return $this->shepherd_prefix . '/api/v2/desktop-apps/activecollab/releases/win32/download';
    }

    /**
     * @return string
     */
    public function getMacDownloadUrl()
    {
        return $this->shepherd_prefix . '/api/v2/desktop-apps/activecollab/releases/darwin/download';
    }

    public function isVisible(): bool
    {
        return false;
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'download_urls' => [
                'mac' => $this->getMacDownloadUrl(),
                'windows' => $this->getWindowsDownloadUrl(),
            ],
        ]);
    }
}
