<?php

use humhub\widgets\TopMenu;
use humhub\modules\employeeTraining\Events;
use humhub\modules\user\controllers\AuthController;
use humhub\modules\dashboard\widgets\Sidebar;

return [
    'id' => 'employeeTraining',
    'namespace' => 'humhub\modules\employeeTraining',
    'class' => 'humhub\modules\employeeTraining\Module',
    'controllerNamespace' => 'humhub\modules\employeeTraining\controllers',
    'events' => [
        [TopMenu::class, TopMenu::EVENT_INIT, [Events::class, 'onTopMenuInit']],
        [AuthController::class, AuthController::EVENT_AFTER_LOGIN, [Events::class, 'onAfterLogin']],
        [Sidebar::class, Sidebar::EVENT_INIT, [Events::class, 'onDashboardSidebarInit']]
    ]
];
