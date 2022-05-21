<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate companies model.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateUpdateCompaniesModel extends AngieModelMigration
{
    /**
     * Update companies model.
     */
    public function up()
    {
        $companies = $this->useTableForAlter('companies');

        $companies->addColumn(
            new DBBoolColumn('is_archived'),
            'original_state'
        );
        $companies->addColumn(
            new DBBoolColumn('is_trashed'),
            'is_archived'
        );
        $companies->addColumn(
            new DBDateTimeColumn('trashed_on'),
            'is_trashed'
        );
        $companies->addColumn(
            DBFkColumn::create('trashed_by_id'),
            'trashed_on'
        );
        $companies->addIndex(DBIndex::create('trashed_by_id'));

        defined('STATE_TRASHED') or define('STATE_TRASHED', 1);
        defined('STATE_ARCHIVED') or define('STATE_ARCHIVED', 2);

        $this->execute('UPDATE ' . $companies->getName() . ' SET is_archived = ? WHERE state = ?', true, STATE_ARCHIVED);
        $this->execute('UPDATE ' . $companies->getName() . ' SET is_archived = ?, is_trashed = ?, trashed_on = NOW() WHERE state = ? AND original_state = ?', true, true, STATE_TRASHED, STATE_ARCHIVED);
        $this->execute('UPDATE ' . $companies->getName() . ' SET is_trashed = ?, trashed_on = NOW() WHERE state = ? AND is_trashed = ?', true, STATE_TRASHED, STATE_ARCHIVED, false);

        $companies->dropColumn('state');
        $companies->dropColumn('original_state');

        $companies->addColumn(new DBTextColumn('address'), 'name');
        $companies->addColumn(DBStringColumn::create('homepage_url'), 'address');
        $companies->addColumn(DBStringColumn::create('phone'), 'homepage_url');
        $companies->alterColumn('note', new DBTextColumn('note'));
        $companies->addColumn(DBIntegerColumn::create('currency_id', DBIntegerColumn::NORMAL, null)->setUnsigned(true), 'note');
        $companies->addColumn(DBStringColumn::create('tax_id'), 'currency_id');

        [$companies, $config_options, $config_option_values] = $this->useTables('companies', 'config_options', 'config_option_values');

        if ($rows = $this->execute("SELECT name, parent_id, value FROM $config_option_values WHERE parent_type = 'Company' AND name IN ('office_address', 'office_homepage', 'office_phone', 'office_fax')")) {
            $company_data = [];

            /*
             * Unserialize value
             *
             * @param string $v
             * @return string
             */
            $unserialize_value = function ($v) {
                return $v && str_starts_with($v, 's:') ? trim(unserialize($v)) : trim($v);
            };

            foreach ($rows as $row) {
                $company_id = $row['parent_id'];

                if (empty($company_data[$company_id])) {
                    $company_data[$company_id] = ['address' => null, 'homepage_url' => null, 'phone' => null, 'fax' => null];
                }

                switch ($row['name']) {
                    case 'office_address':
                        $company_data[$company_id]['address'] = $unserialize_value($row['value']);
                        break;
                    case 'office_homepage':
                        $company_data[$company_id]['homepage_url'] = $unserialize_value($row['value']);
                        break;
                    case 'office_phone':
                        $company_data[$company_id]['phone'] = $unserialize_value($row['value']);
                        break;
                    case 'office_fax':
                        $company_data[$company_id]['fax'] = $unserialize_value($row['value']);
                        break;
                }
            }

            foreach ($company_data as $company_id => $company) {
                $company_note = trim($this->executeFirstCell("SELECT note FROM $companies WHERE id = ?", $company_id));

                if ($company['fax']) {
                    if (empty($company_note)) {
                        $company_note = 'Fax: ' . $company['fax'];
                    } else {
                        $company_note .= "\n\nFax: " . $company['fax'];
                    }
                }

                $this->execute("UPDATE $companies SET address = ?, homepage_url = ?, phone = ?, note = ? WHERE id = ?", $company['address'], $company['homepage_url'], $company['phone'], $company_note, $company_id);
            }
        }

        $this->execute("DELETE FROM $config_options WHERE name IN ('office_address', 'office_homepage', 'office_phone', 'office_fax')");
        $this->execute("DELETE FROM $config_option_values WHERE name IN ('office_address', 'office_homepage', 'office_phone', 'office_fax')");

        $this->doneUsingTables();
    }
}
