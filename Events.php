<?php

namespace humhub\modules\employeeTraining;

use humhub\widgets\TopMenu;
use yii\base\Event;
use yii\helpers\Url;
use Yii;

class Events
{
    public static function onTopMenuInit(Event $event)
    {
        /** @var \humhub\widgets\TopMenu $menu */
        $menu = $event->sender;

        $menu->addItem([
            'label' => 'User Info',
            'url' => Url::to(['/employeeTraining/role/user-info']),
            'icon' => '<i class="fa fa-info"></i>',
            'sortOrder' => 100,
            'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'employeeTraining'),
        ]);
    }
}