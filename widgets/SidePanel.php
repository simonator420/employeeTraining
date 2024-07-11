<?php

namespace humhub\modules\employeeTraining\widgets;

use yii\base\Widget;

class SidePanel extends Widget
{
    public function run()
    {
        return $this->render('sidePanel');
    }
}
