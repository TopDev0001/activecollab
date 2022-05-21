<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\InlineImageDetailsResolver;

interface InlineImageDetailsResolverInterface
{
    public function getDetailsByParent(string $image_id, string $parent_type, int $parent_id): array;
}
