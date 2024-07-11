<?php

use humhub\widgets\TopMenu;
use humhub\modules\employeeTraining\Events;
use humhub\modules\user\controllers\AuthController;

return [
    'id' => 'employeeTraining',
    'namespace' => 'humhub\modules\employeeTraining',
    'class' => 'humhub\modules\employeeTraining\Module',
    'events' => [
        [TopMenu::class, TopMenu::EVENT_INIT, [Events::class, 'onTopMenuInit']],
        [AuthController::class, AuthController::EVENT_AFTER_LOGIN, [Events::class, 'onAfterLogin']]
        // [
        //     'class'=> User::className(),
        //     // TODO EVENT AFTER LOGIN DOESNT EXIST
        //     'event' => User::EVENT_AFTER_LOGIN,
        //     'callback' => ['humhub\modules\employeeTraining\Events', 'onUserLogin']
        // ]
    ],
];
