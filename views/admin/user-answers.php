<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $user common\models\User */
/* @var $trainings array */
/* @var $answers array */

// $this->title = 'Training Answers - ' . Html::encode($user->profile->firstname . ' ' . $user->profile->lastname);
$this->title = 'Training Answers - ';
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
                    <?php 
                        $length = number_format(count($answers[$training['training_id']][$instance['created_at']]), 2);
                        $formattedScore = number_format($instance['total_score'], 2);


                        Yii::warning("Tohle je instance length: " . $length) ?>
                    <?php
                    $scoreStatus = $instance['is_scored']
                        ? "<span style='color: green; font-weight: bold; padding-left:5px;'>Score: {$formattedScore}/{$length}</span>"
                        : "<span style='color: red; font-weight: bold; padding-left:5px;'>Training not scored</span>";
                    ?>
                    <button class="collapsible" data-training-id="<?= Html::encode($training['training_id']) ?>">
                        <b><?= Html::encode($training['training_name']) ?></b> - <?= Html::encode($instance['created_at']) ?>
                        <?= $scoreStatus ?>
                    </button>
                    <div class="content" style="display:none;">
                        <?php if (!empty($answers[$training['training_id']][$instance['created_at']])): ?>
                            <?php foreach ($answers[$training['training_id']][$instance['created_at']] as $index => $answer): ?>

                                <?php
                                $questionType = Yii::$app->db->createCommand('
                                    SELECT type 
                                    FROM training_questions 
                                    WHERE id = :question_id
                                    ')
                                    ->bindValue(':question_id', $answer['question_id'])
                                    ->queryScalar();
                                ?>

                                <div class="question-answer-pair" data-question-id="<?= Html::encode($answer['question_id']) ?>"
                                    data-question-type="<?= Html::encode($questionType) ?>"
                                    data-user-training-id="<?= Html::encode($answer['user_training_id']) ?>">
                                    <p><b>Question:</b> <?= Html::encode($answer['question_text']) ?></p>


                                    <?php if ($questionType === 'multiple_choice'): ?>
                                        <?php
                                        $multipleAnswers = $answer['multiple_choice_answers'];
                                        if (empty($multipleAnswers)) {
                                            echo '<p style="padding-top:5px;">User selected no options</p>';
                                        }

                                        $correctOptions = Yii::$app->db->createCommand('
                                            SELECT option_text 
                                            FROM training_multiple_choice_answers 
                                            WHERE question_id = :question_id AND is_correct = 1
                                        ')
                                            ->bindValue(':question_id', $answer['question_id'])
                                            ->queryColumn();

                                        foreach ($multipleAnswers as $idx => $singleAnswer):

                                            $tmcuaId = Yii::$app->db->createCommand('
                                                SELECT id 
                                                FROM training_multiple_choice_user_answers 
                                                WHERE question_id = :question_id AND multiple_choice_answer_id = :option_id
                                            ')
                                                ->bindValue(':question_id', $answer['question_id'])
                                                ->bindValue(':option_id', $singleAnswer['id'])
                                                ->queryScalar();
                                            ?>

                                            <p><b>Answer <?= $idx + 1 ?>:</b> <?= Html::encode($singleAnswer['option_text']) ?></p>
                                            <div class="evaluation" data-option-id="<?= Html::encode($singleAnswer['id']) ?>"
                                                data-tmcua-id="<?= Html::encode($tmcuaId) ?>">
                                                <label style="color: green;">
                                                    <?= Html::checkbox('evaluation[' . $answer['id'] . '][' . $idx . '][correct]', isset($singleAnswer['score']) ? $singleAnswer['score'] > 0 : $singleAnswer['is_correct'] == 1) ?>
                                                    Correct
                                                </label>
                                                <label style="color: red;">
                                                    <?= Html::checkbox('evaluation[' . $answer['id'] . '][' . $idx . '][wrong]', isset($singleAnswer['score']) ? $singleAnswer['score'] == 0 : $singleAnswer['is_correct'] == 0) ?>
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

                                        <?php
                                        $allOptions = Yii::$app->db->createCommand('
                                            SELECT tma.option_text 
                                            FROM training_multiple_choice_answers tma
                                            JOIN training_questions tq ON tma.question_id = tq.id
                                            JOIN training_answers ta ON tq.id = ta.question_id
                                            WHERE tq.id = :question_id AND ta.user_training_id = :user_training_id
                                        ')
                                            ->bindValue(':question_id', $answer['question_id'])
                                            ->bindValue(':user_training_id', $answer['user_training_id'])
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
                                        <div class="evaluation">
                                            <label style="color: green;">
                                                <?= Html::checkbox('evaluation[' . $answer['id'] . '][correct]', isset($answer['score']) ? $answer['score'] > 0 : $isCorrect) ?>
                                                Correct
                                            </label>
                                            <label style="color: red;">
                                                <?= Html::checkbox('evaluation[' . $answer['id'] . '][wrong]', isset($answer['score']) ? $answer['score'] == 0 : !$isCorrect) ?>
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
$completeTrainingUrl = Url::to(['role/save-scores']);
$script = <<<JS
$(document).ready(function() {
    function updateScore(content) {
        var correctCount = 0;
        var totalCount = 0;

        content.find('.question-answer-pair').each(function() {
            var questionText = $(this).find('p:contains("Question:")').text();
            var correctAnswerLabel = $(this).find('p:contains("Correct answer:")');
            var correctOptions = [];

            if (correctAnswerLabel.length > 0) {
                correctOptions = correctAnswerLabel.text().replace('Correct answer:', '').split(',').map(function(option) {
                    return option.trim();
                });

                // console.log('Correct options for question:', questionText);
                // correctOptions.forEach(function(option) {
                //     console.log(option);
                // });
            }

            var evaluations = $(this).find('.evaluation');
            var correctAnswers = evaluations.find('input[type="checkbox"][name*="[correct]"]');
            // console.log('Correct answers:');
            // correctAnswers.each(function() {
            //     console.log($(this).closest('label').text().trim());
            // });

            var totalCorrect = correctOptions.length;
            var correctChecked = correctAnswers.filter(':checked').length;
            var wrongChecked = evaluations.find('input[type="checkbox"][name*="[wrong]"]:checked').length;

            // Adjust totalCorrect based on not scored options
            evaluations.find('input[type="checkbox"][name*="[not_scored]"]:checked').each(function() {
                var optionText = $(this).closest('label').text().trim();
                if (correctOptions.includes(optionText)) {
                    totalCorrect--;
                }
            });

            // Handle special case: no correct answers and no selections
            var selectedOptions = evaluations.find('input[type="checkbox"]:checked').length > 0;
            if (totalCorrect === 0 && !selectedOptions) {
                totalCount++;
                correctCount++;
            } else {
                totalCount++;
                var score = correctChecked / totalCorrect;
                correctCount += score;
            }
        });

        var scoreLabel = content.find('.score-label').find('.score-value');
        scoreLabel.text(correctCount.toFixed(2) + "/" + totalCount.toFixed(2));

        return {
            correctCount: correctCount.toFixed(2),
            totalCount: totalCount.toFixed(2)
        };
    }

    $(document).on('change', 'input[type="checkbox"]', function() {
        var content = $(this).closest('.content');
        var group = $(this).closest('.evaluation');

        if (this.checked) {
            group.find('input[type="checkbox"]').not(this).prop('checked', false);
        }

        var correctChecked = group.find('input[type="checkbox"][name*="[correct]"]:checked').length > 0;
        var wrongChecked = group.find('input[type="checkbox"][name*="[wrong]"]:checked').length > 0;
        var notScoredChecked = group.find('input[type="checkbox"][name*="[not_scored]"]:checked').length > 0;

        if (!correctChecked && !wrongChecked && !notScoredChecked) {
            this.checked = true;
        }

        updateScore(content);
    });

    $(document).on('click', '.submit-score-btn', function() {
        var content = $(this).closest('.content');
        var scores = updateScore(content);

        // Update the corresponding button text with the new score
        var button = content.prev('.collapsible');
        button.find('span').remove(); // Remove the old score or "Training not scored" text
        button.append(' <span style="color: green; font-weight: bold;">Score: ' + scores.correctCount + '/' + scores.totalCount + '</span>');

        // Collect the score data and submit to the server
        var scoreData = [];
        content.find('.question-answer-pair').each(function() {
            var questionId = $(this).data('question-id'); // Ensure the question-id data attribute is set in the HTML
            var questionType = $(this).data('question-type'); // Ensure the question-type data attribute is set in the HTML
            var userTrainingId = $(this).data('user-training-id'); // Ensure the user_training_id data attribute is set in the HTML
            console.log(userTrainingId)
            var evaluation = $(this).find('.evaluation');

            if (questionType == 'multiple_choice') {
                var totalCorrectOptions = $(this).find('input[type="checkbox"][name*="[correct]"]').length;
                var correctCheckedOptions = $(this).find('input[type="checkbox"][name*="[correct]"]:checked').length;
                var score = correctCheckedOptions / totalCorrectOptions;
                var finalScore = 0;

                $(this).find('.evaluation').each(function() {
                    var optionId = $(this).data('option-id'); // Get the option ID
                    var tmcuaId = $(this).data('tmcua-id'); // Get the training_multiple_choice_user_answers ID
                    var optionScore = $(this).find('input[name*="[correct]"]').prop('checked') ? score : 0;

                    finalScore += optionScore;

                    if (finalScore > 1) {
                        finalScore = 1;
                    }

                    console.log('Final score: ' + finalScore + 'Score of current element: ' + score);

                    console.log('Option ID: ' + optionId + ', TMCUA ID: ' + tmcuaId + ', Score: ' + score);

                    scoreData.push({
                        question_id: questionId,
                        option_id: optionId,
                        tmcua_id: tmcuaId,
                        score: optionScore,
                        final_score: finalScore,
                        type: questionType,
                        user_training_id: userTrainingId
                    });
                });
            } else {
                var score = $(this).find('input[name*="[correct]"]').prop('checked') ? 1 : 0;

                console.log('Question ID: ' + questionId + ', Score: ' + score);

                scoreData.push({
                    question_id: questionId,
                    score: score,
                    type: questionType,
                    user_training_id: userTrainingId
                });
            }
        });

        // Submit the score data to the server via AJAX
        $.ajax({
            url: '$completeTrainingUrl',
            type: 'POST',
            data: JSON.stringify(scoreData),
            contentType: 'application/json; charset=utf-8',
            success: function(response) {
                console.log('Scores successfully saved:', response);
            },
            error: function(xhr, status, error) {
                console.error('Error saving scores:', error);
            }
        });
    });





    $('.collapsible').on('click', function() {
        var content = $(this).next('.content');

        $('.collapsible').not(this).removeClass('active');
        $('.content').not(content).slideUp();

        $(this).toggleClass('active');
        content.slideToggle();

        if (content.is(':visible')) {
            updateScore(content);
        }
    });

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