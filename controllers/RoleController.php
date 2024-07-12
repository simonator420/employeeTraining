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

    // Fetches all users from the database and renders the 'user-info' view (displays user information).    
    public function actionUserInfo()
    {
        // Fetch all users from the database
        $users = User::find()->all();

        // Fetch the assigned_training status for each user
        foreach ($users as $user) {
            $assigned_training = Yii::$app->db->createCommand('SELECT assigned_training FROM profile WHERE user_id=:userId')
                ->bindValue(':userId', $user->id)
                ->queryScalar();
            $user->profile->assigned_training = $assigned_training;
        }

        $currentUser = Yii::$app->user;
        $userId = $currentUser->getId();
        $title = $currentUser->identity->profile->title;

        // Checks if the user isn't System Administrator
        if ($title != 'System Administration') {
            return $this->redirect(['site/access-denied']);
        }

        // Render the 'user-info' view and pass the users data to it
        return $this->render('user-info', [
            'users' => $users,
        ]);
    }

    // Function for displaying training screen after login
    // TODO rename the function so it fits all the employees
    public function actionDriver()
    {
        $user = Yii::$app->user;
        $userId = $user->getId();
        $title = $user->identity->profile->title;
        $assigned_training = Yii::$app->db->createCommand('SELECT assigned_training FROM profile WHERE user_id=:userId')
            ->bindValue(':userId', $userId)
            ->queryScalar();

        // Check if the user is logged in and if their title is not System Administrator
        if ($assigned_training === 1) {
            // Yii::info('User is Service Driver, rendering driver popup');
            return $this->render('driver', ['title' => $title]);
        }

        Yii::info('User does not have a training assigned, redirecting to access denied');
        // Redirect to access denied if the conditions are not met
        return $this->redirect(['site/access-denied']);
    }

    public function actionToggleTraining()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    
        $userId = \Yii::$app->request->post('id');
        $assignedTraining = \Yii::$app->request->post('assigned_training');
        $trainingAssignedTime = \Yii::$app->request->post('training_assigned_time');
    
        $user = User::findOne($userId);
        if ($user) {
            $user->profile->assigned_training = $assignedTraining;
            if ($assignedTraining) {
                $user->profile->training_assigned_time = $trainingAssignedTime;
            } else {
                $user->profile->training_assigned_time = null;
            }
    
            if ($user->profile->save()) {
                return ['success' => true];
            }
        }
        return ['success' => false];
    }
    

    public function actionCompleteTraining()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $userId = Yii::$app->user->id;
        $user = User::findOne($userId);
        if ($user) {
            $user->profile->assigned_training = 0;
            if ($user->profile->save()) {
                return ['success' => true];
            }
        }
        return ['success' => false];
    }

}