<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Pusher\Pusher;

abstract class RealTimeIntegration extends Integration implements RealTimeIntegrationInterface
{
    public function isSingleton(): bool
    {
        return true;
    }

    public function getDescription()
    {
        return lang('Push events to everyone using ActiveCollab, so their pages update without the need to hit refresh.');
    }

    public function getWsHost()
    {
        return str_replace(['https://', 'http://'], ['', ''], $this->getApiHost());
    }

    public function getApiUrl(): string
    {
        return ($this->getApiPort() === 443 ? 'https://' : 'http://') . $this->getApiHost();
    }

    /**
     * Return pusher api auth version.
     *
     * @return string
     */
    public function getAuthVersion()
    {
        return '1.0';
    }

    public function getBasePath(): string
    {
        return "/apps/{$this->getAppId()}";
    }

    public function getEventsPath(): string
    {
        return "{$this->getBasePath()}/events";
    }

    /**
     * Return auth query string.
     *
     * @param mixed $payload
     */
    public function buildAuthQueryString(string $method, array $payload, ?int $timestamp = null): string
    {
        return Pusher::build_auth_query_string(
            $this->getAppKey(),
            $this->getAppSecret(),
            strtoupper($method),
            $this->getEventsPath(),
            ['body_md5' => md5(json_encode($payload))],
            $this->getAuthVersion(),
            is_null($timestamp) ? DateTimeValue::now()->getTimestamp() : $timestamp
        );
    }

    public function authOnChannel($channel_name, $socket_id, IUser $user, $user_info = [])
    {
        $pusher = new Pusher(
            $this->getAppKey(),
            $this->getAppSecret(),
            $this->getAppId(),
            [
                'cluster' => $this->getAppCluster(),
            ]
        );

        if (str_starts_with($channel_name, RealTimeIntegrationInterface::SOCKET_CHANNEL_PRIVATE)) {
            $encoded_string = $pusher->socket_auth($channel_name, $socket_id);
        } else {
            $encoded_string = $pusher->presence_auth(
                $channel_name,
                $socket_id,
                $user->getId(),
                $user_info
            );
        }

        return json_decode($encoded_string, true);
    }

    /**
     * @param string     $channel_name
     * @param IUser|User $user
     */
    public function isValidChannel(
        $channel_name,
        IUser $user
    ): bool
    {
        if (str_starts_with($channel_name, RealTimeIntegrationInterface::SOCKET_CHANNEL_PRIVATE)) {
            return $this->isValidPrivateChannel($user, $channel_name);
        } elseif (str_starts_with($channel_name, RealTimeIntegrationInterface::SOCKET_CHANNEL_PRESENCE)) {
            return $this->isValidPresenceChannel($user, $channel_name);
        }

        return false;
    }

    /**
     * @param  string $channel_name
     * @return bool
     */
    private function isValidPrivateChannel(User $user, $channel_name)
    {
        $is_on_demand = $this->isOnDemand();
        $account_id = $this->getAccountId();

        $valid_channel = RealTimeIntegrationInterface::SOCKET_CHANNEL_PRIVATE . '-';

        if ($is_on_demand) {
            $valid_channel .= 'instance-' . $account_id . '-';
        }

        $valid_channel .= 'user-' . $user->getId();

        return $valid_channel === $channel_name;
    }

    /**
     * @param  string $channel_name
     * @return bool
     */
    private function isValidPresenceChannel(User $user, $channel_name)
    {
        // "presence-instance-1-task-20" - example of on demand presence channel
        // "presence-task-20" - example of self hosted presence channel
        // "presence-instance-1-global" - example of on-demand global presence channel
        // "presence-global" - example of self-hosted global presence channel

        $is_on_demand = $this->isOnDemand();
        $account_id = $this->getAccountId();

        if ($this->checkIsGlobalPresenceChannel($is_on_demand, $account_id, $channel_name)) {
            return true;
        }

        $bits = explode('-', $channel_name);

        $object_id = (int) array_pop($bits);
        $object_type = ucfirst((string) array_pop($bits)); // -task-

        if (!class_exists($object_type) || !is_subclass_of($object_type, DataObject::class)) {
            return false;
        }

        /** @var IProjectElement $object */
        $object = DataObjectPool::get($object_type, $object_id);

        if (empty($object) || !$object->canView($user)) {
            return false;
        }

        if ($is_on_demand) {
            $instance_id = (int) array_pop($bits);

            return $instance_id === $account_id;
        }

        return true;
    }

    private function checkIsGlobalPresenceChannel(bool $is_on_demand, int $account_id, string $channel): bool
    {
        return ($is_on_demand && $channel === "presence-instance-{$account_id}-global") ||
            (!$is_on_demand && $channel === 'presence-global');
    }

    private $account_id;

    public function getAccountId(): int
    {
        if ($this->account_id) {
            return $this->account_id;
        }

        return AngieApplication::getAccountId();
    }

    public function setAccountId(?int $account_id)
    {
        $this->account_id = $account_id;
    }

    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'app_key' => $this->getAppKey(),
                'app_cluster' => $this->getAppCluster(),
                'ws_host' => $this->getWsHost(),
                'ws_port' => $this->getApiPort(),
            ]
        );
    }

    abstract public function getAppCluster(): ?string;
    abstract protected function getApiHost(): string;
    abstract public function getApiPort(): int;
    abstract public function getAppKey(): string;
    abstract protected function getAppSecret(): string;
    abstract public function getAppId(): string;
    abstract protected function isOnDemand(): bool;
}
