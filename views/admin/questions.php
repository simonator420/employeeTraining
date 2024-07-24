<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

?>

<div class="training-question-container">
    <div class="training-questions-form">

        <!-- Header and button for going back to overview -->
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1>Edit Training Questions</h1>
            <?= Html::a('&laquo; Back to overview', Url::to(['role/admin']), ['class' => 'btn go-back-button']) ?>
        </div>

        <br>

        <!-- Begin the ActiveForm -->
        <?php $form = ActiveForm::begin([
            'id' => 'training-questions-form',
            'enableAjaxValidation' => false,
            'enableClientValidation' => true,
        ]); ?>

        <!-- Dropdown list for displaying all employee titles and selecting any of them -->
        <div class="form-group">
            <label>Select Title</label><br>
            <?= Html::dropDownList('title', null, array_combine($titles, $titles), ['prompt' => 'Select Title', 'class' => 'form-control title-dropdown', 'id' => 'title-select']) ?>
        </div>

        <!-- Hidden input to store titles as JSON -->
        <?= Html::hiddenInput('titles_json', json_encode($titles), ['id' => 'titles-json']) ?>

        <!-- Container for displaying all question with their input fields -->
        <div id="questions-container" style="display: none;">
            <div class="question-item">
                <label>Question 1</label>
                <div class="form-group">
                    <?= Html::dropDownList('TrainingQuestions[0][type]', 'text', ['text' => 'Text', 'number' => 'Number', 'range' => 'Range'], ['class' => 'form-control question-type']) ?>
                </div>
                <div class="form-group">
                    <?= Html::textInput('TrainingQuestions[0][question]', '', ['class' => 'form-control question-text', 'placeholder' => 'Enter your question here']) ?>
                </div>
            </div>
        </div>

        <!-- Buttons for Adding/Removing question by user -->
        <div class="form-group">
            <button type="button" id="add-question-btn" class="btn btn-secondary" style="display: none;">+ Add
                Question</button>
            <button type="button" id="remove-question-btn" class="btn btn-danger" style="display: none;">- Remove
                Question</button>
        </div>

        <!-- Button for Advanced settings and checkbox for selecting all users with checkbox -->
        <!-- TODO make button unchecked if not toggled -->
        <div class="form-group">
            <button type="button" id="advanced-settings-btn" tabindex="1" style="display:none;">Advanced settings <span
                    id="arrow-down">▼</span></button>
            <div id="assign-to-all" style="display: none;">
                <input type="checkbox" id="all-users" name="all-users" class="assign-to-all-checkbox">
                <label for="all-users">Assign question(s) to all titles</label>
            </div>
        </div>

        <!-- Button for submiting the form and sending data to endpoint -->
        <div class="form-group">
            <?= Html::button('Submit', ['class' => 'btn btn-success', 'id' => 'submit-btn', 'style' => 'display: none;']) ?>
        </div>

        <div class="form-group">

        </div>

        <?php ActiveForm::end(); ?>

    </div>
</div>

<?php
$createQuestionsUrl = Url::to(['training-questions/save-questions']);
$fetchQuestionsUrl = Url::to(['training-questions/fetch-questions']);
$script = <<<JS

// Event handler for title selection change
$('#title-select').on('change', function() {
    var selectedTitle = $(this).val();
    var titleText = $('#title-select option:selected').text();
    console.log(titleText);
    if (selectedTitle) {
        // Make an AJAX request to fetch questions related to the selected title
        $.ajax({
            // Url to fetch questions
            url: '$fetchQuestionsUrl',
            // Request method
            type: 'GET',
            // Data sent to the server
            data: { title: titleText },
            // Callback function for successful request
            success: function(response) {
                // If the response is successfull, update the constraints
                if (response.success) {
                    $('#questions-container').html(response.html);
                } 
                // If no questions found, display a default question form
                else {
                    $('#questions-container').html(
                        '<div class="question-item">' +
                            '<label>Question 1</label>' +
                            '<div class="form-group">' +
                                '<select name="TrainingQuestions[0][type]" class="form-control question-type title-dropdown">' +
                                    '<option value="">Select Type</option>' +
                                    '<option value="text">Text</option>' +
                                    '<option value="number">Number</option>' +
                                    '<option value="range">Range</option>' +
                                '</select>' +
                            '</div>' +
                            '<div class="form-group">' +
                                '<input type="text" name="TrainingQuestions[0][question]" class="form-control question-text" placeholder="Enter your question here">' +
                            '</div>' +
                        '</div>'
                    );
                }
                // Show the questions container and action buttons
                $('#questions-container').show();
                $('#add-question-btn').show();
                $('#advanced-settings-btn').show();
                $('#submit-btn').show();
                // Update the labels for each question
                updateQuestionLabels();
                // Show the remove question button if there is more than one question
                if ($('.question-item').length > 1) {
                    $('#remove-question-btn').show();
                } else {
                    $('#remove-question-btn').hide();
                }
            },
            // Callback function for error response
            error: function(xhr, status, error) {
                alert('Error occurred while fetching questions.');
                console.log("Error details:", xhr.responseText, status, error);
            }
        });
    } 
    // If no title is selected, reset the questions container with a default question form
    else {
        $('#questions-container').html(
            '<div class="question-item">' +
                '<label>Question 1</label>' +
                '<div class="form-group">' +
                    '<select name="TrainingQuestions[0][type]" class="form-control question-type title-dropdown">' +
                        '<option value="">Select Type</option>' +
                        '<option value="text">Text</option>' +
                        '<option value="number">Number</option>' +
                        '<option value="range">Range</option>' +
                    '</select>' +
                '</div>' +
                '<div class="form-group">' +
                    '<input type="text" name="TrainingQuestions[0][question]" class="form-control question-text" placeholder="Enter your question here">' +
                '</div>' +
            '</div>'
        );
        // Hide the questions container and action buttons
        $('#questions-container').hide();
        $('#add-question-btn').hide();
        $('#remove-question-btn').hide();
        $('#advanced-settings-btn').hide();
        $('#submit-btn').hide();
        $('#assign-to-all').hide();
    }
});
// Event handler for the "Add Question" click
$('#add-question-btn').on('click', function() {
    // Get the current number of question items
    var questionIndex = $('.question-item').length;
    
    // Generate a new question item HTML
    var newQuestionItem = 
        '<div class="question-item">' +
            '<label>Question ' + (questionIndex + 1) + '</label>' +
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
        '</div>';

    // Appemd the new question item to the questions container
    $('#questions-container').append(newQuestionItem);
    // Update the labels for all question items
    updateQuestionLabels();
    // Show the "Remmove Question" button if there is more than one question item
    if ($('.question-item').length > 1) {
        $('#remove-question-btn').show();
    }
});

// Event handler for the "Remove Question" button click
$('#remove-question-btn').on('click', function() {
    // Remove the last question item
    $('.question-item').last().remove();
    // Update the labels for all remaining question items
    updateQuestionLabels();
    // Hide the "Remove Question" button if only one question item remains
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
    // Get the form element
    var form = document.getElementById('training-questions-form');
    // Create a FormData object from the form
    var formData = new FormData(form);

    // Check if "Assign to all titles" is checked
    var assignAll = $('#all-users').is(':checked');
    if (assignAll) {
        // Get the titles from the hidden input
        var titles = JSON.parse($('#titles-json').val());
        // Append all titles to the form data
        titles.forEach(function(title) {
            formData.append('titles[]', title);
        });
    } else {
        var selectedTitle = $('#title-select').val();
        formData.append('titles[]', selectedTitle);
    }

    let isValid = true;

    // Iterate over each input field to collect data and validate
    $('.question-text').each(function() {
        // Get the name attribute of the input
        let inputName = $(this).attr('name');
        // Get the value of the input
        let inputValue = $(this).val();

        // If the input value is empty, mark the input as invalid and highlight it
        if (!inputValue) {
            isValid = false;
            $(this).css('border', '2px solid red');
        }
        // If input value is valid, reset the border color
        else {
            $(this).css('border', '1px solid #dee2e6');
        }
    });

    if (isValid) {
        // Make an AJAX request to submit the form data
        $.ajax({
            // URL to save questions
            url: '$createQuestionsUrl',
            // Request method
            type: 'POST',
            // Form data
            data: formData,
            // Prevent jQuery from processing the data
            processData: false,
            // Prevent jQuery from setting the content type
            contentType: false,
            // Callback function for successful response
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
    }
});

$('#all-users').on('change', function() {
    console.log("Click on checkbox");
    $('#title-select').prop('disabled', this.checked);
    console.log('Checkbox is ' + (this.checked ? 'checked' : 'unchecked'));
});

// Function to update labels of all question items
function updateQuestionLabels() {
    $('.question-item').each(function(index) {
        // Update label text
        $(this).find('label').text('Question ' + (index + 1));
    });
}

// Document ready function to initialize the form
$(document).ready(function() {
    // Update labels when the document is ready
    updateQuestionLabels();
    if ($('.question-item').length <= 1) {
        // Hide the "Remove Question" button if only one question item
        $('#remove-question-btn').hide();
    }
});


JS;
$this->registerJs($script);
?>

<style>
    .title-dropdown {
        width: 190px;
    }
</style>