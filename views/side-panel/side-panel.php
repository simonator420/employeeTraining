<?php

use yii\helpers\Html;

?>

<div class="side-panel">
    <h3>Training Assignment</h3>
    <p class="training-message">Dear <b> <?= Html::encode($firstName) ?> </b>, you have been assigned a mandatory
        training. Please complete it at your earliest convenience.</p>
    <div class="text-center">
        <?= Html::a('Go to Training', ['/employeeTraining/role/employee'], ['class' => 'btn btn-primary']) ?>
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