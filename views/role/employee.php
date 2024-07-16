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
                <p><b>Using the range display how much do you enjoy working with your colleagues</b></p>
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
// URL for the function in RoleController
$completeTrainingUrl = Url::to(['role/complete-training']);
$script = <<<JS

// Initializing a flag to prevent multiple submissions
// Initializing a flag to prevent multiple submissions
let trainingCompleted = false;

$('#submit-btn').on('click', function(e) {
    e.preventDefault();
    
    if (!trainingCompleted) {
        let isValid = true;
        let data = {
            _csrf: yii.getCsrfToken()
        };

        // Function to gather input values and check if they are valid
        function gatherInputValues(inputClass, data) {
            $('.' + inputClass).each(function() {
                let inputName = $(this).attr('name');
                let inputValue = $(this).val();

                if (!inputValue) {
                    isValid = false;
                    $(this).css('border', '2px solid red');
                } else {
                    $(this).css('border', '1px solid #dee2e6');
                }

                data[inputName] = inputValue;
            });
        }

        if ('$title' === 'Service Driver') {
            gatherInputValues('driver-input', data);
        } else if ('$title' === 'Accountant') {
            gatherInputValues('accountant-input', data);
        }

        if (isValid) {
            console.log(data);
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
                error: function() {
                    alert('Error in AJAX request. Please try again or contact System Administrator.');
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