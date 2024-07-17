<?php

namespace humhub\modules\employeeTraining\controllers;

use humhub\components\Controller;
use humhub\modules\user\models\Profile;
use humhub\modules\user\models\User;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use Yii;


// Controller for handling actions related to roles within the Employee Training module.
class RoleController extends Controller
{

    // Function for handling the request to display the Employee Training Overview page
    public function actionAdmin()
    {
        // Retrieve all users from the database
        $users = User::find()->all();

        // For each user retrieve the assigned_training status from the profile table
        foreach ($users as $user) {
            $assigned_training = Yii::$app->db->createCommand('SELECT assigned_training FROM profile WHERE user_id=:userId')
                ->bindValue(':userId', $user->id) // Replaces userID in the SQL command with the actual user ID
                ->queryScalar(); // Executes the SQL command and returns a single scalar value (the value of the assigned_training for the current user)

            // Sets the assigned_training property of the user's profile to the retrieved value
            $user->profile->assigned_training = $assigned_training;
        }

        // Retrieve the current logged-in user's information
        $currentUser = Yii::$app->user;
        $title = $currentUser->identity->profile->title;

        // Checks if the user isn't admin
        if (!$currentUser->isAdmin()) {
            // Redirect the user to an access denied page if he's not admin
            return $this->redirect(['site/access-denied']);
        }

        // Retrieve all unique titles from the profile table
        $titles = Yii::$app->db->createCommand('SELECT DISTINCT title FROM profile')
            ->queryColumn();

        sort($titles);

        // Retrieve all unique storage locations from the profile table
        $storage_locations = Yii::$app->db->createCommand('SELECT DISTINCT storage_location FROM profile')
        ->queryColumn();

        sort($storage_locations);

        // Render the 'user-info' view and pass the users data to it
        return $this->render('admin', [
            'users' => $users,
            'titles' => $titles,
            'storage_locations' => $storage_locations,
        ]);
    }

    // Function for handling the request to display Employee Training Overview page
    public function actionEmployee()
    {
        // Retrieve the current logged-in user's information
        $user = Yii::$app->user;
        $userId = $user->getId();
        $title = $user->identity->profile->title;
        $firstName = $user->identity->profile->firstname;
        // Retrieve the assigned_training status from the profile table
        $assigned_training = Yii::$app->db->createCommand('SELECT assigned_training FROM profile WHERE user_id=:userId')
            ->bindValue(':userId', $userId) // Replaces userID in the SQL command with the actual user ID
            ->queryScalar(); // Executes the SQL command and returns a single scalar value (the value of the assigned_training for the current user)

        // Check if the user has an assigned training
        if ($assigned_training === 1) {
            // Render the driver view
            return $this->render('employee', ['title' => $title, 'firstName' => $firstName]);
        }

        // Redirect to access denied if the conditions are not met
        return $this->redirect(['site/access-denied']);
    }

    // Handling the AJAX request to toggle the training assignment for a user
    public function actionToggleTraining()
    {
        // Setting the response format to JSON
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Retrieving the data SENT via the POST request
        $userId = \Yii::$app->request->post('id');
        $assignedTraining = \Yii::$app->request->post('assigned_training');
        $trainingAssignedTime = \Yii::$app->request->post('training_assigned_time');

        // Finding the user in the database by the ID
        $user = User::findOne($userId);
        // Checking if the user exists
        if ($user) {
            // Setting the assigned_training status
            $user->profile->assigned_training = $assignedTraining;
            // Setting the time for training_assigned_time
            $user->profile->training_assigned_time = $trainingAssignedTime;

            // Clear training_complete_time if assigned_training is set to 1
            if ($assignedTraining) {
                $user->profile->training_complete_time = null;
            }

            // Attempts to save the updated user profile
            if ($user->profile->save()) {
                // If the profile is successfully saved
                return ['success' => true];
            }
        }
        // If the user is not found or the profile fails
        return ['success' => false];
    }


    // Next two functions not working due to missing cron jobs

    // public function actionAssignTraining()
    // {
    //     \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

    //     $rawTime = \Yii::$app->request->post('selected_time');
    //     $selectedTitles = \Yii::$app->request->post('selected_titles');
    //     $selectedLocations = \Yii::$app->request->post('selected_locations');
    //     $selectedTime = date('Y-m-d H:i:s', strtotime($rawTime));

    //     $users = User::find()
    //         ->joinWith('profile')
    //         ->andFilterWhere(['profile.title' => $selectedTitles])
    //         ->andFilterWhere(['profile.storage_location' => $selectedLocations])
    //         ->all();

    //     foreach ($users as $user) {
    //         // Update training_assigned_time and reset assigned_training if time is now
    //         $user->profile->training_assigned_time = $selectedTime;
    //         $user->profile->assigned_training = 0;

    //         if (!$user->profile->save()) {
    //             return ['success' => false];
    //         }
    //     }

    //     return ['success' => true];
    // }

    // public function actionCheckTrainingAssignments()
    // {
    //     $currentTime = new \DateTime();
    //     $users = User::find()
    //         ->joinWith('profile')
    //         ->where(['assigned_training' => 0])
    //         ->andWhere(['<=', 'training_assigned_time', $currentTime->format('Y-m-d H:i:s')])
    //         ->all();

    //     foreach ($users as $user) {
    //         $user->profile->assigned_training = 1;
    //         if (!$user->profile->save()) {
    //             return ['success' => false];
    //         }
    //     }

    //     return ['success' => true];
    // }

    // Handling the AJAX request to mark the training as complete for the current user
    public function actionCompleteTraining()
    {
        // Setting the response format to JSON
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Retrieve the id of the currently logged-in user
        $userId = \Yii::$app->user->id;

        // Find the user by ID
        $user = User::findOne($userId);

        // Get the request data
        $requestData = Yii::$app->request->post();
        // Yii::info('Complete Training request data: ' . var_export($requestData, true));
        $accountingOne = isset($requestData['accounting_one']) ? $requestData['accounting_one'] : null;
        Yii::info('Accounting One: ' . $accountingOne);

        // Check if the user exists
        if ($user) {

            // Update the user's training_complete_time attribute with current time
            $user->profile->training_complete_time = new \yii\db\Expression('NOW()');

            // Update the user's assigned_training attribute to 0
            $user->profile->assigned_training = 0;

            // Save the users profile and return success if saved
            if ($user->profile->save()) {
                return ['success' => true];
            }
        }
        // Return failure if user not found or save failed
        return ['success' => false];
    }
}