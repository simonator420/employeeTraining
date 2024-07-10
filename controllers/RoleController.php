<?php

namespace humhub\modules\employeeTraining\controllers;

use humhub\components\Controller;
use humhub\modules\user\models\User;
use Yii;

/**
 * This controller handles actions related to roles within the Employee Training module.
 */
class RoleController extends Controller
{
    /**
     * This action fetches all users from the database and renders the 'user-info' view (displays user information).
     */
    public function actionUserInfo()
    {
        // Fetch all users from the database
        $users = User::find()->all();

        // Render the 'user-info' view and pass the users data to it
        return $this->render('user-info', [
            'users' => $users,
        ]);
    }
}