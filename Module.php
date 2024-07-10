<?php

namespace humhub\modules\employeeTraining;

class Module extends \humhub\components\Module
{
    public function init()
    {
        parent::init();
        $this->registerAssets();
    }

    protected function registerAssets()
    {
        \Yii::$app->view->registerAssetBundle('humhub\modules\employeeTraining\assets\EmployeeTrainingAsset');
    }
}