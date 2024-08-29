<?php

use yii\helpers\Html;
use yii\helpers\Url;

?>

<?php
$fileUrl = Yii::$app->db->createCommand('
            SELECT initial_file_url 
            FROM training 
            WHERE id = :trainingId
            ')
    ->bindValue(':trainingId', $trainingId)
    ->queryScalar();
if ($fileUrl != null):
    // Determine the file type based on its extension
    $fileExtension = pathinfo($fileUrl, PATHINFO_EXTENSION);
endif;
?>

<div class="employee-training-container">
    <div class="employee-training-card"
        style="height: 70vh; display: flex; flex-direction: column; justify-content: space-between;">
        <div class="training-content" style="flex-grow: 1; overflow-y: auto;">
            <h1><?= Yii::t('employeeTraining', 'Welcome to the ') ?> <b> <?= Html::encode($trainingId) ?> </b>
                <?= Yii::t('employeeTraining', 'training') ?></h1>
            <p class="welcome-text"><?= Yii::t('employeeTraining', 'Dear ') ?> <b>
                    <?= Html::encode($firstName) ?></b><?= Yii::t('employeeTraining', ', you have been assigned this training. Please complete it at your earliest convenience. Please note that if the page is refreshed during the training, your inputs will not be saved.') ?>
            </p><br>

            <?php if ($fileUrl != null): ?>
                <div class="form-group" id="file-container" style="text-align: center;">
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

                    <button id="end-file-btn"
                        class="btn btn-secondary"><?= Yii::t('employeeTraining', 'Continue') ?></button>
                </div>

                <div id="questions-container" style="display: none;"></div>
                <?= Html::button(Yii::t('employeeTraining', 'Next'), ['class' => 'btn btn-primary', 'id' => 'next-btn', 'style' => 'display: none; ']) ?>
                <?= Html::button(Yii::t('employeeTraining', 'Previous'), ['class' => 'btn btn-secondary', 'id' => 'prev-btn', 'style' => 'display: none;']) ?>
                <?= Html::a(Yii::t('employeeTraining', 'Submit'), Url::to(['/dashboard']), ['class' => 'btn btn-success', 'id' => 'submit-btn', 'style' => 'display: none;']) ?>
            <?php else: ?>
                <div id="questions-container"></div>
                <?= Html::button(Yii::t('employeeTraining', 'Next'), ['class' => 'btn btn-primary', 'id' => 'next-btn']) ?>
                <?= Html::button(Yii::t('employeeTraining', 'Previous'), ['class' => 'btn btn-secondary', 'id' => 'prev-btn', 'style' => 'display: none;']) ?>
                <?= Html::a(Yii::t('employeeTraining', 'Submit'), Url::to(['/dashboard']), ['class' => 'btn btn-success', 'id' => 'submit-btn', 'style' => 'display: none;']) ?>
            <?php endif; ?>
            <div id="question-navigation" style="text-align: center; margin-top: 20px;">
                <!-- Question numbers will be dynamically added here -->
            </div>
        </div>
    </div>
</div>



<?php
// URL to display questions based on the title
$displayQuestionsUrl = Url::to(['questions/display-questions', 'title' => $title]);
// URL to complete the training
$completeTrainingUrl = Url::to(['training/complete-training']);
$script = <<<JS
var trainingId = '{$trainingId}';
// Document ready function to initialize when the page is loaded
$(document).ready(function() {
    var trainingId = '{$trainingId}';
    var currentQuestionIndex = 0;

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
                    generateQuestionNavigation();
                    showQuestion(currentQuestionIndex);
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

    function generateQuestionNavigation() {
        var totalQuestions = $('.question-item').length;
        var navHtml = '';

        for (var i = 0; i < totalQuestions; i++) {
            navHtml += '<button class="question-nav-btn" data-index="' + i + '">' + (i + 1) + '</button>';
        }

        $('#question-navigation').html(navHtml);
        highlightCurrentQuestion(currentQuestionIndex);
    }

    function highlightCurrentQuestion(index) {
        $('.question-nav-btn').removeClass('active');
        $('.question-nav-btn[data-index="' + index + '"]').addClass('active');
    }

    function showQuestion(index) {
        var totalQuestions = $('.question-item').length;
        $('.question-item').hide();
        $('.question-item').eq(index).show();

        if (index === 0) {
            $('#prev-btn').hide();
        } else {
            $('#prev-btn').show();
        }

        if (index === totalQuestions - 1) {
            $('#next-btn').hide();
            $('#submit-btn').show();
        } else {
            $('#next-btn').show();
            $('#submit-btn').hide();
        }

        highlightCurrentQuestion(index);
    }

    $('#next-btn').on('click', function() {
        if (currentQuestionIndex < $('.question-item').length - 1) {
            currentQuestionIndex++;
            showQuestion(currentQuestionIndex);
        }
    });

    $('#prev-btn').on('click', function() {
        if (currentQuestionIndex > 0) {
            currentQuestionIndex--;
            showQuestion(currentQuestionIndex);
        }
    });

    $(document).on('click', '.question-nav-btn', function() {
        currentQuestionIndex = $(this).data('index');
        showQuestion(currentQuestionIndex);
    });

    // Function to hide the video and show the questions
    $('#end-file-btn').on('click', function(e) {
        var videoElement = document.getElementById('training-video');
        if (videoElement) {
            videoElement.pause();
            videoElement.currentTime = 0;
        }
        $('#file-container').hide();
        $('#questions-container').show();
        loadQuestions();  // Display the first question
        $('#next-btn').show();  // Show the "Next" button after questions are loaded
    });

    $('#submit-btn').on('click', function(e) {
        e.preventDefault();
        let isValid = true;
        let data = { _csrf: yii.getCsrfToken(), training_id: trainingId, TrainingQuestions: {} };

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

        $('.multiple-choice-option').each(function() {
            let questionId = $(this).data('question-id');
            let questionText = $(this).data('question-text');
            let questionType = $(this).data('question-type');

            if (!data.TrainingQuestions[questionId]) {
                data.TrainingQuestions[questionId] = {
                    question_id: questionId,
                    question: questionText,
                    answer: [],
                    question_type: questionType
                };
            }

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
                        window.location.href = $('#submit-btn').attr('href');
                    } else {
                        alert('Failed to complete the training. Please try again.');
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error in AJAX request. Please try again.');
                }
            });
        }
    });

    // Initially hide the "Next" button if a video or PDF is present
    if ($('#file-container').length > 0) {
        $('#next-btn').hide();
    }

    $('#prev-btn').hide();
    $('#submit-btn').hide();

    function checkMultipleChoiceSelection() {
        if ($('.multiple-choice-option:checked').length === 0) {
            console.log("No multiple choice options are selected.");
        }
    }
});
JS;
$this->registerJs($script);
?>