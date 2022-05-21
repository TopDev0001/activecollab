<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Angie application installer.
 *
 * @package angie.library.application
 */
final class AngieApplicationInstaller
{
    /**
     * Installer adapter.
     *
     * @var AngieApplicationInstallerAdapter
     */
    private static $adapter;

    /**
     * Initialize installer.
     *
     * @param  string                   $adapter_class
     * @param  string                   $adapter_class_path
     * @throws InvalidInstanceError
     * @throws ClassNotImplementedError
     * @throws FileDnxError
     */
    public static function init($adapter_class = null, $adapter_class_path = null)
    {
        if (empty($adapter_class)) {
            $adapter_class = APPLICATION_NAME . 'InstallerAdapter';
        }

        if (empty($adapter_class_path)) {
            $adapter_class_path = APPLICATION_PATH . "/resources/$adapter_class.class.php";
        }

        if (is_file($adapter_class_path)) {
            require_once $adapter_class_path;

            if (class_exists($adapter_class)) {
                $adapter = new $adapter_class();

                if ($adapter instanceof AngieApplicationInstallerAdapter) {
                    self::$adapter = $adapter;
                } else {
                    throw new InvalidInstanceError('adapter', $adapter, $adapter_class);
                }
            } else {
                throw new ClassNotImplementedError($adapter_class, $adapter_class_path);
            }
        } else {
            throw new FileDnxError($adapter_class_path);
        }
    }

    /**
     * Render installer dialog.
     */
    public static function render()
    {
        print '<!DOCTYPE html>';
        print '<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"><title>' . AngieApplication::getName() . ' Installer</title>';
        print '<script type="text/javascript">' . file_get_contents(__DIR__ . '/installer.jquery.js') . '</script>';
        print '<script type="text/javascript">' . file_get_contents(__DIR__ . '/installer.form.js') . '</script>';
        print '<script type="text/javascript">' . file_get_contents(__DIR__ . '/installer.js') . '</script>';
        print '<style type="text/css">' . file_get_contents(__DIR__ . '/installer.css') . '</style>';
        print '</head>';

        print '<body><div id="application_installer">';

        $counter = 1;
        foreach (self::getSections() as $section_name => $section_title) {
            print '<div class="installer_section" installer_section="' . $section_name . '">';
            print '<h1 class="head">' . $counter . '. <span>' . clean($section_title) . '</span></h1>';
            print '<div class="body">' . self::getSectionContent($section_name) . '</div>';
            print '</div>';

            ++$counter;
        }

        print '</div><p class="center">&copy;' . date('Y') . ' ' . AngieApplication::getVendor() . '. All rights reserved.</p><script type="text/javascript">$("#application_installer").installer({"name" : "' . AngieApplication::getName() . '"});</script>';
        print '</body></html>';
    }

    // ---------------------------------------------------
    //  Sections
    // ---------------------------------------------------

    /**
     * Return all installer sections.
     *
     * @return array
     */
    public static function getSections()
    {
        return self::$adapter->getSections();
    }

    /**
     * Return initial content for a given section.
     *
     * @param  string $name
     * @return string
     */
    public static function getSectionContent($name)
    {
        return self::$adapter->getSectionContent($name);
    }

    /**
     * Secuted section submission.
     *
     * @param string $name
     * @param mixed  $data
     */
    public static function executeSection($name, $data = null)
    {
        $response = '';

        if (self::$adapter->executeSection($name, $data, $response)) {
            header('HTTP/1.0 200 OK');
        } else {
            header('HTTP/1.0 409 Conflict');
        }

        print $response;
    }

    /**
     * Run installation from CLI.
     *
     * @param  array               $database_params
     * @param  array               $admin_params
     * @param  array               $license_params
     * @param  array               $additional_params
     * @param  mixed               $log
     * @return bool
     * @throws NotImplementedError
     */
    public static function runInstallationFromCli($database_params, $admin_params, $license_params, $additional_params, &$log)
    {
        if (php_sapi_name() == 'cli') {
            self::$adapter->validateInstallation($database_params, $admin_params, $license_params, $additional_params);
            $log = self::$adapter->printValidationLog(false);

            return self::$adapter->everythingValid();
        } else {
            throw new NotImplementedError(__METHOD__, 'This method is available only for CLI calls');
        }
    }
}
