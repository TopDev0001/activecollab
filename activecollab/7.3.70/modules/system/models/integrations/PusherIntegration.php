<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class PusherIntegration extends RealTimeIntegration
{
    public function getName()
    {
        return 'Pusher';
    }

    public function getShortName(): string
    {
        return 'pusher';
    }

    public function isInUse(User $user = null): bool
    {
        return !empty($this->getAppId());
    }

    public function getAppCluster(): ?string
    {
        return $this->getAdditionalProperty('cluster');
    }

    public function setAppCluster($value): ?string
    {
        $this->setAdditionalProperty('cluster', $value);

        return $this->getAppCluster();
    }

    protected function getApiHost(): string
    {
        return $this->getAppCluster()
            ? 'api-' . $this->getAppCluster() . '.pusher.com'
            : 'api.pusherapp.com';
    }

    public function setApiHost($value): string
    {
        $this->setAdditionalProperty('api_host', (string) $value);

        return $this->getApiHost();
    }

    public function getApiPort(): int
    {
        return $this->getAdditionalProperty('api_port')
            ? (int) $this->getAdditionalProperty('api_port')
            : 443;
    }

    public function setApiPort($value): int
    {
        $this->setAdditionalProperty('api_port', (int) $value);

        return $this->getApiPort();
    }

    public function getAppId(): string
    {
        return (string) $this->getAdditionalProperty('app_id');
    }

    public function setAppId($value): string
    {
        $this->setAdditionalProperty('app_id', (string) $value);

        return $this->getAppId();
    }

    public function getAppKey(): string
    {
        return (string) $this->getAdditionalProperty('key');
    }

    public function setAppKey($value): string
    {
        $this->setAdditionalProperty('key', (string) $value);

        return $this->getAppKey();
    }

    protected function getAppSecret(): string
    {
        return (string) $this->getAdditionalProperty('secret');
    }

    public function setAppSecret($value)
    {
        $this->setAdditionalProperty('secret', (string) $value);

        return $this->getAppSecret();
    }

    public function getApiUrl(): string
    {
        $protocol = $this->getApiPort() === 443 ? 'https://' : 'http://';

        $host = $this->getAppCluster()
            ? 'api-' . $this->getAppCluster() . '.pusher.com'
            : 'api.pusherapp.com';

        return $protocol . $host;
    }

    protected function isOnDemand(): bool
    {
        return false;
    }
}
