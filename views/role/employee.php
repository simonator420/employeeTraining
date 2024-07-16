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
                <?= Html::input('text', 'driver_learning_time', '', ['class' => 'form-control', 'placeholder' => 'Enter your answer here']) ?>
            </div>
        <?php elseif ($title == 'Accountant'): ?>
            <div class="question-container">
                <p>How do you relate with new accounting trends?</p>
                <?= Html::input('text', 'accounting_trends', '', ['class' => 'form-control', 'placeholder' => 'Enter your answer here']) ?>
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
    
        // Sends and AJAX POST request to the server
        $.ajax({
            // URL to send the request to
            url: '$completeTrainingUrl',
    
            // HTTP method to use
            type: 'POST',
    
            // Data to send
            data: {
                _csrf: yii.getCsrfToken() // Sending the CSRF token for security
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
                    alert('Failed to complete the training. Please try again.');
                }
            },
            error: function() {
                alert('Error in AJAX request. Please try again.');
            }
        });
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

    .btn-primary:hover {
        background-color: #ffffff;
        color: rgb(85, 85, 85) !important;
        /* Ensure the text color is correctly applied */
        border-color: rgb(85, 85, 85);
        border-radius: 4px;
    }

    .question-container {
        text-align: left;
        margin-bottom: 20px;
    }
</style>