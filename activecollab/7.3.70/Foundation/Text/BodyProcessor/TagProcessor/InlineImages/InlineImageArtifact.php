<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\InlineImages;

use ActiveCollab\Foundation\Text\BodyProcessor\Artifact\Artifact;

class InlineImageArtifact extends Artifact
{
    private $inline_image_code;

    public function __construct(string $inline_image_code)
    {
        $this->inline_image_code = $inline_image_code;
    }

    public function getInlineImageCode(): string
    {
        return $this->inline_image_code;
    }
}
