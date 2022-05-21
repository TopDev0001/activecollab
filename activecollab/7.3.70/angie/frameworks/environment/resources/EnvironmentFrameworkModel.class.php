<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Localization\LanguageInterface;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;
use ActiveCollab\Foundation\Webhooks\WebhookInterface;
use Angie\Reports\Report;
use Angie\Reports\Report\Implementation as ReportImplementation;

class EnvironmentFrameworkModel extends AngieFrameworkModel
{
    public function __construct(EnvironmentFramework $parent)
    {
        parent::__construct($parent);

        $this->addTableFromFile('config_options');
        $this->addTableFromFile('config_option_values');

        $this->addModelFromFile('uploaded_files')->setTypeFromField('type');
        $this->addModelFromFile('webhooks')
            ->setTypeFromField('type')
            ->addModelTrait(WebhookInterface::class);
        $this->addTableFromFile('executed_model_migrations');
        $this->addTableFromFile('job_batches');
        $this->addTableFromFile('jobs_queue');
        $this->addTableFromFile('jobs_queue_failed');
        $this->addTableFromFile('memories');

        $this
            ->addModelFromFile('system_notifications')
            ->setTypeFromField('type')
            ->setObjectIsAbstract(true)
            ->setOrderBy('created_on DESC, id DESC');

        $this->addModel(
            DB::createTable('access_logs')->addColumns(
                [
                    (new DBIdColumn())
                        ->setSize(DBColumn::BIG),
                    new DBParentColumn(),
                    new DBUserColumn('accessed_by'),
                    new DBDateTimeColumn('accessed_on'),
                    DBStringColumn::create('ip_address', 50),
                    new DBBoolColumn('is_download', false),
                ]
            )->addIndices(
                [
                    DBIndex::create('accessed_on'),
                ]
            )
        );

        $this->addTable(
            DB::createTable('routing_cache')->addColumns(
                [
                    new DBIdColumn(),
                    DBStringColumn::create('path_info', DBStringColumn::MAX_LENGTH),
                    DBStringColumn::create('name', DBStringColumn::MAX_LENGTH),
                    new DBTextColumn('content'),
                    new DBDateTimeColumn('last_accessed_on'),
                ]
            )->addIndices(
                [
                    DBIndex::create('path_info', DBIndex::UNIQUE),
                ]
            )
        );

        $this->addModel(
            DB::createTable('currencies')->addColumns(
                [
                    new DBIdColumn(),
                    new DBNameColumn(50, true),
                    DBStringColumn::create('code', 3),
                    DBStringColumn::create('symbol', 15),
                    DBStringColumn::create('symbol_native', 15),
                    DBIntegerColumn::create('decimal_spaces', 1, 2)->setUnsigned(true),
                    DBDecimalColumn::create('decimal_rounding', 4, 3, '0.000')->setUnsigned(true),
                    new DBBoolColumn('is_default', false),
                    new DBUpdatedOnColumn(),
                ]
            )->addIndices(
                [
                    DBIndex::create('code', DBIndex::UNIQUE),
                ]
            )
        )
            ->setOrderBy('name')
            ->addModelTrait(null, IResetInitialSettingsTimestamp::class)
            ->addModelTrait(RoutingContextInterface::class, RoutingContextImplementation::class);

        $this->addModel(
            DB::createTable('data_filters')->addColumns(
                [
                    new DBIdColumn(),
                    new DBTypeColumn('DataFilter', 150),
                    new DBNameColumn(),
                    new DBAdditionalPropertiesColumn(),
                    new DBCreatedOnByColumn(),
                    new DBUpdatedOnColumn(),
                    new DBBoolColumn('is_private', false),
                ]
            )->addIndices(
                [
                    DBIndex::create('name'),
                ]
            )
        )
            ->setTypeFromField('type')
            ->setOrderBy('name')
            ->addModelTrait(Report::class, ReportImplementation::class)
            ->addModelTrait(RoutingContextInterface::class, RoutingContextImplementation::class);

        $this->addModel(
            DB::createTable('integrations')->addColumns(
                [
                    new DBIdColumn(),
                    new DBTypeColumn(),
                    new DBAdditionalPropertiesColumn(),
                    new DBCreatedOnByColumn(),
                ]
            )->addIndices(
                [
                    DBIndex::create('type', DBIndex::UNIQUE),
                ]
            )
        )
            ->setTypeFromField('type')
            ->setObjectIsAbstract(true)
            ->addModelTrait(RoutingContextInterface::class, RoutingContextImplementation::class);

        $this->addModel(
            DB::createTable('day_offs')->addColumns(
                [
                    new DBIdColumn(),
                    new DBNameColumn(100),
                    new DBDateColumn('start_date'),
                    new DBDateColumn('end_date'),
                    new DBBoolColumn('repeat_yearly', false),
                    new DBCreatedOnColumn(),
                    new DBUpdatedOnColumn(),
                ]
            )->addIndices(
                [
                    DBIndex::create('day_off_name', DBIndex::UNIQUE, ['name', 'start_date', 'end_date']),
                ]
            )
        )
            ->setOrderBy('start_date')
            ->addModelTrait(RoutingContextInterface::class, RoutingContextImplementation::class);

        $this->addTable(DB::createTable('favorites')->addColumns([
            new DBIdColumn(),
            new DBParentColumn(false),
            DBIntegerColumn::create('user_id', 10)->setUnsigned(true),
        ])->addIndices([
            DBIndex::create('favorite_object', DBIndex::UNIQUE, ['parent_type', 'parent_id', 'user_id']),
            DBIndex::create('user_id'),
        ]));

        $this->addModel(
            DB::createTable('languages')->addColumns(
                [
                    new DBIdColumn(),
                    new DBNameColumn(50, true),
                    DBStringColumn::create('locale', 30, ''),
                    DBStringColumn::create('decimal_separator', 1, '.'),
                    DBStringColumn::create('thousands_separator', 1, ','),
                    new DBBoolColumn('is_rtl'),
                    new DBBoolColumn('is_community_translation'),
                    new DBBoolColumn('is_default'),
                    new DBUpdatedOnColumn(),
                ]
            )->addIndices(
                [
                    DBIndex::create('locale', DBIndex::UNIQUE),
                ]
            )
        )
            ->setOrderBy('name')
            ->addModelTrait(LanguageInterface::class)
            ->addModelTrait(null, IResetInitialSettingsTimestamp::class)
            ->addModelTrait(RoutingContextInterface::class, RoutingContextImplementation::class);

        if (is_file(__DIR__ . '/table.test_data_objects.php')) {
            $this->addModelFromFile('test_data_objects')->setOrderBy('name')->setTypeFromField('type');
        }
    }

    /**
     * Load initial framework data.
     */
    public function loadInitialData()
    {
        $this->addConfigOption('maintenance_message');

        $this->addConfigOption('help_improve_application', true);
        $this->addConfigOption('require_index_rebuild', false);

        $this->addConfigOption('identity_name', 'Application');
        $this->addConfigOption('identity_logo');

        // wallpaper
        $this->addConfigOption('homepage');
        $this->addConfigOption('wallpaper', 'wallpaper.jpg');
        $this->addConfigOption('theme', 'indigo');
        $this->addConfigOption('sidebar_collapsed', false);

        // Globalizatioj
        $this->addConfigOption('time_first_week_day', 0);
        $this->addConfigOption('time_timezone_autodetect', true);
        $this->addConfigOption('time_timezone', 'UTC');
        $this->addConfigOption('time_workdays', [1, 2, 3, 4, 5]);

        $this->addConfigOption('format_date', '%b %e. %Y');
        $this->addConfigOption('format_time', '%I:%M %p');

        $this->addConfigOption('initial_settings_timestamp', time());

        $this->addConfigOption('show_visual_editor_toolbar', false);

        // ---------------------------------------------------
        //  Languages, currencies
        // ---------------------------------------------------

        $this->loadTableData(
            'languages',
            [
                [
                    'name' => 'English',
                    'locale' => 'en_US.UTF-8',
                    'decimal_separator' => '.',
                    'thousands_separator' => ',',
                    'is_default' => true,
                    'updated_on' => date(DATETIME_MYSQL),
                ],
            ]
        );

        $currencies = [];

        foreach (json_decode(file_get_contents(ANGIE_PATH . '/frameworks/environment/resources/Common-Currency.json'), true) as $currency_code => $currency_details) {
            $currencies[] = DB::prepare(
                '(?, ?, ?, ?, ?, ?)',
                $currency_details['name'],
                $currency_code,
                $currency_details['symbol'],
                $currency_details['symbol_native'],
                $currency_details['decimal_digits'],
                $currency_details['rounding']
            );
        }

        DB::execute('INSERT INTO currencies (name, code, symbol, symbol_native, decimal_spaces, decimal_rounding) VALUES ' . implode(', ', $currencies));

        DB::execute('UPDATE currencies SET updated_on = UTC_TIMESTAMP()');
        DB::execute('UPDATE currencies SET is_default = ? WHERE code = ?', true, 'USD');

        parent::loadInitialData();
    }
}
