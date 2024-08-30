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

$fileExtension = '';
if ($fileUrl != null):
    // Determine the file type based on its extension
    $fileExtension = pathinfo($fileUrl, PATHINFO_EXTENSION);
endif;
?>

<div class="employee-training-container">
    <div class="employee-training-card"
        style="height: 77vh; display: flex; flex-direction: column; justify-content: space-between; padding: 15px;">
        <div class="training-content" style="flex-grow: 1; overflow-y: auto; padding: 20px;">
            <h1><?= Yii::t('employeeTraining', 'Welcome to the ') ?> <b> <?= Html::encode($trainingId) ?> </b>
                <?= Yii::t('employeeTraining', 'training') ?></h1>
            <p class="welcome-text"><?= Yii::t('employeeTraining', 'Dear ') ?> <b>
                    <?= Html::encode($firstName) ?></b><?= Yii::t('employeeTraining', ', you have been assigned this training. Please complete it at your earliest convenience. Please note that if the page is refreshed during the training, your inputs will not be saved.') ?>
            </p><br>

            <?php if ($fileUrl != null): ?>
                <div class="form-group" id="file-container" style="text-align: center;">
                    <?php if (in_array($fileExtension, ['mp4', 'webm', 'ogg'])): ?>
                        <video id="training-video" style="max-height: 35vh;" controls>
                            <source src="<?= Url::to('@web/' . $fileUrl) ?>" type="video/<?= $fileExtension ?>">
                            Your browser does not support the video tag.
                        </video><br>
                    <?php elseif ($fileExtension === 'pdf'): ?>
                        <?php $pdfUrl = Url::to('@web/' . $fileUrl) . '?t=' . time(); ?>
                        <div style="max-height: 35vh; overflow-y: auto; display: inline-block; width: 90%;">
                            <embed src="<?= $pdfUrl ?>" type="application/pdf" width="100%" height="100%"
                                style="min-height: 35vh;" />
                        </div><br>
                    <?php endif; ?>

                    <button id="end-file-btn"
                        class="btn btn-secondary"><?= Yii::t('employeeTraining', 'Continue') ?></button>
                </div>

                <div id="questions-container" style="display: none;"></div>
            <?php else: ?>
                <div id="questions-container"></div>
            <?php endif; ?>
        </div>

        <div id="question-navigation" style="text-align: center; margin-top: 10px;"></div>

        <div class="button-group" style="text-align: center; margin-top: 20px;">
            <?= Html::button(Yii::t('employeeTraining', 'Previous'), ['class' => 'btn btn-secondary', 'id' => 'prev-btn', 'style' => 'display: none;']) ?>
            <?= Html::button(Yii::t('employeeTraining', 'Next'), ['class' => 'btn btn-primary', 'id' => 'next-btn']) ?>
            <?= Html::a(Yii::t('employeeTraining', 'Submit'), Url::to(['/dashboard']), ['class' => 'btn btn-success', 'id' => 'submit-btn', 'style' => 'display: none;']) ?>
        </div>
    </div>
</div>

<div id="imageModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content" style="background:transparent">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                    style="color: white; opacity: 1;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="Image" style="width: 100%; height: auto;">
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
var fileExtension = '{$fileExtension}';
// Document ready function to initialize when the page is loaded
$(document).ready(function() {
    var trainingId = '{$trainingId}';
    var currentQuestionIndex = 0;
    var questionsLoaded = false; // Track if questions have been loaded

    $(document).on('click', '.question-image img', function() {
        var imgSrc = $(this).attr('src');
        $('#modalImage').attr('src', imgSrc);
        $('#imageModal').modal('show');
    });

    function loadQuestions() {
        $.ajax({
            url: '$displayQuestionsUrl',
            type: 'GET',
            cache: false, // Disable caching for this request
            data: { training_id: trainingId }, // Pass the training_id dynamically
            success: function(response) {
                if (response.success) {
                    $('#questions-container').html(response.html);
                    generateQuestionNavigation();
                    showQuestion(currentQuestionIndex);
                    questionsLoaded = true; // Mark questions as loaded
                } else {
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


        if (fileExtension !== '') {
            var fileIcon = fileExtension === 'pdf' ? 'fa-file-pdf-o' : 'fa-video-camera';

            // Add the video icon at the beginning of the navigation
            navHtml += '<button class="question-nav-btn video-nav-btn" data-index="video"><i class="fa ' + fileIcon + '"></i></button>';
        }

        

        // Add the question numbers to the navigation
        for (var i = 0; i < totalQuestions; i++) {
            navHtml += '<button class="question-nav-btn" data-index="' + i + '">' + (i + 1) + '</button>';
        }

        $('#question-navigation').html(navHtml);
        highlightCurrentQuestion(currentQuestionIndex);
    }

    function highlightCurrentQuestion(index) {
        $('.question-nav-btn').removeClass('active');
        if (index === 'video') {
            $('.video-nav-btn').addClass('active');
        } else {
            $('.question-nav-btn[data-index="' + index + '"]').addClass('active');
        }
    }

    function showQuestion(index) {
        if (index === 'video') {
            $('#questions-container').hide();
            $('#file-container').show();
            $('#question-navigation').hide(); // Hide the navigation
            $('#prev-btn').hide();
            $('#next-btn').hide();
            $('#submit-btn').hide();
            highlightCurrentQuestion('video');
        } else {
            $('#file-container').hide();
            $('#questions-container').show();
            $('#question-navigation').show(); // Show the navigation
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
        var index = $(this).data('index');
        currentQuestionIndex = index === 'video' ? 'video' : parseInt(index);
        showQuestion(currentQuestionIndex);
    });

    $('#submit-btn').on('click', function(e) {
        e.preventDefault();
        let isValid = true;
        let firstInvalidIndex = -1;
        let data = { _csrf: yii.getCsrfToken(), training_id: trainingId, TrainingQuestions: {} };

        $('.question-input').each(function(index) {
            let questionId = $(this).data('question-id');
            let questionText = $(this).data('question-text');
            let questionType = $(this).data('question-type');
            let inputValue = $(this).val();

            if (!inputValue) {
                isValid = false;
                $(this).css('border', '2px solid red');
                if (firstInvalidIndex === -1) {
                    firstInvalidIndex = index;
                }
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

        $('.multiple-choice-option').each(function(index) {
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
        } else {
            // Redirect to the first unanswered question
            if (firstInvalidIndex !== -1) {
                currentQuestionIndex = firstInvalidIndex;
                showQuestion(currentQuestionIndex);
            }
        }
    });

    if (fileExtension === '') {
        loadQuestions(); // Load questions immediately if no initial file is present
        $('#next-btn').show();  // Show the "Next" button
    }

    // Function to hide the video and show the questions
    $('#end-file-btn').on('click', function(e) {
        if (currentQuestionIndex === 'video') {
            currentQuestionIndex = 0;  // Start with the first question when "Continue" is clicked
        }
        $('#file-container').hide();
        $('#questions-container').show();
        if (!questionsLoaded) {
            loadQuestions();  // Load questions only if they haven't been loaded
        } else {
            showQuestion(currentQuestionIndex);  // Show the first question without reloading
        }
        $('#next-btn').show();  // Show the "Next" button after questions are loaded
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