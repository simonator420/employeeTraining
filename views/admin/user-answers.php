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
                                        if (empty($multipleAnswers)) {
                                            echo '<p style="padding-top:5px;">User selected no options</p>';
                                        }
                                        $correctOptions = [];
                                        foreach ($multipleAnswers as $idx => $singleAnswer):
                                            if ($singleAnswer['is_correct']) {
                                                $correctOptions[] = $singleAnswer['option_text'];
                                            }
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
                                        <p style="padding-top:10px; padding-bottom:2px;"><b>Correct answer:</b>
                                            <?= empty($correctOptions) ? 'N/A' : Html::encode(implode(', ', $correctOptions)) ?>
                                        </p>

                                        <!-- TODO Add to training_answers foreign_key user_training ID -->
                                        <?php
                                        $allOptions = Yii::$app->db->createCommand('
                                                SELECT option_text 
                                                FROM training_multiple_choice_answers 
                                                WHERE question_id = :question_id
                                            ')
                                            ->bindValue(':question_id', $answer['question_id'])
                                            ->queryAll();

                                        // Extract option_text values
                                        $allOptionTexts = array_column($allOptions, 'option_text');
                                        ?>

                                        <p style="padding-bottom:10px;"><b>All options:</b>
                                            <?= empty($allOptionTexts) ? 'N/A' : Html::encode(implode(', ', $allOptionTexts)) ?>
                                        </p>

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
                                        <p style="padding-top:10px; padding-bottom:10px;"><b>Correct answer:</b>
                                            <?= !$correctAnswer ? 'N/A' : Html::encode($correctAnswer) ?>
                                        </p>
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
            var evaluations = $(this).find('.evaluation');
            var notScoredChecked = evaluations.find('input[type="checkbox"][name*="[not_scored]"]:checked').length > 0;
            var correctAnswers = evaluations.find('input[type="checkbox"][name*="[correct]"]');
            var totalCorrect = correctAnswers.length;
            var correctChecked = correctAnswers.filter(':checked').length;

            // Handle special case: no correct answers and no selections
            var selectedOptions = evaluations.find('input[type="checkbox"]:checked').length > 0;
            if (totalCorrect === 0 && !selectedOptions) {
                totalCount++;
                correctCount++;
            } else if (!notScoredChecked) {
                totalCount++;
                correctCount += (correctChecked / totalCorrect);
            } else {
                var partialCorrect = 0;
                var partialTotal = 0;
                evaluations.each(function() {
                    if (!$(this).find('input[type="checkbox"][name*="[not_scored]"]:checked').length) {
                        partialTotal++;
                        if ($(this).find('input[type="checkbox"][name*="[correct]"]:checked').length) {
                            partialCorrect++;
                        }
                    }
                });

                if (partialTotal > 0) {
                    totalCount++;
                    correctCount += (partialCorrect / partialTotal);
                }
            }
        });

        var scoreLabel = content.find('.score-label').find('.score-value');
        scoreLabel.text(correctCount.toFixed(2) + "/" + totalCount);
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

    // Initial score calculation for all expanded contents
    $('.collapsible').each(function() {
        var content = $(this).next('.content');
        if (content.is(':visible')) {
            updateScore(content);
        }
    });
});

JS;
$this->registerJs($script);
?>