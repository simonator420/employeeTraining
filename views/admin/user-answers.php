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
                            <?php foreach ($answers[$training['training_id']][$instance['created_at']] as $index => $answer): ?>
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

                                    <?php Yii::warning("Tohle je type: " . $questionType) ?>
                                    <?php if ($questionType === 'multiple_choice'): ?>
                                        <?php
                                        $multipleAnswers = $answer['multiple_choice_answers'];
                                        foreach ($multipleAnswers as $idx => $singleAnswer):
                                            ?>
                                            <p><b>Answer <?= $idx + 1 ?>:</b> <?= Html::encode($singleAnswer['option_text']) ?></p>
                                            <div class="evaluation">
                                                <label style="color: green;">
                                                    <?= Html::checkbox('evaluation[' . $answer['id'] . '][' . $idx . '][correct]', $singleAnswer['is_correct'] == 1) ?>
                                                    Correct
                                                </label>
                                                <label style="color: red;">
                                                    <?= Html::checkbox('evaluation[' . $answer['id'] . '][' . $idx . '][wrong]', $singleAnswer['is_correct'] == 0) ?>
                                                    Wrong
                                                </label>
                                                <label style="color: gray;">
                                                    <?= Html::checkbox('evaluation[' . $answer['id'] . '][' . $idx . '][not_scored]', false) ?>
                                                    Not Scored
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p><b>Answer:</b> <?= Html::encode($answer['answer']) ?></p>
                                        <?php
                                        // Fetch correct_answer for the question
                                        $correctAnswer = Yii::$app->db->createCommand('
                                            SELECT correct_answer 
                                            FROM training_questions 
                                            WHERE id = :question_id
                                            AND training_id = :training_id
                                        ')
                                            ->bindValue(':training_id', $training['training_id'])
                                            ->bindValue(':question_id', $answer['question_id'])
                                            ->queryScalar();
                                        $isCorrect = ($correctAnswer == $answer['answer']);
                                        ?>
                                        <?php Yii::warning("Tohle je answer: " . $answer['answer'] . " a tohle je jeji correct: " . $correctAnswer, __METHOD__); ?>
                                        <div class="evaluation">
                                            <label style="color: green;">
                                                <?= Html::checkbox('evaluation[' . $answer['id'] . '][correct]', $isCorrect) ?>
                                                Correct
                                            </label>
                                            <label style="color: red;">
                                                <?= Html::checkbox('evaluation[' . $answer['id'] . '][wrong]', !$isCorrect) ?>
                                                Wrong
                                            </label>
                                            <label style="color: gray;">
                                                <?= Html::checkbox('evaluation[' . $answer['id'] . '][not_scored]', false) ?>
                                                Not Scored
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                    <hr>
                                </div>
                                <?php if ($index === count($answers[$training['training_id']][$instance['created_at']]) - 1): ?>
                                    <button class="submit-score-btn">Submit Score</button>
                                    <label class="score-label">Score: <span class="score-value">0/0</span></label>
                                <?php endif; ?>
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
    function updateScore(content) {
        var correctCount = 0;
        var totalCount = 0;

        content.find('.question-answer-pair').each(function() {
            var correctChecked = $(this).find('input[type="checkbox"][name*="[correct]"]:checked').length > 0;
            var notScoredChecked = $(this).find('input[type="checkbox"][name*="[not_scored]"]:checked').length > 0;

            if (!notScoredChecked) {
                totalCount++;
                if (correctChecked) {
                    correctCount++;
                }
            }
        });

        var scoreLabel = content.find('.score-label').find('.score-value');
        scoreLabel.text(correctCount + "/" + totalCount);
    }

    $('.collapsible').on('click', function() {
        var content = $(this).next('.content');

        // Close all other open contents
        $('.collapsible').not(this).removeClass('active');
        $('.content').not(content).slideUp();

        // Toggle the clicked collapsible
        $(this).toggleClass('active');
        content.slideToggle();

        // Update the score when the collapsible is expanded
        if (content.is(':visible')) {
            updateScore(content);
        }
    });

    $(document).on('change', 'input[type="checkbox"]', function() {
        var content = $(this).closest('.content');
        var group = $(this).closest('.evaluation');

        // Ensure only one checkbox is checked in the group
        if (this.checked) {
            group.find('input[type="checkbox"]').not(this).prop('checked', false);
        }

        // Ensure at least one checkbox is checked
        var correctChecked = group.find('input[type="checkbox"][name*="[correct]"]:checked').length > 0;
        var wrongChecked = group.find('input[type="checkbox"][name*="[wrong]"]:checked').length > 0;
        var notScoredChecked = group.find('input[type="checkbox"][name*="[not_scored]"]:checked').length > 0;

        if (!correctChecked && !wrongChecked && !notScoredChecked) {
            this.checked = true; // Revert the change if no checkbox is checked
        }

        updateScore(content);
    });

    $(document).on('click', '.submit-score-btn', function() {
        var content = $(this).closest('.content');
        updateScore(content);
    });
});
JS;
$this->registerJs($script);
?>