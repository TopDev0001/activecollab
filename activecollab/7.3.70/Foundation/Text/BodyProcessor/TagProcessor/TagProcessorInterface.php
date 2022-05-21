<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor;

use ActiveCollab\Foundation\Models\IdentifiableInterface;
use simple_html_dom;

interface TagProcessorInterface
{
    public function processForStorage(simple_html_dom $dom): array;
    public function processForDisplay(simple_html_dom $dom, IdentifiableInterface $context, string $display): void;
    public function processForEditor(simple_html_dom $dom, IdentifiableInterface $context): void;
    public function getAllowedTags(): array;
}
