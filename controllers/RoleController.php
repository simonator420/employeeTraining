<?php

namespace humhub\modules\employeeTraining\controllers;

use humhub\components\Controller;
use humhub\modules\user\models\User;
use Yii;

class RoleController extends Controller
{
    public function actionUserInfo()
    {
        $users = User::find()->all();

        return $this->render('user-info', [
            'users' => $users,
        ]);
    }

    // public function getRoles($user)
    // {
    //     $roles = [];
    //     foreach ($user->groups as $group) {
    //         $roles[] = $group->name;
    //     }

    //     return $roles;
    // }
}