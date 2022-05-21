<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

interface RealTimeIntegrationInterface extends IntegrationInterface
{
    const JOBS_QUEUE_CHANNEL = 'socket';
    const CHAT_JOBS_QUEUE_CHANNEL = 'chat';

    const SOCKET_CHANNEL_PRIVATE = 'private';
    const SOCKET_CHANNEL_PRESENCE = 'presence';

    const SOCKET_CHANNELS = [
        self::SOCKET_CHANNEL_PRIVATE,
        self::SOCKET_CHANNEL_PRESENCE,
    ];

    public function getApiUrl(): string;

    public function getEventsPath(): string;

    public function buildAuthQueryString(string $method, array $payload, ?int $timestamp = null): string;

    /**
     * Authenticate on channel.
     *
     * @param  string $channel_name
     * @param  mixed  $socket_id
     * @param  array  $user_info
     * @return mixed
     */
    public function authOnChannel(
        $channel_name,
        $socket_id,
        IUser $user,
        $user_info = []
    );

    public function isValidChannel($channel_name, IUser $user): bool;
}
