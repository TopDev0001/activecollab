<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Authentication\Policies;

use ActiveCollab\Authentication\Password\Policy\PasswordPolicy as BasePasswordPolicy;
use ConfigOptions;

/**
 * Password policy.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class PasswordPolicy extends BasePasswordPolicy
{
    public function getMinLength()
    {
        return (int) ConfigOptions::getValue('password_policy_min_length', 15);
    }

    public function requireNumbers()
    {
        return (bool) ConfigOptions::getValue('password_policy_require_numbers');
    }

    public function requireMixedCase()
    {
        return (bool) ConfigOptions::getValue('password_policy_require_mixed_case');
    }

    public function requireSymbols()
    {
        return (bool) ConfigOptions::getValue('password_policy_require_symbols');
    }
}
