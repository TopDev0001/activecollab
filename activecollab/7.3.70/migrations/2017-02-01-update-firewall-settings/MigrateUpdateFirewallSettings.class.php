<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateUpdateFirewallSettings extends AngieModelMigration
{
    public function up()
    {
        $this->removeConfigOption('firewall_temp_list');
        $this->removeConfigOption('firewall_settings');

        $this->fixList('firewall_white_list');
        $this->fixList('firewall_black_list');
    }

    /**
     * Validate and fix values in a particular list.
     *
     * @param string $config_option_name
     */
    private function fixList($config_option_name)
    {
        $rules = $this->getConfigOptionValue($config_option_name);

        if (is_string($rules)) {
            $rules = explode("\n", $rules);
        }

        if (!is_array($rules)) {
            $rules = [];
        }

        $new_option_value = [];

        foreach ($rules as $rule) {
            if ($rule && $this->isValidFirewallRule($rule)) {
                $new_option_value[] = $rule;
            }
        }

        $this->setConfigOptionValue($config_option_name, $new_option_value);
    }

    /**
     * Validate firewall rule.
     *
     * @param  string $rule
     * @return bool
     */
    private function isValidFirewallRule($rule)
    {
        if (strpos($rule, '/')) {
            [$rule, $mask] = explode('/', $rule);
        }

        if (filter_var($rule, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if (isset($mask) && ($mask < 1 || $mask > 30)) {
                return false;
            }

            return true;
        } elseif (filter_var($rule, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if (isset($mask) && ($mask < 1 || $mask > 128)) {
                return false;
            }

            return true;
        }

        return false;
    }
}
