<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user common\models\User */
/* @var $answers array */

$this->title = 'User Answers: ' . Html::encode($user->profile->firstname . ' ' . $user->profile->lastname);
$this->params['breadcrumbs'][] = ['label' => 'Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-answers-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Question</th>
                <th>Answer</th>
                <th>Training ID</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($answers as $answer): ?>
                <tr>
                    <td><?= Html::encode($answer['question_text']) ?></td>
                    <td><?= Html::encode($answer['answer']) ?></td>
                    <td><?= Html::encode($answer['training_id']) ?></td>
                    <td><?= Html::encode($answer['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</div>
