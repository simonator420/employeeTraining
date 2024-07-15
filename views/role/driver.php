<?php

use yii\helpers\Html;
use yii\helpers\Url;

?>

<!-- View for displaying the training screen for the user -->
<!-- TODO center content, make it more nice, add content based on title of the user (probably forms), figure out the way how to send the data somewhere -->
<div class="user-info-container">
    <div class="user-info-card">
        
        <!-- Displays a welcome message including an encoded profile title -->
        <h1>Welcome to the <b> <?= Html::encode($title) ?> </b> training</h1>
        
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