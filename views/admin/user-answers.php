<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $user common\models\User */
/* @var $trainings array */
/* @var $answers array */

$this->title = 'User Answers: ' . Html::encode($user->profile->firstname . ' ' . $user->profile->lastname);
$this->params['breadcrumbs'][] = ['label' => 'Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-answers-view">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="collapsible-container">
        <?php foreach ($trainings as $training): ?>
            <?php foreach ($training['instances'] as $instance): ?>
                <button class="collapsible" data-training-id="<?= Html::encode($training['training_id']) ?>">
                    Training ID: <?= Html::encode($training['training_id']) ?> - <?= Html::encode($instance['created_at']) ?>
                </button>
                <div class="content" style="display:none;">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Question</th>
                                <th>Answer</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($answers[$training['training_id']][$instance['created_at']])): ?>
                                <?php foreach ($answers[$training['training_id']][$instance['created_at']] as $answer): ?>
                                    <tr>
                                        <td><?= Html::encode($answer['question_text']) ?></td>
                                        <td><?= Html::encode($answer['answer']) ?></td>
                                        <td><?= Html::encode($answer['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3">No answers found for this training.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
</div>

<?php
$script = <<<JS
$(document).ready(function() {
    $('.collapsible').on('click', function() {
        var content = $(this).next('.content');

        // Close all other open contents
        $('.collapsible').not(this).removeClass('active');
        $('.content').not(content).slideUp();

        // Toggle the clicked collapsible
        $(this).toggleClass('active');
        content.slideToggle();
    });
});
JS;
$this->registerJs($script);
?>