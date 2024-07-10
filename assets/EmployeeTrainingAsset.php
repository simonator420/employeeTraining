<?php

namespace humhub\modules\employeeTraining\assets;

use yii\web\AssetBundle;


/**
 * This class defines the assets (CSS, JS, images, etc.) used in the Employee Training module.
 */
class EmployeeTrainingAsset extends AssetBundle
{
     // The directory that contains the asset files
    public $sourcePath = '@employeeTraining/resources';
    public $css = [
        'css/styles.css',
    ];
    public $js = [
        'js/script.js'
    ];  
}
