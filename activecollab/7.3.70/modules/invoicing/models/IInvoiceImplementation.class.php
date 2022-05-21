<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Text\VariableProcessor\VariableProcessorInterface;
use Angie\Inflector;
use Angie\Search\SearchItem\SearchItemInterface;

trait IInvoiceImplementation
{
    use IRoundFieldValueToDecimalPrecisionImplementation;

    protected array $roundable_fields = [];
    /**
     * Tax grouped by type.
     *
     * @var bool
     */
    private $tax_grouped_by_type = false;

    public function registerIInvoiceImplementation()
    {
        $this->registerEventHandler('on_before_save', function ($is_new, $modifications) {
            if (method_exists($this, 'getHash') && !$this->getHash()) {
                $this->setHash(make_string(40));
            }

            if ($is_new) {
                if (!$this->isModifiedField('second_tax_is_enabled')) {
                    $this->setSecondTaxIsEnabled(Invoices::isSecondTaxEnabled());
                }

                if (!$this->isModifiedField('second_tax_is_compound')) {
                    $this->setSecondTaxIsCompound(Invoices::isSecondTaxCompound());
                }
            } else {
                if (isset($modifications['discount_rate']) && $items = $this->getItems()) {
                    foreach ($this->getItems() as $item) {
                        $item->setDiscountRate($modifications['discount_rate'][1]);
                        $item->save();
                    }
                }

                $this->recalculate();
            }
        });

        $this->registerEventHandler(
            'on_before_delete',
            function () {
                InvoiceItems::delete(InvoiceItems::parentToCondition($this));
                InvoiceItems::clearCache();
            }
        );

        $this->registerEventHandler('on_validate', function (ValidationErrors & $errors) {
            if (!$this->validateMinValueOf('discount_rate', 0) || !$this->validateMaxValueOf('discount_rate', 100)) {
                $errors->addError('Discount rate needs to be between 0 and 100', 'discount_rate');
            }
        });

        $this->registerEventHandler('on_json_serialize', function (array & $result) {
            $email_from = $this->getEmailFrom();

            if ($email_from instanceof User) {
                $email_from = $email_from->getId();
            } elseif ($email_from instanceof AnonymousUser) {
                $email_from = [$email_from->getEmail(), $email_from->getName()];
            } else {
                $email_from = null;
            }

            $result['company_id'] = $this->getCompanyId();
            $result['company_name'] = $this->getCompanyName();
            $result['company_address'] = $this->getCompanyAddress();
            $result['note'] = $this->getNote();
            $result['currency_id'] = $this->getCurrencyId();
            $result['language_id'] = $this->getLanguageId();
            $result['discount_rate'] = $this->getDiscountRate();
            $result['subtotal'] = $this->getSubtotal();
            $result['discount'] = $this->getDiscount();
            $result['tax'] = $this->getTax();
            $result['rounding_difference'] = $this->getRoundingDifference();
            $result['rounded_total'] = $this->getRoundedTotal();
            $result['total'] = $this->getTotal();
            $result['paid_amount'] = $this->getPaidAmount();
            $result['balance_due'] = $this->getBalanceDue();
            $result['recipients'] = $this->getRecipients();
            $result['email_from'] = $email_from;
            $result['email_subject'] = $this->getEmailSubject();
            $result['email_body'] = $this->getEmailBody();

            $result['second_tax_is_enabled'] = $this->getSecondTaxIsEnabled();
            $result['second_tax_is_compound'] = $this->getSecondTaxIsCompound();
        });

        $this->registerEventHandler('on_describe_single', function (array & $result) {
            $result['items'] = $this->getItems();

            if (empty($result['items'])) {
                $result['items'] = [];
            }
        });

        if ($this instanceof SearchItemInterface) {
            $this->addSearchFields(
                'company_name',
                'company_address',
                'recipients',
                'note',
                'private_note'
            );
        }

        if ($this instanceof IHistory) {
            $this->addHistoryFields(
                'company_id',
                'company_name',
                'company_address',
                'currency_id',
                'language_id',
                'discount_rate',
                'note',
                'private_note',
                'email_from_name',
                'email_from_email',
                'email_subject',
                'email_body',
                'recipients'
            );
        }
    }

    /**
     * Return item that belong to this object.
     *
     * @return DBResult|InvoiceItem[]
     */
    public function getItems()
    {
        return InvoiceItems::find(
            [
                'conditions' => [
                    'parent_type = ? AND parent_id = ?',
                    get_class($this),
                    $this->getId(),
                ],
            ]
        );
    }

    /**
     * Calculate total by walking through list of items.
     */
    public function recalculate()
    {
        $subtotal = 0;
        $discount = 0;
        $taxes = [];

        if ($items = $this->getItems()) {
            foreach ($items as $item) {
                $subtotal += $item->getSubTotal();
                $discount += $item->getDiscount();

                if ($item->getFirstTaxRateId()) {
                    if (!array_key_exists($item->getFirstTaxRateId(), $taxes)) {
                        $taxes[$item->getFirstTaxRateId()] = [
                            'amount' => 0,
                        ];
                    }
                    $taxes[$item->getFirstTaxRateId()]['amount'] += $item->getFirstTax();
                }

                if ($this->getSecondTaxIsEnabled() && $item->getSecondTaxRateId()) {
                    if (!array_key_exists($item->getSecondTaxRateId(), $taxes)) {
                        $taxes[$item->getSecondTaxRateId()] = [
                            'amount' => 0,
                        ];
                    }
                    $taxes[$item->getSecondTaxRateId()]['amount'] += $item->getSecondTax();
                }
            }
        }

        $decimal_spaces = $this->getCurrency()->getDecimalSpaces();

        $sum_tax = 0.000;
        if (count($taxes)) {
            foreach ($taxes as $key => $tax) {
                $sum_tax += round($tax['amount'], $decimal_spaces);
            }
        }

        $this->setSubtotal(round($subtotal, $decimal_spaces));
        $this->setDiscount(round($discount, $decimal_spaces));
        $this->setTax($sum_tax);
        $this->setTotal($this->getSubtotal() - $this->getDiscount() + $this->getTax());

        if ($this instanceof Invoice) {
            $this->setPaidAmount(round(Payments::sumByParent($this), $decimal_spaces));
            $this->setBalanceDue(round($this->getRoundedTotal() - $this->getPaidAmount(), $decimal_spaces));
        } else {
            $this->setBalanceDue($this->getTotal());
        }
    }

    /**
     * Return invoice currency.
     *
     * @return Currency
     */
    public function getCurrency()
    {
        $currency = DataObjectPool::get(Currency::class, $this->getCurrencyId());

        return $currency instanceof Currency ? $currency : Currencies::getDefault();
    }

    /**
     * Return total rounded to a precision defined by the invoice currency.
     *
     * @return float
     */
    public function getRoundedTotal()
    {
        if ($this->isLoaded()) {
            return AngieApplication::cache()->getByObject($this, 'rounded_total', function () {
                return Currencies::roundDecimal($this->getTotal(), $this->getCurrency());
            });
        } else {
            return Currencies::roundDecimal($this->getTotal(), $this->getCurrency());
        }
    }

    /**
     * Get who sent the email to the client.
     *
     * @return IUser|null
     */
    public function getEmailFrom()
    {
        return $this->getUserFromFieldSet('email_from');
    }

    /**
     * Get rounding difference.
     *
     * @return float
     */
    public function getRoundingDifference()
    {
        return $this->requireRounding() ? $this->getRoundedTotal() - $this->getTotal() : 0;
    }

    /**
     * Check if invoice total require rounding.
     *
     * @return bool
     */
    public function requireRounding()
    {
        return $this->getCurrency()->getDecimalRounding() > 0;
    }

    /**
     * Return document language.
     *
     * @return Language
     */
    public function getLanguage()
    {
        $language = DataObjectPool::get(Language::class, $this->getLanguageId());

        return $language instanceof Language ? $language : Languages::findDefault();
    }

    /**
     * Return invoice company.
     *
     * @return Company|DataObject
     */
    public function &getCompany()
    {
        return DataObjectPool::get(Company::class, $this->getCompanyId());
    }

    /**
     * Return recipient instances.
     *
     * @return IUser[]
     */
    public function getRecipientInstances()
    {
        $recipients = $this->getRecipients() ? Users::findByAddressList($this->getRecipients()) : null;

        return empty($recipients) ? [] : $recipients;
    }

    public function countItems(): int
    {
        return AngieApplication::cache()->getByObject(
            $this,
            'items_count',
            function () {
                return DB::executeFirstCell(
                    'SELECT COUNT(id) AS "row_count" FROM invoice_items WHERE ' . InvoiceItems::parentToCondition($this)
                );
            }
        );
    }

    /**
     * Add items from attributes.
     */
    public function addItemsFromAttributes(
        array $attributes,
        VariableProcessorInterface $variable_processor = null
    )
    {
        try {
            DB::beginWork('Begin: adding items from attributes @ ' . __CLASS__);

            if (isset($attributes['items']) && is_foreachable($attributes['items'])) {
                $counter = 1;

                foreach ($attributes['items'] as $item) {
                    if (($item['unit_cost'] && $item['quantity']) || $item['description']) {
                        if (empty($item['description'])) {
                            $item['description'] = lang('Item :item_number', ['item_number' => $counter]);
                        }

                        $this->addItem($item, $counter++, true, $variable_processor);
                    }
                }
            }

            $this->recalculate();
            $this->save();

            DB::commit('Done: adding items from attributes @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: adding items from attributes @ ' . __CLASS__);
            throw $e;
        }
    }

    public function addItem(
        array $attributes,
        int $position,
        bool $bulk = false,
        VariableProcessorInterface $variable_processor = null
    )
    {
        try {
            DB::beginWork('Begin: add invoice item @ ' . __CLASS__);

            $item = InvoiceItems::create(
                array_merge(
                    $attributes,
                    [
                        'parent_type' => get_class($this),
                        'parent_id' => $this->getId(),
                        'description' => $variable_processor
                            ? $variable_processor->process($attributes['description'])
                            : $attributes['description'],
                        'second_tax_is_enabled' => $this->getSecondTaxIsEnabled(),
                        'second_tax_is_compound' => $this->getSecondTaxIsCompound(),
                        'discount_rate' => $this->getDiscountRate(),
                        'position' => $position,
                    ]
                )
            );

            if ($item instanceof InvoiceItems && empty($bulk)) {
                $this->recalculate();
                $this->save();
            }

            DB::commit('Done: add invoice item @ ' . __CLASS__);

            return $item;
        } catch (Exception $e) {
            DB::rollback('Rollback: add invoice item @ ' . __CLASS__);
            throw $e;
        }
    }

    /**
     * Update items from attributes.
     */
    public function updateItemsFromAttributes(array $attributes)
    {
        try {
            DB::beginWork('Begin: update items from attributes @ ' . __CLASS__);

            if (isset($attributes['items']) && is_array($attributes['items'])) {
                $current_item_ids = DB::executeFirstColumn(
                    'SELECT id FROM invoice_items WHERE parent_type = ? AND parent_id = ?',
                    get_class($this),
                    $this->getId()
                );

                $counter = 1;

                foreach ($attributes['items'] as $item) {
                    $existing_item = isset($item['id']) && $item['id'] ? DataObjectPool::get('InvoiceItem', $item['id']) : null;

                    if ($existing_item instanceof InvoiceItem) {
                        if (empty($item['description'])) {
                            $item['description'] = lang('Item :item_number', ['item_number' => $counter]);
                        }

                        InvoiceItems::update($existing_item, $item, false);

                        $existing_item->setParent($this);
                        $existing_item->setPosition($counter++);
                        $existing_item->save();

                        if (($k = array_search($existing_item->getId(), $current_item_ids)) !== false) {
                            unset($current_item_ids[$k]);
                        }
                    } else {
                        if (($item['unit_cost'] && $item['quantity']) || $item['description']) {
                            if (empty($item['description'])) {
                                $item['description'] = lang('Item :item_number', ['item_number' => $counter]);
                            }

                            $this->addItem($item, $counter++, true);
                        }
                    }
                }

                if ($current_item_ids && is_foreachable($current_item_ids)) {
                    $items_to_delete = InvoiceItems::findByIds($current_item_ids);

                    /** @var InvoiceItem[] $items_to_delete */
                    if ($items_to_delete) {
                        foreach ($items_to_delete as $item_to_delete) {
                            $item_to_delete->delete();
                        }
                    }
                }

                $this->recalculate();
                $this->save();
            }

            DB::commit('Done: update items from attributes @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: update items from attributes @ ' . __CLASS__);
            throw $e;
        }
    }

    /**
     * Set who sends email.
     *
     * @param  User      $from
     * @return User|null
     */
    public function setEmailFrom($from)
    {
        return $this->setUserFromFieldSet($from, 'email_from', true, false);
    }

    /**
     * Set by user for given field set.
     *
     * @param  IUser                   $by_user
     * @param  string                  $field_set_prefix
     * @param  bool                    $optional
     * @param  bool                    $can_be_anonymous
     * @return User|AnonymousUser|null
     */
    abstract public function setUserFromFieldSet($by_user, $field_set_prefix, $optional = true, $can_be_anonymous = true);

    /**
     * Get tax grouped by tax type.
     *
     * @return array
     */
    public function getTaxGroupedByType()
    {
        if ($this->tax_grouped_by_type === false) {
            $this->tax_grouped_by_type = [];

            if ($items = $this->getItems()) {
                foreach ($items as $item) {
                    if ($item->getFirstTaxRateId()) {
                        if (!array_key_exists($item->getFirstTaxRateId(), $this->tax_grouped_by_type)) {
                            $this->tax_grouped_by_type[$item->getFirstTaxRateId()] = [
                                'id' => $item->getFirstTaxRateId(),
                                'name' => $item->getFirstTaxRate()->getName(),
                                'amount' => 0,
                                'percentage' => $item->getFirstTaxRate()->getPercentage(),
                            ];
                        }
                        $this->tax_grouped_by_type[$item->getFirstTaxRateId()]['amount'] += $item->getFirstTax();
                    }

                    if ($this->getSecondTaxIsEnabled() && $item->getSecondTaxRateId()) {
                        if (!array_key_exists($item->getSecondTaxRateId(), $this->tax_grouped_by_type)) {
                            $this->tax_grouped_by_type[$item->getSecondTaxRateId()] = [
                                'id' => $item->getSecondTaxRateId(),
                                'name' => $item->getSecondTaxRate()->getName(),
                                'amount' => 0,
                                'percentage' => $item->getSecondTaxRate()->getPercentage(),
                            ];
                        }
                        $this->tax_grouped_by_type[$item->getSecondTaxRateId()]['amount'] += $item->getSecondTax();
                    }
                }
            }
        }

        return $this->tax_grouped_by_type;
    }

    public function canView(User $user): bool
    {
        return $user->isFinancialManager();
    }

    public function canEdit(User $user): bool
    {
        return $user->isFinancialManager();
    }

    public function canDelete(User $user): bool
    {
        return $user->isFinancialManager();
    }

    /**
     * Return true if $user can view access logs.
     *
     * @return bool
     */
    public function canViewAccessLogs(User $user)
    {
        return $user->isFinancialManager();
    }

    /**
     * Check if this object has CJK (Chinese, Japanese, Korean Characters).
     */
    public function hasCJKCharacters(): bool
    {
        if (has_cjk_characters($this->getCompanyName())) {
            return true;
        }
        if (has_cjk_characters($this->getCompanyAddress())) {
            return true;
        }
        if (has_cjk_characters($this->getNote())) {
            return true;
        }

        $items = $this->getItems();

        if (!empty($items)) {
            foreach ($items as $item) {
                if (has_cjk_characters($item->getDescription())) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function getSearchEngine()
    {
        return AngieApplication::search();
    }

    /**
     * Export to PDF and return path of the exported file.
     */
    public function exportToFile(): string
    {
        $filename = $this->getPdfCacheDirPath() . '/' . $this->getPdfCacheFileName();

        if (!file_exists($filename)) {
            InvoicePDFGenerator::save($this, $filename);
        }

        return $filename;
    }

    protected function getPdfCacheDirPath(): string
    {
        $pdf_cache_dir = UPLOAD_PATH . '/pdf-cache';

        if (!is_dir($pdf_cache_dir)) {
            $old_umask = umask(0000);
            $dir_created = mkdir($pdf_cache_dir, 0777, true);
            umask($old_umask);

            if (empty($dir_created)) {
                throw new DirectoryCreateError($pdf_cache_dir);
            }
        }

        return $pdf_cache_dir;
    }

    protected function getPdfCacheFileName(): string
    {
        return implode(
            '-',
            [
                AngieApplication::getAccountId(),
                Inflector::underscore(get_class($this)),
                $this->getId(),
                date('Y-m-d-H-i-s', $this->getUpdatedOn()->getTimestamp()),
            ]
        ) . '.pdf';
    }

    // ---------------------------------------------------
    //  Requirements
    // ---------------------------------------------------

    abstract protected function registerEventHandler(string $event, callable $handler): void;
    abstract public function isLoaded();
    abstract public function validateMinValueOf($field, $min);
    abstract public function validateMaxValueOf($field, $max);
    abstract public function isModifiedField($field);
    abstract public function getId();
    abstract public function getCompanyId();
    abstract public function getCompanyName();
    abstract public function getCompanyAddress();
    abstract public function getUpdatedOn();
    abstract public function setBalanceDue($value);
    abstract public function getLanguageId();
    abstract public function getCurrencyId();
    abstract public function getNote();
    abstract public function getPrivateNote();
    abstract public function getPaidAmount();
    abstract public function getBalanceDue();
    abstract public function getSubtotal();
    abstract public function setSubtotal($value);
    abstract public function getDiscountRate();
    abstract public function getDiscount();
    abstract public function setDiscount($value);
    abstract public function getTax();
    abstract public function setTax($value);
    abstract public function getTotal();
    abstract public function setTotal($value);
    abstract public function getUserFromFieldSet($field_set_prefix);
    abstract public function getSecondTaxIsEnabled();
    abstract public function setSecondTaxIsEnabled($value);
    abstract public function getSecondTaxIsCompound();
    abstract public function setSecondTaxIsCompound($value);
    abstract public function getRecipients();
    abstract public function getEmailSubject();
    abstract public function getEmailBody();
}
