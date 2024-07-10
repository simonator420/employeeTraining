<?php

use yii\helpers\Html;

?>

<!-- View for displaying informations about the users. -->
<div class="user-info-container">
    <div class="user-info-card">
        <h1>User Info</h1>
        <?php foreach ($users as $user): ?>
            <p>
                <strong>User:</strong> <?= Html::encode($user->username) ?> (ID: <?= $user->id ?>)<br>
                <strong>Title:</strong> <?= Html::encode($user->profile->title) ?> <br>
                <strong>Roles:</strong>
                <?php
                $groups = $user->getGroups()->all();
                $groupNames = array_map(function ($group) {
                    return $group->name;
                }, $groups);
                echo Html::encode(implode(', ', $groupNames));
                ?>
            </p>
            <hr>
        <?php endforeach; ?>
    </div>
</div>