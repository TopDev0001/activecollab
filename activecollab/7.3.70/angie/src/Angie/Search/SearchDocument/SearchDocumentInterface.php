<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\SearchDocument;

interface SearchDocumentInterface
{
    const CONTEXT_PROJECTS = 'projects';
    const CONTEXT_PEOPLE = 'people';
    const CONTEXT_INVOICES = 'invoices';
    const CONTEXT_ESTIMATES = 'estimates';

    const CONTEXTS = [
        self::CONTEXT_PROJECTS,
        self::CONTEXT_PEOPLE,
        self::CONTEXT_INVOICES,
        self::CONTEXT_ESTIMATES,
    ];

    /**
     * Return document context.
     *
     * @return string
     */
    public function getDocumentContext();

    /**
     * Return document ID.
     *
     * @return string
     */
    public function getDocumentId();

    /**
     * Return document payload, the one that is sent to the search engine.
     *
     * @return array
     */
    public function getDocumentPayload();
}
