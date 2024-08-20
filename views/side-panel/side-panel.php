<?php

use yii\helpers\Html;

?>

<div class="side-panel">
    <?php
    $trainingId = Yii::$app->db->createCommand('
        SELECT training_id
        FROM user_training 
        WHERE user_id = :userId AND assigned_training = 1;
        ')
        ->bindValue(':userId', $user = Yii::$app->user->id, \PDO::PARAM_INT)
        ->queryScalar();

    $deadline = Yii::$app->db->createCommand('
        SELECT deadline
        FROM user_training
        WHERE user_id = :userId AND training_id = :trainingId AND assigned_training = 1;
        ')
        ->bindValue(':userId', Yii::$app->user->id)
        ->bindValue(':trainingId', $trainingId)
        ->queryScalar();

    $completionDeadline = date('j. n. Y', strtotime($deadline));
    ?>
    <h3> <?= Yii::t('employeeTraining', "Training Assignment") ?></h3>
    <p class="training-message">Dear <b> <?= Html::encode($firstName) ?> </b>, you have been assigned a mandatory
        training. Please complete it at your earliest convenience.</p>
    <p class="deadline-message">This training should be completed by <b><?= Html::encode($completionDeadline) ?></b>.
    </p>
    <div class="text-center">
        <?= Html::a('Go to Training', ['/employeeTraining/role/employee', 'id' => $trainingId], ['class' => 'btn btn-primary']) ?>
    </div>
</div>

<style>
    .side-panel {
        background-color: #fff;
        padding: 20px;
        margin-bottom: 15px;
        border: 3px solid #f08080;
        border-radius: 4px;
        text-align: center;
    }

    .side-panel h3 {
        margin-top: 0;
        font-size: 1.5em;
    }

    .side-panel .training-message {
        margin-bottom: 20px;
        color: #d9534f;
        font-size: 1.2em;
    }

    .side-panel .deadline-message{
        margin-bottom: 20px;
        color: rgb(85, 85, 85);
        font-size: 1.1em;
    }

    .side-panel .btn {
        background-color: #d9534f;
        color: #fff;
        text-decoration: none;
        padding: 10px 20px;
        border-radius: 4px;
        display: inline-block;
        transition: 0.3s;
    }

    .side-panel .btn:hover {
        background-color: darkslategray;
        text-decoration: none;
        cursor: pointer;
    }
</style>