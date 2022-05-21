<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Features\InvoicesFeatureInterface;
use Angie\Error;
use Angie\Globalization;

class RecurringProfile extends BaseRecurringProfile
{
    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_TWO_WEEKS = 'biweekly';
    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_TWO_MONTHS = 'bimonthly';
    public const FREQUENCY_THREE_MONTHS = 'quarterly';
    public const FREQUENCY_SIX_MONTHS = 'halfyearly';
    public const FREQUENCY_YEARLY = 'yearly';
    public const FREQUENCY_BIENNIAL = 'biennial';

    public function getHistoryFields(): array
    {
        return array_merge(
            parent::getHistoryFields(),
            [
                'purchase_order_number',
                'project_id',
                'start_on',
                'frequency',
                'occurrences',
                'is_enabled',
                'auto_issue',
                'stored_card_id',
            ],
        );
    }

    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'project_id' => $this->getProjectId(),
                'purchase_order_number' => $this->getPurchaseOrderNumber(),
                'start_on' => $this->getStartOn(),
                'last_trigger_on' => $this->getLastTriggerOn(),
                'frequency' => $this->getFrequency(),
                'occurrences' => $this->getOccurrences(),
                'triggered_number' => $this->getTriggeredNumber(),
                'stored_card_id' => $this->getStoredCardId(),
                'auto_issue' => $this->getAutoIssue(),
                'is_enabled' => $this->getIsEnabled(),
                'is_completed' => $this->isCompleted(),
                'private_note' => $this->getPrivateNote(),
                'invoice_due_after' => $this->getInvoiceDueAfter(),
                'qr_note' => $this->getQrNote(),
                'qr_note_content' => $this->getQrNoteContent(),
            ],
        );
    }

    /**
     * Describe single.
     */
    public function describeSingleForFeather(array &$result)
    {
        parent::describeSingleForFeather($result);

        $result['stored_card'] = $this->getStoredCard();
        $result['invoices'] = DB::executeFirstColumn(
            'SELECT id FROM invoices WHERE based_on_type = ? AND based_on_id = ? ORDER BY created_on',
            RecurringProfile::class,
            $this->getId(),
        );

        if (empty($result['invoices'])) {
            $result['invoices'] = [];
        }
    }

    /**
     * @return string
     */
    public function getVerboseFrequency(Language $language = null)
    {
        switch ($this->getFrequency()) {
            case self::FREQUENCY_DAILY:
                return lang('Every Day', null, true, $language);
            case self::FREQUENCY_WEEKLY:
                return lang('Every Week on :day', ['day' => Globalization::getDayName($this->getStartOn()->getWeekday())], true, $language);
            case self::FREQUENCY_TWO_WEEKS:
                return lang('Every Two Weeks on :day', ['day' => Globalization::getDayName($this->getStartOn()->getWeekday())], true, $language);
            case self::FREQUENCY_MONTHLY:
                return lang('Every Month on :day.', ['day' => $this->getStartOn()->getDay()], true, $language);
            case self::FREQUENCY_TWO_MONTHS:
                return lang('Every Two Months on :day.', ['day' => $this->getStartOn()->getDay()], true, $language);
            case self::FREQUENCY_THREE_MONTHS:
                return lang('Every Three Months on :day.', ['day' => $this->getStartOn()->getDay()], true, $language);
            case self::FREQUENCY_SIX_MONTHS:
                return lang('Every Six Months on :day.', ['day' => $this->getStartOn()->getDay()], true, $language);
            case self::FREQUENCY_YEARLY:
                return lang('Every Year on :month/:day.', ['month' => $this->getStartOn()->getMonth(), 'day' => $this->getStartOn()->getDay()], true, $language);
            case self::FREQUENCY_BIENNIAL:
                return lang('Every Two Years on :month/:day.', ['month' => $this->getStartOn()->getMonth(), 'day' => $this->getStartOn()->getDay()], true, $language);
        }

        return null;
    }

    /**
     * Return stored card that is associated with this profile.
     *
     * @return StoredCard
     */
    public function getStoredCard()
    {
        return DataObjectPool::get('StoredCard', $this->getStoredCardId());
    }

    public function getRoutingContext(): string
    {
        return 'recurring_profile';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'recurring_profile_id' => $this->getId(),
        ];
    }

    /**
     * Returns true if this recurring profile is completed.
     *
     * @return bool
     */
    public function isCompleted()
    {
        return $this->getOccurrences() > 0 && $this->getTriggeredNumber() >= $this->getOccurrences();
    }

    /**
     * Update next trigger on date.
     *
     * @param DateValue $last_trigger_on
     */
    private function registerInvoiceCreated($last_trigger_on = null)
    {
        $this->setTriggeredNumber($this->getTriggeredNumber() + 1);
        $this->setLastTriggerOn($last_trigger_on ? $last_trigger_on : DateValue::now());

        $this->save();
    }

    // ---------------------------------------------------
    //  Invoice based on
    // ---------------------------------------------------
    /**
     * Create new invoice instance based on parent object.
     *
     * @param  string     $number
     * @param  string     $client_address
     * @param  array|null $additional
     * @return Invoice
     */
    public function createInvoice($number, Company $client = null, $client_address = null, $additional = null, IUser $user = null)
    {
        if ($this->isCompleted()) {
            throw new Error('This profile is done sending invoices');
        }

        try {
            DB::beginWork('Begin: create invoice from recurring profile @ ' . __CLASS__);

            $issued_on = DateValue::now();
            $due_on = $this->getInvoiceDueAfter() ? $issued_on->advance($this->getInvoiceDueAfter() * 86400, false) : $issued_on;

            $invoice = Invoices::create(
                [
                    'number' => $number,
                    'based_on_type' => get_class($this),
                    'based_on_id' => $this->getId(),
                    'project_id' => $this->getProjectId(),
                    'company_id' => $this->getCompanyId(),
                    'company_name' => $this->getCompanyName(),
                    'company_address' => $this->getCompanyAddress(),
                    'currency_id' => $this->getCurrencyId(),
                    'language_id' => $this->getLanguageId(),
                    'note' => $this->replaceVariablesWithValues($this->getNote()),
                    'private_note' => $this->replaceVariablesWithValues($this->getPrivateNote()),
                    'purchase_order_number' => $this->getPurchaseOrderNumber(),
                    'created_by_id' => $this->getCreatedById(),
                    'created_by_name' => $this->getCreatedByName(),
                    'created_by_email' => $this->getCreatedByEmail(),
                    'issued_on' => $issued_on,
                    'due_on' => $due_on,
                    'discount_rate' => $this->getDiscountRate(),
                    'skip_variable_processing' => true,
                    'qr_note' => $this->getQrNote(),
                    'qr_note_content' => $this->getQrNoteContent(),
                ],
            );

            $items = $this->prepareItemsForInvoice();

            if (empty($items)) {
                throw new Error('Invoice must have at least one item');
            }

            $this->commitInvoiceItems($items, $invoice); // Save, add items, recalculate
            $this->registerInvoiceCreated(array_var($additional, 'trigger_date'));

            DB::commit('Done: create invoice from recurring profile @ ' . __CLASS__);

            return $invoice;
        } catch (Exception $e) {
            DB::rollback('Rollback: create invoice from recurring profile @ ' . __CLASS__);

            throw $e;
        }
    }

    /**
     * Return items preview based on given settings.
     *
     * @param  array $settings
     * @return mixed
     */
    public function previewInvoiceItems($settings = null, IUser $user = null)
    {
        return $this->prepareItemsForInvoice();
    }

    /**
     * Create items for invoice.
     *
     * @return mixed
     */
    protected function prepareItemsForInvoice()
    {
        $result = [];

        if ($items = $this->getItems()) {
            foreach ($items as $item) {
                $result[] = [
                    'description' => $this->replaceVariablesWithValues($item->getDescription()),
                    'unit_cost' => $item->getUnitCost(),
                    'quantity' => $item->getQuantity(),
                    'first_tax_rate_id' => $item->getFirstTaxRateId(),
                    'second_tax_rate_id' => $item->getSecondTaxRateId(),
                    'total' => $item->getTotal(),
                    'subtotal' => $item->getSubtotal(),
                ];
            }
        }

        return $result;
    }

    /**
     * @var array
     */
    private $variables_and_replacements = false;

    /**
     * Replace variables in string with approprirate values.
     *
     * @param  string $in_string
     * @return string
     */
    private function replaceVariablesWithValues($in_string)
    {
        if ($this->variables_and_replacements === false) {
            $now = DateValue::now()->advance(Globalization::getGmtOffset(), false);

            $language = DataObjectPool::get(Language::class, $this->getLanguageId());

            if (empty($language)) {
                $language = Languages::findDefault();
            }

            $month = $now->getMonth();
            $year = $now->getYear();

            if ($month > 1 && $month <= 3) {
                $quarter = 1;
            } else {
                if ($month > 3 && $month <= 6) {
                    $quarter = 2;
                } else {
                    if ($month > 6 && $month <= 9) {
                        $quarter = 3;
                    } else {
                        $quarter = 4;
                    }
                }
            }

            $this->variables_and_replacements = [
                '{day}' => $now->formatDateForUser($this->getCreatedBy(), 0, $language),
                '{month}' => Globalization::getMonthName($month, false, $language),
                '{next_month}' => Globalization::getMonthName(($month === 12 ? 1 : $month + 1), false, $language),
                '{previous_month}' => Globalization::getMonthName(($month === 1 ? 12 : $month - 1), false, $language),
                '{quarter}' => $quarter,
                '{next_quarter}' => $quarter === 4 ? 1 : $quarter + 1,
                '{previous_quarter}' => $quarter === 1 ? 4 : $quarter - 1,
                '{year}' => $year,
                '{next_year}' => $year + 1,
                '{previous_year}' => $year - 1,
            ];

            $this->variables_and_replacements = [array_keys($this->variables_and_replacements), array_values($this->variables_and_replacements)];
        }

        return $in_string
            ? str_replace($this->variables_and_replacements[0], $this->variables_and_replacements[1], $in_string)
            : $in_string;
    }

    /**
     * Check should send invoice from this recouring profile.
     *
     * @return bool
     */
    public function shouldSendOn(DateValue $date)
    {
        return AngieApplication::featureFactory()->makeFeature(InvoicesFeatureInterface::NAME)->isEnabled()
            && $this->getIsEnabled()
            && $this->getNextTriggerOn()->toMySQL() == ($date instanceof DateTimeValue ? $date->dateToMySQL() : $date->toMySQL());
    }

    /**
     * Return date for next trigger.
     *
     * @return DateValue|string
     */
    public function getNextTriggerOn()
    {
        $now = DateTimeValue::now();
        $today = $now->setTime($now->getHour(), $now->getMinute(), $now->getSecond())->getSystemDate()->beginningOfDay();
        $start_on = $this->getStartOn();
        $last_trigger_on = $this->getLastTriggerOn();

        if (!$last_trigger_on instanceof DateValue) {
            if ($start_on->isSameDay($today)) {
                return $start_on;
            }

            $next_trigger_on = $start_on;
            $last_trigger_on = $start_on;
        } else {
            if ($last_trigger_on->getTimestamp() < $start_on->getTimestamp()) {
                if ($start_on->isSameDay($today)) {
                    return $start_on;
                } else {
                    $next_trigger_on = $start_on;
                    $last_trigger_on = $start_on;
                }
            } else {
                $next_trigger_on = $last_trigger_on;
            }
        }

        if ($today->getTimestamp() < $next_trigger_on->getTimestamp()) {
            return $next_trigger_on;
        }

        do {
            $timestamp = $next_trigger_on->getTimestamp();

            switch ($this->getFrequency()) {
                case self::FREQUENCY_DAILY:
                    $next_trigger_on = DateValue::makeFromTimestamp(strtotime('+1 day', $timestamp));

                    break;
                case self::FREQUENCY_WEEKLY:
                    $next_trigger_on = DateValue::makeFromTimestamp(strtotime('+1 week', $timestamp));

                    break;
                case self::FREQUENCY_TWO_WEEKS:
                    $next_trigger_on = DateValue::makeFromTimestamp(strtotime('+2 week', $timestamp));

                    break;
                case self::FREQUENCY_MONTHLY:
                    $next_trigger_on = 'next month';

                    break;
                case self::FREQUENCY_TWO_MONTHS:
                    $next_trigger_on = '+2 month';

                    break;
                case self::FREQUENCY_THREE_MONTHS:
                    $next_trigger_on = '+3 month';

                    break;
                case self::FREQUENCY_SIX_MONTHS:
                    $next_trigger_on = '+6 month';

                    break;
                case self::FREQUENCY_YEARLY:
                    $next_trigger_on = '+1 year';

                    break;
                case self::FREQUENCY_BIENNIAL:
                    $next_trigger_on = '+2 year';

                    break;
                default:
                    throw new Exception('Invalid recurring profile frequency');
            }

            if (!$next_trigger_on instanceof DateValue) {
                $new_month = DateValue::makeFromTimestamp(strtotime($next_trigger_on, $timestamp));
                $last_date_in_new_month = DateValue::makeFromTimestamp(strtotime("last day of {$next_trigger_on}", $timestamp));

                $next_trigger_on = $last_date_in_new_month->getTimestamp() < $new_month->getTimestamp() ? $last_date_in_new_month : $new_month;

                if ($next_trigger_on->getDay() < $last_trigger_on->getDay() && $last_trigger_on->getDay() <= $last_date_in_new_month->getDay()) {
                    $diff_days = $last_trigger_on->getDay() - $next_trigger_on->getDay();
                    $next_trigger_on = DateValue::makeFromTimestamp(strtotime('+' . $diff_days . ' days', $next_trigger_on->getTimestamp()));
                }
            }
        } while ($next_trigger_on->getTimestamp() < $today->getTimestamp());

        return $next_trigger_on;
    }

    // ---------------------------------------------------
    //  System
    // ---------------------------------------------------

    /**
     * Delete specific object (and related objects if neccecery).
     *
     * @param bool $bulk
     */
    public function delete($bulk = false)
    {
        try {
            DB::beginWork('Begin: delete a recurring profile @ ' . __CLASS__);

            if ($stored_card = $this->getStoredCard()) {
                $stored_card->delete(true);
            }

            if ($invoice_ids = DB::executeFirstColumn('SELECT id FROM invoices WHERE based_on_type = ? AND based_on_id = ?', 'RecurringProfile', $this->getId())) {
                DB::execute('UPDATE invoices SET based_on_type = ?, based_on_id = ?, updated_on = UTC_TIMESTAMP() WHERE id IN (?)', null, 0, $invoice_ids);
                Invoices::clearCacheFor($invoice_ids);
            }

            parent::delete($bulk);

            DB::commit('Done: delete a recurring profile @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: delete a recurring profile @ ' . __CLASS__);

            throw $e;
        }
    }

    public function validate(ValidationErrors &$errors)
    {
        $this->validatePresenceOf('name') or $errors->fieldValueIsRequired('name');
        $this->validatePresenceOf('start_on') or $errors->fieldValueIsRequired('start_on');
        $this->validatePresenceOf('frequency') or $errors->fieldValueIsRequired('frequency');
        $this->validatePresenceOf('company_name') or $errors->fieldValueIsRequired('company_name');
        $this->validatePresenceOf('company_address') or $errors->fieldValueIsRequired('company_address');

        parent::validate($errors);
    }

    public function validateStartOn(DateValue $new_start_on): void
    {
        if ($this->getLastTriggerOn() !== null &&
            ($this->getStartOn()->getTimestamp() !== $new_start_on->getTimestamp())
        ) {
            throw new InvalidArgumentException(lang("Start on can't be changed after first invoice created from this recurring invoice."));
        }
    }
}
