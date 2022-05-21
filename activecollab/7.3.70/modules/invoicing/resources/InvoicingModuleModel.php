<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Invoicing\Resources;

use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;
use ActiveCollab\Module\Invoicing\InvoicingModule;
use ActiveCollabModuleModel;
use DB;
use DBActionOnByColumn;
use DBAdditionalPropertiesColumn;
use DBBoolColumn;
use DBColumn;
use DBCreatedOnByColumn;
use DBDateColumn;
use DBDateTimeColumn;
use DBDecimalColumn;
use DBEnumColumn;
use DBFkColumn;
use DBIdColumn;
use DBIndex;
use DBIntegerColumn;
use DBMoneyColumn;
use DBNameColumn;
use DBParentColumn;
use DBRelatedObjectColumn;
use DBStringColumn;
use DBTextColumn;
use DBTrashColumn;
use DBTypeColumn;
use DBUpdatedOnByColumn;
use DBUpdatedOnColumn;
use DBUserColumn;
use IInvoice;
use IInvoiceBasedOn;
use IInvoiceBasedOnImplementation;
use IInvoiceExport;
use IInvoiceImplementation;
use IPayments;
use IPaymentsImplementation;
use IProjectBasedOn;
use IResetInitialSettingsTimestamp;
use IRoundFieldValueToDecimalPrecisionImplementation;

require_once APPLICATION_PATH . '/resources/ActiveCollabModuleModel.class.php';

class InvoicingModuleModel extends ActiveCollabModuleModel
{
    public function __construct(InvoicingModule $parent)
    {
        parent::__construct($parent);

        $this->addModel(
            DB::createTable('invoices')->addColumns(
                [
                    new DBIdColumn(),
                    new DBRelatedObjectColumn('based_on', false),
                    DBStringColumn::create('number'),
                    DBStringColumn::create('purchase_order_number'),
                    DBFkColumn::create('company_id', 0, true),
                    DBStringColumn::create('company_name', DBStringColumn::MAX_LENGTH),
                    new DBTextColumn('company_address'),
                    DBIntegerColumn::create('currency_id', 4, 0)->setUnsigned(true),
                    DBIntegerColumn::create('language_id', 3, 0)->setUnsigned(true),
                    DBIntegerColumn::create('project_id', 5)->setUnsigned(true),
                    DBDecimalColumn::create('discount_rate', 5, 2, 0)->setUnsigned(true),
                    new DBMoneyColumn('subtotal', 0),
                    new DBMoneyColumn('discount', 0),
                    new DBMoneyColumn('tax', 0),
                    new DBMoneyColumn('total', 0),
                    new DBMoneyColumn('balance_due', 0),
                    new DBMoneyColumn('paid_amount', 0),
                    new DBDateColumn('last_payment_on'),
                    new DBTextColumn('note'),
                    DBStringColumn::create('private_note', DBStringColumn::MAX_LENGTH),
                    new DBEnumColumn(
                        'qr_note',
                        [
                            'none',
                            'payment_url',
                            'custom',
                        ],
                        'none'
                    ),
                    new DBTextColumn('qr_note_content'),
                    new DBBoolColumn('second_tax_is_enabled', false),
                    new DBBoolColumn('second_tax_is_compound', false),
                    new DBCreatedOnByColumn(true),
                    new DBUpdatedOnColumn(),
                    new DBDateColumn('due_on'),
                    new DBDateColumn('issued_on'),
                    new DBDateTimeColumn('sent_on'),
                    new DBTextColumn('recipients'),
                    new DBUserColumn('email_from'),
                    DBStringColumn::create('email_subject'),
                    new DBTextColumn('email_body'),
                    new DBDateTimeColumn('reminder_sent_on'),
                    new DBDateColumn('closed_on'),
                    new DBUserColumn('closed_by'),
                    new DBBoolColumn('is_canceled', false),
                    new DBBoolColumn('is_muted', false),
                    DBStringColumn::create('hash', 50),
                    new DBTrashColumn(),
                ]
            )->addIndices(
                [
                    DBIndex::create('number', DBIndex::UNIQUE),
                    DBIndex::create('project_id'),
                    DBIndex::create('company_name'),
                    DBIndex::create('total'),
                    DBIndex::create('issued_on'),
                    DBIndex::create('due_on'),
                    DBIndex::create('sent_on'),
                    DBIndex::create('closed_on'),
                ]
            )
        )
            ->implementHistory()
            ->implementAccessLog()
            ->implementActivityLog()
            ->implementSearch()
            ->implementReminders()
            ->implementTrash()
            ->addModelTrait(RoutingContextInterface::class, RoutingContextImplementation::class)
            ->addModelTrait(IInvoice::class, IInvoiceImplementation::class)
            ->addModelTrait(IPayments::class, IPaymentsImplementation::class)
            ->addModelTrait(IInvoiceExport::class)
            ->addModelTraitTweak('IInvoiceImplementation::canViewAccessLogs insteadof IAccessLogImplementation');

        $this->addModel(
            DB::createTable('recurring_profiles')->addColumns(
                [
                    new DBIdColumn(),
                    new DBNameColumn(),
                    DBFkColumn::create('stored_card_id'),
                    DBStringColumn::create('purchase_order_number'),
                    DBFkColumn::create('company_id'),
                    DBStringColumn::create('company_name', DBStringColumn::MAX_LENGTH),
                    new DBTextColumn('company_address'),
                    DBFkColumn::create('currency_id'),
                    DBFkColumn::create('language_id'),
                    DBFkColumn::create('project_id'),
                    DBIntegerColumn::create('discount_rate', DBColumn::TINY, 0)->setUnsigned(true),
                    new DBMoneyColumn('subtotal', 0),
                    new DBMoneyColumn('discount', 0),
                    new DBMoneyColumn('tax', 0),
                    new DBMoneyColumn('total', 0),
                    new DBMoneyColumn('balance_due', 0),
                    new DBMoneyColumn('paid_amount', 0),
                    new DBTextColumn('note'),
                    DBStringColumn::create('private_note', DBStringColumn::MAX_LENGTH),
                    new DBEnumColumn(
                        'qr_note',
                        [
                            'none',
                            'payment_url',
                            'custom',
                        ],
                        'none'
                    ),
                    new DBTextColumn('qr_note_content'),
                    new DBBoolColumn('second_tax_is_enabled', false),
                    new DBBoolColumn('second_tax_is_compound', false),
                    new DBCreatedOnByColumn(true),
                    new DBUpdatedOnColumn(),
                    new DBDateColumn('start_on'),
                    DBIntegerColumn::create('invoice_due_after', DBColumn::NORMAL, 15)->setUnsigned(true),
                    new DBEnumColumn(
                        'frequency',
                        [
                            'daily',
                            'weekly',
                            'biweekly',
                            'monthly',
                            'bimonthly',
                            'quarterly',
                            'halfyearly',
                            'yearly',
                            'biennial',
                        ],
                        'monthly'
                    ),
                    DBIntegerColumn::create('occurrences', DBColumn::NORMAL, 0)->setUnsigned(true),
                    DBIntegerColumn::create('triggered_number', DBColumn::NORMAL, 0)->setUnsigned(true),
                    new DBDateColumn('last_trigger_on'),
                    new DBBoolColumn('auto_issue', false),
                    new DBTextColumn('recipients'),
                    DBIntegerColumn::create('email_from_id', DBColumn::NORMAL, 0)->setUnsigned(true),
                    DBStringColumn::create('email_subject'),
                    new DBTextColumn('email_body'),
                    new DBBoolColumn('is_enabled'),
                ]
            )->addIndices(
                [
                    DBIndex::create('company_name'),
                    DBIndex::create('start_on'),
                ]
            )
        )
            ->implementHistory()
            ->addModelTrait(RoutingContextInterface::class, RoutingContextImplementation::class)
            ->addModelTrait(IInvoice::class, IInvoiceImplementation::class)
            ->addModelTrait(IInvoiceBasedOn::class, IInvoiceBasedOnImplementation::class);

        $this->addModel(
            DB::createTable('estimates')->addColumns(
                [
                    new DBIdColumn(),
                    new DBNameColumn(),
                    DBFkColumn::create('company_id', 0, true),
                    DBStringColumn::create('company_name', DBStringColumn::MAX_LENGTH),
                    new DBTextColumn('company_address'),
                    DBIntegerColumn::create('currency_id', 4, 0)->setUnsigned(true),
                    DBIntegerColumn::create('language_id', 3, 0)->setUnsigned(true),
                    DBIntegerColumn::create('discount_rate', DBColumn::TINY, 0)->setUnsigned(true),
                    new DBMoneyColumn('subtotal', 0),
                    new DBMoneyColumn('discount', 0),
                    new DBMoneyColumn('tax', 0),
                    new DBMoneyColumn('total', 0),
                    new DBMoneyColumn('balance_due', 0),
                    new DBMoneyColumn('paid_amount', 0),
                    new DBTextColumn('note'),
                    DBStringColumn::create('private_note', DBStringColumn::MAX_LENGTH),
                    new DBEnumColumn(
                        'status',
                        [
                            'draft',
                            'sent',
                            'won',
                            'lost',
                        ],
                        'draft'
                    ),
                    new DBBoolColumn('second_tax_is_enabled', false),
                    new DBBoolColumn('second_tax_is_compound', false),
                    new DBTextColumn('recipients'),
                    new DBUserColumn('email_from'),
                    DBStringColumn::create('email_subject'),
                    new DBTextColumn('email_body'),
                    new DBCreatedOnByColumn(true),
                    new DBUpdatedOnColumn(),
                    new DBActionOnByColumn('sent', true),
                    DBStringColumn::create('hash', 50),
                    new DBTrashColumn(),
                ]
            )->addIndices(
                [
                    DBIndex::create('company_name'),
                    DBIndex::create('status'),
                    DBIndex::create('updated_on'),
                    DBIndex::create('sent_on'),
                    DBIndex::create('hash', DBIndex::UNIQUE),
                ]
            ))
            ->implementHistory()
            ->implementAccessLog()
            ->implementActivityLog()
            ->implementSearch()
            ->implementTrash()
            ->addModelTrait(RoutingContextInterface::class, RoutingContextImplementation::class)
            ->addModelTrait(IInvoice::class, IInvoiceImplementation::class)
            ->addModelTrait(IInvoiceBasedOn::class, IInvoiceBasedOnImplementation::class)
            ->addModelTrait(IProjectBasedOn::class)
            ->addModelTraitTweak('IInvoiceImplementation::canViewAccessLogs insteadof IAccessLogImplementation');

        $this->addModel(
            DB::createTable('invoice_items')->addColumns(
                [
                    new DBIdColumn(),
                    new DBParentColumn(false),
                    DBFkColumn::create('first_tax_rate_id'),
                    DBFkColumn::create('second_tax_rate_id'),
                    DBDecimalColumn::create('discount_rate', 5, 2, 0)->setUnsigned(true),
                    new DBTextColumn('description'),
                    DBDecimalColumn::create('quantity', 13, 3, 1)->setUnsigned(true),
                    new DBMoneyColumn('unit_cost', 0),
                    new DBMoneyColumn('subtotal', 0),
                    DBDecimalColumn::create('discount', 13, 5, 0),
                    DBDecimalColumn::create('first_tax', 13, 5, 0),
                    DBDecimalColumn::create('second_tax', 13, 5, 0),
                    DBDecimalColumn::create('total', 13, 5, 0),
                    new DBBoolColumn('second_tax_is_enabled', false),
                    new DBBoolColumn('second_tax_is_compound', false),
                    DBIntegerColumn::create('position', 11)->setUnsigned(true),
                    DBIntegerColumn::create('project_id', 10, 0)->setUnsigned(true),
                ]
            )->addIndices(
                [
                    DBIndex::create('parent_id', DBIndex::KEY, ['parent_id', 'parent_type', 'position']),
                ]
            )
        )
            ->setOrderBy('position')
            ->addModelTrait(null, IRoundFieldValueToDecimalPrecisionImplementation::class);

        $this->addModel(
            DB::createTable('invoice_item_templates')->addColumns(
                [
                    new DBIdColumn(),
                    DBIntegerColumn::create('first_tax_rate_id', 3, '0')->setUnsigned(true),
                    DBIntegerColumn::create('second_tax_rate_id', 3, '0')->setUnsigned(true),
                    new DBTextColumn('description'),
                    DBDEcimalColumn::create('quantity', 13, 3, 1)->setUnsigned(true),
                    new DBMoneyColumn('unit_cost', 0),
                    DBIntegerColumn::create('position', 10, 0)->setUnsigned(true),
                ]
            )->addIndices(
                [
                    DBIndex::create('position'),
                ]
            )
        )
            ->setOrderBy('ISNULL(position) DESC, position')
            ->addModelTrait(RoutingContextInterface::class, RoutingContextImplementation::class);

        $this->addModel(
            DB::createTable('invoice_note_templates')->addColumns(
                [
                    new DBIdColumn(),
                    new DBNameColumn(150, true),
                    new DBTextColumn('content'),
                    new DBBoolColumn('is_default', false),
                    DBIntegerColumn::create('position', 10, 0)->setUnsigned(true),
                ]
            )->addIndices(
                [
                    DBIndex::create('position'),
                ]
            )
        )
            ->setOrderBy('ISNULL(position) DESC, position')
            ->addModelTrait(RoutingContextInterface::class, RoutingContextImplementation::class);

        $this->addModel(
            DB::createTable('tax_rates')->addColumns(
                [
                    new DBIdColumn(),
                    new DBNameColumn(50),
                    DBDecimalColumn::create('percentage', 6, 3, 0),
                    new DBBoolColumn('is_default', false),
                ]
            )->addIndices(
                [
                    DBIndex::create('name', DBIndex::UNIQUE, ['name', 'percentage']),
                ]
            )
        )
            ->setOrderBy('name')
            ->addModelTrait(null, IResetInitialSettingsTimestamp::class)
            ->addModelTrait(RoutingContextInterface::class, RoutingContextImplementation::class);

        $this->addModel(
            DB::createTable('remote_invoices')->addColumns(
                [
                    new DBIdColumn(),
                    new DBTypeColumn('RemoteInvoice'),
                    DBStringColumn::create('invoice_number', 45),
                    DBStringColumn::create('client', 75),
                    DBStringColumn::create('remote_code', 100),
                    new DBMoneyColumn('amount', 0),
                    new DBMoneyColumn('balance', 0),
                    new DBEnumColumn('based_on', ['fixed', 'time_and_expenses'], 'time_and_expenses'),
                    new DBUpdatedOnByColumn(),
                    new DBAdditionalPropertiesColumn(),
                ]
            )
        )
            ->setTypeFromField('type');

        $this->addModel(
            DB::createTable('remote_invoice_items')->addColumns(
                [
                    new DBIdColumn(),
                    new DBParentColumn(false),
                    DBStringColumn::create('line_id_string', 50),
                    new DBMoneyColumn('amount', 0),
                    DBIntegerColumn::create('project_id', 10, 0)->setUnsigned(true),
                    new DBUpdatedOnByColumn(),
                ]
            )
        );
    }

    /**
     * Load initial module data.
     */
    public function loadInitialData()
    {
        $this->addConfigOption('prefered_currency');

        $this->addConfigOption('on_invoice_based_on', 'keep_records_as_separate_invoice_items');
        $this->addConfigOption('description_format_grouped_by_task');
        $this->addConfigOption('description_format_grouped_by_project');
        $this->addConfigOption('description_format_grouped_by_job_type');
        $this->addConfigOption('description_format_separate_items');
        $this->addConfigOption('first_record_summary_transformation', 'prefix_with_colon');
        $this->addConfigOption('second_record_summary_transformation');
        $this->addConfigOption('completed_projects_in_uninvoiced_report', false);

        $this->addConfigOption('invoice_template');

        $this->addConfigOption('print_invoices_as');
        $this->addConfigOption('print_proforma_invoices_as');

        $this->addConfigOption('invoicing_default_due', 15);

        $this->addConfigOption('invoice_second_tax_is_enabled', false);
        $this->addConfigOption('invoice_second_tax_is_compound', false);

        $this->addConfigOption('invoice_notify_on_payment', 1);
        $this->addConfigOption('invoice_notify_on_cancel', 1);
        $this->addConfigOption('invoice_notify_financial_managers', 2);
        $this->addConfigOption('invoice_notify_financial_manager_ids', 0);

        // Accounting config options
        $this->addConfigOption('accounting_adapter');
        $this->addConfigOption('accounting_auth_data');
        $this->addConfigOption('accounting_invoices');
        $this->addConfigOption('accounting_clients');
        $this->addConfigOption('accounting_items');
        $this->addConfigOption('accounting_accounts');
        $this->addConfigOption('accounting_taxes');
        $this->addConfigOption('accounting_payments');

        // Invoice Overdue Reminders
        $this->addConfigOption('invoice_overdue_reminders_enabled', false);
        $this->addConfigOption('invoice_overdue_reminders_send_first', 7);
        $this->addConfigOption('invoice_overdue_reminders_send_every', 7);
        $this->addConfigOption('invoice_overdue_reminders_first_message', 'We would like to remind you that the following invoice has been overdue. Please send your payment promptly. Thank you.');
        $this->addConfigOption('invoice_overdue_reminders_escalation_enabled', false);
        $this->addConfigOption('invoice_overdue_reminders_escalation_messages', [['send_escalated' => 14, 'escalated_message' => null]]);
        $this->addConfigOption('invoice_overdue_reminders_dont_send_to');

        // display mode
        $this->addConfigOption('display_mode_invoices', 'grid');
        $this->addConfigOption('display_mode_estimates', 'grid');

        $this->addConfigOption('default_tracking_records_summarization', 'sum_all_by_task');

        // default invoice app (invoicing as default, quickbooks... as options)
        $this->addConfigOption('default_accounting_app');

        // Tax rates
        if (DB::tableExists('tax_rates') && DB::executeFirstCell("SELECT COUNT(id) AS 'row_count' FROM tax_rates WHERE name = 'VAT'") == 0) {
            $this->loadTableData('tax_rates', [['name' => 'VAT', 'percentage' => 17.5]]);
        }

        $this->addConfigOption('show_recently_completed_projects_in_invoice_form', true);

        parent::loadInitialData();
    }
}
