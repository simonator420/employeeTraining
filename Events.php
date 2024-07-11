<?php

namespace humhub\modules\employeeTraining;

use humhub\widgets\TopMenu;
use yii\base\Event;
use yii\helpers\Url;
use Yii;

use humhub\components\behaviors\GUID;
use humhub\libs\UUIDValidator;
use humhub\modules\admin\Module as AdminModule;
use humhub\modules\admin\permissions\ManageGroups;
use humhub\modules\admin\permissions\ManageSpaces;
use humhub\modules\admin\permissions\ManageUsers;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentContainerSettingsManager;
use humhub\modules\content\models\Content;
use humhub\modules\friendship\models\Friendship;
use humhub\modules\space\helpers\MembershipHelper;
use humhub\modules\space\models\Space;
use humhub\modules\user\authclient\Password as PasswordAuth;
use humhub\modules\user\behaviors\Followable;
use humhub\modules\user\behaviors\ProfileController;
use humhub\modules\user\components\ActiveQueryUser;
use humhub\modules\user\components\PermissionManager;
use humhub\modules\user\events\UserEvent;
use humhub\modules\user\helpers\AuthHelper;
use humhub\modules\user\Module;
use humhub\modules\user\services\PasswordRecoveryService;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\web\IdentityInterface;


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


        // Log message to PHP log file
        Yii::info('Admin check in Employee Training module onTopMenuInit.');



        if (Yii::$app->user->isAdmin()) {
            $menu->addItem([
                'label' => 'User Info',
                'url' => Url::to(['/employeeTraining/role/user-info']),
                'icon' => '<i class="fa fa-info"></i>',
                'sortOrder' => 100,
                'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'employeeTraining'),
            ]);
        }
    }

    public static function onAfterLogin(Event $event)
    {
        $user = Yii::$app->user;
        $username = $user->identity->username;
        $title =$user->identity->profile->title;
        

        Yii::info('User : ' . $username . ' with id: ' . $user->getId() . ' and title: ' . $title);

        $userSpaces = $user->identity->getSpaces()->all();

        if (!empty($userSpaces)) {
            foreach ($userSpaces as $space) {
                Yii::info($username . ' is a member of space: ' . $space->name);
            }
        }

        // // Check if the user has the title 'Driver'
        // if ($user->profile->title === 'Driver') {
        //     // Set flash message to show driver popup
        //     Yii::$app->session->setFlash('showDriverPopup', true);
            
        //     // Log a message to confirm the function is being executed
        //     Yii::debug('Driver user logged in: ' . $user->username, __METHOD__);
        // }
    }
}