<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class WebhookPayloadTransformator implements WebhookPayloadTransformatorInterface
{
    public function shouldTransform(string $url): bool
    {
        return false;
    }

    public function transform(string $event_type, DataObject $payload): ?array
    {
        return $payload->jsonSerialize();
    }

    public function getSupportedEvents(): array
    {
        return [];
    }
}
