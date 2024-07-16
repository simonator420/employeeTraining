<?php

use yii\helpers\Html;
use yii\helpers\Url;

?>

<!-- View for displaying the training screen for the user -->
<!-- TODO center content, make it more nice, add content based on title of the user (probably forms), figure out the way how to send the data somewhere -->
<div class="employee-training-container">
    <div class="employee-training-card">

        <!-- Displays a welcome message including an encoded profile title -->
        <h1>Welcome to the <b> <?= Html::encode($title) ?> </b> training</h1>

        <!-- Display different questions based on the title -->
        <?php if ($title == 'Service Driver'): ?>
            <div class="question-container">
                <p>How much time did you spend learning the new driving regulations?</p>
                <?= Html::input('text', 'driver_learning_time', '', ['class' => 'form-control driver-input', 'placeholder' => 'Enter your answer here']) ?>
            </div>
            <div class="question-container">
                <p>On a scale of 1-5, how satisfied were you with your driving workload last month?</p>
                <div class="input-container">
                    <?= Html::input('number', 'driver_workload_satisfaction', '', ['class' => 'form-control driver-input input-number', 'min' => '1', 'max' => '5', 'placeholder' => '1-5']) ?>
                </div>
            </div>

        <?php elseif ($title == 'Accountant'): ?>
            <div class="question-container">
                <p>How do you relate with new accounting trends?</p>
                <?= Html::input('text', 'accounting_trends', '', ['class' => 'form-control accountant-input', 'placeholder' => 'Enter your answer here']) ?>
            </div>
            <div class="question-container">
                <p>On a scale of 1-5, how satisfied were you with your accounting workload last month?</p>
                <div class="input-container">
                    <?= Html::input('number', 'accounting_workload_satisfaction', '', ['class' => 'form-control accountant-input input-number', 'min' => '1', 'max' => '5', 'placeholder' => '1-5']) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Hyperlink styled as a button for submitting the form and navigating back to dashboard -->
        <?= Html::a('Submit', Url::to(['/dashboard']), ['class' => 'btn btn-primary', 'id' => 'submit-btn']) ?>
    </div>
</div>

<?php
// URL for the function in RoleController
$completeTrainingUrl = Url::to(['role/complete-training']);
$script = <<<JS

// Initializing a flag to prevent multiple submissions
let trainingCompleted = false;

// Handle submit button click
$('#submit-btn').on('click', function(e) {
    // Prevents the default action of the click event
    e.preventDefault();
    
    // Checks if training is not already completed
    if (!trainingCompleted) {
    
        // Validate input fields
        let isValid = true;
        let inputClass = '';

        if ('$title' === 'Service Driver') {
            inputClass = 'driver-input';
        } else if ('$title' === 'Accountant') {
            inputClass = 'accountant-input';
        }

        $('.' + inputClass).each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).css('border', '2px solid red');
            } else {
                $(this).css('border', '1px solid #dee2e6');
            }
        });

        // If all inputs are valid, proceed with AJAX request
        if (isValid) {
            // Sends and AJAX POST request to the server
            $.ajax({
                // URL to send the request to
                url: '$completeTrainingUrl',
        
                // HTTP method to use
                type: 'POST',
        
                // Data to send
                data: {
                    _csrf: yii.getCsrfToken(), // Sending the CSRF token for security
                    driver_learning_time: $('input[name="driver_learning_time"]').val(),
                    driver_workload_satisfaction: $('input[name="driver_workload_satisfaction"]').val(),
                    accounting_trends: $('input[name="accounting_trends"]').val(),
                    accounting_workload_satisfaction: $('input[name="accounting_workload_satisfaction"]').val()
                },

                // Handling a successful response from the server
                success: function(response) {

                    // Checks if the responses indicates success
                    if (response.success) {
                        alert('Thank you for completing the training!');

                        // Sets the trainingCompleted flag to true to prevent further submissions
                        trainingCompleted = true;

                        // Update training complete time element with the current time
                        var currentTime = new Date().toISOString().slice(0, 19).replace('T', ' ');
                        $('#training-complete-time-' + response.userId).text(currentTime);
                        
                        // Redirecting the user to the URL specified in the href attribute of the submit button
                        window.location.href = $('#submit-btn').attr('href');
                        
                    } else {
                        alert('Failed to complete the training. Please try again or contact the System Admininstrator.');
                    }
                },
                error: function() {
                    alert('Error in AJAX request. Please try again.');
                }
            });
        }
    }
});

$('.form-control[type="number"]').on('input', function() {
    var value = $(this).val();
    if (value < 1) {
        $(this).val(1);
    } else if (value > 5) {
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
        text-align: center;
        max-width: 70vh;
        width: 100%;
    }

    .employee-training-card h1 {
        color: #333333;
        margin-bottom: 20px;
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
        /* Ensure padding remains the same */
        border: 2px solid rgb(85, 85, 85);
        /* Ensure border remains the same */
        outline: none;
        /* Remove outline on focus */
    }

    .question-container {
        text-align: left;
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

    .input-container {
        text-align: left;
    }

    .input-number {
        width: auto;
        display: inline-block;
        padding: 5px;
        font-size: 14px;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        /* margin-top: 10px; */
    }
</style>