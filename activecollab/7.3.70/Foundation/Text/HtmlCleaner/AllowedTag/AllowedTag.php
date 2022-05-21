<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\HtmlCleaner\AllowedTag;

class AllowedTag implements AllowedTagInterface
{
    private string $tag_name;
    private array $allowed_attributes;

    public function __construct(string $tag_name, string ...$allowed_attributes)
    {
        $this->tag_name = $tag_name;
        $this->allowed_attributes = $allowed_attributes;
    }

    public function getTagName(): string
    {
        return $this->tag_name;
    }

    public function getAllowedAttributes(): array
    {
        return $this->allowed_attributes;
    }

    public function allowAttributes(string ...$attributes): void
    {
        $this->allowed_attributes = array_unique(array_merge($this->allowed_attributes, $attributes));
    }

    public function isAttributeAllowed(string $attribute_name): bool
    {
        return in_array($attribute_name, $this->allowed_attributes);
    }
}
