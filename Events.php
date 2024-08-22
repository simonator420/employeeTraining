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
     * Displays the Employee Training page in the top menu for Admin or Team Leader.
     *
     * This function adds a menu item to the top menu for administrators and team leaders,
     * and dynamically adds items for any training assigned to the current user.
     *
     * @param Event $event The event object containing the sender of the event.
     */
    public static function onTopMenuInit(Event $event)
    {
        // Retrieve the menu object from the event
        $menu = $event->sender;

        $currentUser = Yii::$app->user;

        $userRole = $currentUser->identity->profile->role;

        $userId = $currentUser->getId();

        // Check if the current logged-in user is an administrator or team leader.
        if ($userRole == 'admin' || $userRole == 'team_leader') {
            // Add a new menu item for the administrator
            $menu->addItem([
                'label' => Yii::t('employeeTraining', 'Training'),
                'url' => Url::to(['/employeeTraining/role/admin']),
                'icon' => '<i class="fa fa-pencil-square-o"></i>',
                'sortOrder' => 100,
                'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'employeeTraining'), // Check if the current menu should be marked as an active
            ]);
        }

        // Fetch the training records assigned to the current user.
        $trainingRecords = Yii::$app->db->createCommand('
            SELECT * FROM user_training
            WHERE user_id = :userId AND assigned_training = 1
            ORDER BY training_assigned_time DESC
        ')
            ->bindValue(':userId', $userId)
            ->queryAll();

        // Loop through each assigned training record and add it to the menu.
        foreach ($trainingRecords as $trainingRecord) {
            $assignedTrainingId = $trainingRecord['training_id'];
            
            // Retrieve the name of the assigned training.
            $trainingName = Yii::$app->db->createCommand('
                SELECT name
                FROM training
                WHERE id = :trainingId
            ')
            ->bindValue(':trainingId', $assignedTrainingId)
            ->queryScalar();
            
            // Add a menu item for each assigned training.
            $menu->addItem([
                'label' => '<span style="color: red;">' . $trainingName . '</span>',
                'url' => Url::to(['/employeeTraining/role/employee', 'id' => $assignedTrainingId]),
                'icon' => '<i class="fa fa-pencil-square-o" style="color: red;"></i>',
                'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'employeeTraining'), // Check if the current menu should be marked as an active
            ]);
        }
    }

    /**
     * Redirects users with assigned training to the Employee Training page after login.
     *
     * This function checks if the user has any assigned training after logging in,
     * and redirects them to the Employee Training page if they do.
     *
     * @param Event $event The event object containing the user who logged in.
     */
    public static function onAfterLogin(Event $event)
    {
        // Retrieve the current logged-in user's information
        $user = Yii::$app->user;
        $userId = $user->getId();

        // Fetch the latest training record for the user where assigned_training = 1
        $trainingRecord = Yii::$app->db->createCommand('
            SELECT * FROM user_training
            WHERE user_id = :userId AND assigned_training = 1
            ORDER BY training_assigned_time DESC
            LIMIT 1
        ')
            ->bindValue(':userId', $userId)
            ->queryOne();

        // If a relevant training record is found, redirect the user to the training page.            
        if ($trainingRecord) {
            $assignedTrainingId = $trainingRecord['training_id'];
            $assignedTrainingStatus = $trainingRecord['assigned_training'];

            if ($assignedTrainingStatus === 1) {
                Yii::$app->response->redirect(Url::to(['/employeeTraining/role/employee', 'id' => $assignedTrainingId]));
                return;
            }
        } else {
            // Log if no relevant training record is found
            Yii::info('No assigned training record found for user with id: ' . $userId);
        }
    }




    /**
     * Displays the Employee Training side panel on the dashboard if the user has assigned training.
     *
     * This function checks if the user has any assigned training and adds a widget
     * to the dashboard sidebar if they do.
     *
     * @param Event $event The event object containing the sender of the event.
     */
    public static function onDashboardSidebarInit(Event $event)
    {
        // Retrieve the current logged-in user's information
        $user = Yii::$app->user;
        $userId = $user->getId();

        $assigned_training = Yii::$app->db->createCommand('SELECT assigned_training FROM user_training WHERE user_id=:userId AND assigned_training = 1')
            ->bindValue(':userId', $userId)
            ->queryScalar();

        // Check if the user has an assigned training
        if ($assigned_training) {
            // Retrieve the first name from the profile of currently logged-in user
            $firstName = $user->identity->profile->firstname;
            // Adds the SidePanel widget to the sidebar, specifies the class name of the widget to be added, passes the user's first name to the widget, specifies the order in which the widget should appear in the sidebar
            $event->sender->addWidget(\humhub\modules\employeeTraining\widgets\SidePanel::className(), ['firstName' => $firstName], ['sortOrder' => 10]);
        }
    }
}
