<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\Files\Utils\FileDuplicator\FileDuplicator;
use ActiveCollab\Module\Files\Utils\FileDuplicator\FileDuplicatorInterface;
use function DI\get;

return [
    FileDuplicatorInterface::class => get(FileDuplicator::class),
];
