<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\Links\TextReplacement;

interface TextReplacementInterface
{
    const REPLACE_WITH_NAME = 'name';
    const REPLACE_WITH_URL = 'url';

    const REPLACEMENTS = [
        self::REPLACE_WITH_NAME,
        self::REPLACE_WITH_URL,
    ];

    public function getReplacementType(): string;
    public function getReplacement(): string;
    public function getSuffix(): string;
}
