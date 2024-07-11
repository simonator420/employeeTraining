<?php

namespace humhub\modules\employeeTraining;

use humhub\widgets\TopMenu;
use yii\base\Event;
use yii\helpers\Url;
use Yii;


/**
 * Events class.
 * This class contains event handlers for the Employee Training module.
 */
class Events
{
    /**
     * This method adds a "User Info" item to the top menu.
     */
    public static function onTopMenuInit(Event $event)
    {
        $menu = $event->sender;

        if (Yii::$app->user->isAdmin()) {
            $menu->addItem([
                'label' => 'User Info',
                'url' => Url::to(['/employeeTraining/role/user-info']),
                'icon' => '<i class="fa fa-info"></i>',
                'sortOrder' => 100,
                'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'employeeTraining'),
            ]);
        }
    }

    public static function onAfterLogin(Event $event)
    {
        $user = Yii::$app->user;
        $username = $user->identity->username;
        $title =$user->identity->profile->title;

        Yii::info('User : ' . $username . ' with id: ' . $user->getId() . ' and title: ' . $title);

        $userSpaces = $user->identity->getSpaces()->all();

        if ($title === 'Service Driver' || $title === 'Accountant') {
            Yii::$app->response->redirect(Url::to(['/employeeTraining/role/driver']));
        }

        // if (!empty($userSpaces)) {
        //     foreach ($userSpaces as $space) {
        //         Yii::info($username . ' is a member of space: ' . $space->name);
        //     }
        // }
    }

    public static function onDashboardSidebarInit($event)
    {
        $event->sender->addWidget(\humhub\modules\employeeTraining\widgets\SidePanel::className(), [], ['sortOrder' => 10]);
    }
}
