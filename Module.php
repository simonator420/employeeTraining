<?php

namespace humhub\modules\employeeTraining;

use Yii;

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
        $this->registerTranslations();
    }

    /**
     * This method registers the EmployeeTrainingAsset bundle with the Yii view component.
     */
    protected function registerAssets()
    {
        \Yii::$app->view->registerAssetBundle('humhub\modules\employeeTraining\assets\EmployeeTrainingAsset');
    }

    /** 
     *  Method for registering the translations
     */
    
    protected function registerTranslations()
    {
        Yii::$app->i18n->translations['employeeTraining*'] = [
            'class' => 'yii\i18n\PhpMessageSource',
            'basePath' => '@humhub/modules/employeeTraining/messages',
            'fileMap' => [
                'employeeTraining' => 'employeeTraining.php',
            ],
        ];
    }
}