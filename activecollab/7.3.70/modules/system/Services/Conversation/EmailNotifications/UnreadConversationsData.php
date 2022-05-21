<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Services\Conversation\EmailNotifications;

class UnreadConversationsData implements UnreadConversationsDataInterface
{
    private int $total;
    private array $breakdown;

    public function __construct(int $total, array $breakdown)
    {
        $this->total = $total;
        $this->breakdown = $breakdown;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getBreakdown(): array
    {
        return $this->breakdown;
    }
}
