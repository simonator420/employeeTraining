<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

?>

<div class="training-question-container">
    <div class="training-questions-form">

        <h1>Edit Training Questions</h1>

        <br>

        <?php $form = ActiveForm::begin([
            'id' => 'training-questions-form',
            'enableAjaxValidation' => false,
            'enableClientValidation' => true,
        ]); ?>


        <div class="form-group">
            <label>Select Title</label><br>
            <?= Html::dropDownList('title', null, array_combine($titles, $titles), ['prompt' => 'Select Title', 'class' => 'form-control', 'id' => 'title-select']) ?>
        </div>

        <div id="questions-container" style="display: none;">
            <div class="question-item">
                <label>Question 1</label>
                <div class="form-group">
                    <?= Html::dropDownList('TrainingQuestions[0][type]', null, ['text' => 'Text', 'number' => 'Number', 'range' => 'Range'], ['prompt' => 'Select Type', 'class' => 'form-control question-type']) ?>
                </div>
                <div class="form-group">
                    <?= Html::textInput('TrainingQuestions[0][question]', '', ['class' => 'form-control question-text', 'placeholder' => 'Enter your question here']) ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <button type="button" id="add-question-btn" class="btn btn-secondary" style="display: none;">+ Add
                Question</button>
            <button type="button" id="remove-question-btn" class="btn btn-danger" style="display: none;">- Remove
                Question</button>
        </div>

        <div class="form-group">
            <?= Html::button('OK', ['class' => 'btn btn-success', 'id' => 'submit-btn', 'style' => 'display: none;']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
</div>

<?php
$createQuestionsUrl = Url::to(['training-questions/save-questions']);
$fetchQuestionsUrl = Url::to(['training-questions/fetch-questions']);
$script = <<<JS
$('#title-select').on('change', function() {
    var selectedTitle = $(this).val();
    var titleText = $('#title-select option:selected').text();
    console.log(titleText);
    if (selectedTitle) {
        $.ajax({
            url: '$fetchQuestionsUrl',
            type: 'GET',
            data: { title: titleText },
            success: function(response) {
                if (response.success) {
                    $('#questions-container').html(response.html);
                } else {
                    $('#questions-container').html(
                        '<div class="question-item">' +
                            '<label>Question 1</label>' +
                            '<div class="form-group">' +
                                '<select name="TrainingQuestions[0][type]" class="form-control question-type">' +
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
    } else {
        $('#questions-container').html(
            '<div class="question-item">' +
                '<label>Question 1</label>' +
                '<div class="form-group">' +
                    '<select name="TrainingQuestions[0][type]" class="form-control question-type">' +
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
        $('#questions-container').hide();
        $('#add-question-btn').hide();
        $('#remove-question-btn').hide();
        $('#submit-btn').hide();
    }
});

$('#add-question-btn').on('click', function() {
    var questionIndex = $('.question-item').length;
    var newQuestionItem = 
        '<div class="question-item">' +
            '<label>Question ' + (questionIndex + 1) + '</label>' +
            '<div class="form-group">' +
                '<select name="TrainingQuestions[' + questionIndex + '][type]" class="form-control question-type">' +
                    '<option value="">Select Type</option>' +
                    '<option value="text">Text</option>' +
                    '<option value="number">Number</option>' +
                    '<option value="range">Range</option>' +
                '</select>' +
            '</div>' +
            '<div class="form-group">' +
                '<input type="text" name="TrainingQuestions[' + questionIndex + '][question]" class="form-control question-text" placeholder="Enter your question here">' +
            '</div>' +
        '</div>';

    $('#questions-container').append(newQuestionItem);
    updateQuestionLabels();
    if ($('.question-item').length > 1) {
        $('#remove-question-btn').show();
    }
});

$('#remove-question-btn').on('click', function() {
    $('.question-item').last().remove();
    updateQuestionLabels();
    if ($('.question-item').length <= 1) {
        $('#remove-question-btn').hide();
    }
});

$('#submit-btn').on('click', function() {
    var form = document.getElementById('training-questions-form');
    var formData = new FormData(form);

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

function updateQuestionLabels() {
    $('.question-item').each(function(index) {
        $(this).find('label').text('Question ' + (index + 1));
    });
}

$(document).ready(function() {
    updateQuestionLabels();
    if ($('.question-item').length <= 1) {
        $('#remove-question-btn').hide();
    }
});
JS;
$this->registerJs($script);
?>