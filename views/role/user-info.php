<?php

use yii\helpers\Html;
use yii\helpers\Url;

?>

<!-- View for displaying informations about the users. -->
<div class="user-info-container">
    <div class="user-info-card">
        <h1>User Info</h1>
        <?php foreach ($users as $user): ?>
            <p>
                <strong>User:</strong> <?= Html::encode($user->username) ?> (ID: <?= $user->id ?>)<br>
                <strong>Title:</strong> <?= Html::encode($user->profile->title) ?> <br>
                <strong>Roles:</strong>
                <?php
                $groups = $user->getGroups()->all();
                $groupNames = array_map(function ($group) {
                    return $group->name;
                }, $groups);
                echo Html::encode(implode(', ', $groupNames));
                ?>
            </p>
            <input type="checkbox" class="toggle-info-btn" data-id="<?= $user->id ?>" <?= $user->profile->assigned_training ? 'checked' : '' ?>>
            <hr>
        <?php endforeach; ?>
    </div>
</div>

<?php
$toggleTrainingUrl = Url::to(['role/toggle-training']);
$script = <<< JS
    $(document).on('change', '.toggle-info-btn', function() {
        var userId = $(this).data('id');
        var assignedTraining = $(this).is(':checked') ? 1 : 0;

        $.ajax({
            url: '$toggleTrainingUrl',
            type: 'POST',
            data: {
                id: userId,
                assigned_training: assignedTraining,
                _csrf: yii.getCsrfToken()
            },
            success: function(response) {
                if (response.success) {
                    console.log('Update successful');
                } else {
                    console.log('Update failed');
                }
            },
            error: function() {
                console.log('Error in AJAX request');
                alert("Error uz zase");
            }
        });
    });
JS;
$this->registerJs($script);
?>