<?php

use yii\helpers\Html;
use yii\helpers\Url;

?>

<!-- View for displaying informations about the users. -->
<div class="employee-overview-container">
    <div class="employee-info-card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1>Employee Training Overview</h1>
            <?= Html::a('Edit Questions', Url::to(['training-questions/questions']), ['class' => 'btn edit-question-btn']) ?>
        </div>

        <!-- Checkboxes for each title with label "Assign to all" -->
        <label>Assign training to all employees with title:</label>
        <div id="title-checkboxes">
            <?php foreach ($titles as $title): ?>
                <label class="checkbox-label">
                    <input type="checkbox" class="title-checkbox" value="<?= Html::encode($title) ?>">
                    <?= Html::encode($title) ?>
                </label>
            <?php endforeach; ?>
        </div>
        <br>

        <!-- Checkboxes for each storage location with label "with storage location in:" -->
        <label>With storage location in:</label>
        <div id="storage-location-checkboxes">
            <?php foreach ($storage_locations as $location): ?>
                <label class="checkbox-label">
                    <input type="checkbox" class="location-checkbox" value="<?= Html::encode($location) ?>">
                    <?= Html::encode($location) ?>
                </label>
            <?php endforeach; ?>
        </div>
        <br>

        <!-- Buttons for selecting all employee filters and confirmation of training assignment -->
        <button id="select-all-btn">Select all</button>
        <button id="confirm-selection-btn">Assign Now</button>

        <br>
        <br>

        <!-- Button to toggle visibility of the user list for specific training assignment -->
        <button id="toggle-user-list-btn">
            Assign training to specific user <span id="arrow-down">▼</span>
        </button>
        <br><br>

        <!-- User list for specific training assignment, hidden by default -->
        <div id="user-list" style="display: none;">
            <?php foreach ($users as $user): ?>
                <label class="checkbox-label">
                    <input type="checkbox" class="user-checkbox" value="<?= Html::encode($user->id) ?>">
                    <?= Html::encode($user->profile->firstname) ?>     <?= Html::encode($user->profile->lastname) ?>
                </label>
                <br>
            <?php endforeach; ?>
            <button id="select-all-users-btn">Select All</button>
            <button id="confirm-specific-users-btn">Assign Now</button>
        </div>

        <!-- Input for selecting date and time when the training should be assigned -->
        <label>Or select time when the training should be assigned.</label>
        <input type="datetime-local" id="training-time-picker" name="training-time">
        <button id="confirm-time-btn">OK</button>
        <br>

        <hr>
        <br>

        <!-- Loop through each user and display their information -->
        <?php foreach ($users as $user): ?>
            <div class="employee-info-container" style="display: flex;">
                <div class="employee-info" style="flex: 1; width: 50%;" data-id="<?= Html::encode($user->id) ?>"
                    data-title="<?= Html::encode($user->profile->title) ?>"
                    data-location="<?= Html::encode($user->profile->storage_location) ?>">

                    <p>
                        <!-- Display the user's username and id -->
                        <strong>User:</strong> <?= Html::encode($user->username ?: 'N/A') ?> (ID: <?= $user->id ?>)<br>

                        <!-- Display the user's fullname -->
                        <strong>Full name:</strong> <?= Html::encode($user->profile->firstname) ?>
                        <?= Html::encode($user->profile->lastname) ?> <br>

                        <!-- Display the user's title -->
                        <strong>Title:</strong> <?= Html::encode($user->profile->title ?: 'N/A') ?><br>

                        <!-- Display the user's address by concatenating available address components -->
                        <!-- <strong>Address:</strong>
                    <?php
                    // $addressComponents = [
                    //     $user->profile->street,
                    //     $user->profile->city,
                    //     $user->profile->zip,
                    //     $user->profile->country,
                    //     $user->profile->state,
                    // ];
                
                    // // Filtering out any empty values from the array
                    // $filteredAddressComponents = array_filter($addressComponents);
                
                    // if (empty($filteredAddressComponents)) {
                    //     echo 'N/A';
                    // } else {
                    //     echo Html::encode(implode(', ', $filteredAddressComponents)); // implode joins all elements into one string
                    // }
                    ?>
                    <br> -->

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

                        <!-- Display the user's storage_location -->
                        <strong>Storage Location:</strong>
                        <?= Html::encode($user->profile->storage_location ?: 'N/A') ?><br>

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
                        <span id="training-complete-time-<?= $user->id ?>" class="<?php

                          // Training has been completed by the employee
                          if ($user->profile->training_complete_time) {
                              echo 'text-green';
                          }
                          // Training has been scheduled for employee by admin
                          elseif (!$user->profile->assigned_training && $user->profile->training_assigned_time && !$user->profile->training_complete_time) {
                              echo 'text-orange';
                          }
                          // Training has been either set by the admin straight away or employee has seen the training that was scheduled but didn't complete it yet
                          elseif ($user->profile->assigned_training) {
                              echo 'text-red';
                          }
                          // Employee doesn't have any training set either completed 
                          else {
                              echo 'text-black';
                          }
                          ?>">
                            <!-- If employee didn't complete the training yet display N/A -->
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
                </div>

                <!-- Windows for displaying answers from user -->
                <?php if (!empty($latestAnswers[$user->id])): ?>
                    <div class="right-panel"
                        style="width: 50%; background-color: #ffffff; height: 215px; border: 2px solid transparent;border-color: rgb(85, 85, 85); border-radius: 4px; overflow-y: auto; padding: 5px">
                        <strong>Answers from latest training</strong>
                        <hr>
                        <?php foreach ($latestAnswers[$user->id] as $answer): ?>
                            <strong>Question:</strong> <?= Html::encode($answer['question_text']) ?><br>
                            <strong>Answer:</strong> <?= Html::encode($answer['answer']) ?><br><br>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
// URLs for the function in RoleController
$toggleTrainingUrl = Url::to(['role/toggle-training']);
$assignTrainingUrl = Url::to(['role/assign-training']);
$script = <<<JS

// Get the current time and adjust it to the local timezone
var currentTime = null;
var now = new Date();
var localTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
currentTime = localTime.toISOString().slice(0, 19).replace('T', ' ');
document.getElementById("training-time-picker").setAttribute("min", currentTime);
    
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
                    console.log('Success')
                    // Selecting the two spans
                    var assignedTimeElement = $('#training-assigned-time-' + userId);
                    var completeTimeElement = $('#training-complete-time-' + userId);
                    if (assignedTraining) {
                        // Sets the text to current time if training is assigned 
                        assignedTimeElement.text(currentTime);
                        // Sets the text to N/A and sets it to red color
                        completeTimeElement.text('N/A').removeClass('text-green text-black text-orange').addClass('text-red');
                    } else {
                        // Sets the text to N/A if training is not assigned
                        assignedTimeElement.text('N/A');
                        // Sets the text to black color
                        completeTimeElement.text('N/A').removeClass('text-green text-red text-orange').addClass('text-black');
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
    // Function ensuring that the code inside runs only after the whole HTML document has been fully loaded
    $(document).ready(function() {
    
    // Variable to track the toggle state of the action (check/uncheck)
    var toggleState = false;

    // Variable to track whether all checkboxes should be selected or deselected when the "Select All" button is clicked

    // Function that is executed whenever the "Assign Now" button is clicked
    $('#confirm-selection-btn').on('click', function() {

        // Array to store the selected titles
        var selectedTitles = [];
        // Iterate through all checked title checkboxes
        $('.title-checkbox:checked').each(function() {
            // For each checked checkbox, its value is added to the selectedTitles array
            selectedTitles.push($(this).val());
        });

        // Array to store the selected locations 
        var selectedLocations = [];
        // Iterate through all checked title checkboxes
        $('.location-checkbox:checked').each(function() {
            // For each checked checkbox, its value is added to the selectedLocations array
            selectedLocations.push($(this).val());
        });

        // Determine the toggle action based on the current state of toggleState
        // If toggleState is true, set toggleAction to 'uncheck', otherwise set it to 'check'
        var toggleAction = 'check';
        var userNumber = $('.toggle-info-btn').length
        var anyChecked = $('.toggle-info-btn:checked').length;
        if (userNumber === anyChecked) {
            toggleAction = 'uncheck';
        }

        // Iterating over all elements with the class '.employee-info' (the card with the details about each employee)
        $('.employee-info').each(function() {

            // Retrieves the value of the data-title attribute for the current .employee-info element (this)
            // This value represents the storage location of the user
            var userTitle = $(this).data('title');

            // Retrieve the value of the data-locate attribute for the current .employee-info element
            // This value represents the storage location of the user
            var userLocation = $(this).data('location');

            // Finding the checkbox element within the current .employee-info element
            // This checkbox will be toggled based on the selected titles and location
            var checkbox = $(this).find('.toggle-info-btn');

            // Checks if the userTitle is in the selectedTitles array
            var titleMatch = selectedTitles.includes(userTitle);
            
            // Checks if the userLocation is in the selectedLocations array
            var locationMatch = selectedLocations.includes(userLocation);

            // If both titles and locations are selected (at least one title and one location checkbox in the filter section)
            if (selectedTitles.length > 0 && selectedLocations.length > 0) {
                // Check if both titles and locations are true
                if (titleMatch && locationMatch) {   
                    // Set the checkbox's checked property base on the value of toggleAction and trigger the change event
                    checkbox.prop('checked', toggleAction === 'check').trigger('change');
                }
            } 
            
            // If only titles are selected
            else if (selectedTitles.length > 0) {
                // Check if titleMatch is true
                if (titleMatch) {
                    // If matches, set the checkbox's chcekd property base on the value of 'toggleAction' and triger the 'change' event
                    checkbox.prop('checked', toggleAction === 'check').trigger('change');
                }
            }
            
            // If only locations are selected
            else if (selectedLocations.length > 0) {
                // Check if locationMatch is true
                if (locationMatch) {
                    // If matches, set the checkbox's checked property base on the value of 'toggleAction' and trigger the 'change' event
                    checkbox.prop('checked', toggleAction === 'check').trigger('change');
                }
            }
        });

        // Toggle the state for the next click
        toggleState = !toggleState;
    });

    // Function to toggle the state (check/uncheck) of all title and location checkboxes
    $('#select-all-btn').on('click', function() {
        // Variable to determine if all checkboxes are currently selected
        var selectAll = true;

        // Iterate through each title and location checkbox
        $('.title-checkbox, .location-checkbox').each(function() {
            // If any checkbox is not checked, set selectAll to false and break the loop
            if (!$(this).is(':checked')) {
                selectAll = false;
                return false; // Break the loop
            }
        })

        // Toggle the state of selectAll (if all were selected, it will now be false, and vice versa)
        selectAll = !selectAll;
        // Setting the checked property of all title and location checkboxes to the value of selectAll
        $('.title-checkbox').prop('checked', selectAll);
        $('.location-checkbox').prop('checked', selectAll);

        // Disable or enable the user list button and user checkboxes based on selectAll state
        $('#toggle-user-list-btn').prop('disabled', selectAll);
        $('.user-checkbox').prop('disabled', selectAll);
        // Hide the user list
        $('#user-list').hide();
        // Reset the arrow indicator
        $('#arrow-down').text('▼');
    });

    // Function to assign training at a specific time or immediately
    $('#confirm-time-btn').on('click', function() {
        // Retrieve the selected time from the datetime-local input
        var selectedTime = $('#training-time-picker').val();
        // If no time is selected, it shows an alert and exits the function
        if (!selectedTime) {
            alert('Please select a time.');
            return;
        }

        // Array to store the selected titles
        var selectedTitles = [];
        // Iterate through all checked title checkboxes
        $('.title-checkbox:checked').each(function() {
            // For each checked checkbox, its value is added to the selectedTitless array
            selectedTitles.push($(this).val());
        });

        // Array to store the selected locations
        var selectedLocations = [];
        // Iterate through all checked location checkboxes
        $('.location-checkbox:checked').each(function() {
            // For each checked checkbox, its value is added to the selectedLocations array
            selectedLocations.push($(this).val());
        });

        var selectedUsers = [];
        $('.user-checkbox:checked').each(function() {
            selectedUsers.push($(this).val());
        })

        // If no title or location is selected, show an alert and exit the function
        if (selectedTitles.length === 0 && selectedLocations.length === 0 && selectedUsers.length === 0) {
            alert('Please select at least one title or location.');
            return;
        }

        // Get the current time and adjust it to the local timezone and format it as a string in the ISO format
        var now = new Date();
        var localNow = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
        var currentTime = localNow.toISOString().slice(0, 19).replace('T', ' ');

        // Check if the selected time is the same as the current time
        if (selectedTime === currentTime) {
            // Handle assigning training immediately
            $('.employee-info').each(function() {
                var userTitle = $(this).data('title');
                var userLocation = $(this).data('location');
                var checkbox = $(this).find('.toggle-info-btn');

                var titleMatch = selectedTitles.includes(userTitle);
                var locationMatch = selectedLocations.includes(userLocation);

                // Check if both titles and locations are selected and match                
                if (selectedTitles.length > 0 && selectedLocations.length > 0) {
                    if (titleMatch && locationMatch) {
                        checkbox.prop('checked', true).trigger('change');
                    }
                }
                // Check if only titles are selected and match
                else if (selectedTitles.length > 0) {
                    if (titleMatch) {
                        checkbox.prop('checked', true).trigger('change');
                    }
                }
                // Check if only locations are selected and match
                else if (selectedLocations.length > 0) {
                    if (locationMatch) {
                        checkbox.prop('checked', true).trigger('change');
                    }
                }
            });
        } else {
            // Handle assigning training at a specific time
            $.ajax({
                // URL to assign training
                url: '$assignTrainingUrl',
                type: 'POST',
                // Specifies the data to be sent to the server with the request
                data: {
                    selected_time: selectedTime,
                    selected_titles: selectedTitles,
                    selected_locations: selectedLocations,
                    selected_users: selectedUsers,
                    _csrf: yii.getCsrfToken()
                },
                success: function(response) {
                    if (response.success) {
                        // alert('Training assigned time set successfully.');
                        location.reload(); // Reload the page to update the training times
                    } else {
                        alert('Failed to set training assigned time.');
                    }
                },
                error: function() {
                    alert('Error in AJAX request.');
                }
            });
        }
    });

    // Event handler for toggling the visibility of the user list when the button is clicked
    $('#toggle-user-list-btn').on('click', function() {
         // Get the user list element
        var userList = $('#user-list');
         // Toggle the visibility of the user list
        userList.toggle();
        // Get the arrow elemetn
        var arrow = $('#arrow-down');

        // Check if the user list is visible after toggling
        if (userList.is(':visible')) {
            // Change arrow to up if the list is visible
            arrow.text('▲');
        } else {
            // Change arrow to down if the list is hidden
            arrow.text('▼');
            // Uncheck all user checkboxes and re-enable title and location checkboxes
            $('.user-checkbox:checked').each(function() {
                $(this).prop('checked', false); // Uncheck the checkbox
                $('.title-checkbox').prop('disabled', false); // Enable title checkbox
                $('.location-checkbox').prop('disabled', false); // Enable location checkbox
                $('#select-all-btn').prop('disabled', false); // Enable the select all button
                $('#confirm-selection-btn').prop('disabled', false); // Enable the confirm selection button
            });
        }
    });

    // Event handler for "Assign Now" button in the user list
    $('#confirm-specific-users-btn').on('click', function() {
        // Array to store the selected user IDs
        var selectedUsers = [];
        // Iterate through all checked user checkboxes
        $('.user-checkbox:checked').each(function() {
            // For each checked checkbox, its value (user ID) is added to the selectedUsers array
            selectedUsers.push($(this).val());
        });

        console.log("Selected users:", selectedUsers);

        // Determine the toggle action based on the number of checked checkboxes
        var toggleAction = 'check';
        var userNumber = $('.toggle-info-btn').length // Total number of user checkboxes
        var anyChecked = $('.toggle-info-btn:checked').length; // Number of checked checkboxes
        // If all checkboxes are checked, set toggle action to uncheck
        if (userNumber === anyChecked) {
            toggleAction = 'uncheck';
        }
        console.log("Toggle action:", toggleAction);

        // Iterate over all elements with the class '.employee-info' (the card with the details about each employee)
        $('.employee-info').each(function() {
            // Retrieve the user ID from the data-id attribute of the current .employee-info element (this)
            var userId = $(this).data('id');
            console.log("Checking user ID:", userId);

            // Check if userId is undefined and log it
            if (typeof userId === 'undefined') {
                console.error("userId is undefined for element", $(this));
                return;
            }

            // Finding the checkbox element within the current .employee-info element
            // This checkbox will be toggled based on the selected users
            var checkbox = $(this).find('.toggle-info-btn');

            // Checks if the userId is in the selectedUsers array
            var userMatch = selectedUsers.includes(userId.toString());
            console.log("Does user ID match?", userMatch);

            // If the user ID matches one of the selected users, toggle the checkbox
            if (userMatch) {
                console.log("Match found, toggling checkbox for user ID:", userId);
                // Toggle the checkbox's checked property based on the toggleAction value and trigger the change event
                checkbox.prop('checked', toggleAction === 'check').trigger('change');
            } else {
                console.log("No match, not toggling checkbox for user ID:", userId);
            }
        });

        // Toggle the state for the next click
        toggleState = !toggleState;
    });

    // Event handler for "Select All" button in the user list
    $('#select-all-users-btn').on('click', function() {
        var selectAllUsers = true;
        // Iterate through each user checkbox
        $('.user-checkbox').each(function() {
            // If any checkbox is not checked, set selectAllUsers to false and break the loop
            if (!$(this).is(':checked')) {
                selectAllUsers = false;
                return false;
            }
        });

        // Toggle the state of selectAllUsers (if all were selected, it will now be false, and vice versa)
        selectAllUsers = !selectAllUsers;
        // Set the checked property of all user checkboxes to the value of selectAllUsers
        $('.user-checkbox').prop('checked', selectAllUsers);

        // Disable or enable title and location checkboxes based on selectAllUsers state
        $('.title-checkbox').prop('disabled', selectAllUsers);
        $('.location-checkbox').prop('disabled', selectAllUsers);
        $('#select-all-btn').prop('disabled', selectAllUsers);
        $('#confirm-selection-btn').prop('disabled', selectAllUsers);
    })

    // Event handler to disable title and location checkboxes if any user checkbox is checked
    $('.user-checkbox').on('change', function() {
        var anyUserChecked = $('.user-checkbox:checked').length > 0;
        $('.title-checkbox').prop('disabled', anyUserChecked);
        // Disable title and location checkboxes if any user checkbox is checked
        $('.location-checkbox').prop('disabled', anyUserChecked);
        $('#select-all-btn').prop('disabled', anyUserChecked);
        $('#confirm-selection-btn').prop('disabled', anyUserChecked);
    });
    
    // Event handler to disable user checkboxes if any title or location checkbox is checked
    $('.title-checkbox, .location-checkbox').on('change', function() {
        var anyTitleOrLocationChecked = $('.title-checkbox:checked').length > 0 || $('.location-checkbox:checked').length > 0;
        // Disable user checkboxes if any title or location checkbox is checked
        $('.user-checkbox').prop('disabled', anyTitleOrLocationChecked);
        $('#user-list').hide();
        $('#toggle-user-list-btn').prop('disabled', anyTitleOrLocationChecked);
        $('#arrow-down').text('▼');
        $('#select-all-btn').prop('disabled', false);
        $('#confirm-selection-btn').prop('disabled', false);
        
    });


});

JS;
$this->registerJs($script);
?>