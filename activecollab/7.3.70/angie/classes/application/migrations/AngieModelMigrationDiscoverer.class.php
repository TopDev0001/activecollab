<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Angie model migration discoverer.
 *
 * NOTE: This class needs to be fully independent because it is used by upgrade script as well
 *
 * @package angie.library.application
 * @subpackage model
 */
final class AngieModelMigrationDiscoverer
{
    /**
     * Discover migrations for a given version.
     *
     * @param  string                $for_version
     * @return AngieModelMigration[]
     */
    public static function discover($for_version = 'current')
    {
        $compatibility_classes = [
            'DBTable' => dirname(__DIR__, 2) . '/database/engineer/DBTable.class.php',
            'DBJsonColumn' => dirname(__DIR__, 2) . '/database/engineer/columns/DBJsonColumn.class.php',
        ];

        foreach ($compatibility_classes as $compatibility_class_name => $compatibility_class_path) {
            if (!class_exists($compatibility_class_name, false) && is_file($compatibility_class_path)) {
                require_once $compatibility_class_path;
            }
        }

        return self::discoverFromPaths(self::getPathsToScan($for_version));
    }

    public static function getPathsToScan($for_version = 'current')
    {
        return [
            ROOT . '/' . $for_version . '/migrations', // Application
            ROOT . '/' . $for_version . '/modules/on_demand/migrations', // OnDemand migrations
            $for_version == 'current' ? ANGIE_PATH . '/migrations' : ROOT . '/' . $for_version . '/angie/migrations', // Framework, development or production
        ];
    }

    /**
     * Discover migrations in list of paths.
     *
     * @param  array                 $paths_to_scan
     * @return AngieModelMigration[]
     * @throws RuntimeException
     */
    public static function discoverFromPaths($paths_to_scan)
    {
        $result = [];

        foreach ($paths_to_scan as $path) {
            $scripts_dirs = get_folders($path);

            if (is_array($scripts_dirs)) {
                sort($scripts_dirs);

                foreach ($scripts_dirs as $scripts_dir) {
                    $changeset = basename($scripts_dir);

                    if (self::isValidScriptsDirName($changeset)) {
                        $files = get_files($scripts_dir, 'php');

                        if (is_array($files)) {
                            foreach ($files as $file) {
                                $basename = basename($file);

                                if (str_starts_with($basename, 'Migrate') && str_ends_with($basename, '.class.php')) {
                                    require_once $file;

                                    $class_name = first(explode('.', $basename));

                                    if (class_exists($class_name, false)) {
                                        if (empty($result[$changeset])) {
                                            $result[$changeset] = [];
                                        }

                                        $migration = new $class_name();

                                        if ($migration instanceof AngieModelMigration) {
                                            $result[$changeset][get_class($migration)] = $migration;
                                        }
                                    } else {
                                        throw new RuntimeException("Class '$class_name' definition not found in '$file");
                                    }
                                } else {
                                    throw new RuntimeException("Migration definition file '$file' is not properly named");
                                }
                            }
                        }
                    }
                }
            }
        }

        ksort($result);

        return $result;
    }

    /**
     * Check if name is valid scripts dir.
     *
     * @param  string $name
     * @return bool
     */
    private static function isValidScriptsDirName($name)
    {
        return (bool) preg_match('/^(\d{4})-(\d{2})-(\d{2})-(.*)$/', $name);
    }
}
