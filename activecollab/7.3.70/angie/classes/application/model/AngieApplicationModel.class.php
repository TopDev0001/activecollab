<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use Angie\Error;
use Angie\Inflector;
use Angie\ModuleFactory\ModuleFactory;
use Angie\Modules\AngieFramework;

class AngieApplicationModel
{
    /**
     * List of loaded models.
     *
     * @var AngieFrameworkModel[]|AngieModuleModel[]
     */
    private static array $models = [];

    private static array $loaded_frameworks = [];
    private static array $loaded_modules = [];

    /**
     * List of queries that need to be executed to reinitialize database for testing.
     */
    private static array $test_queries = [];

    public static function load(array $framework_names, array $module_names): void
    {
        if (self::isLoaded()) {
            return; // Model already loaded
        }

        self::loadFrameworksByNames($framework_names);
        self::loadModulesByNames($module_names);
    }

    private static function isLoaded(): bool
    {
        return !empty(self::$loaded_frameworks) && !empty(self::$loaded_modules);
    }

    private static function loadFrameworksByNames(array $framework_names): void
    {
        foreach ($framework_names as $framework_name) {
            $framework_class = Inflector::camelize($framework_name) . 'Framework';

            $file = ANGIE_PATH . "/frameworks/$framework_name/$framework_class.php";
            if (is_file($file)) {
                require_once $file;

                $framework = new $framework_class();
                if ($framework instanceof AngieFramework) {
                    self::$loaded_frameworks[] = $framework_name;

                    if ($framework->getModel() instanceof AngieFrameworkModel) {
                        self::$models[$framework->getName()] = $framework->getModel();
                    }
                }
            }
        }
    }

    private static function loadModulesByNames(array $module_names): void
    {
        $module_factory = new ModuleFactory(
            sprintf('%s/modules', APPLICATION_PATH)
        );

        foreach ($module_names as $module_name) {
            $module = $module_factory->createModule($module_name);

            self::$loaded_modules[] = $module_name;

            if ($module->getModel() instanceof AngieModuleModel) {
                self::$models[$module->getName()] = $module->getModel();
            }
        }
    }

    /**
     * Revert to model's original state.
     *
     * @param string $environment
     */
    public static function revert($environment = null)
    {
        self::clear();
        self::init($environment);
    }

    public static function clear(bool $drop_tables = false): void
    {
        if (!empty(self::$loaded_frameworks) && !empty(self::$loaded_modules)) {
            foreach (self::getTables() as $table) {
                if ($table->exists()) {
                    if ($drop_tables) {
                        $table->delete();
                    } else {
                        $table->truncate();
                    }
                }
            }
        } else {
            throw new Error('Model not loaded');
        }
    }

    /**
     * Return all tables.
     *
     * @return DBTable[]
     */
    public static function getTables()
    {
        $tables = [];

        foreach (self::$models as $model) {
            foreach ($model->getTables() as $k => $v) {
                $tables[$k] = $v;
            }
        }

        return $tables;
    }

    /**
     * Initialize all loaded frameworks and modules for given environment.
     *
     * @param string $environment
     */
    public static function init($environment = null)
    {
        if (self::canRunTestQueries($environment)) {
            self::runTestQueries();

            return;
        }

        $query_log_count = $environment === 'test' ? DB::getQueryCount() : 0;

        self::createModelTables();
        self::loadInitialModelData();
        self::markMigrationsAsExecuted();

        if ($environment === 'test') {
            self::$test_queries = [];

            foreach (array_slice(DB::getAllQueries(), $query_log_count) as $current_query) {
                if (!self::isInitialDataQuery($current_query)) {
                    continue;
                }

                self::$test_queries[] = $current_query;
            }
        }
    }

    private static function canRunTestQueries(?string $environment): bool
    {
        return $environment === 'test' && count(self::$test_queries);
    }

    private static function runTestQueries(): void
    {
        foreach (self::$test_queries as $query) {
            DB::execute($query);
        }
    }

    private static function createModelTables(): void
    {
        foreach (self::$models as &$model) {
            $model->createTables();
        }
    }

    private static function loadInitialModelData(): void
    {
        foreach (self::$models as &$model) {
            $model->loadInitialData();
        }
    }

    private static function markMigrationsAsExecuted(): void
    {
        $paths_to_scan = [
            ANGIE_PATH . '/migrations',
            APPLICATION_PATH . '/migrations',
            APPLICATION_PATH . '/modules/on_demand/migrations',
        ];

        if (!class_exists('AngieModelMigrationDiscoverer', false) && !class_exists('AngieModelMigration', false)) {
            require_once ANGIE_PATH . '/classes/application/migrations/AngieModelMigration.class.php';
            require_once ANGIE_PATH . '/classes/application/migrations/AngieModelMigrationDiscoverer.class.php';
        }

        $migrations = [];

        /** @var AngieModelMigration[] $scripts */
        foreach (AngieModelMigrationDiscoverer::discoverFromPaths($paths_to_scan) as $scripts) {
            foreach ($scripts as $script) {
                $changeset = $script->getChangeset();

                $changeset_timestamp = $script->getChangesetTimestamp($changeset);
                $changeset_name = substr($changeset, 11);

                $migrations[] = DB::prepare('(?, ?, ?, UTC_TIMESTAMP())', get_class($script), $changeset_timestamp, $changeset_name);
            }
        }

        DB::execute(
            'REPLACE INTO `executed_model_migrations` (`migration`, `changeset_timestamp`, `changeset_name`, `executed_on`) VALUES ' . implode(', ', $migrations)
        );
    }

    private static function isInitialDataQuery(string $query): bool
    {
        $blocked_queries = [
            'CREATE TABLE',
            'CREATE TRIGGER',
            'ALTER TABLE',
        ];

        foreach ($blocked_queries as $blocked_query) {
            if (str_starts_with($query, $blocked_query)) {
                return false;
            }
        }

        return true;
    }

    // ---------------------------------------------------
    //  Getters
    // ---------------------------------------------------

    /**
     * Returns true if this model is empty (there are no model instances loaded).
     *
     * @return bool
     */
    public static function isEmpty()
    {
        return empty(self::$models);
    }

    /**
     * Return specific table.
     *
     * @param  string            $table_name
     * @return DBTable
     * @throws InvalidParamError
     * @throws Exception
     */
    public static function &getTable($table_name)
    {
        foreach (self::$models as $model) {
            try {
                $table = $model->getTable($table_name);
                if ($table instanceof DBTable) {
                    return $table;
                }
            } catch (InvalidParamError $e) {
                // Skip name error
            } catch (Exception $e) {
                throw $e;
            }
        }

        throw new InvalidParamError('table_name', $table_name, "Table '$table_name' is not defined in any of the models");
    }

    /**
     * Return all model builders.
     *
     * @return AngieFrameworkModelBuilder[]
     */
    public static function getModelBuilders()
    {
        $model_builders = [];

        foreach (self::$models as $model) {
            foreach ($model->getModelBuilders() as $k => $v) {
                $model_builders[$k] = $v;
            }
        }

        return $model_builders;
    }

    /**
     * Return model builder for specific table.
     *
     * @param  string                     $for_table_name
     * @return AngieFrameworkModelBuilder
     * @throws InvalidParamError
     * @throws Exception
     */
    public static function &getModelBuilder($for_table_name)
    {
        foreach (self::$models as $model) {
            try {
                $model_builder = $model->getModelBuilder($for_table_name);
                if ($model_builder instanceof AngieFrameworkModelBuilder) {
                    return $model_builder;
                }
            } catch (InvalidParamError $e) {
                // Skip name error
            } catch (Exception $e) {
                throw $e;
            }
        }

        throw new InvalidParamError('for_table_name', $for_table_name, "Model builder is not defined for '$for_table_name' table in any of the models");
    }
}
