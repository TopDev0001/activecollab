<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

interface WebhookPayloadTransformatorInterface
{
    public function shouldTransform(string $url): bool;
    public function transform(string $event_type, DataObject $payload): ?array;
    public function getSupportedEvents(): array;
}
