<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Invoicing;

use ActiveCollab\Module\System\Events\Maintenance\DailyMaintenanceEventInterface;
use AngieApplication;
use AngieModule;
use BaseInvoiceSearchDocument;
use DataObjectPool;
use DraftInvoiceCreatedViaRecurringProfileNotification;
use Estimate;
use EstimateNotification;
use Estimates;
use EstimateSearchDocument;
use EstimatesSearchBuilder;
use EstimateUpdatedNotification;
use IInvoice;
use IInvoiceBasedOn;
use IInvoiceBasedOnImplementation;
use IInvoiceBasedOnTrackedDataImplementation;
use IInvoiceBasedOnTrackingFilterResultImplementation;
use IInvoiceExport;
use IInvoiceImplementation;
use Invoice;
use InvoiceGeneratedViaRecurringProfileNotification;
use InvoiceItem;
use InvoiceItems;
use InvoiceItemTemplate;
use InvoiceItemTemplates;
use InvoiceNoteTemplate;
use InvoiceNoteTemplates;
use InvoiceNotification;
use InvoiceOverdueReminders;
use InvoicePaidNotification;
use InvoicePaymentsFilter;
use InvoicePDFGenerator;
use InvoiceReminderNotification;
use Invoices;
use InvoiceSearchDocument;
use InvoicesFilter;
use InvoicesSearchBuilder;
use InvoiceTemplate;
use IRoundFieldValueToDecimalPrecisionImplementation;
use QuickbooksIntegration;
use QuickbooksInvoice;
use QuickbooksInvoices;
use RecurringProfile;
use RecurringProfileNotification;
use RecurringProfiles;
use SendEstimateNotification;
use SendInvoiceNotification;
use TaxRate;
use TaxRates;
use XeroIntegration;
use XeroInvoice;
use XeroInvoices;

class InvoicingModule extends AngieModule
{
    const NAME = 'invoicing';
    const PATH = __DIR__;

    protected string $name = 'invoicing';
    protected string $version = '5.0';

    public function init()
    {
        parent::init();

        DataObjectPool::registerTypeLoader(
            Invoice::class,
            function (array $ids): ?iterable
            {
                return Invoices::findByIds($ids);
            }
        );

        DataObjectPool::registerTypeLoader(
            InvoiceItem::class,
            function (array $ids): ?iterable
            {
                return InvoiceItems::findByIds($ids);
            }
        );

        DataObjectPool::registerTypeLoader(
            Estimate::class,
            function (array $ids): ?iterable
            {
                return Estimates::findByIds($ids);
            }
        );

        DataObjectPool::registerTypeLoader(
            RecurringProfile::class,
            function (array $ids): ?iterable
            {
                return RecurringProfiles::findByIds($ids);
            }
        );

        DataObjectPool::registerTypeLoader(
            TaxRate::class,
            function (array $ids): ?iterable
            {
                return TaxRates::findByIds($ids);
            }
        );

        DataObjectPool::registerTypeLoader(
            InvoiceItemTemplate::class,
            function (array $ids): ?iterable
            {
                return InvoiceItemTemplates::findByIds($ids);
            }
        );

        DataObjectPool::registerTypeLoader(
            InvoiceNoteTemplate::class,
            function (array $ids): ?iterable
            {
                return InvoiceNoteTemplates::findByIds($ids);
            }
        );

        DataObjectPool::registerTypeLoader(
            QuickbooksInvoice::class,
            function (array $ids): ?iterable
            {
                return QuickbooksInvoices::findByIds($ids);
            }
        );

        DataObjectPool::registerTypeLoader(
            XeroInvoice::class,
            function (array $ids): ?iterable
            {
                return XeroInvoices::findByIds($ids);
            }
        );
    }

    public function defineClasses()
    {
        require_once __DIR__ . '/resources/autoload_model.php';

        AngieApplication::setForAutoload(
            [
                IInvoice::class => __DIR__ . '/models/IInvoice.class.php',
                IInvoiceImplementation::class => __DIR__ . '/models/IInvoiceImplementation.class.php',

                IInvoiceExport::class => __DIR__ . '/models/IInvoiceExport.class.php',

                InvoiceTemplate::class => __DIR__ . '/models/InvoiceTemplate.class.php',
                InvoicePDFGenerator::class => __DIR__ . '/models/InvoicePDFGenerator.class.php',

                IRoundFieldValueToDecimalPrecisionImplementation::class => __DIR__ . '/models/IRoundFieldValueToDecimalPrecisionImplementation.class.php',
                InvoiceOverdueReminders::class => __DIR__ . '/models/InvoiceOverdueReminders.class.php',

                // Invoice Based On
                IInvoiceBasedOn::class => __DIR__ . '/models/invoice_based_on/IInvoiceBasedOn.php',
                IInvoiceBasedOnImplementation::class => __DIR__ . '/models/invoice_based_on/IInvoiceBasedOnImplementation.php',
                IInvoiceBasedOnTrackedDataImplementation::class => __DIR__ . '/models/invoice_based_on/IInvoiceBasedOnTrackedDataImplementation.php',
                IInvoiceBasedOnTrackingFilterResultImplementation::class => __DIR__ . '/models/invoice_based_on/IInvoiceBasedOnTrackingFilterResultImplementation.php',

                InvoicesFilter::class => __DIR__ . '/models/reports/InvoicesFilter.php',
                InvoicePaymentsFilter::class => __DIR__ . '/models/reports/InvoicePaymentsFilter.php',

                // Search
                InvoicesSearchBuilder::class => __DIR__ . '/models/search_builders/InvoicesSearchBuilder.php',
                EstimatesSearchBuilder::class => __DIR__ . '/models/search_builders/EstimatesSearchBuilder.php',

                BaseInvoiceSearchDocument::class => __DIR__ . '/models/search_documents/BaseInvoiceSearchDocument.php',
                InvoiceSearchDocument::class => __DIR__ . '/models/search_documents/InvoiceSearchDocument.php',
                EstimateSearchDocument::class => __DIR__ . '/models/search_documents/EstimateSearchDocument.php',

                // Notifications
                InvoiceNotification::class => __DIR__ . '/notifications/InvoiceNotification.class.php',
                SendInvoiceNotification::class => __DIR__ . '/notifications/SendInvoiceNotification.class.php',
                InvoicePaidNotification::class => __DIR__ . '/notifications/InvoicePaidNotification.class.php',
                InvoiceReminderNotification::class => __DIR__ . '/notifications/InvoiceReminderNotification.class.php',

                EstimateNotification::class => __DIR__ . '/notifications/EstimateNotification.class.php',
                SendEstimateNotification::class => __DIR__ . '/notifications/SendEstimateNotification.class.php',
                EstimateUpdatedNotification::class => __DIR__ . '/notifications/EstimateUpdatedNotification.class.php',

                RecurringProfileNotification::class => __DIR__ . '/notifications/RecurringProfileNotification.class.php',
                InvoiceGeneratedViaRecurringProfileNotification::class => __DIR__ . '/notifications/InvoiceGeneratedViaRecurringProfileNotification.class.php',
                DraftInvoiceCreatedViaRecurringProfileNotification::class => __DIR__ . '/notifications/DraftInvoiceCreatedViaRecurringProfileNotification.class.php',

                // Quickbooks
                QuickbooksInvoice::class => __DIR__ . '/models/quickbooks_invoices/QuickbooksInvoice.class.php',
                QuickbooksInvoices::class => __DIR__ . '/models/quickbooks_invoices/QuickbooksInvoices.class.php',

                // Quickbooks Integration
                QuickbooksIntegration::class => __DIR__ . '/models/integrations/QuickbooksIntegration.php',

                // Xero Invoice
                XeroInvoice::class => __DIR__ . '/models/xero_invoices/XeroInvoice.class.php',
                XeroInvoices::class => __DIR__ . '/models/xero_invoices/XeroInvoices.class.php',

                // Xero Integration
                XeroIntegration::class => __DIR__ . '/models/integrations/XeroIntegration.php',
            ]
        );
    }

    public function defineHandlers()
    {
        $this->listen('on_daily_maintenance');
        $this->listen('on_morning_mail');

        $this->listen('on_rebuild_activity_logs');

        $this->listen('on_object_from_notification_context');
        $this->listen('on_notification_context_view_url');

        $this->listen('on_history_field_renderers');

        $this->listen('on_protected_config_options');
        $this->listen('on_initial_settings');
        $this->listen('on_resets_initial_settings_timestamp');

        $this->listen('on_visible_object_paths');

        $this->listen('on_search_rebuild_index');
        $this->listen('on_user_access_search_filter');

        $this->listen('on_trash_sections');

        $this->listen('on_available_integrations');
        $this->listen('on_extra_stats');
    }

    public function defineListeners(): array
    {
        return [
            DailyMaintenanceEventInterface::class => AngieApplication::recurringInvoicesDispatcher(),
        ];
    }

    public function install()
    {
        recursive_mkdir(WORK_PATH . '/invoices', 0777, WORK_PATH);

        parent::install();
    }
}
