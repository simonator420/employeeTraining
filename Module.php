<?php

namespace humhub\modules\employeeTraining;

/**
 * This class represents the Employee Training module in HumHub.
 */
class Module extends \humhub\components\Module
{

    public $resoucePath = 'resources';

    /**
     * This method is called when the module is being initialized and registers necessary assets.
     */
    public function init()
    {
        parent::init();
        $this->registerAssets();
    }

    /**
     * This method registers the EmployeeTrainingAsset bundle with the Yii view component.
     */
    protected function registerAssets()
    {
        \Yii::$app->view->registerAssetBundle('humhub\modules\employeeTraining\assets\EmployeeTrainingAsset');
    }
}