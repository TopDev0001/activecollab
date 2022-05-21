<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie;

use ActiveCollab\Memories\Memories;
use Angie\AutoUpgrade\Error\DownloadError;
use AngieApplication;
use AngieApplicationEnvironmentChecker;
use AngieModelMigration;
use DateTimeValue;
use DB;
use DBConnection;
use InvalidParamError;
use Phar;
use RuntimeException;

class AutoUpgrade
{
    private Memories $memories;
    private string $proxy;
    private bool $help_improve_application;

    public function __construct(
        Memories $memories,
        string $proxy = '',
        bool $help_improve_application = false
    )
    {
        $this->memories = $memories;
        $this->proxy = $proxy;
        $this->help_improve_application = $help_improve_application;
    }

    public function checkForUpdates(): array
    {
        $curl = curl_init(
            $this->getCheckForUpdatesUrl(
                $this->getLicenseKey()
            )
        );

        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($this->getStats($this->help_improve_application)));
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if ($proxy_url = $this->getProxy()) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_url);
        }

        if (VERIFY_APPLICATION_VENDOR_SSL) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($curl, CURLOPT_CAINFO, CUSTOM_CA_FILE);
        } else {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            curl_close($curl);
            unset($curl);

            throw new RuntimeException(sprintf('Check for updates failed. Reason: %s', curl_error($curl)), curl_errno($curl));
        }

        curl_close($curl);
        unset($curl);

        $response = json_decode($response, true);

        if (json_last_error()) {
            throw new RuntimeException(sprintf('Failed to parse response. Reason: %s', json_last_error_msg()), json_last_error());
        }

        if (array_key_exists('latest_version', $response)
            && array_key_exists('latest_available_version', $response)
            && array_key_exists('license', $response)
        ) {
            if ($response['license']['uid'] == 666) {
                throw new RuntimeException('Invalid license');
            }

            $this->setLastAutoUpgradeResponse($response);
        } else {
            throw new RuntimeException('Invalid response from the server');
        }

        return $response;
    }

    private function getStats($help_improve_application)
    {
        $stats = [];

        if ($help_improve_application) {
            $stats = AngieApplication::getStats();
        }

        if (empty($stats['current_version']) || $stats['current_version'] != $this->getCurrentVersion()) {
            $stats['current_version'] = $this->getCurrentVersion();
        }

        $stats['url'] = ROOT_URL;
        $stats['php_version'] = PHP_VERSION;
        $stats['mysql_version'] = DB::getConnection() instanceof DBConnection ? DB::getConnection()->getServerVersion() : 'unknown';
        $stats['frequently_last_run'] = $this->memories->get('frequently_last_run');
        $stats['hourly_last_run'] = $this->memories->get('hourly_last_run');
        $stats['check_imap_last_run'] = $this->memories->get('check_imap_last_run');

        return $stats;
    }

    /**
     * @var array|null
     */
    private $last_auto_upgrade_response;

    /**
     * Return last auto-upgrade response.
     *
     * @return array
     */
    private function getLastAutoUpgradeResponse()
    {
        if (empty($this->last_auto_upgrade_response)) {
            $data_from_memories = $this->memories->get('last_auto_upgrade_reponse');

            if (is_array($data_from_memories) && isset($data_from_memories['timestamp']) && $data_from_memories['response']) {
                $this->last_auto_upgrade_response = $data_from_memories['response'];
            }
        }

        return $this->last_auto_upgrade_response;
    }

    /**
     * Get info from last auto-upgrade response.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    private function getFromLastAutoUpgradeResponse($key, $default = null)
    {
        if (empty($this->last_auto_upgrade_response)) {
            $this->getLastAutoUpgradeResponse();
        }

        if (strpos($key, '.')) {
            $temp = &$this->last_auto_upgrade_response;

            foreach (explode('.', $key) as $bit) {
                $temp = &$temp[$bit];
            }

            return $temp;
        } else {
            return isset($this->last_auto_upgrade_response[$key]) ? $this->last_auto_upgrade_response[$key] : $default;
        }
    }

    /**
     * Save last check for updates response to the database.
     */
    private function setLastAutoUpgradeResponse(array $response)
    {
        $this->memories->set('last_auto_upgrade_reponse', ['timestamp' => time(), 'response' => $response]);
        $this->last_auto_upgrade_response = $response;
    }

    /**
     * Return latest stable release.
     *
     * @return string
     */
    public function getLatestStableVersion()
    {
        return $this->getFromLastAutoUpgradeResponse('latest_version');
    }

    /**
     * Return latest available version.
     *
     * @return string
     */
    public function getLatestAvailableVersion()
    {
        return $this->getFromLastAutoUpgradeResponse('latest_available_version');
    }

    /**
     * Return support subscription expiration timestamp.
     *
     * @return int
     */
    public function getSupportSubscriptionExpiresOn()
    {
        return $this->getFromLastAutoUpgradeResponse('license.expires');
    }

    /**
     * Return renew support URL.
     *
     * @return string
     */
    public function getRenewSupportUrl()
    {
        return $this->getFromLastAutoUpgradeResponse('license.urls.renew_support');
    }

    /**
     * Return upgrade instructions URL.
     *
     * @return string
     */
    public function getUpgradeInstructionsUrl()
    {
        return $this->getFromLastAutoUpgradeResponse('license.urls.update_instructions');
    }

    /**
     * Return release notes.
     *
     * @return array
     */
    public function getReleaseNotes()
    {
        return $this->getFromLastAutoUpgradeResponse('license.release_notes', []);
    }

    /**
     * Return upgrade warnings, if any.
     *
     * @return array
     */
    public function getUpgradeWarnings()
    {
        return $this->getFromLastAutoUpgradeResponse('license.upgrade_warnings', []);
    }

    public function prepareForDownload(): void
    {
        @session_write_close(); // Free up the app for other requests
    }

    /**
     * Download a particular release.
     *
     * @return string
     */
    public function downloadRelease(
        string $version,
        string $target_path,
        callable $on_progress = null,
        callable $on_completed = null
    )
    {
        if (!AngieApplication::isValidVersionNumber($version)) {
            throw new InvalidParamError('version', $version);
        }

        $this->prepareForDownload();

        $curl_url = $this->getDownloadReleaseUrl($version);

        $temp_path = $this->download(
            $curl_url,
            function () {
                $this->memories->set('auto_upgrade_download_progress', 0);
            },
            function ($percent) use ($on_progress) {
                $this->memories->set('auto_upgrade_download_progress', $percent);

                if ($on_progress) {
                    call_user_func($on_progress, $percent);
                }
            },
            function ($file_path, $headers) use ($on_completed) {
                $this->memories->set('auto_upgrade_download_progress', 100);

                if ($on_completed) {
                    call_user_func($on_completed, $file_path, $headers);
                }
            }
        );

        rename($temp_path, $target_path);

        return $target_path;
    }

    /**
     * Return download progress.
     *
     * @return int
     */
    public function getDownloadProgress()
    {
        return $this->memories->get('auto_upgrade_download_progress');
    }

    public function download(
        string $download_url,
        callable $on_download_started = null,
        callable $on_download_progress = null,
        callable $on_download_completed = null
    ): string
    {
        if ($on_download_started) {
            call_user_func($on_download_started);
        }

        [
            $file_path,
            $headers,
        ] = $this->downloadFromUrl(
            $download_url,
            WORK_PATH,
            function ($percents) use ($on_download_progress) {
                if ($on_download_progress) {
                    call_user_func($on_download_progress, $percents);
                }
            }
        );

        if (!is_file($file_path) || empty($headers)) {
            throw new DownloadError($download_url, 'Failed to download file');
        }

        if (md5_file($file_path) != array_var($headers, 'x-autoupgrade-package-hash', null)) {
            throw new DownloadError($download_url, 'MD5 verification failed');
        }

        if ($on_download_completed) {
            call_user_func($on_download_completed, $file_path, $headers);
        }

        return $file_path;
    }

    /**
     * Download file from server.
     *
     * @param  string $url
     * @param  string $destination_dir
     * @return array
     */
    private function downloadFromUrl($url, $destination_dir, callable $progress_callback = null)
    {
        $destination_file = AngieApplication::getAvailableFileName($destination_dir, 'auto-upgrade-temp');

        if ($write_handle = fopen($destination_file, 'w+b')) {
            $curl = curl_init($url);

            if ($curl_error = curl_error($curl)) {
                throw new DownloadError($url, "Operation failed with error: '$curl_error'");
            }

            curl_setopt($curl, CURLOPT_FILE, $write_handle);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 3000);
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                "User-Agent: '" . AngieApplication::getName() . ' v' . AngieApplication::getVersion() . "'" .
                "X-AutoUpgrade-RootUrl: '" . ROOT_URL . "'",
                "X-AutoUpgrade-LicenseKey: '" . AngieApplication::getLicenseKey() . "'",
            ]);

            if (VERIFY_APPLICATION_VENDOR_SSL) {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($curl, CURLOPT_CAINFO, CUSTOM_CA_FILE);
            } else {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            }

            if ($this->getProxy()) {
                curl_setopt($curl, CURLOPT_PROXY, $this->getProxy());
            }

            $response_headers = null;

            curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($curl, $header_line) use (&$response_headers) {
                $response_headers .= $header_line;

                return strlen($header_line);
            });

            if ($progress_callback) {
                curl_setopt($curl, CURLOPT_NOPROGRESS, false);
                curl_setopt($curl, CURLOPT_PROGRESSFUNCTION, function ($p1, $p2, $p3) use ($progress_callback) {
                    // This IF is needed because in some cases we'll get resources as $p1
                    // Info: http://stackoverflow.com/a/26622217/338473
                    if (is_resource($p1) || is_a($p1, 'CurlHandle')) {
                        $download_size = $p2;
                        $downloaded_size = $p3;
                    } else {
                        $download_size = $p1;
                        $downloaded_size = $p2;
                    }

                    $percents = 0;

                    if ($download_size > 0) {
                        $percents = round($downloaded_size * 100 / $download_size);
                    }

                    call_user_func($progress_callback, $percents);
                });
            }

            curl_exec($curl);

            if ($curl_error = curl_error($curl)) {
                throw new DownloadError($url, "Operation failed with error: '$curl_error'");
            }

            $response_headers = $this->parseHeaders($response_headers);

            fclose($write_handle);

            curl_close($curl);
            unset($curl);

            if (empty($response_headers['status']) || $response_headers['status'] != 200) {
                throw new DownloadError($url, "HTTP $response_headers[status], $response_headers[status_text]");
            }

            return [$this->renameTempFile($url, $destination_dir, $destination_file, $response_headers), $response_headers];
        } else {
            throw new DownloadError($url, 'Cannot write update package to temporary folder');
        }
    }

    /**
     * Unpack upgrade package to application folder.
     *
     * @param string $phar_path
     * @param string $latest_version
     */
    public function unpackPhar($phar_path, $latest_version, callable $on_before_unpack = null, callable $on_unpacked = null)
    {
        $unpack_phar_path = ROOT . '/' . $latest_version;

        $phar = new Phar($phar_path);

        if ($on_before_unpack && is_callable($on_before_unpack)) {
            call_user_func($on_before_unpack, $unpack_phar_path);
        }

        $phar->extractTo($unpack_phar_path, null, true);

        if ($on_unpacked && is_callable($on_unpacked)) {
            call_user_func($on_unpacked, $unpack_phar_path);
        }

        unlink($phar_path);
        $this->latest_downloaded_version = false; // Reset cache, if any
    }

    /**
     * Return true if one of migrations declares that it can't run.
     *
     * @param  string        $latest_version
     * @param  callable|null $on_cant_migrate
     * @return bool
     */
    public function canMigrate($latest_version, $on_cant_migrate = null)
    {
        if (php_sapi_name() == 'cli') {
            return true;
        }

        foreach (AngieApplication::migration()->getScripts($latest_version) as $migrations) {
            foreach ($migrations as $migration) {
                $reason = '';

                if ($migration instanceof AngieModelMigration && !$migration->canExecute($reason)) {
                    if ($on_cant_migrate && is_callable($on_cant_migrate)) {
                        call_user_func($on_cant_migrate, $migration, $reason);
                    }

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Run database migrations.
     *
     * @param string $latest_version
     */
    public function runMigrations($latest_version, callable $on_migration_message = null, callable $on_done = null)
    {
        AngieApplication::migration()->up($latest_version, $on_migration_message);
        AngieApplication::cache()->clear();

        foreach ([CACHE_PATH, COMPILE_PATH] as $dir_to_empty) {
            empty_dir($dir_to_empty, true);
        }

        DB::getConnection()->execute('TRUNCATE TABLE routing_cache');

        if ($on_done && is_callable($on_done)) {
            call_user_func($on_done);
        }
    }

    /**
     * Update version.php file.
     *
     * @param string $updated_to_version
     */
    public function updateVersionFile($updated_to_version, callable $on_done = null)
    {
        file_put_contents(
            CONFIG_PATH . '/version.php',
            "<?php\n\n  define('APPLICATION_VERSION', '{$updated_to_version}');\n"
        );

        if ($on_done && is_callable($on_done)) {
            call_user_func($on_done, $updated_to_version);
        }
    }

    /**
     * Include migration files from latest available release.
     */
    public function includeLatestUpgradeClasses(callable $on_files_included = null)
    {
        if ($latest_version = $this->getLatestDownloadedVersion()) {
            $angie_path = ROOT . "/$latest_version/angie";
        } else {
            $angie_path = ANGIE_PATH;
        }

        require_once "$angie_path/classes/application/migrations/AngieModelMigration.class.php";
        require_once "$angie_path/classes/application/migrations/AngieModelMigrationDiscoverer.class.php";
        require_once "$angie_path/classes/application/delegates/AngieMigrationDelegate.class.php";
        require_once "$angie_path/classes/application/AngieApplicationEnvironmentChecker.php";

        if ($on_files_included && is_callable($on_files_included)) {
            call_user_func($on_files_included, $angie_path);
        }
    }

    /**
     * Check system requirements.
     *
     * @return bool
     */
    public function checkEnvironment(callable $on_pass = null, callable $on_fail = null)
    {
        return (new AngieApplicationEnvironmentChecker())->check($on_pass, $on_fail);
    }

    /**
     * Backup database prior to migration.
     *
     * @param string $target_dir
     */
    public function backupDatabase($target_dir, callable $on_before = null, callable $on_after = null)
    {
        $target_file = $target_dir . '/' . date('Y-m-d H-i-s') . '.sql';

        if (is_callable($on_before)) {
            call_user_func($on_before, $target_file);
        }

        DB::exportToFile(DB::listTables(), $target_file);

        if (is_callable($on_after)) {
            call_user_func($on_after, $target_file);
        }
    }

    /**
     * Cached latest downloaded version.
     *
     * @var bool
     */
    private $latest_downloaded_version = false;

    /**
     * Walk through application folder and return latest version that we have on file.
     *
     * @return string
     */
    public function getLatestDownloadedVersion()
    {
        if ($this->latest_downloaded_version === false) {
            $this->latest_downloaded_version = '';

            if ($h = @opendir(ROOT)) {
                while (false !== ($version = readdir($h))) {
                    if (substr($version, 0, 1) == '.') {
                        continue;
                    }

                    if (AngieApplication::isValidVersionNumber($version)) {
                        if (empty($this->latest_downloaded_version)) {
                            $this->latest_downloaded_version = $version;
                        } else {
                            if (version_compare($this->latest_downloaded_version, $version, '<')) {
                                $this->latest_downloaded_version = $version;
                            }
                        }
                    }
                }
            }
        }

        return $this->latest_downloaded_version;
    }

    public function getAppliedUpgrades(): array
    {
        $applied_upgrades = $this->memories->get('applied_upgrades');

        if (empty($applied_upgrades) || !is_array($applied_upgrades)) {
            $applied_upgrades = [];
        }

        return $applied_upgrades;
    }

    public function setAppliedUpgrade(string $version, array $release_notes): array
    {
        $applied_upgrades = $this->getAppliedUpgrades();
        $applied_upgrades[] = [
            'version' => $version,
            'release_notes' => $release_notes,
            'updated_on' => DateTimeValue::now()->getTimestamp(),
        ];

        $this->memories->set('applied_upgrades', $applied_upgrades);

        return $applied_upgrades;
    }

    private function getProxy(): ?string
    {
        return $this->proxy;
    }

    /**
     * Parse HTTP header, and return array with key => values.
     *
     * @param  string $headers
     * @return array
     */
    private function parseHeaders($headers)
    {
        $headers = explode("\n", trim($headers));
        $output = [];

        if ('HTTP' === substr($headers[0], 0, 4)) {
            [, $output['status'], $output['status_text']] = explode(' ', trim($headers[0]));
            unset($headers[0]);
        }

        foreach ($headers as $v) {
            $h = preg_split('/:\s*/', $v);
            $output[strtolower($h[0])] = trim($h[1]);
        }

        return $output;
    }

    /**
     * Rename cryptic temp file name the .phar or .phar.gz file name that includes version number.
     *
     * @param  string $url
     * @param  string $destination_dir
     * @param  string $destination_file
     * @param  array  $response_headers
     * @return string
     */
    private function renameTempFile($url, $destination_dir, $destination_file, $response_headers)
    {
        $filename = $this->getFilenameFromContentDisposition(array_var($response_headers, 'content-disposition'));

        if ($destination_dir && $filename) {
            $new_destination_file = $destination_dir . '/' . $filename;

            if (is_file($new_destination_file) && !@unlink($new_destination_file)) {
                throw new DownloadError($url, "File '$new_destination_file' already exists and cannot be deleted"); // Work file already exists and can't be deleted
            }

            if (!rename($destination_file, $new_destination_file)) {
                throw new DownloadError($url, "Failed to rename file to '$filename'"); // Can't rename temporary file
            }

            $destination_file = $new_destination_file;
        }

        return $destination_file;
    }

    /**
     * Return file name from content disposition.
     *
     * @param  string $content_disposition
     * @return string
     */
    private function getFilenameFromContentDisposition($content_disposition)
    {
        if ($content_disposition) {
            foreach (explode(';', $content_disposition) as $content_disposition_part) {
                $content_disposition_part = trim($content_disposition_part);
                if (strpos($content_disposition_part, 'filename=') === 0) {
                    return trim(substr($content_disposition_part, 9), " \t\n\r\0\x0B\"\'");
                }
            }
        }

        return '';
    }

    /**
     * @var string
     */
    private $license_key;

    /**
     * @return string
     */
    private function getLicenseKey()
    {
        if (empty($this->license_key)) {
            $this->license_key = explode('/', AngieApplication::getLicenseKey())[0];
        }

        return $this->license_key;
    }

    /**
     * @var string
     */
    private $current_version;

    /**
     * Return current software version.
     *
     * @return string
     */
    private function getCurrentVersion()
    {
        if (empty($this->current_version)) {
            $this->current_version = APPLICATION_VERSION == 'current' ? '5.0.0' : APPLICATION_VERSION;
        }

        return $this->current_version;
    }

    /**
     * Copy assets to public directory.
     *
     * @param string $latest_version
     */
    public function copyAssetsToPublicDirectory(
        $latest_version,
        callable $on_target_emptied = null,
        callable $on_file_copied = null,
        callable $on_done = null
    )
    {
        $destination_dir = defined('ASSETS_PATH') && ASSETS_PATH && is_dir(ASSETS_PATH) ? ASSETS_PATH : '';
        $module_directories = $this->getFrontendModuleDirectories($latest_version);

        if (empty($destination_dir) || empty($module_directories)) {
            if ($on_done) {
                call_user_func($on_done, 'No assets found to copy');
            }

            return;
        }

        /**
         * Prepare directories.
         *
         * @param string $module_path
         * @param string $assets_path
         * @param bool   $prepared
         */
        function prepare_directories($module_path, $assets_path, &$prepared)
        {
            if (!$prepared) {
                if (!is_dir($module_path)) {
                    mkdir($module_path);
                }
                if (!is_dir($assets_path)) {
                    mkdir($assets_path);
                }
                $prepared = true;
            }
        }

        /**
         * Copy recursive.
         *
         * @param string          $src
         * @param string          $dst
         * @param callable|string $on_file_copied
         */
        function copy_recursive($src, $dst, callable $on_file_copied = null)
        {
            $dir = opendir($src);
            @mkdir($dst);
            while (false !== ($file = readdir($dir))) {
                if (($file != '.') && ($file != '..')) {
                    if (is_dir($src . '/' . $file)) {
                        copy_recursive($src . '/' . $file, $dst . '/' . $file, $on_file_copied);
                    } else {
                        copy($src . '/' . $file, $dst . '/' . $file);

                        if ($on_file_copied) {
                            call_user_func($on_file_copied, $src . '/' . $file, $dst . '/' . $file);
                        }
                    }
                }
            }
            closedir($dir);
        }

        $asset_types = ['images', 'fonts'];

        $icon_set_folders = [];
        foreach ($this->getFrontendIconDefinitions($latest_version) as $icon_set) {
            $icon_set_folders[] = $icon_set['folder_name'];
        }

        // empty images directory
        empty_dir($destination_dir, true);

        if ($on_file_copied) {
            $on_target_emptied($destination_dir);
        }

        // copy images from modules directory to the new directory
        foreach ($module_directories as $module_dir) {
            $module_name = basename($module_dir);
            $module_output_directory = $destination_dir . '/' . $module_name;

            foreach ($asset_types as $asset_type) {
                $module_assets_directory = $module_dir . '/' . $asset_type;
                if (!is_dir($module_assets_directory)) {
                    continue;
                }

                $module_assets_output_directory = $module_output_directory . '/' . $asset_type;
                $module_assets_entries = glob($module_assets_directory . '/*');
                if (is_array($module_assets_entries) && count($module_assets_entries)) {
                    $prepared = false;

                    foreach ($module_assets_entries as $module_assets_entry) {
                        if (is_dir($module_assets_entry)) {
                            $module_assets_entry_folder_name = basename($module_assets_entry);
                            if (!in_array($module_assets_entry_folder_name, $icon_set_folders)) {
                                prepare_directories($module_output_directory, $module_assets_output_directory, $prepared);
                                copy_recursive($module_assets_entry, $module_assets_output_directory . '/' . $module_assets_entry_folder_name, $on_file_copied);
                            }
                        } else {
                            if (is_file($module_assets_entry)) {
                                prepare_directories($module_output_directory, $module_assets_output_directory, $prepared);
                                copy($module_assets_entry, $module_assets_output_directory . '/' . basename($module_assets_entry));

                                if ($on_file_copied) {
                                    call_user_func($on_file_copied, $module_assets_entry, $module_assets_output_directory . '/' . basename($module_assets_entry));
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($on_done) {
            call_user_func($on_done, 'New assets copied');
        }
    }

    private function getFrontendModuleDirectories(string $latest_version): array
    {
        $frontend_path = $this->getFrontendPathForVersion($latest_version);

        $result = [];

        if ($latest_version == 'current') {
            $dirs_to_scan = array_merge(glob("$frontend_path/modules/*"), glob("$frontend_path/frameworks/*"));
        } else {
            $dirs_to_scan = glob("$frontend_path/assets/*");
        }

        if (!empty($dirs_to_scan)) {
            foreach ($dirs_to_scan as $potential_module_dir) {
                if (is_dir($potential_module_dir)) {
                    $result[] = $potential_module_dir;
                }
            }
        }

        return $result;
    }

    private function getFrontendIconDefinitions(string $latest_version): array
    {
        $result = [];

        $icon_definitions_path = $this->getFrontendPathForVersion($latest_version) . '/icon_definitions.php';

        if (is_file($icon_definitions_path)) {
            $result = require_once $icon_definitions_path;
        }

        return $result;
    }

    private function getFrontendPathForVersion(string $version): string
    {
        return ROOT . '/' . $version . '/frontend';
    }

    private function getCheckForUpdatesUrl(string $license_key): string
    {
        return sprintf(
            'https://%s/api/v1/check-for-updates/%s/',
            $this->useNewAutoUpgradeApi()
                ? 'activecollab.com'
                : 'my.activecollab.com',
            $license_key
        );
    }

    /**
     * Return download release url.
     */
    private function getDownloadReleaseUrl(string $version): string
    {
        return sprintf(
            'https://%s/api/v1/download-update/%s/%s/%s/',
            $this->useNewAutoUpgradeApi()
                ? 'activecollab.com'
                : 'my.activecollab.com',
            $this->getLicenseKey(),
            $this->getCurrentVersion(),
            $version
        );
    }

    private function useNewAutoUpgradeApi(): bool
    {
        return defined('USE_NEW_UPGRADE_API') && USE_NEW_UPGRADE_API;
    }
}
