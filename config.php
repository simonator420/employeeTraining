<?php

use humhub\widgets\TopMenu;

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
    ],
];