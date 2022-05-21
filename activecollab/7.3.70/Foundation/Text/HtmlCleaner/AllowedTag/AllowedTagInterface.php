<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\HtmlCleaner\AllowedTag;

use ActiveCollab\Foundation\Text\HtmlCleaner\AllowedElement\AllowedElementInterface;

interface AllowedTagInterface extends AllowedElementInterface
{
    public function getTagName(): string;
    public function getAllowedAttributes(): array;
    public function allowAttributes(string ...$attributes): void;
    public function isAttributeAllowed(string $attribute_name): bool;
}
