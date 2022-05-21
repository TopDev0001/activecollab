<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\Invoicing\Utils\ExistingInvoiceNumbersResolver\ExistingInvoiceNumbersResolver;
use ActiveCollab\Module\Invoicing\Utils\ExistingInvoiceNumbersResolver\ExistingInvoiceNumbersResolverInterface;
use ActiveCollab\Module\Invoicing\Utils\InvoiceAttributesProcessor\InvoiceAttributesProcessor;
use ActiveCollab\Module\Invoicing\Utils\InvoiceAttributesProcessor\InvoiceAttributesProcessorInterface;
use ActiveCollab\Module\Invoicing\Utils\InvoiceNumberSuggester\InvoiceNumberSuggester;
use ActiveCollab\Module\Invoicing\Utils\InvoiceNumberSuggester\InvoiceNumberSuggesterInterface;
use ActiveCollab\Module\Invoicing\Utils\RelatedInvoiceProjects\Resolver\RelatedInvoiceProjectsResolver;
use ActiveCollab\Module\Invoicing\Utils\RelatedInvoiceProjects\Resolver\RelatedInvoiceProjectsResolverInterface;
use ActiveCollab\Module\Invoicing\Utils\VariableProcessor\Factory\VariableProcessorFactory;
use ActiveCollab\Module\Invoicing\Utils\VariableProcessor\Factory\VariableProcessorFactoryInterface;
use ActiveCollab\Module\System\Utils\QrGenerator\EndroidQrGenerator;
use ActiveCollab\Module\System\Utils\QrGenerator\QrGeneratorInterface;
use function DI\get;
use Endroid\QrCode\Factory\QrCodeFactory;

return [
    ExistingInvoiceNumbersResolverInterface::class => get(ExistingInvoiceNumbersResolver::class),
    InvoiceNumberSuggesterInterface::class => get(InvoiceNumberSuggester::class),
    VariableProcessorFactoryInterface::class => get(VariableProcessorFactory::class),
    QrGeneratorInterface::class => function () {
        return new EndroidQrGenerator(new QrCodeFactory());
    },
    RelatedInvoiceProjectsResolverInterface::class => get(RelatedInvoiceProjectsResolver::class),
    InvoiceAttributesProcessorInterface::class => get(InvoiceAttributesProcessor::class),
];
