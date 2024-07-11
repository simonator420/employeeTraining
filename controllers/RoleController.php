<?php

namespace humhub\modules\employeeTraining\controllers;

use humhub\components\Controller;
use humhub\modules\user\models\User;
use Yii;


// Controller for handling actions related to roles within the Employee Training module.
class RoleController extends Controller
{
    
    // Fetches all users from the database and renders the 'user-info' view (displays user information).    
    public function actionUserInfo()
    {
        // Fetch all users from the database
        $users = User::find()->all();

        // Checks if the user isn't System Administrator
        if (Yii::$app->user->identity->profile->title != "System Administration") {
            return $this->redirect(['site/access-denied']);
        }

        // Render the 'user-info' view and pass the users data to it
        return $this->render('user-info', [
            'users' => $users,
        ]);
    }

    // Function for displaying training screen after login
    // TODO rename the function so it suits all the employees
    public function actionDriver()
    {
        $user = Yii::$app->user;
        $title = $user->identity->profile->title;

        // Check if the user is logged in and if their title is not System Administrator
        if (!$user->isGuest && $title != 'System Administration') {
            Yii::info('User is Service Driver, rendering driver popup');
            return $this->render('driver', [
                'title' => $title
            ]);
        }

        Yii::info('User is not a Service Driver, redirecting to access denied');
        // Redirect to access denied if the conditions are not met
        return $this->redirect(['site/access-denied']);
    }
}