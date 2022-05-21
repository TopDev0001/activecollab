<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Invoicing\Utils\InvoiceAttributesProcessor;

use ActiveCollab\Foundation\Text\VariableProcessor\VariableProcessorInterface;
use ActiveCollab\Foundation\Wrappers\ConfigOptions\ConfigOptionsInterface;
use ActiveCollab\Foundation\Wrappers\DataObjectPool\DataObjectPoolInterface;
use ActiveCollab\Module\System\Utils\DefaultCurrencyResolver\DefaultCurrencyResolverInterface;
use Company;
use InvalidArgumentException;

class InvoiceAttributesProcessor implements InvoiceAttributesProcessorInterface
{
    private DataObjectPoolInterface $pool;
    private ConfigOptionsInterface $config_options;
    private DefaultCurrencyResolverInterface $default_currency_resolver;

    public function __construct(
        DataObjectPoolInterface $pool,
        ConfigOptionsInterface $config_options,
        DefaultCurrencyResolverInterface $default_currency_resolver
    )
    {
        $this->pool = $pool;
        $this->config_options = $config_options;
        $this->default_currency_resolver = $default_currency_resolver;
    }

    public function prepareAttributesForNewInvoice(
        array $attributes,
        VariableProcessorInterface $variable_processor
    ): array
    {
        $attributes = $this->prepareCompanyAttributes($attributes);
        $attributes = $this->prepareNoteAttributes($attributes, $variable_processor);

        if ($this->shouldProcessQrNoteContent($attributes)) {
            $attributes['qr_note_content'] = $variable_processor->process((string) $attributes['qr_note_content']);
        }

        $attributes = $this->prepareTaxAttributes($attributes);

        $attributes['discount_rate'] = empty($attributes['discount_rate'])
            ? 0
            : floor($attributes['discount_rate'] * 100) / 100; // be sure that discount has max two digits

        return $this->prepareDiscountAttributes($attributes);
    }

    public function prepareAttributesForNewEstimate(
        array $attributes,
        VariableProcessorInterface $variable_processor
    ): array
    {
        $attributes = $this->prepareCompanyAttributes($attributes);

        return $this->prepareNoteAttributes($attributes, $variable_processor);
    }

    public function prepareAttributesForNewRecurringProfile(array $attributes): array
    {
        $attributes = $this->prepareCompanyAttributes($attributes);
        $attributes = $this->prepareTaxAttributes($attributes);

        return $this->prepareDiscountAttributes($attributes);
    }

    private function prepareCompanyAttributes(array $attributes): array
    {
        $company = !empty($attributes['company_id'])
            ? $this->pool->get(Company::class, $attributes['company_id'])
            : null;

        if (!$company instanceof Company) {
            $attributes['company_id'] = 0;

            return $attributes;
        }

        if ($company->getIsOwner()) {
            throw new InvalidArgumentException("Can't issue internal invoices");
        }

        if (empty($attributes['company_name'])) {
            $attributes['company_name'] = $company->getName();
        }

        if (empty($attributes['company_address'])) {
            $attributes['company_address'] = $company->getAddress();
        }

        if (empty($attributes['currency_id'])) {
            $attributes['currency_id'] = $company->getCurrencyId()
                ? $company->getCurrencyId()
                : $this->default_currency_resolver->getDefaultCurrencyId();
        }

        return $attributes;
    }

    private function prepareNoteAttributes(
        array $attributes,
        VariableProcessorInterface $variable_processor
    ): array
    {
        foreach (['note', 'private_note'] as $field_value) {
            if (!empty($attributes[$field_value])) {
                $attributes[$field_value] = $variable_processor->process((string) $attributes[$field_value]);
            }
        }

        return $attributes;
    }

    private function prepareTaxAttributes(array $attributes): array
    {
        $attributes['second_tax_is_enabled'] = $this->isSecondTaxEnabled();
        $attributes['second_tax_is_compound'] = $this->isSecondTaxCompound();

        return $attributes;
    }

    private function prepareDiscountAttributes(array $attributes): array
    {
        $attributes['discount_rate'] = empty($attributes['discount_rate'])
            ? 0
            : floor($attributes['discount_rate'] * 100) / 100; // be sure that discount has max two digits

        return $attributes;
    }

    private function isSecondTaxEnabled(): bool
    {
        return (bool) $this->config_options->getValue('invoice_second_tax_is_enabled');
    }

    private function isSecondTaxCompound(): bool
    {
        return $this->isSecondTaxEnabled()
            && $this->config_options->getValue('invoice_second_tax_is_compound');
    }

    private function shouldProcessQrNoteContent(array $attributes): bool
    {
        return !empty($attributes['qr_note'])
            && $attributes['qr_note'] === 'custom'
            && !empty($attributes['qr_note_content']);
    }
}
