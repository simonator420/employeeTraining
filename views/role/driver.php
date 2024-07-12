<?php

use yii\helpers\Html;
use yii\helpers\Url;

?>

<!-- View for displaying informations about the users. -->
<div class="user-info-container">
    <div class="user-info-card">
        <h1>Welcome to the <b> <?= Html::encode($title) ?> </b> training</h1>
        <?= Html::a('Submit', Url::to(['/dashboard']), ['class' => 'btn btn-primary', 'id' => 'submit-btn']) ?>
    </div>
</div>

<?php
$completeTrainingUrl = Url::to(['role/complete-training']);
$script = <<<JS
let trainingCompleted = false;

// Prevent navigation away from the page
// window.onbeforeunload = function(a) {
//     a.preventDefault();
//     if (!trainingCompleted) {
//         alert("You cannot leave this page until you complete the training.");
//         return "You cannot leave this page until you complete the training.";
//     }
// };

// Disable all links except the submit button
// $('a').not('#submit-btn').on('click', function(e) {
//     if (!trainingCompleted) {
//         e.preventDefault();
//         alert("You cannot leave this page until you complete the training.");
//     }
// });

// Handle submit button click
$('#submit-btn').on('click', function(e) {
    e.preventDefault();
    if (!trainingCompleted) {
        $.ajax({
            url: '$completeTrainingUrl',
            type: 'POST',
            data: {
                _csrf: yii.getCsrfToken()
            },
            success: function(response) {
                if (response.success) {
                    alert('Thank you for completing the training!');
                    trainingCompleted = true;
                    window.onbeforeunload = null; // Allow navigation away from the page
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