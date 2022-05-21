<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

require_once ANGIE_PATH . '/classes/application/AngieApplication.class.php';

spl_autoload_register(['AngieApplication', 'autoload']);

require_once ANGIE_PATH . '/classes/application/AngieModule.class.php';

AngieApplication::setForAutoload(
    [
        'AngieApplicationModel' => ANGIE_PATH . '/classes/application/model/AngieApplicationModel.class.php',
        'AngieFrameworkModel' => ANGIE_PATH . '/classes/application/model/AngieFrameworkModel.class.php',
        'AngieModuleModel' => ANGIE_PATH . '/classes/application/model/AngieModuleModel.class.php',
        'AngieFrameworkModelBuilder' => ANGIE_PATH . '/classes/application/model/AngieFrameworkModelBuilder.class.php',

        'AngieDelegate' => ANGIE_PATH . '/classes/application/AngieDelegate.class.php',
        'AngieMigrationDelegate' => ANGIE_PATH . '/classes/application/delegates/AngieMigrationDelegate.class.php',
    ]
);
