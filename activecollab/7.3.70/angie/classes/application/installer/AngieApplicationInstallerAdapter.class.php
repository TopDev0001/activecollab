<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Application installer adapter.
 *
 * @package angie.library.application
 */
abstract class AngieApplicationInstallerAdapter
{
    public const VALIDATION_OK = 'ok';
    public const VALIDATION_WARNING = 'warning';
    public const VALIDATION_ERROR = 'error';

    /**
     * Minimal PHP version.
     */
    protected string $min_php_version = '7.4.0';

    /**
     * Minimal memory_limit value (in MB).
     */
    protected ?int $min_memory = null;

    /**
     * Recommended PHP version.
     */
    protected string $recommended_php_version = '7.2.0';

    /**
     * Minimal MySQL version.
     */
    protected string $min_mysql_version = '5.7.8';

    /**
     * Min MariaDB version.
     */
    protected string $min_mariadb_version = '10.2.7';

    /**
     * List of PHP requestions that are required to be installed.
     */
    protected array $required_php_extensions = [
        'mysqli',
        'pcre',
        'tokenizer',
        'ctype',
        'session',
        'json',
        'xml',
        'dom',
        'phar',
        'openssl',
        'gd',
        'mbstring',
        'curl',
        'zlib',
        'fileinfo',
        'hash',
    ];

    /**
     * List of PHP extensions that are recommended to be installed for some
     * application features to work.
     */
    protected array $recommended_php_extensions = [
        'imap' => 'import email messages and replies',
        'iconv' => 'characterset operations',
    ];

    /**
     * List of folder that will need to be writable for installed to be able to
     * set up the application.
     */
    protected array $writable_folders = [
        'cache',
        'compile',
        'config',
        'logs',
        'public/assets',
        'thumbnails',
        'upload',
        'work',
    ];

    /**
     * List of files that needs to be writable.
     */
    protected array $writable_files = [
        'config/version.php',
    ];

    /**
     * Default installer mode is self-install.
     */
    protected bool $is_self_install = true;

    // ---------------------------------------------------
    //  Sections
    // ---------------------------------------------------

    /**
     * Validation log.
     */
    protected array $validation_log = [];

    /**
     * Return installer sections.
     *
     * @return array
     */
    public function getSections()
    {
        return [
            'welcome' => 'Welcome',
            'database' => 'Database Connection',
            'owner' => 'License Verification',
        ];
    }

    /**
     * Render initial section content.
     *
     * @param  string $name
     * @return string
     */
    public function getSectionContent($name)
    {
        $application_name = AngieApplication::getName();

        switch ($name) {
            // Render welcome message
            case 'welcome':
                return '<form action="' . clean($this->getFormActionUrl()) . '" method=post>' .
                    '<p>Welcome to ' . $application_name . ' Installer. This tool will help you set up the system easily and quickly, within minutes.</p>' .
                    '<p>First step is to check if your platform can run ' . $application_name . '. Click on the button below to run the tests.</p>' .
                    '<p><button type="submit">Validate</button></p>' .
                    '</form>';

            // Render database form
            case 'database':
                return '<p>Good, your platform can run ' . $application_name . '. Now lets connect to database. Please provide database host, username and password, as well as name of the database that you want to use for ' . $application_name . '</p>' .
                $this->getDatabaseConnectionForm('localhost') .
                '<script type="text/javascript">$("#application_installer").installer("validate", "database", validate_database_parameters);</script>';

            // Render my.activecollab.com owner credentials form
            case 'owner':
                return "<p>Now that we have connected to database, we need to verify your license and set up an owner's account. Please fill in your <a href=\"https://my.activecollab.com\" target=\"_blank\">my.activecollab.com</a> credentials and click on Verify and Install button to complete the installation</p>" .
                $this->getOwnerForm() .
                '<script type="text/javascript">$("#application_installer").installer("validate", "owner", validate_owner_parameters);</script>';
            default:
                return ''; // Invalid section name
        }
    }

    /**
     * Return database connection form.
     *
     * @param  string $host
     * @param  string $user
     * @param  string $name
     * @return string
     */
    private function getDatabaseConnectionForm($host = 'localhost', $user = '', $name = '')
    {
        return '<form action="' . clean($this->getFormActionUrl()) . '" method=post>' .
            '<p class="wrap_form_element"><label for="database_host_input">Host</label> <input type="text" name="database[host]" id="database_host_input" value="' . clean($host) . '"></p>' .
            '<p class="wrap_form_element"><label for="database_user_input">Username</label> <input type="text" name="database[user]" id="database_user_input" value="' . clean($user) . '"></p>' .
            '<p class="wrap_form_element"><label for="database_pass_input">Password</label> <input type="password" name="database[pass]" id="database_pass_input"></p>' .
            '<p class="wrap_form_element"><label for="database_host_input">Database Name</label> <input type="text" name="database[name]" id="database_name_input" value="' . clean($name) . '"></p>' .
            '<p><button type="submit">Connect</button></p>' .
            '</form>';
    }

    /**
     * Return owner account form.
     *
     * @param  string $owner_email
     * @return string
     */
    private function getOwnerForm($owner_email = '')
    {
        return '<form action="' . clean($this->getFormActionUrl()) . '" method=post>' .
            '<p class="wrap_form_element">' .
                '<label for="owner_email_input">Your Email Address</label> <input type="email" name="owner[email]" id="owner_email_input" value="' . clean($owner_email) . '">' .
            '</p>' .
            '<p class="wrap_form_element">' .
                '<label for="owner_pass_input">Your Password</label> <input type="password" name="owner[pass]" id="owner_pass_input"> <input type="checkbox" id="owner_reveal_password"> Reveal Password' .
            '</p>' .
            '<p class="wrap_form_element">' .
                '<input type="checkbox" name="license[help_improve]" id="help_improve_input"> Send non-identifying ' . AngieApplication::getName() . ' usage data to help us improve the software in the future</a>' .
            '</p>' .
            '<p class="wrap_form_element">' .
                '<input type="checkbox" name="license[accepeted]" id="license_accepeted_input"> I Accept <a href="' . AngieApplication::getLicenseAgreementUrl() . '" tabindex="-1" target="_blank">' . AngieApplication::getName() . ' License Agreement</a>' .
            '</p>' .
            '<p><button type="submit">Verify and Install</button></p>' .
            '</form>
          <script type="text/javascript">
            $("#owner_reveal_password").click(function () {
                var old_password_input = $("#owner_pass_input");
                if (this.checked) {
                    var new_password_input_type = "text";
                    } else {
                        var new_password_input_type = "password";
                    }
                    var new_password_input = $(\'<input type="\' + new_password_input_type + \'" name="owner[pass]" id="owner_pass_input">\').val(old_password_input.val());
                    old_password_input.after(new_password_input).remove();
                    new_password_input.attr("id", "owner_pass_input");
                });
          </script>';
    }

    // ---------------------------------------------------
    //  General
    // ---------------------------------------------------

    /**
     * Handle given section.
     *
     * @param  string $name
     * @param  mixed  $data
     * @param  string $response
     * @return bool
     */
    public function executeSection($name, $data, &$response)
    {
        switch ($name) {
            // Run environment tests
            case 'welcome':
                // Environment is valid
                if ($this->validateEnvironment()) {
                    $response = $this->printValidationLog();

                    return true;

                    // Environment is not valid
                } else {
                    $response = '<form action="index.php" method="post">' . $this->printValidationLog() . '<p><button type="submit">Revalidate</button></p></form>';

                    return false;
                }

            // Connect to database and validate database support
            // no break
            case 'database':
                $database_params = $this->getDatabaseParams($_POST);

                if ($this->validateDatabase($database_params)) {
                    $response = $this->printValidationLog();

                    return true;
                } else {
                    $response = $this->printValidationLog();
                    $response .= $this->getDatabaseConnectionForm($database_params['host'], $database_params['user'], $database_params['name']);

                    return false;
                }

            // Create owner account
            // no break
            case 'owner':
                $database_params = $this->getDatabaseParams($_POST);
                $owner_params = $this->getOwnerParams($_POST);
                $license_params = $this->getLicenseParams($_POST);

                if ($this->validateInstallation($database_params, $owner_params, $license_params)) {
                    $response = $this->printValidationLog();

                    return true;
                } else {
                    $response = $this->printValidationLog();
                    $response .= $this->getOwnerForm($owner_params['email']);

                    return false;
                }
        }

        return false;
    }

    /**
     * Validate environment installation.
     *
     * @return bool
     */
    public function validateEnvironment()
    {
        $this->cleanUpValidationLog();

        // Validate PHP version and Zend Engine compatibility
        $php_version = PHP_VERSION;

        if (version_compare($php_version, $this->min_php_version) == -1) {
            $this->validationLogError("Minimum PHP version required in order to run activeCollab is PHP $this->min_php_version. Your PHP version: $php_version");
        } elseif (version_compare(PHP_VERSION, $this->recommended_php_version) == -1) {
            $this->validationLogWarning("Your PHP version is $php_version. Recommended version is PHP $this->recommended_php_version or later");
        } else {
            $this->validationLogOk("Your PHP version is $php_version");
        }

        // Validate safe mode
        if (ini_get('safe_mode')) {
            $this->validationLogWarning('PHP safe mode is On', 'This feature has been DEPRECATED as of PHP 5.3.0. Relying on this feature is highly discouraged.');
        } else {
            $this->validationLogOk('PHP safe mode is turned Off');
        }

        // Validate Zend Engine 1 compatibility mode
        if (ini_get('zend.ze1_compatibility_mode')) {
            $this->validationLogError('zend.ze1_compatibility_mode is set to On', 'This feature has been DEPRECATED and REMOVED as of PHP 5.3.0.');
        } else {
            $this->validationLogOk('zend.ze1_compatibility_mode is turned Off');
        }

        // Check always_populate_raw_post_data value for PHP 5.6
        if (version_compare(PHP_VERSION, '5.6.0', '>=') && version_compare(PHP_VERSION, '5.7.0', '<')) {
            if (ini_get('always_populate_raw_post_data') != -1) {
                $this->validationLogError('always_populate_raw_post_data is set to ' . var_export(ini_get('always_populate_raw_post_data'), true), "This option needs to be set to '-1' in PHP configuration file for " . AngieApplication::getName() . ' to work properly.');
            } else {
                $this->validationLogOk('always_populate_raw_post_data is -1 (good!)');
            }
        }

        // Check for eAccelerator
        if (extension_loaded('eAccelerator') && ini_get('eaccelerator.enable')) {
            $this->validationLogError('eAccelerator extension was found', 'System is not compatible with eAccelerator opcode cache. Please disable it for this folder or use APC instead');
        }

        // Check for XCache
        if (extension_loaded('XCache') && ini_get('xcache.cacher')) {
            $this->validationLogError('XCache extension was found', 'System is not compatible with XCache opcode cache. Please disable it for this folder or use APC instead');
        }

        // Check memory limit
        if ($this->min_memory > 0) {
            $memory_limit = php_config_value_to_bytes(ini_get('memory_limit'));

            $formatted_memory_limit = $memory_limit == -1 ? 'unlimited' : format_file_size($memory_limit);

            if ($memory_limit === -1 || $memory_limit >= ($this->min_memory * 1024 * 1024)) {
                $this->validationLogOk('Your memory limit is ' . $formatted_memory_limit);
            } else {
                $this->validationLogError('Your memory is too low to complete the installation. Minimal value is ' . $this->min_memory . 'MB, and you have it set to ' . $formatted_memory_limit);
            }
        }

        // Validate required PHP extensions
        foreach ($this->required_php_extensions as $extension) {
            if (extension_loaded($extension)) {
                $this->validationLogOk("Required extension '$extension' found");
            } else {
                $this->validationLogError("Required extension '$extension' not found");
            }
        }

        // Validate recommended PHP extensions
        foreach ($this->recommended_php_extensions as $extension => $explanation) {
            if (extension_loaded($extension)) {
                $this->validationLogOk("Recommended extension '$extension' found");
            } else {
                $this->validationLogWarning("Recommended extension '$extension' not found", "'$extension' is used for $explanation");
            }
        }

        // Validate URL rewriting
        if (php_sapi_name() == 'cli-server' || (defined('SKIP_URL_REWRITE_CHECK') && SKIP_URL_REWRITE_CHECK)) {
            $this->validationLogWarning('URL rewriting check not performed', 'You are either running PHP built in server, or you explicitly turned off the URL rewriting check. Either way, we expect that you know what you are doing.');
        } else {
            if (extension_loaded('curl')) {
                try {
                    $this->validateUrlRewriting();
                    $this->validationLogOk('URL rewriting appears to be working fine');
                } catch (Exception $e) {
                    $this->validationLogError('URL rewriting is not enabled', $e->getMessage());
                }
            } else {
                $this->validationLogError("Can't validate URL rewriting without 'curl' extension");
            }
        }

        // Validate folders
        if (is_array($this->writable_folders)) {
            foreach ($this->writable_folders as $relative_folder_path) {
                $check_this = realpath(ROOT . "/../$relative_folder_path");

                if (is_dir($check_this) && folder_is_writable($check_this)) {
                    $this->validationLogOk("/$relative_folder_path folder is writable");
                } else {
                    $this->validationLogError("/$relative_folder_path folder is not writable");
                }
            }
        }

        // Validate files
        if (is_array($this->writable_files)) {
            foreach ($this->writable_files as $relative_file_path) {
                $check_this = realpath(ROOT . "/../$relative_file_path");

                if (is_file($check_this) && file_is_writable($check_this)) {
                    $this->validationLogOk("/$relative_file_path file is writable");
                } else {
                    $this->validationLogError("/$relative_file_path file is not writable");
                }
            }
        }

        return $this->everythingValid();
    }

    /**
     * Clean up validation log.
     */
    public function cleanUpValidationLog()
    {
        if (!empty($this->validation_log)) {
            $this->validation_log = [];
        }
    }

    // ---------------------------------------------------
    //  Register requirements
    // ---------------------------------------------------

    /**
     * Log validation error message.
     *
     * @param string $message
     * @param string $explanation
     */
    protected function validationLogError($message, $explanation = null)
    {
        $this->validation_log[] = [
            'status' => self::VALIDATION_ERROR,
            'message' => $message,
            'explanation' => $explanation,
        ];
    }

    /**
     * Log validation warning message.
     *
     * @param string $message
     * @param string $explanation
     */
    protected function validationLogWarning($message, $explanation = null)
    {
        $this->validation_log[] = [
            'status' => self::VALIDATION_WARNING,
            'message' => $message,
            'explanation' => $explanation,
        ];
    }

    /**
     * Log validation OK message.
     *
     * @param string $message
     */
    public function validationLogOk($message)
    {
        $this->validation_log[] = [
            'status' => self::VALIDATION_OK,
            'message' => $message,
            'explanation' => null,
        ];
    }

    /**
     * Check if mod_rewrite is properly configured.
     *
     * @throws Exception
     */
    private function validateUrlRewriting()
    {
        $hash = sha1(LICENSE_KEY . microtime());
        $url = $this->getRootUrl() . '/verify-existence';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Angie-Verify-Existence: $hash"]);

        $result = curl_exec($ch);

        if ($curl_error = curl_error($ch)) {
            curl_close($ch);
            unset($ch);

            throw new Exception("Failed to call '$url'. Reason: $curl_error");
        } else {
            curl_close($ch);
            unset($ch);

            $json = json_decode($result, true);

            if (empty($json['ok']) || empty($json['echo']) || $json['echo'] != $hash) {
                throw new Exception('Invalid URL rewrite check response: ' . (mb_strlen($result) > 255 ? mb_substr($result, 0, 255) . '...' : $result));
            }
        }
    }

    public function getFormActionUrl(): string
    {
        if (INSTALLER_USE_PHP_SELF && !empty($_SERVER['PHP_SELF'])) {
            return $_SERVER['PHP_SELF'];
        } else {
            return 'index.php';
        }
    }

    /**
     * Return ROOT_URL value.
     *
     * @return string
     */
    public function getRootUrl()
    {
        $url = AngieApplication::getRequestSchema() . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        if (($pos = strpos($url, '?')) !== false) {
            $url = substr($url, 0, $pos); // Remove query string
        }

        if (str_ends_with($url, '/index.php')) {
            $url = str_replace('/index.php', '', $url); // Remove script name
        }

        return $url;
    }

    /**
     * Returns true if there are no errors in validation log.
     *
     * @return bool
     */
    public function everythingValid()
    {
        foreach ($this->validation_log as $v) {
            if ($v['status'] == self::VALIDATION_ERROR) {
                return false;
            }
        }

        return true;
    }

    /**
     * Print validation log.
     *
     * @param  bool   $html
     * @return string
     */
    public function printValidationLog($html = true)
    {
        return $html ? $this->printValidationLogToHtml() : $this->printValidationLogToConsole();
    }

    /**
     * Print validation log to HTML.
     *
     * @return string
     */
    protected function printValidationLogToHtml()
    {
        $response = '<ul class="validation_log">';

        foreach ($this->validation_log as $log_entry) {
            switch ($log_entry['status']) {
                case self::VALIDATION_ERROR:
                    $class = 'error';
                    $status = 'Error';

                    break;
                case self::VALIDATION_WARNING:
                    $class = 'warning';
                    $status = 'Warning';

                    break;
                default:
                    $class = 'ok';
                    $status = 'OK';
            }

            $response .= '<li class="' . $class . '"><span class="status">' . $status . '</span> &mdash; <span class="message">' . clean($log_entry['message']) . '</span>';

            if ($log_entry['explanation']) {
                $response .= '<span class="explanation">' . clean($log_entry['explanation']) . '</span>';
            }

            $response .= '</li>';
        }

        return "$response</ul>";
    }

    /**
     * Print validation log to CLI.
     *
     * @return string
     */
    protected function printValidationLogToConsole()
    {
        $response = '';

        foreach ($this->validation_log as $log_entry) {
            switch ($log_entry['status']) {
                case self::VALIDATION_ERROR:
                    $status = 'Error';

                    break;
                case self::VALIDATION_WARNING:
                    $status = 'Warning';

                    break;
                default:
                    $status = 'OK';
            }

            $response .= $status . ': ' . $log_entry['message'];

            if ($log_entry['explanation']) {
                $response .= ' (' . clean($log_entry['explanation']) . ')';
            }

            $response .= "\n";
        }

        return "$response\n";
    }

    // ---------------------------------------------------
    //  Validation
    // ---------------------------------------------------

    /**
     * Return database parameters array.
     *
     * @param  array $from
     * @return array
     */
    public function getDatabaseParams($from)
    {
        $params = isset($from['database']) && is_array($from['database']) ? $from['database'] : [];

        if (!isset($params['host'])) {
            $params['host'] = '';
        }

        if (!isset($params['user'])) {
            $params['user'] = '';
        }

        if (!isset($params['pass'])) {
            $params['pass'] = '';
        }

        if (!isset($params['name'])) {
            $params['name'] = '';
        }

        return $params;
    }

    /**
     * Validate database connection parameters.
     *
     * @param  array $database_params
     * @return bool
     */
    public function validateDatabase($database_params)
    {
        $this->cleanUpValidationLog();

        $database_host = $database_params['host'];
        $database_user = $database_params['user'];
        $database_pass = $database_params['pass'];
        $database_name = $database_params['name'];

        if ($database_host && $database_user && $database_name) {
            $link = mysqli_connect($database_host, $database_user, $database_pass, $database_name);

            if ($link instanceof mysqli) {
                $this->validationLogOk("Connected to database as {$database_user}@{$database_host} (using password: " . (empty($database_pass) ? 'No' : 'Yes') . ')');

                $mysql_version = $this->getMySqlVersion($link);

                [$mysql_server, $min_mysql_server_version, $mysql_version_ok] = $this->validateMySqlVersion(
                    $mysql_version,
                    $this->min_mysql_version,
                    $this->min_mariadb_version,
                );

                if ($mysql_version_ok) {
                    $this->validationLogOk("{$mysql_server} version is {$mysql_version}");

                    // Make suret that we are installing in an empty database
                    if ($this->isDatabaseEmpty($link)) {
                        $this->validationLogOk('Database is empty');
                    } else {
                        $this->validationLogError('Database is not empty');
                    }

                    // Confirm that InnoDB support is enabled
                    if ($this->checkHaveInno($link)) {
                        $this->validationLogOk('InnoDB support available');
                    } else {
                        $this->validationLogError('InnoDB support not available');
                    }

                    // Confirm that we can use UTF8MB4 charset
                    if ($this->checkHaveUtf8mb4($link)) {
                        $this->validationLogOk('UTF8MB4 support available');
                    } else {
                        $this->validationLogError('UTF8MB4 support not available');
                    }

                    // Check thread_stack
                    if ($this->checkThreadStack($link)) {
                        $this->validationLogOk("{$mysql_server} thread stack is 256kb");
                    } else {
                        $this->validationLogError("{$mysql_server} thread stack should be 256kb");
                    }
                } else {
                    $this->validationLogError("{$mysql_server} {$min_mysql_server_version} or later is required. Your {$mysql_server} version is {$mysql_version}");
                }
            } else {
                $this->validationLogError('Failed to connect to database', "Failed to connect to database as {$database_user}@{$database_host}/{$database_name} (using password: " . (empty($database_pass) ? 'No' : 'Yes') . ')');
            }
        } else {
            $this->validationLogError('Database connection parameters are not provided');
        }

        return $this->everythingValid();
    }

    private function getMySqlVersion(mysqli $link)
    {
        if ($result = $link->query("SELECT VERSION() AS 'version'")) {
            while ($row = $result->fetch_assoc()) {
                return $row['version'];
            }
        }

        return $link->get_server_info();
    }

    private function validateMySqlVersion($version, $min_mysql_version, $min_mariadb_version)
    {
        if (strpos(strtolower($version), 'mariadb') !== false) {
            return [
                'MariaDB',
                $min_mariadb_version,
                strpos($version, $min_mariadb_version) !== false
                    || version_compare($version, $min_mariadb_version) >= 0,
            ];
        } else {
            return [
                'MySQL',
                $min_mysql_version,
                version_compare($version, $min_mysql_version) >= 0,
            ];
        }
    }

    /**
     * Return true if database is empty (has no tables).
     *
     * @param  mysqli $link
     * @return bool
     */
    public function isDatabaseEmpty($link)
    {
        if ($result = $link->query('SHOW TABLES')) {
            return $result->num_rows < 1;
        }

        return true;
    }

    /**
     * Returns true if MySQL supports InnoDB.
     *
     * @param  mysqli $link
     * @return bool
     */
    public function checkHaveInno($link)
    {
        if ($result = $link->query('SHOW ENGINES')) {
            while ($engine = $result->fetch_assoc()) {
                if (strtolower($engine['Engine']) == 'innodb' && in_array(strtolower($engine['Support']), ['yes', 'default'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  mysqli $link
     * @return bool
     */
    public function checkHaveUtf8mb4($link)
    {
        if ($result = $link->query("SHOW CHARACTER SET LIKE 'utf8mb4'")) {
            while ($charset = $result->fetch_assoc()) {
                if (strtolower($charset['Charset']) == 'utf8mb4') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  mysqli $link
     * @return bool
     */
    public function checkThreadStack($link)
    {
        if ($result = $link->query("SELECT @@thread_stack AS 'thread_stack'")) {
            while ($row = $result->fetch_assoc()) {
                return (int) $row['thread_stack'] >= 262144; // 256kb
            }
        }

        return false;
    }

    /**
     * Return owner parameters.
     *
     * @param  array $from
     * @return array
     */
    public function getOwnerParams($from)
    {
        $params = isset($from['owner']) && is_array($from['owner']) ? $from['owner'] : [];

        if (!isset($params['email'])) {
            $params['email'] = '';
        }

        if (!isset($params['pass'])) {
            $params['pass'] = '';
        }

        return $params;
    }

    /**
     * Return license parameters.
     *
     * @param  array $from
     * @return array
     */
    public function getLicenseParams($from)
    {
        $params = isset($from['license']) && is_array($from['license']) ? $from['license'] : [];

        if (!array_key_exists('accepted', $params)) {
            $params['accepted'] = false;
        }

        $params['help_improve'] = !empty($params['help_improve']);

        return $params;
    }

    /**
     * Validate system installation.
     *
     * @param  array $database_params
     * @param  array $owner_params
     * @param  array $licensing_params
     * @param  array $additional_params
     * @return bool
     */
    public function validateInstallation($database_params, $owner_params, $licensing_params, $additional_params = null)
    {
        $this->cleanUpValidationLog();

        $owner_email = array_var($owner_params, 'email', null, true);
        $owner_password = array_var($owner_params, 'pass', null, true);

        $license_accepted = (bool) $licensing_params['accepted'];
        $license_validated = false;
        $license_validation_error = false;

        if ($owner_email && is_valid_email($owner_email) && $owner_password && $license_accepted) {
            $license_validated = true;
        }

        // We have all the data
        if ($license_validated) {
            $database_host = $database_params['host'];
            $database_user = $database_params['user'];
            $database_pass = $database_params['pass'];
            $database_name = $database_params['name'];

            // Lets connect to database
            try {
                DB::setConnection(
                    'default',
                    new MySQLDBConnection(
                        $database_host,
                        $database_user,
                        $database_pass,
                        $database_name,
                    ),
                );
            } catch (Exception $e) {
                $this->validationLogError('Failed to connect to database. Reason: ' . $e->getMessage());

                return false;
            }

            // Initialize and load model
            try {
                AngieApplicationModel::load(AngieApplication::getFrameworkNames(), $this->getModulesToInstall());
                AngieApplicationModel::init();

                $this->validationLogOk(AngieApplication::getName() . ' tables have been created and initial data loaded');
            } catch (Exception $e) {
                $this->validationLogError('Failed to build model. Reason: ' . $e->getMessage());

                return false;
            }

            // Owner
            try {
                $this->createOwner($owner_email, $owner_password, $owner_params);
                $this->validationLogOk("'$owner_email' owner account has been created");
            } catch (Exception $e) {
                $this->validationLogError('Failed to create owner. Reason: ' . $e->getMessage());

                return false;
            }

            // Set help improve option
            $this->setConfigOption('help_improve_application', !empty($licensing_params['help_improve']));

            // Create configuration file for self-hosted instance. We reseted $owner_params values in previous step, so we are rebuilding the
            if (!AngieApplication::isOnDemand()) {
                $this->createConfigFile(CONFIG_PATH . '/config.php', $this->getConfigOptions($database_params, [
                    'email' => $owner_email,
                    'pass' => $owner_password,
                ], $additional_params));

                $this->validationLogOk('Configuration file has been created');
            }

            // Invalid input data
        } else {
            if (empty($owner_email)) {
                $this->validationLogError("Email address for owner's account not provided");
            } elseif (!is_valid_email($owner_email)) {
                $this->validationLogError("Email address for owner's account is not valid");
            }

            if (empty($owner_password)) {
                $this->validationLogError("Password for owner's account not provided");
            }

            if ($license_accepted) {
                if (!$license_validated) {
                    $this->validationLogError($license_validation_error ? $license_validation_error : 'Failed to validate your ' . AngieApplication::getName() . ' user credentials and license data');
                }
            } else {
                $this->validationLogError(AngieApplication::getName() . " can't be used unless you accept the license agreement");
            }
        }

        return $this->everythingValid();
    }

    /**
     * Return a list of modules that need to be installed.
     *
     * @return array
     */
    public function getModulesToInstall()
    {
        $modules = ['system'];

        $all_modules = get_folders(APPLICATION_PATH . '/modules');

        if ($all_modules) {
            foreach ($all_modules as $module_path) {
                $module_name = basename($module_path);

                if (!in_array($module_name, $modules)) {
                    $modules[] = $module_name;
                }
            }
        }

        return $modules;
    }

    /**
     * Create owner user account.
     *
     * This function returns owner's user ID
     *
     * @param  string $email
     * @param  string $password
     * @return int
     */
    public function createOwner($email, $password, array $other_params = null)
    {
        $owner_id = (int) DB::executeFirstCell("SELECT id FROM users WHERE type = 'Owner'");

        // We already have an owner, update the account
        if ($owner_id) {
            DB::execute('UPDATE users SET email = ?, password = ?, password_hashed_with = ? WHERE id = ?', $email, password_hash($password, PASSWORD_DEFAULT), 'php', $owner_id);

            // Add a new user account
        } else {
            DB::execute('INSERT INTO users (type, state, email, password, password_hashed_with, created_on, created_by_id) VALUES (?, ?, ?, ?, ?, ?, ?)', Owner::class, 3, $email, password_hash($password, PASSWORD_DEFAULT), 'php', DateTimeValue::now(), 1);
            $owner_id = DB::lastInsertId();
        }

        return $owner_id;
    }

    /**
     * Set configuration option value.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function setConfigOption($name, $value)
    {
        DB::execute('UPDATE config_options SET value = ? WHERE name = ?', serialize($value), $name);
    }

    /**
     * Create configuration file.
     *
     * @param  string $config_file_path
     * @param  array  $params
     * @return bool
     */
    public function createConfigFile($config_file_path, $params)
    {
        $lines = [
            '<?php',
            '',
            '/**',
            ' * ' . AngieApplication::getName() . ' configuration file',
            ' *',
            ' * Automatically generated by the installer script on ' . date(DATE_MYSQL),
            ' */',
            '',
        ];

        foreach ($params as $k => $v) {
            $lines[] = "const $k = " . var_export($v, true) . ';';
        }

        $lines[] = '';
        $lines[] = "defined('CONFIG_PATH') or define('CONFIG_PATH', __DIR__);";
        $lines[] = '';

        $lines[] = "require_once CONFIG_PATH . '/version.php';";
        $lines[] = "require_once CONFIG_PATH . '/defaults.php';";
        $lines[] = '';

        return file_put_contents($config_file_path, implode("\r\n", $lines));
    }

    /**
     * Return configuration options array.
     *
     * Supported additional params:
     *
     * - root_url - Used instead of internal getRootUrl() call
     *
     * @param  array $database_params
     * @param  array $owner_params
     * @param  array $additional_params
     * @return array
     */
    public function getConfigOptions($database_params, $owner_params, $additional_params = null)
    {
        $config_options = [
            'ROOT' => ROOT,
            'ROOT_URL' => $this->getRootUrl(),
            'DB_HOST' => $database_params['host'],
            'DB_USER' => $database_params['user'],
            'DB_PASS' => $database_params['pass'],
            'DB_NAME' => $database_params['name'],
            'ADMIN_EMAIL' => $owner_params['email'],
            'LICENSE_KEY' => LICENSE_KEY,
            'APPLICATION_UNIQUE_KEY' => APPLICATION_UNIQUE_KEY,
        ];

        if ($this->isSelfInstall()) {
            if (isset($_SERVER['REMOTE_ADDR']) && ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1')) {
                $config_options['COOKIE_DOMAIN'] = ''; // In case of localhost, fix COOKIE_DOMAIN
            }
        }

        if (is_array($additional_params) && isset($additional_params['force_config_options']) && is_array($additional_params['force_config_options'])) {
            foreach ($additional_params['force_config_options'] as $k => $v) {
                $config_options[$k] = $v;
            }
        }

        return $config_options;
    }

    /**
     * Returns true if this is self-installer (application that installs itself).
     *
     * @return bool
     */
    public function isSelfInstall()
    {
        return $this->is_self_install;
    }

    /**
     * Set minimal PHP version.
     *
     * @param string $version
     */
    public function setMinPHPVersion($version)
    {
        $this->min_php_version = $version;
    }

    // ---------------------------------------------------
    //  Log
    // ---------------------------------------------------

    /**
     * Set recommended PHP version.
     *
     * @param string $version
     */
    public function setRecommendedPHPVersion($version)
    {
        $this->recommended_php_version = $version;
    }

    /**
     * Set min memory value.
     *
     * @param $min_memory
     */
    public function setMinMemory($min_memory)
    {
        $this->min_memory = $min_memory;
    }

    /**
     * Set minimal MySQL version.
     *
     * @param string $version
     */
    public function setMinMySQLVersion($version)
    {
        $this->min_mysql_version = $version;
    }

    /**
     * Add a one or more of PHP extensions to list of required extensions.
     */
    public function addRequiredPhpExtension(string ...$extensions_to_add)
    {
        foreach ($extensions_to_add as $v) {
            if (!in_array($v, $this->required_php_extensions)) {
                $this->required_php_extensions[] = $v;
            }
        }
    }

    /**
     * Add one or more recommended PHP extensions to the list.
     *
     * @param string $extension
     * @param string $why_recommended
     */
    public function addRecommendedPhpExtension($extension, $why_recommended = null)
    {
        if (is_array($extension)) {
            $to_add = $extension;
        } else {
            $to_add = [$extension => $why_recommended];
        }

        foreach ($to_add as $k => $v) {
            $this->recommended_php_extensions[$k] = $v;
        }
    }

    /**
     * Add folder to the list of folder that will need to be writable.
     *
     * @param string[]|string $rel_path
     */
    public function addWritableFolder($rel_path)
    {
        if (is_array($rel_path)) {
            foreach ($rel_path as $k) {
                if (!in_array($k, $this->writable_folders)) {
                    $this->writable_folders[] = $k;
                }
            }
        } else {
            if (!in_array($rel_path, $this->writable_folders)) {
                $this->writable_folders[] = $rel_path;
            }
        }
    }

    /**
     * Add file that needs to be writable.
     *
     * @param string $rel_path
     */
    public function addWritableFile($rel_path)
    {
        if (is_array($rel_path)) {
            foreach ($rel_path as $k) {
                if (!in_array($k, $this->writable_files)) {
                    $this->writable_files[] = $k;
                }
            }
        } else {
            if (!in_array($rel_path, $this->writable_files)) {
                $this->writable_files[] = $rel_path;
            }
        }
    }
}
