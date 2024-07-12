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
                <strong>User:</strong> <?= Html::encode($user->username ?: 'N/A') ?> (ID: <?= $user->id ?>)<br>
                <strong>Full name:</strong> <?= Html::encode($user->profile->firstname) ?> <?= Html::encode($user->profile->lastname) ?> <br>
                <strong>Title:</strong> <?= Html::encode($user->profile->title ?: 'N/A') ?><br>
                <strong>Address:</strong>
                <?php
                $addressComponents = [
                    $user->profile->street,
                    $user->profile->city,
                    $user->profile->zip,
                    $user->profile->country,
                    $user->profile->state,
                ];

                $filteredAddressComponents = array_filter($addressComponents);

                if (empty($filteredAddressComponents)) {
                    echo 'N/A';
                } else {
                    echo Html::encode(implode(', ', $filteredAddressComponents));
                }
                ?>
                <br>
                <strong>Roles:</strong>
                <?php
                $groups = $user->getGroups()->all();
                $groupNames = array_map(function ($group) {
                    return $group->name;
                }, $groups);
                echo Html::encode(!empty($groupNames) ? implode(', ', $groupNames) : 'N/A');
                ?><br>
                <strong>Last login:</strong> <?= Html::encode($user->last_login ?: 'N/A') ?><br> <br>
                <strong>Training Assigned Time:</strong> <span
                    id="training-assigned-time-<?= $user->id ?>"><?= Html::encode($user->profile->training_assigned_time ?: 'N/A') ?></span><br>
                <strong>Training Complete Time:</strong> <span
                    id="training-complete-time-<?= $user->id ?>"><?= Html::encode($user->profile->training_complete_time ?: 'N/A') ?></span>

            </p>
            <label>
                <input type="checkbox" class="toggle-info-btn" data-id="<?= $user->id ?>"
                    <?= $user->profile->assigned_training ? 'checked' : '' ?>>
                Assigned Training
            </label>
            <hr>
        <?php endforeach; ?>
    </div>
</div>

<?php
$toggleTrainingUrl = Url::to(['role/toggle-training']);
$script = <<<JS
    $(document).on('change', '.toggle-info-btn', function() {
        var userId = $(this).data('id');
        var assignedTraining = $(this).is(':checked') ? 1 : 0;
        var currentTime = assignedTraining ? new Date().toISOString().slice(0, 19).replace('T', ' ') : null;

        $.ajax({
            url: '$toggleTrainingUrl',
            type: 'POST',
            data: {
                id: userId,
                assigned_training: assignedTraining,
                training_assigned_time: currentTime,
                _csrf: yii.getCsrfToken()
            },
            success: function(response) {
                if (response.success) {
                    console.log('Update successful');
                    if (assignedTraining) {
                        $('#training-assigned-time-' + userId).text(currentTime);
                        $('#training-complete-time-' + userId).text('N/A');
                    } else {
                        $('#training-assigned-time-' + userId).text('N/A');
                    }
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