<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

?>

<div class="training-question-container">
    <div class="training-questions-form">

        <!-- Header and button for going back to overview -->
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1>
                <strong> <?= Html::encode($trainingName) ?> </strong>
            </h1>
            <?= Html::a('&laquo; ' . Yii::t('employeeTraining', 'Back to overview'), Url::to(['role/admin']), ['class' => 'btn go-back-button']) ?>
        </div>

        <h3>
            <?= Yii::t('employeeTraining', 'Training ID: ') ?><strong><?= Html::encode($trainingId) ?></strong>
        </h3>

        <h3>
            <?= Yii::t('employeeTraining', 'Deadline for completion in days: ') ?>
            <strong id="deadline-display"><?= Html::encode($deadlineForCompletion) ?></strong>
            <button id="edit-deadline-btn"><?= Yii::t('employeeTraining', 'Edit') ?></button>
        </h3>

        <div id="edit-deadline-form" style="display: none;">
            <input type="number" id="deadline-input" value="<?= Html::encode($deadlineForCompletion) ?>"
                class="form-control" style="width: 100px; display: inline;">
            <button id="submit-deadline-btn"
                class="btn btn-success"><?= Yii::t('employeeTraining', 'Submit') ?></button>
            <button id="cancel-deadline-btn" class="btn btn-danger"><?= Yii::t('employeeTraining', 'Cancel') ?></button>
        </div>

        <br>

        <!-- Begin the ActiveForm -->
        <?php $form = ActiveForm::begin([
            'id' => 'training-questions-form',
            'options' => ['enctype' => 'multipart/form-data'],
            'enableAjaxValidation' => false,
            'enableClientValidation' => true,
        ]); ?>


        <!-- Hidden input to store the training ID -->
        <?= Html::hiddenInput('trainingId', $trainingId) ?>

        <!-- Container for displaying all question with their input fields -->
        <div id="questions-container">
            <!-- Questions are loaded here via JavaScript -->
        </div>

        <!-- Buttons for Adding/Removing question by user -->
        <div class="form-group">
            <button type="button" id="add-question-btn" class="btn btn-secondary">
                <?= Yii::t('employeeTraining', '+ Add question') ?>
            </button>
            <button type="button" id="remove-question-btn" class="btn btn-danger" style="display: none;">
                <?= Yii::t('employeeTraining', '- Remove question') ?>
            </button>
        </div>

        <!-- Button for Advanced settings and checkbox for selecting all users with checkbox -->
        <div class="form-group">
            <button type="button" id="advanced-settings-btn" tabindex="1" style="display:none;">
                <?= Yii::t('employeeTraining', 'Advanced settings ') ?>
                <span id="arrow-down">▼</span></button>
            <div id="assign-to-all" style="display: none;">
                <input type="checkbox" id="all-users" name="all-users" class="assign-to-all-checkbox">
                <label for="all-users">
                    <?= Yii::t('employeeTraining', 'Assign question(s) to all titles') ?>
                </label>
            </div>
        </div>

        <!-- Button for submitting the form and sending data to the endpoint -->
        <div class="form-group">
            <?= Html::button(Yii::t('employeeTraining', 'Submit'), ['class' => 'btn btn-success', 'id' => 'submit-btn']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php
$createQuestionsUrl = Url::to(['training-questions/save-questions']);
$fetchQuestionsUrl = Url::to(['training-questions/fetch-questions']);
$updateDeadlineUrl = Url::to(['training-questions/update-deadline']);
$trainingIdJson = json_encode($trainingId);
$script = <<<JS

function fetchQuestions() {
    var trainingId = $trainingIdJson;
    $.ajax({
        url: '$fetchQuestionsUrl',
        type: 'GET',
        data: { id: trainingId },
        success: function(response) {
            if (response.success) {
                $('#questions-container').html(response.html);
            } else {
                $('#questions-container').html(
                    '<div class="question-item">' +
                        '<label>Question 1</label>' +
                        '<div class="form-group">' +
                            '<select name="TrainingQuestions[0][type]" class="form-control question-type">' +
                                '<option value="text" selected>Text</option>' +
                                '<option value="number">Number</option>' +
                                '<option value="range">Range</option>' +
                            '</select>' +
                        '</div>' +
                        '<div class="form-group">' +
                            '<input type="text" name="TrainingQuestions[0][question]" class="form-control question-text" placeholder="Enter your question here">' +
                        '</div>' +
                        '<div class="form-group">' +
                            '<input type="file" name="TrainingQuestions[0][image]" class="form-control question-image">' +
                        '</div>' +
                    '</div>'
                );
            }
            $('#questions-container').show();
            $('#add-question-btn').show();
            $('#submit-btn').show();
            updateQuestionLabels();
            if ($('.question-item').length > 1) {
                $('#remove-question-btn').show();
            } else {
                $('#remove-question-btn').hide();
            }
        },
        error: function(xhr, status, error) {
            alert('Error occurred while fetching questions.');
            console.log("Error details:", xhr.responseText, status, error);
        }
    });
}

// Event handler for the "Add Question" click
$('#add-question-btn').on('click', function() {
    var questionIndex = $('.question-item').length;
    var newQuestionItem = 
        '<div class="question-item">' +
            '<label>Question ' + (questionIndex + 1) + '</label>' + // This label remains
            '<div class="form-group">' +
                '<select name="TrainingQuestions[' + questionIndex + '][type]" class="form-control question-type">' +
                    '<option value="text" selected>Text</option>' +
                    '<option value="number">Number</option>' +
                    '<option value="range">Range</option>' +
                '</select>' +
            '</div>' +
            '<div class="form-group">' +
                '<input type="text" name="TrainingQuestions[' + questionIndex + '][question]" class="form-control question-text" placeholder="Enter your question here">' +
            '</div>' +
            '<div class="form-group">' +
                '<input type="file" name="TrainingQuestions[' + questionIndex + '][image]" class="form-control question-image">' +
            '</div>' +
        '</div>';
    $('#questions-container').append(newQuestionItem);
    updateQuestionLabels();
    if ($('.question-item').length > 1) {
        $('#remove-question-btn').show();
    }
});

// Event handler for the "Remove Question" button click
$('#remove-question-btn').on('click', function() {
    $('.question-item').last().remove();
    updateQuestionLabels();
    if ($('.question-item').length <= 1) {
        $('#remove-question-btn').hide();
    }
});

// TODO make button unchecked if not toggled
$('#advanced-settings-btn').on('click', function() {
    var allUsers = $('#assign-to-all');
    allUsers.toggle();
    var arrow = $('#arrow-down');

    // Toggle the rotated class for the arrow animation
    arrow.toggleClass('rotated');

    if (!allUsers.is(':visible')) {
        $('#all-users').prop('checked', false);
        $('#title-select').prop('disabled', false);
    }
});

// Event handler for "Submit" button click
// TODO make assign to all titles if 'all-users'checkbox checked
$('#submit-btn').on('click', function() {
    var form = $('#training-questions-form')[0];
    var formData = new FormData(form);

    $('.question-item').each(function(index) {
        var fileInput = $(this).find('.question-image')[0];
        if (fileInput.files.length > 0) {
            formData.append('TrainingQuestions[' + index + '][image]', fileInput.files[0]);
        } else {
            formData.append('TrainingQuestions[' + index + '][image]', null);
        }
    });

    $.ajax({
        url: '$createQuestionsUrl',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                alert('Questions saved successfully!');
            } else {
                alert('Failed to save questions.');
                console.log(response.errors);
            }
        },
        error: function(xhr, status, error) {
            alert('Error occurred while saving questions.');
            console.log(xhr.responseText);
        }
    });
});

$(document).on('click', '.remove-image-btn', function() {
    var index = $(this).data('index');
    var parentDiv = $(this).closest('.form-group');
    parentDiv.find('img').remove();
    $(this).remove();
    $('input[name="TrainingQuestions[' + index + '][existing_image]"]').remove();
    $('input[name="TrainingQuestions[' + index + '][remove_image]"]').val(1);
    parentDiv.find('.question-image').show(); // Show the file input
});

$('#all-users').on('change', function() {
    console.log("Click on checkbox");
    $('#title-select').prop('disabled', this.checked);
    console.log('Checkbox is ' + (this.checked ? 'checked' : 'unchecked'));
});

// Function to update labels of all question items
function updateQuestionLabels() {
    $('.question-item').each(function(index) {
        $(this).find('label').text('Question ' + (index + 1));
    });
}

// Document ready function to initialize the form
$(document).ready(function() {
    fetchQuestions();
    updateQuestionLabels();
    if ($('.question-item').length <= 1) {
        $('#remove-question-btn').hide();
    }
});

// Event handler for "Edit" button click
$('#edit-deadline-btn').on('click', function() {
    $('#deadline-display').hide();
    $('#edit-deadline-btn').hide();
    $('#edit-deadline-form').show();
});

// Event handler for "Submit" button click
$('#submit-deadline-btn').on('click', function() {
        var newDeadline = $('#deadline-input').val();
        var trainingId = $trainingIdJson;

        // AJAX request to update the deadline in the database
        $.ajax({
            url: '$updateDeadlineUrl', // Update this URL to match your endpoint
            type: 'POST',
            data: {
                id: trainingId,
                deadline: newDeadline,
                _csrf: yii.getCsrfToken() // Include CSRF token
            },
            success: function(response) {
                if (response.success) {
                    // Update the display with the new deadline and hide the input form
                    $('#deadline-display').text(newDeadline).show();
                    $('#edit-deadline-form').hide();
                    $('#edit-deadline-btn').show();
                } else {
                    alert('Failed to update deadline.');
                }
            },
            error: function(xhr, status, error) {
                alert('Error occurred while updating deadline.');
                console.log(xhr.responseText);
            }
        });
    });

// Event handler for "Cancel" button click
$('#cancel-deadline-btn').on('click', function() {
    $('#edit-deadline-form').hide();
    $('#edit-deadline-btn').show();
    $('#deadline-display').show();
});


JS;
$this->registerJs($script);
?>

<style>
    .title-dropdown {
        width: 190px;
    }
</style>