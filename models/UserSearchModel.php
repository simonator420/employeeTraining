<?php

namespace humhub\modules\employeeTraining\models;

use yii\base\Model;

class UserSearchModel extends Model
{
    public $query;

    public function rules()
    {
        return [
            [['query'], 'safe'],
        ];
    }
}
