<?php

namespace humhub\modules\employeeTraining\assets;

use humhub\modules\fcmPush\module;
use humuhub\modules\fcmPush\services\DriverService;
use Yii;
use yii\web\AssetBundle;


/**
 * This class defines the assets (CSS, JS, images, etc.) used in the Employee Training module.
 */
class EmployeeTrainingAsset extends AssetBundle
{
    public $defer = true;
     // The directory that contains the asset files
    public $sourcePath = '@employeeTraining/resources';
    public $css = [
        'css/styles.css'
    ];
    public $js = [
        'js/script.js'
    ];
}
