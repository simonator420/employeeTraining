<?php

use yii\helpers\Html;
use yii\helpers\Url;

?>

<div class="employee-training-container">
    <div class="employee-training-card">

        <h1>Welcome to the <b> <?= Html::encode($trainingId) ?> </b> training</h1>
        <p class="welcome-text">Dear <b> <?= Html::encode($firstName) ?></b>, you have been assigned this training.
            Please complete it at your earliest convenience. Please note that if the page is refreshed during the
            training, your inputs will not be saved.</p><br>

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
                    <embed src="<?= $pdfUrl ?>" width="90%" height="65vh" type="application/pdf" style="min-height: 65vh;" alt="pdf" /><br>
                <?php endif; ?>

                <button id="end-file-btn" class="btn btn-secondary">Continue</button>
            </div>
            <div id="questions-container" style="display: none;"></div>
            <?= Html::a('Submit', Url::to(['/dashboard']), ['class' => 'btn btn-primary', 'id' => 'submit-btn', 'style' => 'display: none;']) ?>
        <?php else: ?>
            <div id="questions-container"></div>
            <?= Html::a('Submit', Url::to(['/dashboard']), ['class' => 'btn btn-primary', 'id' => 'submit-btn']) ?>
        <?php endif; ?>
    </div>
</div>

<?php
// URL to display questions based on the title
$displayQuestionsUrl = Url::to(['training-questions/display-questions', 'title' => $title]);
// URL to complete the training
$completeTrainingUrl = Url::to(['role/complete-training']);
$script = <<<JS
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

            console.log(data);

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

                console.log("Collected normal data: ", questionId, questionText, questionType, inputValue);
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

            console.log(data);

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
            // Handle the case when no options are checked
            console.log("No multiple choice options are selected.");
        }
    }
});
JS;
$this->registerJs($script);
?>


<style>
    .employee-training-container {
        display: flex;
        justify-content: center;
        padding: 20px;
    }

    .employee-training-card {
        background-color: #ffffff;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        padding: 30px;
        max-width: 70vh;
        width: 100%;
    }

    .employee-training-card h1 {
        color: #333333;
        margin-bottom: 20px;
        text-align: center;
    }

    .questions-container-left {
        text-align: left;
    }

    .btn-primary {
        display: inline-block;
        margin: 10px 5px 10px 0;
        padding: 10px 20px;
        font-size: 16px;
        color: #ffffff;
        background-color: rgb(85, 85, 85);
        border: 2px solid transparent;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
    }

    .btn-primary:hover,
    .btn-primary:focus,
    .btn-primary:active {
        background-color: #ffffff;
        color: rgb(85, 85, 85) !important;
        border-color: rgb(85, 85, 85);
        border-radius: 5px;
        padding: 10px 20px;
        border: 2px solid rgb(85, 85, 85);
        outline: none;
    }

    .btn-secondary {
        display: inline-block;
        margin: 10px 5px 10px 0;
        padding: 10px 20px;
        font-size: 16px;
        color: #ffffff;
        background-color: #6c757d;
        border: 2px solid transparent;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
    }

    .btn-secondary:hover,
    .btn-secondary:focus,
    .btn-secondary:active {
        background-color: #ffffff;
        color: #6c757d !important;
        border-color: #6c757d;
        border-radius: 5px;
        padding: 10px 20px;
        border: 2px solid #6c757d;
        outline: none;
    }

    .question-item {
        margin-bottom: 20px;
    }

    .form-control {
        width: 100%;
        padding: 10px;
        margin-top: 10px;
        font-size: 14px;
        border: 1px solid #dee2e6;
        border-radius: 5px;
    }

    .question-employee {
        text-align: left;
    }

    .input-number {
        width: auto;
        display: inline-block;
        padding: 5px;
        font-size: 14px;
        border: 1px solid #dee2e6;
        border-radius: 5px;
    }

    input[type=range] {
        -webkit-appearance: none;
        appearance: none;
        width: 100%;
        height: 1px;
        opacity: 0.7;
        transition: opacity .2s;
        cursor: pointer;
        border-radius: 5px;
    }

    input[type="range"]::-webkit-slider-runnable-track {
        background: rgb(85, 85, 85);
        height: 2px;
    }

    input[type=range]::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 30px;
        height: 30px;
        background: rgb(85, 85, 85);
        cursor: pointer;
        border-radius: 50%;
        border: 2px solid #ffffff;
        margin-top: -12px;
    }

    .welcome-text {
        font-size: 16px;
        text-align: center;
    }

    .range-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .range-container span {
        font-size: 14px;
        margin: 0 10px;
    }
</style>