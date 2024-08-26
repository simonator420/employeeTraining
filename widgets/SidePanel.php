<?php

namespace humhub\modules\employeeTraining\widgets;

use yii\base\Widget;


/**
 * SidePanel widget for displaying a custom side panel in the Employee Training module.
 *
 * This widget renders a side panel that can be used to display information 
 * related to the employee's training. It takes the employee's first name as 
 * a parameter and passes it to the view that generates the side panel.
 */
class SidePanel extends Widget
{
    
    /**
     * @var string The first name of the user to be displayed in the side panel.
     */
    public $firstName;

    /**
     * Runs the widget and renders the side panel view.
     *
     * This method is called automatically when the widget is executed. 
     * It renders the side panel using the specified view file and passes 
     * the first name of the user as a parameter to the view.
     *
     * @return string The rendered side panel HTML.
     * 
     */
    public function run()
    {
        return $this->render('@humhub/modules/employeeTraining/views/side-panel/side-panel', ['firstName' => $this->firstName]);
    }
}