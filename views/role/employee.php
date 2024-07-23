<?php

use yii\helpers\Html;
use yii\helpers\Url;

?>

<div class="employee-training-container">
    <div class="employee-training-card">

        <h1>Welcome to the <b> <?= Html::encode($title) ?> </b> training</h1>
        <p class="welcome-text">Dear <b> <?= Html::encode($firstName) ?></b>, you have been assigned this training.
            Please complete it at your earliest convenience. Please note that if the page is refreshed during the
            training, your inputs will not be saved.</p><br>

        <!-- Questions will be dynamically loaded here based on the user's title -->
        <div id="questions-container"></div>

        <?= Html::a('Submit', Url::to(['/dashboard']), ['class' => 'btn btn-primary', 'id' => 'submit-btn']) ?>
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
    // AJAX request to fetch questions based on the title
    $.ajax({
        url: '$displayQuestionsUrl',
        type: 'GET',
        success: function(response) {
            // If the response is successful, display the questions in the container
            if (response.success) {
                $('#questions-container').html(response.html);
            }
            // If no questions are available, display a message
            else {
                $('#questions-container').html('<p>No questions available.</p>');
            }
        },
        // Error function if an error occurs while fetching questions
        error: function() {
            alert('Error occurred while fetching questions.');
        }
    });
});

// Flag to check if training is completed
let trainingCompleted = false;

// Event handler for the submit button click
$('#submit-btn').on('click', function(e) {
    // Prevent the default form submissions
    e.preventDefault();
    
    if (!trainingCompleted) {
        // Flag to check if the form inputs are valid
        let isValid = true;
        // Data object to hold form inputs and CSRF token
        let data = { _csrf: yii.getCsrfToken() };

        // Iterate over each input field to collect data and validate
        $('.question-input').each(function() {
            // Get the name attribute of the input
            let inputName = $(this).attr('name');
            // Get the value of the input
            let inputValue = $(this).val();
            console.log(inputName);
            console.log(inputValue);

            // If the input value is empty, mark the input as invalid and highlight it
            if (!inputValue) {
                isValid = false;
                $(this).css('border', '2px solid red');
            }
            // If input value is valid, reset the border color
            else {
                $(this).css('border', '1px solid #dee2e6');
            }

            // Add the input name and value to the data object
            data[inputName] = inputValue;
        });
        
        if (isValid) {
            // If all inputs are valid, make an AJAX POST request to complete the training
            $.ajax({
                url: '$completeTrainingUrl',
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        // If the response is successful, mark the training as completed
                        console.log(data)
                        alert('Thank you for completing the training!');
                        trainingCompleted = true;
                        var currentTime = new Date().toISOString().slice(0, 19).replace('T', ' ');
                        // Update the training completion time
                        $('#training-complete-time-' + response.userId).text(currentTime);
                        // Redirect to the dashboard
                        window.location.href = $('#submit-btn').attr('href');
                    } else {
                        // If the response is not successful, alert the user
                        alert('Failed to complete the training. Please try again or contact System Administrator.');
                    }
                },
                error: function() {
                    alert('Error in AJAX request. Please try again or contact System Administrator.');
                }
            });
        }
    }
});


// Event handler for number input validation
$('.form-control[type="number"]').on('input', function() {
    var value = $(this).val();
    if (value < 1) {
        // Set minimum value to 1
        $(this).val(1);
    } else if (value > 5) {
        // Set maximum value to 5
        $(this).val(5);
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
