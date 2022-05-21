<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\Links\TextReplacement\Resolver;

use ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\Links\TextReplacement\TextReplacementInterface;
use DataObject;

interface TextReplacementResolverInterface
{
    public function getTextReplacement(
        DataObject $entity,
        string $replacement = TextReplacementInterface::REPLACE_WITH_URL,
        string $suffix = '.'
    ): ?string;
}
