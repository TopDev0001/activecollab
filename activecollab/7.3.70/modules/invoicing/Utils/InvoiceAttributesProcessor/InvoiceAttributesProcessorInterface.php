<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Invoicing\Utils\InvoiceAttributesProcessor;

use ActiveCollab\Foundation\Text\VariableProcessor\VariableProcessorInterface;

interface InvoiceAttributesProcessorInterface
{
    public function prepareAttributesForNewInvoice(
        array $attributes,
        VariableProcessorInterface $variable_processor
    ): array;

    public function prepareAttributesForNewEstimate(
        array $attributes,
        VariableProcessorInterface $variable_processor
    ): array;

    public function prepareAttributesForNewRecurringProfile(array $attributes): array;
}
