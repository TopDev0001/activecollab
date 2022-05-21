<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Conversations;

class MessageOrderIdResolver implements MessageOrderIdResolverInterface
{
    private int $miliseconds_current_timestamp;
    private bool $is_test_mode;

    public function __construct(
        int $miliseconds_current_timestamp,
        bool $is_test_mode = false
    )
    {
        $this->miliseconds_current_timestamp = $miliseconds_current_timestamp;
        $this->is_test_mode = $is_test_mode;
    }

    public function resolve(?int $value): int
    {
        if ($value) {
            return !$this->is_test_mode && !$this->isInValidRange($value)
                ? $this->miliseconds_current_timestamp
                : $value;
        } else {
            return $this->miliseconds_current_timestamp;
        }
    }

    private function isInValidRange(int $value): bool
    {
        return $value >= $this->miliseconds_current_timestamp - 3000 && $value <= $this->miliseconds_current_timestamp;
    }
}
