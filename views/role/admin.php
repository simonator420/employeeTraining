<?php

use yii\helpers\Html;
use yii\helpers\Url;

?>

<!-- View for displaying informations about the users. -->
<div class="employee-overview-container">
    <div class="employee-info-card">
        <h1><?= Yii::t('employeeTraining', 'Employee Training Overview') ?></h1>

        <!-- Checkboxes for each title with label "Assign to all" -->
        <label><?= Yii::t('employeeTraining', 'Assign training to all employees with title:') ?></label>
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
        <label><?= Yii::t('employeeTraining', 'With storage location in:') ?></label>
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
        <button id="select-all-btn"><?= Yii::t('employeeTraining', 'Select all') ?></button>
        <button id="confirm-selection-btn"><?= Yii::t('employeeTraining', 'Assign now') ?></button>

        <br>
        <br>

        <!-- Button to toggle visibility of the user list for specific training assignment -->
        <button id="toggle-user-list-btn">
            <?= Yii::t('employeeTraining', 'Assign training to specific users') ?><span id="arrow-down"> ▼</span>
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
            <div class="user-button-container">
                <button id="select-all-users-btn">
                    <?= Yii::t('employeeTraining', 'Select all') ?>
                </button>
                <button id="confirm-specific-users-btn">
                    <?= Yii::t('employeeTraining', 'Assign now') ?></button>
            </div>
        </div>

        <!-- Input for selecting date and time when the training should be assigned -->
        <label>
            <?= Yii::t('employeeTraining', 'Or select time when the training should be assigned:') ?>
        </label>
        <input type="datetime-local" id="training-time-picker" name="training-time">
        <button id="confirm-time-btn">OK</button>
        <br>

        <hr>
        <!-- Search bar for filtering users -->
        <!-- <input type="text" id="employee-search-bar"
            placeholder="<?= Yii::t('employeeTraining', 'Search employees...') ?>"
            style="margin-bottom:20px; width:100%; padding: 10px"> -->


        <h2><?= Yii::t('employeeTraining', 'User Setup') ?></h2>
        <div class="collapsible-container">
            <button class="collapsible" data-role="user"><?= Yii::t('employeeTraining', 'User') ?></button>
            <div class="content">
                <!-- Content will be populated dynamically -->
            </div>
            <button class="collapsible"
                data-role="team_leader"><?= Yii::t('employeeTraining', 'Team Leader') ?></button>
            <div class="content">
                <!-- Content will be populated dynamically -->
            </div>
            <button class="collapsible" data-role="admin"><?= Yii::t('employeeTraining', 'Admin') ?></button>
            <div class="content">
                <!-- Content will be populated dynamically -->
            </div>
        </div>
        <br>

        <!-- Modal for adding roles -->
        <div id="addRoleModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2 id="modal-title"></h2>
                <!-- Add form elements or any other content here -->
                <form id="add-role-form">
                    <!-- The list of users with checkboxes will be populated here -->
                    <div id="profile-list"></div>
                    <br>
                    <button type="button" id="submit-add-role">Add</button>
                </form>
            </div>
        </div>

        <h2><?= Yii::t('employeeTraining', 'Trainings') ?></h2>
        <!-- Training Information Table -->
        <table class="table table-striped table-bordered" id="training-table">
            <thead>
                <tr>
                    <th><strong><?= Yii::t('employeeTraining', 'Training ID') ?></strong></th>
                    <th><strong><?= Yii::t('employeeTraining', 'Name') ?></strong></th>
                    <th><strong><?= Yii::t('employeeTraining', 'Created At') ?></strong></th>
                    <th><strong><?= Yii::t('employeeTraining', 'Assigned Users Count') ?></strong></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trainings as $training): ?>
                    <tr>
                        <td class="training-id" data-id="<?= Html::encode($training['id']) ?>" style="cursor: pointer;">
                            <a href="<?= Url::to(['training-questions/questions', 'id' => Html::encode($training['id'])]) ?>"
                                style="color: blue; text-decoration: underline;">
                                <?= Html::encode($training['id']) ?>
                            </a>
                        </td>
                        <td><?= Html::encode($training['name']) ?></td>
                        <td><?= Html::encode($training['created_at']) ?></td>
                        <td><?= Html::encode($training['assigned_users_count']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div id="submit-cancel-buttons" style="display: none;">
            <button id="submit-training-btn">Submit</button>
            <button id="cancel-training-btn">Cancel</button>
        </div>
        <button id="create-training-btn">Create Training</button>
        <br>
        <br>

        <!-- User Information Table -->
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th><strong><?= Yii::t('employeeTraining', 'ID') ?></strong></th>
                    <th><strong><?= Yii::t('employeeTraining', 'Full Name') ?></strong></th>
                    <th><strong><?= Yii::t('employeeTraining', 'Job') ?></strong></th>
                    <!-- <th><strong><?= Yii::t('employeeTraining', 'Roles') ?></strong></th> -->
                    <th><strong><?= Yii::t('employeeTraining', 'Location') ?></strong></th>
                    <!-- <th><strong><?= Yii::t('employeeTraining', 'Training assigned time') ?></strong></th> -->
                    <th><strong><?= Yii::t('employeeTraining', 'Last training complete time') ?></strong></th>
                    <th><strong><?= Yii::t('employeeTraining', 'No. of open trainings') ?></strong></th>
                    <th><strong><?= Yii::t('employeeTraining', 'No. of completed trainings') ?></strong></th>
                    <!-- <th><strong><?= Yii::t('employeeTraining', 'Assigned training') ?></strong></th> -->
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr data-id="<?= Html::encode($user->id) ?>" data-title="<?= Html::encode($user->profile->title) ?>"
                        data-location="<?= Html::encode($user->profile->storage_location) ?>"
                        data-fullname="<?= Html::encode($user->profile->firstname . ' ' . $user->profile->lastname) ?>"
                        data-username="<?= Html::encode($user->username) ?>">
                        <td><?= Html::encode($user->id) ?></td>
                        <td><?= Html::encode($user->profile->firstname) ?>     <?= Html::encode($user->profile->lastname) ?>
                        </td>
                        <td><?= Html::encode($user->profile->title ?: 'N/A') ?></td>
                        <td><?= Html::encode($user->profile->storage_location ?: 'N/A') ?></td>
                        <td id="training-complete-time-<?= $user->id ?>" class="<?php
                          if ($user->profile->training_complete_time) {
                              echo 'text-green';
                          } elseif (!$user->profile->assigned_training && $user->profile->training_assigned_time && !$user->profile->training_complete_time) {
                              echo 'text-orange';
                          } elseif ($user->profile->assigned_training) {
                              echo 'text-red';
                          } else {
                              echo 'text-black';
                          }
                          ?>"><?= Html::encode(isset($trainingCompleteTimes[$user->id]) && $trainingCompleteTimes[$user->id] ? $trainingCompleteTimes[$user->id] : 'N/A') ?></td>
                        <td><?= Html::encode($openTrainingsCount[$user->id] !== null ? $openTrainingsCount[$user->id] : 0) ?>
                        </td>
                        <td><?= Html::encode($completedTrainingsCount[$user->id] !== null ? $completedTrainingsCount[$user->id] : 0) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
    </div>
</div>





<?php
// URLs for the function in RoleController
$toggleTrainingUrl = Url::to(['role/toggle-training']);
$assignTrainingUrl = Url::to(['role/assign-training']);
$createTrainingUrl = Url::to(['role/create-training']);
$fetchUsersByRoleUrl = Url::to(['role/fetch-users-by-role']);
$removeRoleUrl = Url::to(['role/remove-role']);
$fetchAllProfilesUrl = Url::to(['role/fetch-all-profiles']);
$addRoleUrl = Url::to(['role/add-role']);
$script = <<<JS

// Get the current time and adjust it to the local timezone
var currentTime = null;
var now = new Date();
var localTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
currentTime = localTime.toISOString().slice(0, 19).replace('T', ' ');
document.getElementById("training-time-picker").setAttribute("min", currentTime);
    
    // Event handler for creating a new training row
    $('#create-training-btn').on('click', function() {
        var table = $('#training-table tbody');
        var newRow = $('<tr>');
        newRow.html(`
            <td><input type="text" id="new-training-id" placeholder="Training ID"></td>
            <td><input type="text" id="new-training-name" placeholder="Name"></td>
            <td><input type="text" disabled></td>
            <td><input type="text" disabled></td>
        `);
        table.append(newRow);
        $('#create-training-btn').hide();
        $('#submit-cancel-buttons').show();
    });

    $('#cancel-training-btn').on('click', function() {
        $('#training-table tbody tr:last').remove();
        $('#create-training-btn').show();
        $('#submit-cancel-buttons').hide();
    });

    $('#submit-training-btn').on('click', function() {
        var trainingId = $('#new-training-id').val();
        var trainingName = $('#new-training-name').val();

        if (trainingId && trainingName) {
            $.ajax({
                url: '$createTrainingUrl',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ id: trainingId, name: trainingName }),
                headers: {
                    'X-CSRF-Token': yii.getCsrfToken()
                },
                success: function(data) {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to create training');
                    }
                }
            });
        } else {
            alert('Please fill in both fields');
        }
    });

    $('.training-id').on('click', function() {
        console.log('click');
        var trainingId = $(this).data('id');
        console.log("Training ID clicked: " + trainingId);
    });

    $('.collapsible').on('click', function() {
        var content = $(this).next('.content');

        // Close all other open contents
        $('.collapsible').not(this).removeClass('active');
        $('.content').not(content).slideUp();

        // Toggle the clicked collapsible
        this.classList.toggle('active');
        content.slideToggle();

        if (content.is(':visible')) {
            var role = $(this).data('role');
            fetchUsersByRole(role, content);
        }
    });
    
    function fetchUsersByRole(role, contentElement) {
    $.ajax({
        url: '$fetchUsersByRoleUrl',
        type: 'GET',
        data: { role: role },
        success: function(response) {
            console.log('AJAX Response:', response);
            var usersHtml = '<ul>';
            if (response.success && response.users.length > 0) {
                $.each(response.users, function(index, user) {
                    console.log('User Data:', user);
                    if (role !== 'user') {
                        usersHtml += '<li><input type="checkbox" class="role-user-checkbox" value="' + user.id + '"> ' + user.firstname + ' ' + user.lastname + '</li>';
                    } else {
                        usersHtml += '<li>' + user.firstname + ' ' + user.lastname + '</li>';
                    }
                });
                usersHtml += '</ul>';
                // Conditionally add the remove button if the role is not "user" and users are found
                if (role !== 'user') {
                    usersHtml += '<button class="remove-role-btn" data-role="' + role + '" style="display:none;">Remove selected</button>';
                }
            } else {
                usersHtml += '<li>No users found.</li>';
                usersHtml += '</ul>';
            }

            if (role !== 'user') {
                var addButtonText = '';
                switch (role) {
                    case 'team_leader':
                        addButtonText = 'Add Team Leader';
                        break;
                    case 'admin':
                        addButtonText = 'Add Admin';
                        break;
                }
                usersHtml += '<button class="add-role-btn" data-role="' + role + '">' + addButtonText + '</button>';
            }

            contentElement.html(usersHtml);
        },
        error: function() {
            var usersHtml = '<ul><li>Error fetching users.</li></ul>';

            if (role !== 'user') {
                var addButtonText = '';
                switch (role) {
                    case 'team_leader':
                        addButtonText = 'Add Team Leader';
                        break;
                    case 'admin':
                        addButtonText = 'Add Admin';
                        break;
                }
                usersHtml += '<button class="add-role-btn" data-role="' + role + '">' + addButtonText + '</button>';
            }

            contentElement.html(usersHtml);
        }
    });
}

    // Event handler for removing selected users from their current role and setting it to "User"
    $(document).on('click', '.remove-role-btn', function() {
        var role = $(this).data('role');
        var selectedUsers = [];
        $(this).closest('.content').find('.role-user-checkbox:checked').each(function() {
            var userId = $(this).val();
            console.log('Checkbox value:', userId); // Log the checkbox value to ensure it's correct
            selectedUsers.push(userId);
        });

        console.log('Selected users:', selectedUsers); // Log the selected users

        if (selectedUsers.length > 0) {
            $.ajax({
                url: '$removeRoleUrl',
                type: 'POST',
                data: {
                    role: role,
                    users: selectedUsers,
                    _csrf: yii.getCsrfToken()
                },
                success: function(response) {
                    console.log('Response:', response); // Log the response
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Failed to remove role.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error); // Log AJAX errors
                    alert('Error in AJAX request.');
                }
            });
        } else {
            alert('Please select at least one user to be removed.');
        }
    });

    // Event handler for adding a user to a role
    var modal = document.getElementById("addRoleModal");

    // Get the button that opens the modal
    var btn = document.getElementsByClassName("add-role-btn");

    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close")[0];

    // When the user clicks the button, open the modal 
    $(document).on('click', '.add-role-btn', function() {
        var role = $(this).data('role');
        var title = '';
        switch (role) {
            case 'team_leader':
                title = 'Add Team Leader';
                break;
            case 'admin':
                title = 'Add Admin';
                break;
        }
        $('#modal-title').text(title);
        modal.style.display = "block";
        $('body').css('overflow', 'hidden'); // Disable scrolling

        // Fetch profiles and display them in the modal
        $.ajax({
            url: '$fetchAllProfilesUrl',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    var profilesHtml = '<ul>';
                    $.each(response.profiles, function(index, profile) {
                        // Exclude profiles with the current role
                        if (profile.role !== role) {
                            profilesHtml += '<li><input type="checkbox" class="profile-checkbox" value="' + profile.id + '"> ' + profile.firstname + ' ' + profile.lastname + '</li>';
                        }
                    });
                    profilesHtml += '</ul>';
                    $('#profile-list').html(profilesHtml); // Populate the profile list
                } else {
                    $('#profile-list').html('<p>No profiles found.</p>');
                }
            },
            error: function() {
                $('#profile-list').html('<p>Error fetching profiles.</p>');
            }
        });
    });

    // When the user clicks on <span> (x), close the modal
    span.onclick = function() {
        modal.style.display = "none";
        $('body').css('overflow', 'auto'); // Enable scrolling
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
            $('body').css('overflow', 'auto'); // Enable scrolling
        }
    }

    // Handle the form submission
    $('#submit-add-role').click(function() {
        var selectedProfiles = [];
        $('#add-role-form').find('.profile-checkbox:checked').each(function() {
            selectedProfiles.push($(this).val());
        });

        var role = $('#modal-title').text().replace('Add ', '').toLowerCase().replace(' ', '_');

        // Perform the AJAX request to add the profiles to the role
        $.ajax({
            url: '$addRoleUrl', // Update to match your actual endpoint
            type: 'POST',
            data: {
                profiles: selectedProfiles,
                role: role,
                _csrf: yii.getCsrfToken()
            },
            success: function(response) {
                if (response.success) {
                    alert('Profiles added successfully');
                    location.reload(); // Reload the page to see the changes
                } else {
                    alert('Failed to add profiles');
                }
                modal.style.display = "none";
                $('body').css('overflow', 'auto'); // Enable scrolling
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error); // Log AJAX errors
                alert('Error in AJAX request.');
                modal.style.display = "none";
                $('body').css('overflow', 'auto'); // Enable scrolling
            }
        });
    });

    $(document).on('change', '.role-user-checkbox', function() {
        var role = $(this).closest('.content').prev('.collapsible').data('role');
        if (role === 'team_leader' || role === 'admin') {
            var anyChecked = $(this).closest('.content').find('.role-user-checkbox:checked').length > 0;
            if (anyChecked) {
                $(this).closest('.content').find('.remove-role-btn').show();
            } else {
                $(this).closest('.content').find('.remove-role-btn').hide();
            }
        }
    });

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
        var arrow = $('#arrow-down');
        arrow.removeClass('rotated');
        arrow.text('▼');    });

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
        // Get the arrow element
        var arrow = $('#arrow-down');

        // Toggle the rotated class for the arrow animation
        arrow.toggleClass('rotated');

        // Check if the user list is visible after toggling
        if (!userList.is(':visible')) {
            // Uncheck all user checkboxes and re-enable title and location checkboxes
            $('.user-checkbox:checked').each(function() {
                $(this).prop('checked', false); // Uncheck the checkbox
            });
            $('.title-checkbox').prop('disabled', false); // Enable title checkbox
            $('.location-checkbox').prop('disabled', false); // Enable location checkbox
            $('#select-all-btn').prop('disabled', false); // Enable the select all button
            $('#confirm-selection-btn').prop('disabled', false); // Enable the confirm selection button
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

        // Determine the toggle action based on the number of checked checkboxes
        var toggleAction = 'check';
        var userNumber = $('.toggle-info-btn').length // Total number of user checkboxes
        var anyChecked = $('.toggle-info-btn:checked').length; // Number of checked checkboxes
        // If all checkboxes are checked, set toggle action to uncheck
        if (userNumber === anyChecked) {
            toggleAction = 'uncheck';
        }

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

        // Reset the arrow direction and remove the rotated class
        var arrow = $('#arrow-down');
        arrow.removeClass('rotated');
        arrow.text('▼');

        $('#select-all-btn').prop('disabled', false);
        $('#confirm-selection-btn').prop('disabled', false);
    });

    $('#employee-search-bar').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        console.log("Search Term:", searchTerm);

        $('.employee-info-container').each(function() {
            var user = $(this).find('.employee-info');
            var id = user.data('id').toString().toLowerCase();
            var title = user.data('title').toLowerCase();
            var location = user.data('location').toLowerCase();
            var fullname = user.data('fullname').toLowerCase();
            var username = user.data('username').toLowerCase();

            console.log("Checking user:", fullname, username, title, location);

            if (id.includes(searchTerm) || title.includes(searchTerm) || location.includes(searchTerm) ||
                fullname.includes(searchTerm) || username.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});

JS;
$this->registerJs($script);
?>