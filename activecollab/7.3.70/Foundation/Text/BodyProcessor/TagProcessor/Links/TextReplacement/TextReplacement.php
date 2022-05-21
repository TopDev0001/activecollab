<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\Links\TextReplacement;

use InvalidArgumentException;

class TextReplacement implements TextReplacementInterface
{
    private $replacement_type;
    private $replacement;
    private $suffix;

    public function __construct(string $replacement_type, string $replacement, string $suffix)
    {
        if (!in_array($replacement_type, self::REPLACEMENTS)) {
            throw new InvalidArgumentException(
                sprintf('Replacement type "%s" is not known.', $replacement_type)
            );
        }

        $this->replacement_type = $replacement_type;
        $this->replacement = $replacement;
        $this->suffix = $suffix;
    }

    public function getReplacementType(): string
    {
        return $this->replacement_type;
    }

    public function getReplacement(): string
    {
        return $this->replacement;
    }

    public function getSuffix(): string
    {
        return $this->suffix;
    }
}
