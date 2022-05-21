<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\BodyProcessor;

use ActiveCollab\Foundation\Models\IdentifiableInterface;
use ActiveCollab\Foundation\Text\BodyProcessor\ProcessedBody\ProcessedBodyInterface;
use ActiveCollab\Foundation\Text\HtmlCleaner\HtmlCleanerInterface;

interface BodyProcessorInterface
{
    const DISPLAY_SCEEEN = 'screen';
    const DISPLAY_EMAIL = 'email';

    const DIPLAYS = [
        self::DISPLAY_SCEEEN,
        self::DISPLAY_EMAIL,
    ];

    public function getHtmlCleaner(): HtmlCleanerInterface;
    public function processForStorage(string $raw_body): ProcessedBodyInterface;
    public function processForEditor(
        string $stored_body,
        IdentifiableInterface $context
    ): ProcessedBodyInterface;
    public function processForDisplay(
        string $stored_body,
        IdentifiableInterface $context,
        string $display = BodyProcessorInterface::DISPLAY_SCEEEN
    ): ProcessedBodyInterface;
}
