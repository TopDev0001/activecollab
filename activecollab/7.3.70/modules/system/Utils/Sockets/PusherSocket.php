<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Sockets;

use ActiveCollab\ActiveCollabJobs\Jobs\Http\SendRequest;
use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEventInterface;
use ActiveCollab\JobsQueue\Jobs\Job;
use ActiveCollab\Module\System\Utils\RealTimeIntegrationResolver\RealTimeIntegrationResolverInterface;
use ActiveCollab\Module\System\Utils\Webhooks\Resolver\RealTimeUsersChannelsResolverInterface;
use ActiveCollab\Module\System\Utils\Webhooks\Transformator\SocketPayloadTransformatorInterface;
use DataObject;
use RealTimeIntegrationInterface;

class PusherSocket extends Socket implements PusherSocketInterface
{
    private RealTimeIntegrationResolverInterface $integration_resolver;
    private RealTimeUsersChannelsResolverInterface $user_channel_resolver;
    private SocketPayloadTransformatorInterface $socket_payload_transformator;
    private SocketPayloadTransformatorInterface $socket_partial_payload_transformator;
    private int $account_id;

    public function __construct(
        RealTimeIntegrationResolverInterface $integration_resolver,
        RealTimeUsersChannelsResolverInterface $user_channel_resolver,
        SocketPayloadTransformatorInterface $socket_payload_transformator,
        SocketPayloadTransformatorInterface $socket_partial_payload_transformator,
        int $account_id
    ) {
        $this->integration_resolver = $integration_resolver;
        $this->user_channel_resolver = $user_channel_resolver;
        $this->socket_payload_transformator = $socket_payload_transformator;
        $this->socket_partial_payload_transformator = $socket_partial_payload_transformator;
        $this->account_id = $account_id;
    }

    public function getRequests(
        string $event_type,
        DataObjectLifeCycleEventInterface $event,
        bool $requests_with_partial_data = false,
        int $delay = 1
    ): array
    {
        $requests = [];

        if ($real_time_integration = $this->integration_resolver->getIntegration()) {
            $this->makeRequests(
                $requests,
                $event_type,
                $event->getObject(),
                $real_time_integration,
                $this->socket_payload_transformator,
                $this->user_channel_resolver->getUsersChannels($event),
                $delay
            );

            if ($requests_with_partial_data) {
                $this->makeRequests(
                    $requests,
                    $event_type,
                    $event->getObject(),
                    $real_time_integration,
                    $this->socket_partial_payload_transformator,
                    $this->user_channel_resolver->getUsersChannels($event, true),
                    $delay
                );
            }
        }

        return $requests;
    }

    private function makeRequests(
        array &$requests,
        string $event_type,
        DataObject $object,
        RealTimeIntegrationInterface $real_time_integration,
        SocketPayloadTransformatorInterface $payload_transformator,
        array $channels,
        int $delay
    ): void
    {
        $data = $payload_transformator->transform($event_type, $object);

        if (!empty($data)) {
            $chunks = ceil(count($channels) / self::CHANNELS_PER_REQUEST);
            $payload = [
                'name' => $event_type,
                'data' => json_encode($data),
            ];

            for ($i = 0; $i < $chunks; $i++) {
                $payload['channels'] = array_slice(
                    $channels,
                    $i * self::CHANNELS_PER_REQUEST,
                    self::CHANNELS_PER_REQUEST
                );

                $url = $real_time_integration->getApiUrl() . $real_time_integration->getEventsPath() . '?';
                $url .= $real_time_integration->buildAuthQueryString('POST', $payload);

                $additional_property = [];
                if ($delay) {
                    $additional_property = [
                        'delay' => $delay,
                    ];
                }

                $requests[] = new SendRequest(
                    array_merge([
                        'priority' => Job::HAS_HIGHEST_PRIORITY,
                        'instance_id' => $this->account_id,
                        'url' => $url,
                        'method' => 'POST',
                        'headers' => [
                            'Content-Type' => 'application/json',
                        ],
                        'payload' => json_encode($payload),
                    ], $additional_property)
                );
            }
        }
    }
}
