<?php

use yii\helpers\Html;
use yii\helpers\Url;

?>

<div class="employee-training-container">
    <div class="employee-training-card">

        <h1><?= Yii::t('employeeTraining', 'Welcome to the ') ?> <b> <?= Html::encode($trainingId) ?> </b> <?= Yii::t('employeeTraining', 'training') ?></h1>
        <p class="welcome-text"><?= Yii::t('employeeTraining', 'Dear ') ?> <b> <?= Html::encode($firstName) ?></b><?=Yii::t('employeeTraining', ', you have been assigned this training. Please complete it at your earliest convenience. Please note that if the page is refreshed during the training, your inputs will not be saved.')?></p><br>

        <!-- Video container with end button -->
        <?php
        $fileUrl = Yii::$app->db->createCommand('
            SELECT initial_file_url 
            FROM training 
            WHERE id = :trainingId
            ')
            ->bindValue(':trainingId', $trainingId)
            ->queryScalar();
        ?>
        <!-- If the initial file was set -->
        <?php if ($fileUrl != null): ?>
            <?php

            // Determine the file type based on its extension
            $fileExtension = pathinfo($fileUrl, PATHINFO_EXTENSION);
            ?>
            <div class="form-group" id="file-container" style="text-align: center;">
                <!-- Display appropriate content based on the file type -->
                <?php if (in_array($fileExtension, ['mp4', 'webm', 'ogg'])): ?>
                    <video id="training-video" width="auto" controls>
                        <source src="<?= Url::to('@web/' . $fileUrl) ?>" type="video/<?= $fileExtension ?>">
                        Your browser does not support the video tag.
                    </video><br>
                <?php elseif ($fileExtension === 'pdf'): ?>
                    <?php $pdfUrl = Url::to('@web/' . $fileUrl) . '?t=' . time(); ?>
                    <embed src="<?= $pdfUrl ?>" width="90%" type="application/pdf" style="min-height: 65vh;"
                        alt="pdf" /><br>
                <?php endif; ?>

                <button id="end-file-btn" class="btn btn-secondary"><?= Yii::t('employeeTraining', 'Continue') ?></button>
            </div>

            <div id="questions-container" style="display: none;"></div>
            <?= Html::a(Yii::t('employeeTraining', 'Submit'), Url::to(['/dashboard']), ['class' => 'btn btn-primary', 'id' => 'submit-btn', 'style' => 'display: none;']) ?>
        <?php else: ?>
            <div id="questions-container"></div>
            <?= Html::a(Yii::t('employeeTraining', 'Submit'), Url::to(['/dashboard']), ['class' => 'btn btn-primary', 'id' => 'submit-btn']) ?>
        <?php endif; ?>
    </div>
</div>

<?php
// URL to display questions based on the title
$displayQuestionsUrl = Url::to(['training-questions/display-questions', 'title' => $title]);
// URL to complete the training
$completeTrainingUrl = Url::to(['role/complete-training']);
$script = <<<JS
var trainingId = '{$trainingId}';
// Document ready function to initialize when the page is loaded
$(document).ready(function() {
    var trainingId = '{$trainingId}';

    function loadQuestions() {
        $.ajax({
            url: '$displayQuestionsUrl',
            type: 'GET',
            cache: false, // Disable caching for this request
            data: { training_id: trainingId }, // Pass the training_id dynamically
            success: function(response) {
                if (response.success) {
                    // Display the questions in the container
                    $('#questions-container').html(response.html);
                } else {
                    // Display a message if no questions are available
                    $('#questions-container').html('<p>No questions available.</p>');
                }
            },
            error: function() {
                alert('Error occurred while fetching questions.');
            }
        });
    }

    // Load questions when the page is ready
    loadQuestions();

    checkMultipleChoiceSelection();

    // Function to hide the video and show the questions
    $('#end-file-btn').on('click', function(e) {
        var videoElement = document.getElementById('training-video');
        if (videoElement) {
            videoElement.pause();
            videoElement.currentTime = 0;
        }
        document.getElementById('file-container').style.display = 'none';
        document.getElementById('questions-container').style.display = 'block';
        document.getElementById('submit-btn').style.display = 'inline';
    });

    // Flag to check if training is completed
    let trainingCompleted = false;

    // Event handler for the submit button click
    $('#submit-btn').on('click', function(e) {
        e.preventDefault();
        if (!trainingCompleted) {
            let isValid = true;
            let data = { _csrf: yii.getCsrfToken(), training_id: trainingId, TrainingQuestions: {} };

            // Collect answers for text, number, and range inputs
            $('.question-input').each(function() {
                let questionId = $(this).data('question-id');
                let questionText = $(this).data('question-text');
                let questionType = $(this).data('question-type');
                let inputValue = $(this).val();

                if (!inputValue) {
                    isValid = false;
                    $(this).css('border', '2px solid red');
                } else {
                    $(this).css('border', '1px solid #dee2e6');
                }

                data.TrainingQuestions[questionId] = {
                    question_id: questionId,
                    question: questionText,
                    answer: inputValue,
                    question_type: questionType
                };
            });

            // Collect multiple-choice answers
            $('.multiple-choice-option').each(function() {
                let questionId = $(this).data('question-id');
                let questionText = $(this).data('question-text');
                let questionType = $(this).data('question-type');

                // Initialize the question if it hasn't been added yet
                if (!data.TrainingQuestions[questionId]) {
                    data.TrainingQuestions[questionId] = {
                        question_id: questionId,
                        question: questionText,
                        answer: [],
                        question_type: questionType
                    };
                }

                // Add the value if the option is checked
                if ($(this).is(':checked')) {
                    data.TrainingQuestions[questionId].answer.push($(this).val());
                }
            });

            if (isValid) {
                $.ajax({
                    url: '$completeTrainingUrl',
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            alert('Thank you for completing the training!');
                            trainingCompleted = true;
                            var currentTime = new Date().toISOString().slice(0, 19).replace('T', ' ');
                            $('#training-complete-time-' + response.userId).text(currentTime);
                            window.location.href = $('#submit-btn').attr('href');
                        } else {
                            alert('Failed to complete the training. Please try again or contact System Administrator.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        alert('Error in AJAX request. Please try again or contact System Administrator.');
                    }
                });
            }
        }
    });

    function checkMultipleChoiceSelection() {
        if ($('.multiple-choice-option:checked').length === 0) {
            console.log("No multiple choice options are selected.");
        }
    }
});
JS;
$this->registerJs($script);
?>