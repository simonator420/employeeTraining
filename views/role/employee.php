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
        <p class="welcome-text">Dear <b> <?= Html::encode($firstName) ?></b>, you have been assigned this training.
            Please complete it at your earliest convenience. Please note that if the page is refreshed during the
            training, your inputs will not be saved.</p><br>

        <!-- Display different questions based on the title -->
        <?php if ($title == 'Service Driver'): ?>
            <div class="question-container">
                <p>How much time did you spend learning the new driving regulations?</p>
                <?= Html::input('text', 'driver_one', '', ['class' => 'form-control driver-input', 'placeholder' => 'Enter your answer here']) ?>
            </div>
            <div class="question-container">
                <p>On a scale of 1-5, how satisfied were you with your driving workload last month?</p>
                <div class="input-container">
                    <?= Html::input('number', 'driver_two', '', ['class' => 'form-control driver-input input-number', 'min' => '1', 'max' => '5', 'placeholder' => '1-5']) ?>
                </div>
            </div>

        <?php elseif ($title == 'Accountant'): ?>
            <div class="question-container">
                <p><b>How do you relate with new accounting trends?</b></p>
                <?= Html::input('text', 'accounting_one', '', ['class' => 'form-control accountant-input', 'placeholder' => 'Enter your answer here']) ?>
            </div>
            <div class="question-container">
                <p><b>This is just a test question, but don't hesitate to write something to it.</b></p>
                <?= Html::input('text', 'accounting_two', '', ['class' => 'form-control accountant-input', 'placeholder' => 'Enter your answer here']) ?>
            </div>
            <div class="question-container">
                <p><b>On a scale of 1-5, how satisfied were you with your accounting workload last month?</b></p>
                <div class="input-container">
                    <?= Html::input('number', 'accounting_three', '', ['class' => 'form-control accountant-input input-number', 'min' => '1', 'max' => '5', 'placeholder' => '1-5']) ?>
                </div>
            </div>
            <div class="question-container">
                <p><b>Using the range display how much do you enjoy working with your colleagues.</b></p>
                <div class="range-container">
                    <span>Not much</span>
                    <?= Html::input('range', 'accounting_four', '50', ['class' => 'form-control accountant-input', 'min' => '1', 'max' => '100']) ?>
                    <span>Very much</span>
                </div>
            </div>
        <?php endif; ?>


        <!-- Hyperlink styled as a button for submitting the form and navigating back to dashboard -->
        <?= Html::a('Submit', Url::to(['/dashboard']), ['class' => 'btn btn-primary', 'id' => 'submit-btn']) ?>
    </div>
</div>

<?php
// URL for the function in RoleController - endpoint
$completeTrainingUrl = Url::to(['role/complete-training']);
$script = <<<JS

// Initializing a flag so the form can be submitted once until the page is reloaded
let trainingCompleted = false;


$('#submit-btn').on('click', function(e) {
    e.preventDefault();
    
    // Ensuring that the form can be only submitted once
    if (!trainingCompleted) {
        // Flag to determine if all form inputs are valid
        let isValid = true;
        // Object initialized with CSRF token for secure server-side processing
        let data = {
            _csrf: yii.getCsrfToken()
        };

        // Function to gather input values and check if they are valid
        function gatherInputValues(inputClass, data) {
            // Itirating over each element with the given class
            $('.' + inputClass).each(function() {
                // Retrieves the name attribute of the current input element
                let inputName = $(this).attr('name');
                // Retrieves the value of the current input element
                let inputValue = $(this).val();

                // If the input field is empty then the border is colored red
                if (!inputValue) {
                    isValid = false;
                    $(this).css('border', '2px solid red');
                }
                // If the input field is not empty, it stays black 
                else {
                    $(this).css('border', '1px solid #dee2e6');
                }

                // The value is added to the data object with the key as inputName e.g. 'accounting_one' => 'I like it here'
                data[inputName] = inputValue;
            });
        }

        // If the user has title Service Driver
        if ('$title' === 'Service Driver') {
            gatherInputValues('driver-input', data);
        } 
        // If the user has title Accountant
        else if ('$title' === 'Accountant') {
            gatherInputValues('accountant-input', data);
        }

        // Execution of the code only if all the input data are valid
        if (isValid) {
            // console.log(data);
            // Request to the server to submit the form data
            $.ajax({
                // Pointer to actionCompleteTraining function in RoleController where the data shoudl be send
                url: '$completeTrainingUrl',
                // HTTP method used for the request
                type: 'POST',
                // Data object that contains the form data and CSRF token
                data: data,
                // This function is executed if the AJAX request is successful, it receives the server's response as its argument
                success: function(response) {
                    // Checks if the the response is success
                    if (response.success) {
                        alert('Thank you for completing the training!');
                        // Setting the trainingCompleted to true to prevent further submissions
                        trainingCompleted = true;
                        // Getting the current time and setting the context of the element with training-complete-time and user's id to currentTime string
                        var currentTime = new Date().toISOString().slice(0, 19).replace('T', ' ');
                        $('#training-complete-time-' + response.userId).text(currentTime);
                        // Redirects the user to the specified url in the href attribute of submit-btn
                        window.location.href = $('#submit-btn').attr('href');
                    } else {
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

// Selecting all input elements of type "number" that also have the class form-controll
$('.form-control[type="number"]').on('input', function() {
    // Storing the current input element in the value variable
    var value = $(this).val();
    // If the input is less than 1 then the value is set to 1
    if (value < 1) {
        $(this).val(1);
    } 
    // If the input is bigger then 5 then the value is sey to 5
    else if (value > 5) {
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

    input[type=range] {
        -webkit-appearance: none;
        appearance: none;
        width: 100%;
        height: 1px;
        /* background: #d3d3d3; */
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