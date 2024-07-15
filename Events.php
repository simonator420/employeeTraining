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
    // Displays the Employee Training Page in the top menu
    public static function onTopMenuInit(Event $event)
    {
        // Retrieve the menu object from the event
        $menu = $event->sender;

        // Check if the current logged-in user is an administrator
        if (Yii::$app->user->isAdmin()) {
            // Add a new menu item for the administrator
            $menu->addItem([
                'label' => 'Training', // The label of the menu item
                'url' => Url::to(['/employeeTraining/role/user-info']), // The url of the menu item
                'icon' => '<i class="fa fa-info"></i>', // The icon of the menu item
                'sortOrder' => 100, // The order in which the menu item should appear
                'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'employeeTraining'), // Check if the current menu should be marked as an active
            ]);
        }
    }

    // Displays the Employee Training page if the user has training assigned
    public static function onAfterLogin(Event $event)
    {
        // Retrieve the current logged-in user's information
        $user = Yii::$app->user;
        $username = $user->identity->username;
        $title = $user->identity->profile->title;
        $userId = $user->getId();
        // Retrieve the assigned_training status from the profile table
        $assigned_training = Yii::$app->db->createCommand('SELECT assigned_training FROM profile WHERE user_id=:userId')
            ->bindValue(':userId', $userId) // Replaces userID in the SQL command with the actual user ID
            ->queryScalar(); // Executes the SQL command and returns a single scalar value (the value of the assigned_training for the current user)

        Yii::info('User : ' . $username . ' with id: ' . $userId . ' and title: ' . $title . ' and his training status is: ' . $assigned_training);

        // Retrive the userSpaces in case I want to display the training site based on the spaces they are in
        $userSpaces = $user->identity->getSpaces()->all();

        // Check if the user has an assigned training
        if ($assigned_training === 1) {
            // Redirects to the Employee Training Page
            Yii::$app->response->redirect(Url::to(['/employeeTraining/role/driver']));
        }
    }

    // Displays the side panel on users screen if he has training assigned
    public static function onDashboardSidebarInit(Event $event)
    {
        // Retrieve the current logged-in user's information
        $user = Yii::$app->user;
        $userId = $user->getId();
        // Retrieve the assigned_training status from the profile table
        $assigned_training = Yii::$app->db->createCommand('SELECT assigned_training FROM profile WHERE user_id=:userId')
            ->bindValue(':userId', $userId) // Replaces userID in the SQL command with the actual user ID
            ->queryScalar(); // Executes the SQL command and returns a single scalar value (the value of the assigned_training for the current user)

        // Check if the user has an assigned training
        if ($assigned_training == 1) {
            // Retrieve the first name from the profile of currently logged-in user
            $firstName = $user->identity->profile->firstname;
            // Adds the SidePanel widget to the sidebar, specifies the class name of the widget to be added, passes the user's first name to the widget, specifies the order in which the widget should appear in the sidebar
            $event->sender->addWidget(\humhub\modules\employeeTraining\widgets\SidePanel::className(), ['firstName' => $firstName], ['sortOrder' => 10]);
        }
    }
}
