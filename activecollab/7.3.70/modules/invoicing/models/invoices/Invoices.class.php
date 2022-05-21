<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Text\VariableProcessor\VariableProcessorInterface;
use ActiveCollab\Module\Invoicing\Utils\InvoiceAttributesProcessor\InvoiceAttributesProcessorInterface;
use ActiveCollab\Module\Invoicing\Utils\VariableProcessor\Factory\VariableProcessorFactoryInterface;

class Invoices extends BaseInvoices
{
    public const DEFAULT_TASK_DESCRIPTION_FORMAT = 'Task #:task_number: :task_summary (:project_name)';
    public const DEFAULT_PROJECT_DESCRIPTION_FORMAT = 'Project :name';
    public const DEFAULT_JOB_TYPE_DESCRIPTION_FORMAT = ':job_type';
    public const DEFAULT_INDIVIDUAL_DESCRIPTION_FORMAT = ':parent_task_or_project:record_summary (:record_date)';
    public const SUMMARY_PUT_IN_PARENTHESES = 'put_in_parentheses';
    public const SUMMARY_PREFIX_WITH_DASH = 'prefix_with_dash';
    public const SUMMARY_SUFIX_WITH_DASH = 'sufix_with_dash';
    public const SUMMARY_PREFIX_WITH_COLON = 'prefix_with_colon';
    public const SUMMARY_SUFIX_WITH_COLON = 'sufix_with_colon';

    /**
     * Return new collection.
     *
     * @param  User|null       $user
     * @return ModelCollection
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        $collection = parent::prepareCollection($collection_name, $user);

        if ($collection_name == 'active_invoices') {
            $collection->setConditions('closed_on IS NULL AND is_trashed = ?', false);
            $collection->setOrderBy('created_on DESC');
        } elseif ($collection_name && str_starts_with($collection_name, 'archived_invoices')) {
            $collection->setConditions('closed_on IS NOT NULL AND is_trashed = ?', false);
            $collection->setOrderBy('issued_on DESC');

            $bits = explode('_', $collection_name);
            $collection->setPagination(array_pop($bits), 30);
        } elseif ($collection_name && str_starts_with($collection_name, 'company_invoices')) {
            $bits = explode('_', $collection_name);

            $page = array_pop($bits);
            array_pop($bits); // _page_

            $company = DataObjectPool::get('Company', array_pop($bits));

            if ($company instanceof Company && !$company->getIsOwner()) {
                $collection->setConditions('company_id = ? AND is_trashed = ?', $company->getId(), false);
                $collection->setPagination($page, 30);
            } else {
                throw new ImpossibleCollectionError('Company not found or owner company found');
            }
        } else {
            throw new InvalidParamError('collection_name', $collection_name);
        }

        return $collection;
    }

    public static function getPrivateNotes(): array
    {
        $result = [];

        $rows = DB::execute('SELECT `id`, `private_note` FROM `invoices`');

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $result[$row['id']] = (string) $row['private_note'];
            }
        }

        return $result;
    }

    // ---------------------------------------------------
    //  Utils
    // ---------------------------------------------------

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): Invoice
    {
        /** @var VariableProcessorInterface $variable_processor */
        $variable_processor = AngieApplication::getContainer()
            ->get(VariableProcessorFactoryInterface::class)
                ->createForInvoice();

        $attributes = AngieApplication::getContainer()
            ->get(InvoiceAttributesProcessorInterface::class)
                ->prepareAttributesForNewInvoice($attributes, $variable_processor);

        try {
            DB::beginWork('Begin: create new invoice @ ' . __CLASS__);

            $invoice = parent::create($attributes, false, false);

            if ($invoice instanceof Invoice && $save) {
                $invoice->dontUpdateSearchIndexOnNextSave();
                $invoice->save();

                $invoice->addItemsFromAttributes($attributes, $variable_processor);

                AngieApplication::search()->add($invoice);
            }

            DB::commit('Done: create new invoice @ ' . __CLASS__);

            if ($save) {
                DataObjectPool::introduce($invoice);
            }

            return DataObjectPool::announce($invoice, DataObjectPool::OBJECT_CREATED, $attributes);
        } catch (Exception $e) {
            DB::rollback('Rollback: create new invoice @ ' . __CLASS__);

            throw $e;
        }
    }

    public static function isSecondTaxEnabled(): bool
    {
        return (bool) ConfigOptions::getValue('invoice_second_tax_is_enabled');
    }

    public static function isSecondTaxCompound(): bool
    {
        return self::isSecondTaxEnabled() && ConfigOptions::getValue('invoice_second_tax_is_compound');
    }

    /**
     * @param Invoice $instance
     */
    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true,
    ): Invoice
    {
        if (array_key_exists('second_tax_is_enabled', $attributes)) {
            unset($attributes['second_tax_is_enabled']);
        }
        if (array_key_exists('second_tax_is_compound', $attributes)) {
            unset($attributes['second_tax_is_compound']);
        }

        // Ensure that discount has max two digits
        $attributes['discount_rate'] = empty($attributes['discount_rate'])
            ? 0
            : floor($attributes['discount_rate'] * 100) / 100;

        try {
            DB::beginWork('Begin: update the invoice @ ' . __CLASS__);

            $instance->dontUpdateSearchIndexOnNextSave();

            parent::update($instance, $attributes, $save);
            $instance->updateItemsFromAttributes($attributes);

            AngieApplication::search()->update($instance);

            DB::commit('Done: update the invoice @ ' . __CLASS__);

            return $instance;
        } catch (Exception $e) {
            DB::rollback('Rollback: update the invoice @ ' . __CLASS__);

            throw $e;
        }
    }

    public static function canAdd(User $user): bool
    {
        return $user->isFinancialManager();
    }

    /**
     * Return list of financial managers.
     *
     * @param  User     $exclude_user
     * @return Member[]
     */
    public static function findFinancialManagers($exclude_user = null): array
    {
        $managers = [];

        $all_admins_and_managers = Users::findByType([Owner::class, Member::class]);

        if ($all_admins_and_managers) {
            $exclude_user_id = $exclude_user instanceof User ? $exclude_user->getId() : null;

            foreach ($all_admins_and_managers as $user) {
                if ($exclude_user_id && $user->getId() == $exclude_user_id) {
                    continue;
                }

                if ($user->isOwner() || $user->getSystemPermission(User::CAN_MANAGE_FINANCES)) {
                    $managers[] = $user;
                }
            }
        }

        return $managers;
    }

    /**
     * Return available company names and addresses that can be used for new invoices, estimates, and recurring profiles.
     */
    public static function getCompanyAddresses(): array
    {
        $result = [];
        $saved_company_names = [];

        $add_to_result = function ($id, $name, $currency_id, $address, ?DateTimeValue $timestamp, array &$result) {
            $timestamp = !is_null($timestamp) ? $timestamp : new DateTimeValue();
            $key = trim(strtolower_utf($name));

            if (empty($result[$key]) || $result[$key]['timestamp'] < $timestamp->getTimestamp()) {
                $result[$key] = [
                    'id' => $id,
                    'name' => $name,
                    'currency_id' => $currency_id,
                    'address' => $address,
                    'timestamp' => $timestamp->getTimestamp(),
                ];
            }
        };

        $saved_companies = DB::execute(
            "SELECT `id`, `name`, `currency_id`, `address`, `updated_on` AS 'timestamp' FROM `companies` WHERE `is_owner` = ?",
            false,
        );

        if (!empty($saved_companies)) {
            $saved_companies->setCasting(
                [
                    'timestamp' => DBResult::CAST_DATETIME,
                ],
            );

            foreach ($saved_companies as $saved_company) {
                $add_to_result(
                    $saved_company['id'],
                    $saved_company['name'],
                    $saved_company['currency_id'],
                    $saved_company['address'],
                    $saved_company['timestamp'],
                    $result,
                );
                $saved_company_names[] = $saved_company['name'];
            }
        }

        foreach (['invoices', 'estimates', 'recurring_profiles'] as $table) {
            $latest_company_addresses = DB::execute(
                "SELECT company_id, company_name, currency_id, company_address, created_on AS 'timestamp' FROM $table WHERE created_on = (SELECT MAX(created_on) FROM $table AS t WHERE t.company_name = $table.company_name)",
            );

            if (!empty($latest_company_addresses)) {
                $latest_company_addresses->setCasting(['timestamp' => DBResult::CAST_DATETIME]);

                foreach ($latest_company_addresses as $latest_company_address) {
                    if (!in_array($latest_company_address['company_name'], $saved_company_names)) {
                        $add_to_result(
                            0,
                            $latest_company_address['company_name'],
                            $latest_company_address['currency_id'],
                            $latest_company_address['company_address'],
                            $latest_company_address['timestamp'],
                            $result,
                        );
                    }
                }
            }
        }

        usort(
            $result,
            function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            },
        );

        return $result;
    }

    public static function getInvoicePdfName(Invoice $invoice): string
    {
        return self::getInvoiceName($invoice->getNumber()) . '.pdf';
    }

    public static function getInvoiceName(string $number, bool $short = false): string
    {
        return $short ? $number : lang('Invoice #:invoice_num', ['invoice_num' => $number]);
    }

    /**
     * Generate task line description.
     *
     * @param  array  $variables
     * @return string
     */
    public static function generateTaskDescription($variables)
    {
        return self::generateDescription(
            'description_format_grouped_by_task',
            self::DEFAULT_TASK_DESCRIPTION_FORMAT,
            $variables,
        );
    }

    /**
     * Generate description based on pattern and variables.
     *
     * @param  string $pattern_config_option
     * @param  string $default_pattern
     * @param  array  $variables
     * @return mixed
     */
    private static function generateDescription($pattern_config_option, $default_pattern, $variables)
    {
        $pattern = ConfigOptions::getValue($pattern_config_option);
        if (empty($pattern)) {
            $pattern = $default_pattern;
        }

        $replacements = [];

        foreach ($variables as $k => $v) {
            $replacements[":$k"] = $v;
        }

        return str_replace(array_keys($replacements), array_values($replacements), $pattern);
    }

    /**
     * Generate project line description.
     *
     * @param  array  $variables
     * @return string
     */
    public static function generateProjectDescription($variables)
    {
        return self::generateDescription(
            'description_format_grouped_by_project',
            self::DEFAULT_PROJECT_DESCRIPTION_FORMAT,
            $variables,
        );
    }

    /**
     * Generate description when tracked data is grouped by job type.
     *
     * @param  array  $variables
     * @return string
     */
    public static function generateJobTypeDescription($variables)
    {
        return self::generateDescription(
            'description_format_grouped_by_job_type',
            self::DEFAULT_JOB_TYPE_DESCRIPTION_FORMAT,
            $variables,
        );
    }

    /**
     * Generate individual item description.
     *
     * @param  array  $variables
     * @return string
     */
    public static function generateIndividualDescription($variables)
    {
        $summary = trim((string) array_var($variables, 'record_summary'));

        if ($summary) {
            $transformations = [
                ConfigOptions::getValue('first_record_summary_transformation'),
                ConfigOptions::getValue('second_record_summary_transformation'),
            ];

            foreach ($transformations as $transformation) {
                if ($transformation) {
                    switch ($transformation) {
                        case self::SUMMARY_PUT_IN_PARENTHESES:
                            $summary = "($summary)";

                            break;
                        case self::SUMMARY_PREFIX_WITH_DASH:
                            $summary = " - $summary";

                            break;
                        case self::SUMMARY_SUFIX_WITH_DASH:
                            $summary = "$summary - ";

                            break;
                        case self::SUMMARY_PREFIX_WITH_COLON:
                            $summary = ": $summary";

                            break;
                        case self::SUMMARY_SUFIX_WITH_COLON:
                            $summary = "$summary: ";

                            break;
                    }
                }
            }
        }

        $variables['record_summary'] = $summary;

        return self::generateDescription(
            'description_format_separate_items',
            self::DEFAULT_INDIVIDUAL_DESCRIPTION_FORMAT,
            $variables,
        );
    }

    // ---------------------------------------------------
    //  Finders
    // ---------------------------------------------------

    /**
     * Find invoice by hash.
     *
     * @param  string             $hash
     * @return Invoice|DataObject
     */
    public static function findByHash($hash)
    {
        return self::findOne(
            [
                'conditions' => ['hash = ?', $hash],
            ],
        );
    }

    /**
     * Return ID-s by company.
     *
     * @return int[]
     */
    public static function findIdsByCompany(Company $company)
    {
        return DB::executeFirstRow('SELECT `id` FROM `invoices` WHERE `company_id` = ?', $company->getId());
    }

    /**
     * Return number of invoices that use $currency.
     *
     * @param Currency $currency
     */
    public static function countByCurrency($currency): int
    {
        return self::count(
            [
                '`currency_id` = ?', $currency->getId(),
            ],
        );
    }

    /**
     * Method use to set update_on field to now on all invoices.
     */
    public static function bulkUpdateOn(): void
    {
        DB::execute('UPDATE `invoices` SET `updated_on` = UTC_TIMESTAMP()');
        self::clearCache();
    }
}
