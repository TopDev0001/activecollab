<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Http\Response\StaticHtmlFile;

interface StaticHtmlFileInterface
{
    public function getPath(): string;
    public function getOptions(): array;
    public function getContent(): string;
}
