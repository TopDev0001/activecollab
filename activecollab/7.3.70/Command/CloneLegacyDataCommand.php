<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Command;

use Angie\Command\Command;
use AngieApplication;
use DB;
use DirectoryIterator;
use Exception;
use mysqli;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package ActiveCollab\Command
 */
class CloneLegacyDataCommand extends Command
{
    protected function configure()
    {
        parent::configure();

        $this->setDescription('Clone legacy data to the database of the current installation')
            ->addArgument('path_to_old_activecollab_config', InputArgument::REQUIRED, "Path ActiveCollab's config.php file");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            if (!defined('APPLICATION_UNIQUE_KEY') || !APPLICATION_UNIQUE_KEY) {
                throw new Exception('APPLICATION_UNIQUE_KEY not defined or empty');
            }

            $path_to_old_activecollab_config = $this->getPathToOldActiveCollabConfig($input);

            $appication_unique_key = $this->getApplicationUniqueKey($path_to_old_activecollab_config, $output);
            $output->writeln("<info>OK:</info> Application key is '$appication_unique_key'");

            $legacy_database = '';
            $legacy_table_prefix = '';
            $mysqli = $this->getDatabaseConnection($path_to_old_activecollab_config, $legacy_database, $legacy_table_prefix, $output);

            // Check if required first party modules are installed
            $this->checkModules($mysqli, $legacy_table_prefix);

            // Backup current tables
            $backup_table_prefix = $this->backUpCurrentTables($output);

            // Clone legacy tables
            $this->cloneLegacyTables($mysqli, $legacy_database, $legacy_table_prefix, $output);

            // Check if we can migrate
            if (in_array('`' . TABLE_PREFIX . 'executed_model_migrations`', $this->cloned_tables)) {
                $output->writeln('<info>OK:</info> Migration is possible');
            } else {
                throw new Exception('Migration not possible. Please upgrade legacy system to ActiveCollab 4.2.17 or newer and try again');
            }

            // Clone files and avatars
            $this->cloneFiles($path_to_old_activecollab_config, $output);

            // Run migration script
            $this->runMigrations($output);

            // Apply license key
            $this->applyApplicationKey($appication_unique_key, $output);

            $output->writeln('<info>OK:</info> Legacy data migrated');
            $output->writeln('<info>OK:</info> Run <info>php activecollab-cli.php rebuild_activity_logs</info> to populate activity logs');
            $output->writeln('<info>OK:</info> Run <info>php activecollab-cli.php rebuild_search_index</info> to populate search index');
            $output->writeln("<info>OK:</info> This instance's tables are backed up with '$backup_table_prefix' prefix");

            return $this->success('Done', $input, $output);
        } catch (Exception $e) {
            $this->revert($output);

            return $this->abortDueToException($e, $input, $output);
        }
    }

    /**
     * List of required first party modules.
     *
     * @var array
     */
    private $required_modules = ['discussions', 'documents', 'files', 'footprints', 'invoicing', 'notebooks', 'password_policy', 'project_exporter', 'status', 'tasks', 'tracking'];

    /**
     * Check installed modules (make sure that required first party modules are installed).
     *
     * @param  string    $legacy_table_prefix
     * @throws Exception
     */
    private function checkModules(mysqli &$mysqli, $legacy_table_prefix)
    {
        $modules = [];

        if ($result = $mysqli->query("SELECT name FROM {$legacy_table_prefix}modules", MYSQLI_USE_RESULT)) {
            while ($row = $result->fetch_assoc()) {
                $modules[] = array_shift($row);
            }

            $result->close();
        }

        $required_modules_missing = [];

        foreach ($this->required_modules as $required_module) {
            if (!in_array($required_module, $modules)) {
                $required_modules_missing[] = $required_module;
            }
        }

        if (!empty($required_modules_missing)) {
            throw new Exception(
                sprintf(
                    'Required modules missing (%s). Please install them on Administration > Modules page of your ActiveCollab 4 before continuing.',
                    implode(', ', $required_modules_missing)
                )
            );
        }
    }

    private function revert(OutputInterface $output)
    {
        $output->writeln('<info>OK:</info> Reverting');

        $this->dropClonedTables($output);
        $this->restoreTablesFromBackUp($output);
        $this->deleteCopiedFiles($output);
    }

    /**
     * @return string
     * @throws Exception
     */
    private function getPathToOldActiveCollabConfig(InputInterface $input)
    {
        $path_to_old_activecollab = $input->getArgument('path_to_old_activecollab_config');

        if ($path_to_old_activecollab && basename($path_to_old_activecollab) == 'config.php' && is_file($path_to_old_activecollab)) {
            return $path_to_old_activecollab;
        } else {
            throw new Exception("ActiveCollab configuration file not found at '$path_to_old_activecollab'");
        }
    }

    /**
     * Read APPLICATION_UNIQUE_KEY or LICENSE_KEY value from files.
     *
     * @param  string    $config_file_path
     * @return string
     * @throws Exception
     */
    private function getApplicationUniqueKey($config_file_path, OutputInterface $output)
    {
        foreach (file($config_file_path) as $line) {
            $line = trim($line);

            if (str_starts_with($line, 'const APPLICATION_UNIQUE_KEY')) {
                $output->writeln('<info>OK:</info> Application key found in config file');

                return $this->getValueFromConst($line);
            } else {
                if (str_starts_with($line, 'define') && strpos($line, 'APPLICATION_UNIQUE_KEY') !== false) {
                    $output->writeln('<info>OK:</info> Application key found in config file');

                    return $this->getValueFromDefine($line);
                }
            }
        }

        $license_file_path = dirname($config_file_path) . '/license.php';

        if (is_file($license_file_path)) {
            foreach (file($license_file_path) as $line) {
                $line = trim($line);

                if (str_starts_with($line, 'const LICENSE_KEY')) {
                    $output->writeln('<info>OK:</info> Application key found in license file');

                    return $this->getValueFromConst($line);
                } else {
                    if (str_starts_with($line, 'define') && strpos($line, 'LICENSE_KEY') !== false) {
                        $output->writeln('<info>OK:</info> Application key found in license file');

                        return $this->getValueFromDefine($line);
                    }
                }
            }
        }

        throw new Exception('Failed to find application file in config and license files');
    }

    /**
     * @param  string    $application_unique_key
     * @throws Exception
     */
    private function applyApplicationKey($application_unique_key, OutputInterface $output)
    {
        $config_file_path = CONFIG_PATH . '/config.php';

        if (is_file($config_file_path)) {
            $config_file_content = file_get_contents($config_file_path);
            $config_file_content = str_replace(APPLICATION_UNIQUE_KEY, $application_unique_key, $config_file_content);

            if (file_put_contents($config_file_path, $config_file_content)) {
                $output->writeln("<info>OK:</info> Application key updated from '" . APPLICATION_UNIQUE_KEY . "' to '$application_unique_key'");
            } else {
                throw new Exception('Failed to write application key to config file');
            }
        } else {
            throw new Exception("Config file not found at '$config_file_path'");
        }
    }

    /**
     * Get connection parameters from configuration file and connect to the database.
     *
     * @param  string    $config_file_path
     * @param  string    $database
     * @param  string    $table_prefix
     * @return mysqli
     * @throws Exception
     */
    private function getDatabaseConnection($config_file_path, &$database, &$table_prefix, OutputInterface $output)
    {
        [
            $hostname,
            $port,
            $username,
            $password,
            $database,
            $table_prefix
        ] = $this->getDatabaseConnectionParametersFromConfig($config_file_path);

        if ($hostname && $username && $database) {
            $link = new MySQLi($hostname, $username, $password, $database, $port);

            if ($link->connect_errno) {
                throw new Exception("Failed to connect to MySQL $username@$hostname/$database (using password " . ($password ? 'Yes' : 'No') . ')');
            }

            $link->query('SET NAMES utf8');

            $output->writeln("<info>OK:</info> Connected to $username@$hostname/$database (using password " . ($password ? 'Yes' : 'No') . ')');

            return $link;
        } else {
            throw new Exception('Failed to find database connection parameters in configuration file');
        }
    }

    /**
     * Get configuration parameters from configurtion file.
     *
     * @param  string $config_file_path
     * @return array
     */
    private function getDatabaseConnectionParametersFromConfig($config_file_path)
    {
        $result = [
            'hostname' => '',
            'port' => 3306,
            'username' => '',
            'password' => '',
            'database' => '',
            'table_prefix' => '',
        ];

        $config_map = [
            'DB_HOST' => 'hostname',
            'DB_USER' => 'username',
            'DB_PASS' => 'password',
            'DB_NAME' => 'database',
            'TABLE_PREFIX' => 'table_prefix',
        ];

        foreach (file($config_file_path) as $line) {
            $line = trim($line);

            foreach ($config_map as $config_option => $result_key) {
                if (str_starts_with($line, "const {$config_option}")) {
                    $result[$result_key] = $this->getValueFromConst($line);
                } elseif (str_starts_with($line, 'define') && strpos($line, $config_option) !== false) {
                    $result[$result_key] = $this->getValueFromDefine($line);
                }
            }
        }

        if (strpos($result['hostname'], ':') !== false) {
            $hostname_bits = explode(':', $result['hostname']);

            $result['hostname'] = $hostname_bits[0];

            if (!empty($hostname_bits[1]) && ctype_digit($hostname_bits[1])) {
                $result['port'] = (int) $hostname_bits[1];
            }
        }

        return array_values($result);
    }

    /**
     * Return single option from const DB_XYZ defition line.
     *
     * @param  string $line
     * @return string
     */
    private function getValueFromConst($line)
    {
        $eq_pos = strpos($line, '=');
        $semicolon_pos = strrpos($line, ';');

        $value = substr($line, $eq_pos + 1, $semicolon_pos - $eq_pos - 1);

        $value = trim($value); // whitespace

        if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
            $value = trim(trim($value, "'")); // single quote
        } else {
            if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                $value = trim(trim($value, '"')); // double quote
            }
        }

        return $value;
    }

    /**
     * Return single option from const DB_XYZ defition line.
     *
     * @param  string $line
     * @return string
     */
    private function getValueFromDefine($line)
    {
        $comma_pos = strpos($line, ',');
        $bracket_pos = strrpos($line, ')');

        $value = substr($line, $comma_pos + 1, $bracket_pos - $comma_pos - 1);

        $value = trim($value); // whitespace

        if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
            $value = trim(trim($value, "'")); // single quote
        } else {
            if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                $value = trim(trim($value, '"')); // double quote
            }
        }

        return $value;
    }

    /**
     * @var array
     */
    private $backed_up_tables = [];

    /**
     * @return string
     * @throws Exception
     */
    private function backUpCurrentTables(OutputInterface $output)
    {
        $output->writeln('<info>OK:</info> Backing up current tables');

        do {
            $backup_table_prefix = substr(sha1(rand()), 0, 5) . '_';
        } while ($backup_table_prefix == TABLE_PREFIX);

        $table_prefix_len = strlen(TABLE_PREFIX);

        foreach (DB::listTables(TABLE_PREFIX) as $old_table) {
            try {
                $new_table = $backup_table_prefix . substr($old_table, $table_prefix_len);

                DB::execute("RENAME TABLE `$old_table` TO `$new_table`");

                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln("<info>OK:</info> Backed up `$old_table` (to `$new_table`)");
                }

                $this->backed_up_tables[$old_table] = $new_table;
            } catch (Exception $e) {
                $this->restoreTablesFromBackUp($output);
                throw $e;
            }
        }

        return $backup_table_prefix;
    }

    private function restoreTablesFromBackUp(OutputInterface $output)
    {
        foreach ($this->backed_up_tables as $old_table => $new_table) {
            DB::execute("RENAME TABLE `$new_table` TO `$old_table`");

            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln("<info>OK:</info> Restored `$old_table` (from `$new_table`)");
            }
        }
    }

    /**
     * List of ActiveCollab 4 tables with all first party modules installed.
     *
     * @var array
     */
    private $legacy_tables = [
        'access_logs',
        'access_logs_archive',
        'activity_logs',
        'announcement_dismissals',
        'announcement_target_ids',
        'announcements',
        'api_client_subscriptions',
        'api_token_logs',
        'assignments',
        'attachments',
        'calendar_events',
        'calendar_users',
        'calendars',
        'categories',
        'code_snippets',
        'comments',
        'commit_project_objects',
        'companies',
        'config_option_values',
        'config_options',
        'currencies',
        'custom_fields',
        'data_filters',
        'data_source_mappings',
        'data_sources',
        'day_offs',
        'documents',
        'estimates',
        'executed_model_migrations',
        'expense_categories',
        'expenses',
        'favorites',
        'file_versions',
        'homescreen_tabs',
        'homescreen_widgets',
        'incoming_mail_attachments',
        'incoming_mail_filters',
        'incoming_mailboxes',
        'incoming_mails',
        'invoice_item_templates',
        'invoice_note_templates',
        'invoice_object_items',
        'invoice_objects',
        'invoice_related_records',
        'job_types',
        'labels',
        'language_phrase_translations',
        'language_phrases',
        'languages',
        'mailing_activity_logs',
        'modification_log_values',
        'modification_logs',
        'modules',
        'notebook_page_versions',
        'notebook_pages',
        'notification_recipients',
        'notifications',
        'object_contexts',
        'on_demand_invoices',
        'on_demand_statuses',
        'outgoing_messages',
        'payment_gateways',
        'payments',
        'project_hourly_rates',
        'project_object_templates',
        'project_objects',
        'project_requests',
        'project_roles',
        'project_templates',
        'project_users',
        'projects',
        'public_task_forms',
        'related_tasks',
        'reminder_users',
        'reminders',
        'routing_cache',
        'search_index_for_documents',
        'search_index_for_names',
        'search_index_for_project_objects',
        'search_index_for_projects',
        'search_index_for_source',
        'search_index_for_users',
        'security_logs',
        'shared_object_profiles',
        'source_commits',
        'source_paths',
        'source_repositories',
        'source_users',
        'status_updates',
        'subscriptions',
        'subtasks',
        'task_segments',
        'tax_rates',
        'text_document_versions',
        'time_records',
        'update_history',
        'user_addresses',
        'user_sessions',
        'users',
    ];

    /**
     * @var array
     */
    private $cloned_tables = [];

    /**
     * Clone legacy tables in the current database.
     *
     * @param $legacy_database
     * @param $legacy_table_prefix
     */
    private function cloneLegacyTables(mysqli &$mysqli, $legacy_database, $legacy_table_prefix, OutputInterface $output)
    {
        $output->writeln('<info>OK:</info> Cloning legacy tables');

        $legacy_table_names = [];

        if ($result = $mysqli->query("SHOW TABLES LIKE '{$legacy_table_prefix}%'", MYSQLI_USE_RESULT)) {
            while ($row = $result->fetch_assoc()) {
                $legacy_table_names[] = array_shift($row);
            }

            $result->close();
        }

        $table_prefix_len = strlen($legacy_table_prefix);

        foreach ($legacy_table_names as $legacy_table_name) {
            $prefixless_table_name = $table_prefix_len ? substr($legacy_table_name, $table_prefix_len) : $legacy_table_name;

            if (in_array($prefixless_table_name, $this->legacy_tables)) {
                $target_table = '`' . DB_NAME . '`.`' . TABLE_PREFIX . $prefixless_table_name . '`';
                $source_table = "`$legacy_database`.`$legacy_table_name`";

                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln("<info>OK:</info> Cloning data from $source_table to $target_table");
                }

                DB::execute("CREATE TABLE $target_table LIKE $source_table");
                DB::execute("INSERT INTO $target_table SELECT * FROM $source_table");

                $this->cloned_tables[] = explode('.', $target_table)[1];
            } else {
                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln("<comment>Skip:</comment> Table $prefixless_table_name is not a stock ActiveCollab table");
                }
            }
        }
    }

    private function dropClonedTables(OutputInterface $output)
    {
        if (count($this->cloned_tables) < 1) {
            return; // Don't clean unless we actually cloned tables
        }

        foreach (DB::listTables(TABLE_PREFIX) as $table) {
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln("<info>OK:</info> Droping table $table");
            }

            DB::dropTable($table);
        }
    }

    /**
     * @var array
     */
    private $copied_files = [];

    /**
     * Find /upload folder in the old ActiveCollab and copy them to the new /upload folder.
     *
     * @param string $path_to_old_activecollab_config
     */
    private function cloneFiles($path_to_old_activecollab_config, OutputInterface $output)
    {
        $path_to_old_activecollab_upload = dirname(dirname($path_to_old_activecollab_config)) . '/upload';

        if (is_dir($path_to_old_activecollab_upload) && is_dir(UPLOAD_PATH)) {
            foreach (new DirectoryIterator($path_to_old_activecollab_upload) as $file) {
                if ($file->isDot() || substr($file->getFilename(), 0, 1) == '.') {
                    continue;
                }

                if ($file->isFile()) {
                    $source_path = $file->getPathname();
                    $target_path = UPLOAD_PATH . '/' . $file->getFilename();

                    if (copy($source_path, $target_path)) {
                        $this->copied_files[] = $target_path;

                        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                            $output->writeln("<info>OK:</info> Copied file '$source_path' to '$target_path'");
                        }
                    } else {
                        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                            $output->writeln("<error>Error:</error> Failed to copy '$source_path' to '$target_path'");
                        }
                    }
                }
            }
        }

        $path_to_old_activecollab_avatars = dirname(dirname($path_to_old_activecollab_config)) . '/public/avatars';

        if (is_dir($path_to_old_activecollab_avatars) && is_dir(PUBLIC_PATH . '/avatars')) {
            foreach (new DirectoryIterator($path_to_old_activecollab_avatars) as $file) {
                if ($file->isDot() || substr($file->getFilename(), 0, 1) == '.') {
                    continue;
                }

                if ($file->isFile()) {
                    $bits = explode('.', $file->getFilename());

                    if (count($bits) == 3 && is_numeric($bits[0]) && $bits[2] == 'png') {
                        $source_path = $file->getPathname();
                        $target_path = PUBLIC_PATH . '/avatars/' . $file->getFilename();

                        if (copy($source_path, $target_path)) {
                            $this->copied_files[] = $target_path;

                            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                                $output->writeln("<info>OK:</info> Copied file '$source_path' to '$target_path'");
                            }
                        } else {
                            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                                $output->writeln("<error>Error:</error> Failed to copy '$source_path' to '$target_path'");
                            }
                        }
                    }
                }
            }
        }
    }

    private function deleteCopiedFiles(OutputInterface $output)
    {
        foreach ($this->copied_files as $copied_file) {
            if (is_file($copied_file)) {
                if (unlink($copied_file)) {
                    if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                        $output->writeln("<info>OK:</info> Deleted '$copied_file'");
                    }
                } else {
                    if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                        $output->writeln("<error>Error:</error> Failed to delete '$copied_file'");
                    }
                }
            }
        }
    }

    /**
     * Run migrations and clear cache.
     */
    private function runMigrations(OutputInterface $output)
    {
        $output->writeln('<info>OK:</info> Running migrations');

        AngieApplication::migration()->getScripts(APPLICATION_VERSION); // Preload scripts

        AngieApplication::migration()->up(null, function ($message) use (&$output) {
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln("<info>Migration Info:</info> $message");
            }
        });

        AngieApplication::cache()->clear();
    }
}
