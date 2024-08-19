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

        $currentUser = Yii::$app->user;

        $userRole = $currentUser->identity->profile->role;

        $userId = $currentUser->getId();

        // Check if the current logged-in user is an administrator
        if ($userRole == 'admin' || $userRole == 'team_leader') {
            // Add a new menu item for the administrator
            $menu->addItem([
                'label' => Yii::t('employeeTraining', 'Training'),
                'url' => Url::to(['/employeeTraining/role/admin']),
                'icon' => '<i class="fa fa-info"></i>',
                'sortOrder' => 100,
                'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'employeeTraining'), // Check if the current menu should be marked as an active
            ]);
        }

        $trainingRecord = Yii::$app->db->createCommand('
            SELECT * FROM user_training
            WHERE user_id = :userId AND assigned_training = 1
            ORDER BY training_assigned_time DESC
        ')
            ->bindValue(':userId', $userId)
            ->queryOne();
            
        if ($trainingRecord) {
            $assignedTrainingId = $trainingRecord['training_id'];
            $menu->addItem([
                'label' => '<span style="color: red;">' . Yii::t('employeeTraining', 'Training') . '</span>',
                'url' => Url::to(['/employeeTraining/role/employee', 'id' => $assignedTrainingId]),
                'icon' => '<i class="fa fa-arrow-circle-o-up" style="color: red;"></i>',
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

        // Fetch the latest training record for the user where assigned_training = 1
        $trainingRecord = Yii::$app->db->createCommand('
            SELECT * FROM user_training
            WHERE user_id = :userId AND assigned_training = 1
            ORDER BY training_assigned_time DESC
            LIMIT 1
        ')
            ->bindValue(':userId', $userId)
            ->queryOne();

        // Log the user information and training status
        Yii::info('User: ' . $username . ' with id: ' . $userId . ' and title: ' . $title);

        if ($trainingRecord) {
            // Extract relevant information from the training record
            $assignedTrainingId = $trainingRecord['training_id'];
            $trainingAssignedTime = $trainingRecord['training_assigned_time'];
            $trainingDeadline = $trainingRecord['deadline'];
            $assignedTrainingStatus = $trainingRecord['assigned_training'];

            // Check if the training has been assigned and is valid
            if ($assignedTrainingStatus === 1) {
                Yii::$app->response->redirect(Url::to(['/employeeTraining/role/employee', 'id' => $assignedTrainingId]));
                return;
            }
        } else {
            // Log if no relevant training record is found
            Yii::info('No assigned training record found for user with id: ' . $userId);
        }
    }




    // Displays the side panel on users screen if he has training assigned
    public static function onDashboardSidebarInit(Event $event)
    {
        // Retrieve the current logged-in user's information
        $user = Yii::$app->user;
        $userId = $user->getId();
        // Retrieve the assigned_training status from the profile table
        // $assigned_training = Yii::$app->db->createCommand('SELECT assigned_training FROM profile WHERE user_id=:userId')
        //     ->bindValue(':userId', $userId)
        //     ->queryScalar();

        // Retrieve the training_assigned_time time from the profile table
        $training_assigned_time = Yii::$app->db->createCommand('SELECT training_assigned_time FROM profile WHERE user_id=:userId')
            ->bindValue(':userId', $userId)
            ->queryScalar();

        // Retrieve the training_complete_time time from the profile table
        $training_complete_time = Yii::$app->db->createCommand('SELECT training_complete_time FROM profile WHERE user_id=:userId')
            ->bindValue(':userId', $userId)
            ->queryScalar();

        // Checking if all the conditions 
        // if ($training_assigned_time && strtotime($training_assigned_time) <= time() && !$training_complete_time) {
        //     // Update the assigned_training status to 1
        //     Yii::$app->db->createCommand()
        //         ->update('profile', ['assigned_training' => 1], 'user_id = :userId')
        //         ->bindValue(':userId', $userId)
        //         ->execute();

        //     // Refresh assigned_training status
        //     $assigned_training = 1;
        // }

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
