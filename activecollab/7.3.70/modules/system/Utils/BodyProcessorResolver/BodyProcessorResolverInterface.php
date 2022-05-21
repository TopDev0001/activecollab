<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\BodyProcessorResolver;

use ActiveCollab\Foundation\Text\BodyProcessor\BodyProcessorInterface;

interface BodyProcessorResolverInterface
{
    public function resolve(bool $with_inline_images = false): BodyProcessorInterface;
}
