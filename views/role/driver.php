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
                    // Update training complete time dynamically
                    var currentTime = new Date().toISOString().slice(0, 19).replace('T', ' ');
                    $('#training-complete-time-' + response.userId).text(currentTime);
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