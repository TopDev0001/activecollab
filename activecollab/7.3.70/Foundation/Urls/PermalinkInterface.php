<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls;

interface PermalinkInterface
{
    public function getViewUrl(): string;
    public function getUrlPath(): string;
}
