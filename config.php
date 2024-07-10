<?php

use humhub\widgets\TopMenu;
// use humhub\modules\user\components\User;
use yii\base\Event;
use yii\web\User;
use humhub\modules\employeeTraining\Events;

return [
    'id' => 'employeeTraining',
    'namespace' => 'humhub\modules\employeeTraining',
    'class' => 'humhub\modules\employeeTraining\Module',
    'events' => [
        [
            'class' => TopMenu::class, 
            'event' => TopMenu::EVENT_INIT, 
            'callback' => ['humhub\modules\employeeTraining\Events', 'onTopMenuInit']
        ],
        [
            'class'=> User::className(),
            // TODO EVENT AFTER LOGIN DOESNT EXIST
            'event' => User::EVENT_AFTER_LOGIN,
            'callback' => ['humhub\modules\employeeTraining\Events', 'onUserLogin']
        ]
    ],
];
