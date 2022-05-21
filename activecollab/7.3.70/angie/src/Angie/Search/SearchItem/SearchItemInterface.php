<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\SearchItem;

use ActiveCollab\Foundation\Urls\PermalinkInterface;
use Angie\Search\SearchDocument\SearchDocumentInterface;

interface SearchItemInterface extends PermalinkInterface
{
    const FIELD_BOOLEAN = 'boolean';
    const FIELD_NUMERIC = 'numeric';
    const FIELD_DATE = 'date';
    const FIELD_DATETIME = 'datetime';
    const FIELD_STRING = 'string';
    const FIELD_TEXT = 'text';

    public function getSearchFields(): array;
    public function addSearchFields(string ...$field_names): void;
    public function getSearchDocument(): SearchDocumentInterface;
    public function getSearchIndexType(): string;

    /**
     * Get object ID.
     *
     * @return int
     */
    public function getId();
    public function getModelName(
        bool $underscore = false,
        bool $singular = false
    ): string;
}
