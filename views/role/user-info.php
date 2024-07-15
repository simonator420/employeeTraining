<?php

use yii\helpers\Html;
use yii\helpers\Url;

?>

<!-- View for displaying informations about the users. -->
<div class="user-info-container">
    <div class="user-info-card">
        <h1>Employee Training Overviews</h1>
        <!-- Loop through each user and display their information -->
        <?php foreach ($users as $user): ?>
            <p>
                <!-- Display the user's username and id -->
                <strong>User:</strong> <?= Html::encode($user->username ?: 'N/A') ?> (ID: <?= $user->id ?>)<br>

                <!-- Display the user's fullname -->
                <strong>Full name:</strong> <?= Html::encode($user->profile->firstname) ?>
                <?= Html::encode($user->profile->lastname) ?> <br>

                <!-- Display the user's title -->
                <strong>Title:</strong> <?= Html::encode($user->profile->title ?: 'N/A') ?><br>

                <!-- Display the user's address by concatenating available address components -->
                <strong>Address:</strong>
                <?php
                $addressComponents = [
                    $user->profile->street,
                    $user->profile->city,
                    $user->profile->zip,
                    $user->profile->country,
                    $user->profile->state,
                ];

                // Filtering out any empty values from the array
                $filteredAddressComponents = array_filter($addressComponents);

                if (empty($filteredAddressComponents)) {
                    echo 'N/A';
                } else {
                    echo Html::encode(implode(', ', $filteredAddressComponents)); // implode joins all elements into one string
                }
                ?><br>

                <!-- Display the user's roles by concatenating group names -->
                <strong>Roles:</strong>
                <?php
                // Retrieves all the groups that the user is a part of
                $groups = $user->getGroups()->all();
                // Aplying callback to each element (group) in the array (groups) and getting array (groupNames) of all the group names
                $groupNames = array_map(function ($group) {
                    return $group->name;
                }, $groups);
                echo Html::encode(!empty($groupNames) ? implode(', ', $groupNames) : 'N/A');
                ?><br>

                <!-- Display the user's last login time -->
                <strong>Last login:</strong> <?= Html::encode($user->last_login ?: 'N/A') ?><br> <br>

                <!-- Display the time when last training was assigned for user -->
                <strong>Training Assigned Time:</strong>
                <!-- Assigning unique id to the span element e.g. 'training-assigned-time-123' -->
                <span id="training-assigned-time-<?= $user->id ?>">
                    <?= Html::encode($user->profile->training_assigned_time ?: 'N/A') ?>
                </span><br>

                <!-- Display the time when user completed the training with dynamic class for color coding-->
                <!-- Assigning right CSS class (text color) based on the conditions -->
                <strong>Training Complete Time:</strong>
                <span id="training-complete-time-<?= $user->id ?>"
                    class="<?= $user->profile->training_complete_time ? 'text-green' : ($user->profile->assigned_training && $user->profile->training_assigned_time !== null ? 'text-red' : 'text-black') ?>">
                    <?= Html::encode($user->profile->training_complete_time ?: 'N/A') ?>
                </span>

            </p>

            <!-- Checkbox to assign/unassign training -->
            <label>
                <!-- Adding a checkbox with custom data-id attribute for storing the user id in the checkbox -->
                <input type="checkbox" class="toggle-info-btn" data-id="<?= $user->id ?>"
                    <?= $user->profile->assigned_training ? 'checked' : '' ?>>
                Assigned Training
            </label>
            <hr>
        <?php endforeach; ?>
    </div>
</div>

<?php
// URL for the function in RoleController
$toggleTrainingUrl = Url::to(['role/toggle-training']);
$script = <<<JS
    // jQuery event handler for checkbox change 
    $(document).on('change', '.toggle-info-btn', function() {
        
        // Get user ID from the data attribute
        // this refers to the checkbox
        // data('id') refers to the data-id attribute
        var userId = $(this).data('id');

        // Determine if training is assigned
        // $(this).is(':checked') returns true if the checkbox is checked and vice versa
        // if checkbox is checked it sets the variable 1 and to 0 if not
        var assignedTraining = $(this).is(':checked') ? 1 : 0;

        // Get current time in ISO format
        // Returns current time if assigned training is 1 and null if its 0
        var currentTime = null;
        if (assignedTraining) {
            var now = new Date();
             // Adjusting the time to local timezone
            var localTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
            currentTime = localTime.toISOString().slice(0, 19).replace('T', ' ');
        }

        // AJAX request to update the training status
        $.ajax({
            // Specifies URL to which request is sent
            url: '$toggleTrainingUrl',
            
            // Specifies the type of HTTP request
            type: 'POST',
            
            // Specifies the data to be sent to the server with the request
            data: {
                id: userId,
                assigned_training: assignedTraining,
                training_assigned_time: currentTime,
                _csrf: yii.getCsrfToken() // Token that protects against CSRF attacks
            },
            
            success: function(response) {
                // Check if the server response indicates success
                if (response.success) {
                    // Selecting the two spans
                    var assignedTimeElement = $('#training-assigned-time-' + userId);
                    var completeTimeElement = $('#training-complete-time-' + userId);
                    if (assignedTraining) {
                        // Sets the text to current time if training is assigned 
                        assignedTimeElement.text(currentTime);
                        // Sets the text to N/A and sets it to red color
                        completeTimeElement.text('N/A').removeClass('text-green text-black').addClass('text-red');
                    } else {
                        // Sets the text to N/A if training is not assigned
                        assignedTimeElement.text('N/A');
                        // Sets the text to black color
                        completeTimeElement.text('N/A').removeClass('text-green text-red').addClass('text-black');
                    }
                } else {
                    console.log('Update failed');
                }
            },
            error: function() {
                console.log('Error in AJAX request');
            }
        });
    });
JS;
$this->registerJs($script);
?>

<style>
    .text-green {
        color: green;
    }

    .text-red {
        color: red;
    }

    .text-black {
        color: rgb(85, 85, 85);
    }
</style>