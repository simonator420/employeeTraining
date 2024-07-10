<?php

namespace humhub\modules\employeeTraining;

use humhub\widgets\TopMenu;
use yii\base\Event;
use yii\helpers\Url;
use yii\log\Logger;
use Yii;

/**
 * Events class.
 * This class contains event handlers for the Employee Training module.
 */
class Events
{
    /**
     * This method adds a "User Info" item to the top menu.
     */
    public static function onTopMenuInit(Event $event)
    {
        /** @var \humhub\widgets\TopMenu $menu */
        $menu = $event->sender;

        if (Yii::$app->user->isAdmin()) {
            $menu->addItem([
                'label' => 'User Info',
                'url' => Url::to(['/employeeTraining/role/user-info']),
                'icon' => '<i class="fa fa-info"></i>',
                'sortOrder' => 100,
                'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'employeeTraining'),
            ]);
            Yii::info('message log');
        }
    }

    public static function onUserLogin(Event $event)
    {
        $user = $event->identity;

        // Add logging to verify the function is called and the user's profile title
        Yii::info('onUserLogin called for user: ', __METHOD__);

        // Check if the user has the title 'Driver'
        if ($user->profile->title === 'Driver') {
            // Set flash message to show driver popup
            Yii::$app->session->setFlash('showDriverPopup', true);
            
            // Log a message to confirm the function is being executed
            // Yii::debug('Driver user logged in: ' . $user->username, __METHOD__);
        }
    }
}