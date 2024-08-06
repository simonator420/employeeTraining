<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $user common\models\User */
/* @var $trainings array */
/* @var $answers array */

$this->title = 'Training Answers - ' . Html::encode($user->profile->firstname . ' ' . $user->profile->lastname);
$this->params['breadcrumbs'][] = ['label' => 'Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<!-- TODO Display the items in collapsibles better and not like table -->
<div class="user-answers-container">
    <div class="user-answers-card">
        <h1><?= Html::encode($this->title) ?></h1>
        <br>
        <div class="collapsible-container">
            <?php foreach ($trainings as $training): ?>
                <?php foreach ($training['instances'] as $instance): ?>
                    <button class="collapsible" data-training-id="<?= Html::encode($training['training_id']) ?>">
                        <b><?= Html::encode($training['training_name']) ?></b> - <?= Html::encode($instance['created_at']) ?>
                    </button>
                    <div class="content" style="display:none;">
                        <?php if (!empty($answers[$training['training_id']][$instance['created_at']])): ?>
                            <?php foreach ($answers[$training['training_id']][$instance['created_at']] as $answer): ?>
                                <div class="question-answer-pair">
                                    <p><b>Question:</b> <?= Html::encode($answer['question_text']) ?></p>
                                    <?php Yii::warning("Tohle je questionId: " . $answer['question_id'] . " a tohle je question_text: " . $answer['question_text'], __METHOD__); ?>

                                    <?php
                                    $questionType = Yii::$app->db->createCommand('
                                        SELECT type 
                                        FROM training_questions 
                                        WHERE id = :question_id
                                        ')
                                        ->bindValue(':question_id', $answer['question_id'])
                                        ->queryScalar();
                                    ?>


                                    <!-- TODO spravne is correct podle atributu a to stejne u ne multiple choice -->
                                    <!-- <?php Yii::warning("Tohle je id: " . $answer['id'] . " a tohle je question_id " . $answer['question_id']) ?> -->
                                    <?php if ($questionType === 'multiple_choice'): ?>
                                        <?php
                                        Yii::warning("Tohle je id: " . $answer['id'] . " a tohle je question_id " . $answer['question_id']);
                                        $multipleAnswers = explode(', ', $answer['answer']);
                                        foreach ($multipleAnswers as $index => $singleAnswer):
                                            // Fetch is_correct for each answer
                                            $isCorrect = Yii::$app->db->createCommand('
                                                SELECT is_correct 
                                                FROM training_multiple_choice_answers 
                                                WHERE question_id = :question_id AND id = :answer_id
                                            ')
                                                ->bindValue(':question_id', $answer['question_id'])
                                                ->bindValue(':answer_id', $answer['id'])
                                                ->queryScalar();
                                            ?>
                                            <?php Yii::warning("Tohle je question_id: " . $answer['question_id']) ?>
                                            <?php Yii::warning("Tohle je id: " . $singleAnswer) ?>
                                            <p><b>Answer <?= $index + 1 ?>:</b> <?= Html::encode($singleAnswer) ?></p>
                                            <div class="evaluation">
                                                <label style="color: green;">
                                                    <?= Html::checkbox('evaluation[' . $answer['id'] . '][' . $index . '][correct]', $isCorrect == 1) ?>
                                                    Correct
                                                </label>
                                                <label style="color: red;">
                                                    <?= Html::checkbox('evaluation[' . $answer['id'] . '][' . $index . '][wrong]', $isCorrect == 0) ?>
                                                    Wrong
                                                </label>
                                                <label style="color: gray;">
                                                    <?= Html::checkbox('evaluation[' . $answer['id'] . '][' . $index . '][not_scored]', false) ?>
                                                    Not Scored
                                                </label>
                                            </div>
                                        <?php endforeach; ?>




                                    <?php else: ?>
                                        <p><b>Answer:</b> <?= Html::encode($answer['answer']) ?></p>
                                        <?php Yii::warning("Tohle je answer: " . $answer['answer'] . " a tohle je zda je correct: ", __METHOD__); ?>
                                        <?php
                                        // Fetch is_correct for the answer
                                        $isCorrect = Yii::$app->db->createCommand('
                                            SELECT is_correct 
                                            FROM training_multiple_choice_user_answers 
                                            WHERE user_id = :user_id AND answer_text = :answer_id
                                        ')
                                            ->bindValue(':user_id', $user->id)
                                            ->bindValue(':answer_id', $answer['answer'])
                                            ->queryScalar();
                                        ?>
                                        <div class="evaluation">
                                            <label style="color: green;">
                                                <?= Html::checkbox('evaluation[' . $answer['id'] . '][correct]', $isCorrect == 1) ?> Correct
                                            </label>
                                            <label style="color: red;">
                                                <?= Html::checkbox('evaluation[' . $answer['id'] . '][wrong]', $isCorrect == 0) ?> Wrong
                                            </label>
                                            <label style="color: gray;">
                                                <?= Html::checkbox('evaluation[' . $answer['id'] . '][not_scored]', false) ?> Not Scored
                                            </label>
                                        </div>
                                    <?php endif; ?>



                                    <hr>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No answers found for this training.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
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