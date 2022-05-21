<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\App\Channel;

interface OnDemandChannelInterface
{
    public function isEdgeChannel(): bool;
    public function isBetaChannel(): bool;
    public function isStableChannel(): bool;
}
