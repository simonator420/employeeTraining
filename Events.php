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
    // This method adds a "User Info" item to the top menu.
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
        $title = $user->identity->profile->title;
        $userId = $user->getId();
        $assigned_training = Yii::$app->db->createCommand('SELECT assigned_training FROM profile WHERE user_id=:userId')
            ->bindValue(':userId', $userId)
            ->queryScalar();

        Yii::info('User : ' . $username . ' with id: ' . $userId . ' and title: ' . $title . ' and his training status is: ' . $assigned_training);

        // Retrive the userSpaces in case I want to display the training site based on the spaces they are in
        $userSpaces = $user->identity->getSpaces()->all();

        if ($assigned_training === 1) {
            Yii::$app->response->redirect(Url::to(['/employeeTraining/role/driver']));
        }

        // if (!empty($userSpaces)) {
        //     foreach ($userSpaces as $space) {
        //         Yii::info($username . ' is a member of space: ' . $space->name);
        //     }
        // }
    }

    // Displays the side panel on users screen if he has training assigned
    public static function onDashboardSidebarInit(Event $event)
    {
        $user = Yii::$app->user;
        $userId = $user->getId();
        $assigned_training = Yii::$app->db->createCommand('SELECT assigned_training FROM profile WHERE user_id=:userId')
            ->bindValue(':userId', $userId)
            ->queryScalar();

        // change this to not title but if training_assigned == true
        if ($assigned_training == 1) {
            $firstName = $user->identity->profile->firstname;
            $event->sender->addWidget(\humhub\modules\employeeTraining\widgets\SidePanel::className(), ['firstName' => $firstName], ['sortOrder' => 10]);
        }
    }
}
