<?php

use yii\helpers\Html;
use yii\helpers\Url;

$addTeamLeaderText = Yii::t('employeeTraining', 'Add Team Leader');
$addAdminText = Yii::t('employeeTraining', 'Add Admin');
$addOptionJobsText = Yii::t('employeeTraining', 'All Jobs');
$addOptionLocationText = Yii::t('employeeTraining', 'All Locations');

?>

<!-- View for displaying informations about the users. -->
<div class="employee-overview-container">
    <div class="employee-info-card">
        <h1 style="padding-bottom:20px;"><?= Yii::t('employeeTraining', 'ILLE Employee Trainings') ?></h1>

        <?php if ($userRole == 'admin'): ?>
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
                        <!-- Filter Form -->
                        <div id="modal-filter-list" class="flex-container" style="display: flex; align-items:center;">
                            <div class="form-group">
                                <label
                                    for="modal-title-select"><?= Yii::t('employeeTraining', 'Select Job Title') ?></label>
                                <select id="modal-title-select" class="form-control" style="height:100%; width:250px">
                                    <!-- Options will be populated dynamically -->
                                </select>
                            </div>
                            <div class="form-group">
                                <label
                                    for="modal-location-select"><?= Yii::t('employeeTraining', 'Select Location') ?></label>
                                <select id="modal-location-select" class="form-control" style="height:100%; width:250px">
                                    <!-- Options will be populated dynamically -->
                                </select>
                            </div>
                            <button type="button" id="modal-submit-filter-btn" class="btn btn-success"
                                style="margin-top:30px">
                                <?= Yii::t('employeeTraining', 'Filter') ?>
                            </button>
                        </div>

                        <!-- Search bar inside the modal -->
                        <div class="search-bar">
                            <input type="text" id="modal-employee-search-bar" placeholder="Search employees..."
                                style="padding:10px; width:250px; margin-bottom:20px; border:2px solid transparent; border-color: lightgray; border-radius: 4px;">
                        </div>

                        <!-- The list of users with checkboxes will be populated here -->
                        <div id="profile-list" class="profile-list-container"></div>
                        <br>
                        <button type="button" id="submit-add-role"><?= Yii::t('employeeTraining', 'Add') ?></button>
                    </form>
                </div>
            </div>
        <?php endif; ?>


        <h2><?= Yii::t('employeeTraining', 'Trainings') ?></h2>

        <!-- Training Information Table -->
        <table class="table table-training table-bordered" id="training-table">
            <thead>
                <tr>
                    <th class="checkbox-column"></th>
                    <th><strong><?= Yii::t('employeeTraining', 'Training ID') ?></strong></th>
                    <th><strong><?= Yii::t('employeeTraining', 'Name') ?></strong></th>
                    <th><strong><?= Yii::t('employeeTraining', 'Created At') ?></strong></th>
                    <th><strong><?= Yii::t('employeeTraining', 'Assigned Users Count') ?></strong></th>
                </tr>
            </thead>
            <tbody>
                <!-- Iterate over all trainings and display them in the table -->
                <?php foreach ($trainings as $training): ?>
                    <tr>
                        <td><input type="checkbox" class="training-checkbox" value="<?= Html::encode($training['id']) ?>">
                        </td>
                        <td class="training-id" data-id="<?= Html::encode($training['id']) ?>" style="cursor: pointer;">
                            <a href="<?= Url::to(['questions/questions', 'id' => Html::encode($training['id'])]) ?>"
                                style="color: blue; text-decoration: underline;">
                                <?= Html::encode($training['id']) ?>
                            </a>
                        </td>
                        <td><?= Html::encode($training['name']) ?></td>
                        <td>
                            <?php
                            // If the training creation date was found, display it
                            if (isset($training['created_at']) && $training['created_at']) {
                                echo Html::encode(date('j. n. Y H:i:s', strtotime($training['created_at'])));
                            }
                            // If it wasn't found, display N/A
                            else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                        <td><?= Html::encode($activeAssignedTrainingsCount[$training['id']] ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div id="submit-cancel-buttons" style="display: none;">
            <button id="submit-training-btn">Submit</button>
            <button id="cancel-training-btn">Cancel</button>
        </div>

        <!-- If the user is admin, display the button for training creation -->
        <?php if ($userRole == 'admin'): ?>
            <div if="training-action-buttons" style="display:flex; align-items:center; gap: 10px">
                <button id="delete-selected-btn"
                    style="display:none;"><?= Yii::t('employeeTraining', 'Delete Selected') ?></button>
                <button id="create-training-btn"><?= Yii::t('employeeTraining', 'Create Training') ?></button>
            </div>
        <?php endif; ?>
        <br>
        <br>

        <!-- User Information Table -->
        <table class="table table-striped table-bordered">

            <div id="filter-list" class="flex-container" style="display: flex; align-items:center;">
                <form id="filter-form" class="flex-form">
                    <div class="form-group">
                        <label for="title-select"><?= Yii::t('employeeTraining', 'Select Job Title') ?></label>
                        <select id="title-select" class="form-control" style="height:100%; width:250px">
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="location-select"><?= Yii::t('employeeTraining', 'Select Location') ?></label>
                        <select id="location-select" class="form-control" style="height:100%; width:250px">
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                    <button type="button" id="submit-filter-btn" class="btn btn-success"
                        style="margin-top:30px"><?= Yii::t('employeeTraining', 'Filter') ?></button>
                </form>
            </div>

            <div class="search-bar">
                <input type="text" id="employee-search-bar" placeholder="Search employees..."
                    style="padding:10px; width:250px; margin-bottom:20px">
            </div>

            <thead>
                <tr>
                    <th><strong><?= Yii::t('employeeTraining', 'ID') ?></strong></th>
                    <th><strong><?= Yii::t('employeeTraining', 'Full Name') ?></strong></th>
                    <th><strong><?= Yii::t('employeeTraining', 'Job') ?></strong></th>
                    <th><strong><?= Yii::t('employeeTraining', 'Location') ?></strong></th>
                    <th><strong><?= Yii::t('employeeTraining', 'Last training complete time') ?></strong></th>
                    <th><strong><?= Yii::t('employeeTraining', 'No. of open trainings') ?></strong></th>
                    <th><strong><?= Yii::t('employeeTraining', 'No. of completed trainings') ?></strong></th>
                </tr>
            </thead>

            <tbody>
                <!-- Iterate throught all users and display the information about them -->
                <?php foreach ($users as $user): ?>
                    <tr data-id="<?= Html::encode($user->id) ?>" data-title="<?= Html::encode($user->profile->title) ?>"
                        data-location="<?= Html::encode($user->profile->storage_location) ?>"
                        data-fullname="<?= Html::encode($user->profile->firstname . ' ' . $user->profile->lastname) ?>"
                        data-username="<?= Html::encode($user->username) ?>">
                        <td class="user_id"><?= Html::encode($user->id) ?></td>
                        <td>
                            <a href="<?= Url::to(['training/user-answers', 'id' => Html::encode($user->id)]) ?>"
                                style="color: blue; text-decoration: underline;">
                                <?= Html::encode($user->profile->firstname) ?>     <?= Html::encode($user->profile->lastname) ?>
                            </a>
                        </td>
                        <td><?= Html::encode($user->profile->title ?: 'N/A') ?></td>
                        <td><?= Html::encode($user->profile->storage_location ?: 'N/A') ?></td>
                        <td id="training-complete-time-<?= $user->id ?>" class="<?php

                          echo 'text-black';

                          ?>">
                            <?php
                            if (isset($trainingCompleteTimes[$user->id]) && $trainingCompleteTimes[$user->id]) {
                                echo Html::encode(date('j. n. Y H:i:s', strtotime($trainingCompleteTimes[$user->id])));
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
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
$toggleTrainingUrl = Url::to(['training/toggle-training']);
$createTrainingUrl = Url::to(['training/create-training']);
$fetchUsersByRoleUrl = Url::to(['role/fetch-users-by-role']);
$removeRoleUrl = Url::to(['role/remove-role']);
$fetchAllProfilesUrl = Url::to(['role/fetch-all-profiles']);
$fetchProfilesUrl = Url::to(['role/fetch-profiles']);
$fetchTitlesUrl = Url::to(['role/fetch-titles']);
$fetchLocationsUrl = Url::to(['role/fetch-locations']);
$fetchFilteredUsersUrl = Url::to(['role/fetch-filtered-users']);
$addRoleUrl = Url::to(['role/add-role']);
$deleteSelectedUrl = Url::to(['training/delete-selected']);
$baseUserAnswersUrl = Url::to(['training/user-answers']);
$script = <<<JS

// Get the current time and adjust it to the local time zone
var currentTime = null;
var now = new Date();
var localTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
currentTime = localTime.toISOString().slice(0, 19).replace('T', ' ');
    
    // Event handler for creating new training
    $('#create-training-btn').on('click', function() {
        var table = $('#training-table tbody');
        var newRow = $('<tr>');
        $('#training-table input[type="checkbox"]').prop('checked', false);
        newRow.html(`
            <td><input type="text" id="new-training-id" placeholder="Training ID"></td>
            <td><input type="text" id="new-training-name" placeholder="Name"></td>
            <td><input type="text" disabled></td>
            <td><input type="text" disabled></td>
        `);
        table.append(newRow);
        $('#create-training-btn').hide(); // Hide the create button
        $('#submit-cancel-buttons').show(); // Show the submit and cancel buttons
        $('.training-checkbox').closest('td').hide(); // Hide the checkboxes for existing trainings
        $('th.checkbox-column').hide(); // Hide the header checkbox cell
        $('#delete-selected-btn').hide(); // Hide the "Delete Selected" button if visible
    });

    // Event handler for canceling the create training action
    $('#cancel-training-btn').on('click', function() {
        $('#training-table tbody tr:last').remove();  // Remove the newly added row
        $('#create-training-btn').show(); // Show the create button again
        $('#submit-cancel-buttons').hide(); // Hide the submit and cancel buttons
        $('.training-checkbox').closest('td').show(); // Show the checkboxes for existing trainings
        $('th.checkbox-column').show(); // Show the checkbox column header
    });

    // Event handler for submitting the creation of new training
    $('#submit-training-btn').on('click', function() {
        var trainingId = $('#new-training-id').val();
        var trainingName = $('#new-training-name').val();

        if (trainingId && trainingName) {
            // Send an AJAX request to create the new training
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

    // Event handler for clicking on a training ID (opens the training questions)
    $('.training-id').on('click', function() {
        var trainingId = $(this).data('id');
    });

    // Event handler for toggling the collapsible content (e.g., users, team leaders, admins)
    $('.collapsible').on('click', function() {
        var content = $(this).next('.content');

        // Close all other open collapsibles
        $('.collapsible').not(this).removeClass('active');
        $('.content').not(content).slideUp();

        // Toggle the current collapsible
        this.classList.toggle('active');
        content.slideToggle();

        if (content.is(':visible')) {
            var role = $(this).data('role');
            fetchUsersByRole(role, content); // Fetch users associated with the role
        }
    });
    
    // Function to fetch users by role and populate the collapsible content
    function fetchUsersByRole(role, contentElement) {
    $.ajax({
        url: '$fetchUsersByRoleUrl',
        type: 'GET',
        data: { role: role },
        success: function(response) {
            var usersHtml = '<ul>';
            if (response.success && response.users.length > 0) {
                $.each(response.users, function(index, user) {
                    if (role !== 'user') {
                        usersHtml += '<li><input type="checkbox" class="role-user-checkbox" value="' + user.id + '"> ' + user.firstname + ' ' + user.lastname + '</li>';
                    } else {
                        usersHtml += '<li>' + user.firstname + ' ' + user.lastname + '</li>';
                    }
                });
                usersHtml += '</ul>';
                // Add a remove button if the role is not "user"
                if (role !== 'user') {
                    switch (role) {
                    case 'team_leader':
                        usersHtml += '<button class="remove-role-btn" data-role="' + role + '" style="display:none;">Remove Team Leader Role</button>';
                        break;
                    case 'admin':
                        usersHtml += '<button class="remove-role-btn" data-role="' + role + '" style="display:none;">Remove Admin Role</button>';
                        break;
                    }
                }
            } else {
                usersHtml += '<li>No users found.</li>';
                usersHtml += '</ul>';
            }

            if (role !== 'user') {
                var addButtonText = '';
                switch (role) {
                    case 'team_leader':
                        addButtonText = '$addTeamLeaderText';
                        break;
                    case 'admin':
                        addButtonText = '$addAdminText';
                        break;
                }
                usersHtml += '<button class="add-role-btn" data-role="' + role + '">' + addButtonText + '</button>';
            }

            contentElement.html(usersHtml); // Populate the content with the fetched users
        },
        error: function() {
            var usersHtml = '<ul><li>Error fetching users.</li></ul>';

            if (role !== 'user') {
                var addButtonText = '';
                switch (role) {
                    case 'team_leader':
                        addButtonText = '$addTeamLeaderText';
                        break;
                    case 'admin':
                        addButtonText = '$addAdminText';
                        break;
                }
                usersHtml += '<button class="add-role-btn" data-role="' + role + '">' + addButtonText + '</button>';
            }

            contentElement.html(usersHtml); // Handle errors by displaying a message
        }
    });
}

    // Event handler for removing a role (sets the role back to User)
    $(document).on('click', '.remove-role-btn', function() {
        var role = $(this).data('role');
        var selectedUsers = [];
        $(this).closest('.content').find('.role-user-checkbox:checked').each(function() {
            var userId = $(this).val();
            selectedUsers.push(userId);
        });

        if (selectedUsers.length > 0) {
            // Send an AJAX request to remove the role
            $.ajax({
                url: '$removeRoleUrl',
                type: 'POST',
                data: {
                    role: role,
                    users: selectedUsers,
                    _csrf: yii.getCsrfToken()
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Failed to remove role.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
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

    // Event handler for opening the modal to add a new role to users
    $(document).on('click', '.add-role-btn', function() {
        // Reset filters and clear previous search results
        $('#modal-title-select').val('');
        $('#modal-location-select').val('');
        $('#profile-list').empty();
        var role = $(this).data('role');
        var title = '';
        switch (role) {
            case 'team_leader':
                title = '$addTeamLeaderText';
                break;
            case 'admin':
                title = '$addAdminText';
                break;
        }
        $('#modal-title').text(title); // Set the modal title based on the role
        modal.style.display = "block"; // Show the modal
        $('body').css('overflow', 'hidden'); // Disable page scrolling

        // Fetch and display profiles in the modal
        $.ajax({
            url: '$fetchProfilesUrl',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    var profilesHtml = '';
                    $.each(response.profiles, function(index, profile) {
                        // Exclude profiles that already have the current role
                        if (profile.role !== role) {
                            profilesHtml += '<div class="profile-item"><input type="checkbox" class="profile-checkbox" value="' + profile.id + '"> ' + profile.firstname + ' ' + profile.lastname + '</div>';
                        }
                    });
                    $('#profile-list').html(profilesHtml);
                } else {
                    $('#profile-list').html('<p>No profiles found.</p>');
                }
            },
            error: function(xhr, status, error) {
                $('#profile-list').html('<p>Error fetching profiles.</p>');
                console.error('Error saving scores:', error);
            }
        });
    });

    // Event handler for closing the modal
    span.onclick = function() {
        modal.style.display = "none"; // Hide the modal
        $('#modal-employee-search-bar').val(''); // Clear the search bar in the modal
        $('body').css('overflow', 'auto'); // Re-enable page scrolling
    }

    // Event handler for closing the modal when clicking outside of it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none"; // Hide the modal
            $('#modal-employee-search-bar').val(''); // Clear the search bar in the modal
            $('body').css('overflow', 'auto'); // Re-enable page scrolling
        }
    }

    // Event handler for submitting the selected users for role assignment
    $('#submit-add-role').click(function() {
        var selectedProfiles = [];
        $('#add-role-form').find('.profile-checkbox:checked').each(function() {
            selectedProfiles.push($(this).val());
        });

        var role = $('#modal-title').text().replace('Add ', '').toLowerCase().replace(' ', '_');

        // Send an AJAX request to assign the role to the selected users
        $.ajax({
            url: '$addRoleUrl',
            type: 'POST',
            data: {
                profiles: selectedProfiles,
                role: role,
                _csrf: yii.getCsrfToken()
            },
            success: function(response) {
                if (response.success) {
                    alert('Profiles added successfully');
                    location.reload();
                } else {
                    alert('Failed to add profiles');
                }
                modal.style.display = "none";
                // Enable scrolling
                $('body').css('overflow', 'auto');
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                alert('Error in AJAX request.');
                modal.style.display = "none";
                // Enable scrolling
                $('body').css('overflow', 'auto');
            }
        });
    });

    // Event handler for showing or hiding the remove role button based on user selection
    $(document).on('change', '.role-user-checkbox', function() {
        var role = $(this).closest('.content').prev('.collapsible').data('role');
        if (role === 'team_leader' || role === 'admin') {
            var anyChecked = $(this).closest('.content').find('.role-user-checkbox:checked').length > 0;
            if (anyChecked) {
                $(this).closest('.content').find('.remove-role-btn').show(); // Show the remove button if any checkbox is checked
            } else {
                $(this).closest('.content').find('.remove-role-btn').hide(); // Hide the remove button if none are checked
            }
        }
    });

    // Event handler for filtering users in the user information table
    $(document).on('click', '#submit-filter-btn', function() {
        var selectedTitle = $('#title-select').val();
        var selectedLocation = $('#location-select').val();

        // Send an AJAX request to filter users based on the selected job title and location
        $.ajax({
            url: '$fetchFilteredUsersUrl',
            type: 'GET',
            data: {
                title: selectedTitle,
                location: selectedLocation
            },
            success: function(response) {
                if (response.success) {
                    // Clear the current table body
                    var tableBody = $('table.table-striped tbody');
                    tableBody.empty();

                    // Iterate over the filtered users and append them to the table
                    if (response.users && response.users.length > 0) {
                        $.each(response.users, function(index, user) {
                            var userRow = '<tr data-fullname="' + 
                            user.firstname + ' ' + 
                            user.lastname + '">';
                        // Add user ID
                        userRow += '<td>' + user.user_id + '</td>';
                        // Add user full name with url
                        userRow += '<td><a href="' + '$baseUserAnswersUrl' + '?id=' + user.user_id + '" style="color: blue; text-decoration: underline;">' + 
                        user.firstname + " " + user.lastname + '</a></td>';
                        // Add user job title
                        userRow += '<td>' + user.title + '</td>';
                        // Add user storage location
                        userRow += '<td>' + user.storage_location + '</td>';
                        // Add latest training complete time (or "N/A")
                        if (user.latest_training_complete_time != 'N/A') {
                            var date = new Date(user.latest_training_complete_time);
                            var formattedDate = date.getDate() + '. ' + (date.getMonth() + 1) + '. ' + date.getFullYear() + ' ' + date.toLocaleTimeString('en-GB');
                            userRow += '<td>' + formattedDate + '</td>';
                        } else {
                            userRow += '<td>N/A</td>';
                        }
                        // Add number of uncompleted trainings
                        userRow += '<td>' + user.open_trainings_count + '</td>';
                        // Add number of completed trainings
                        userRow += '<td>' + user.completed_trainings_count + '</td>';
                        // Add other columns as needed
                        userRow += '</tr>';
                            
                            tableBody.append(userRow);
                        });
                    } else {
                        // If no users match the filter, display a message
                        tableBody.append('<tr><td colspan="7">No users found for the selected criteria.</td></tr>');
                    }
                } else {
                    alert('No users found for the selected criteria.');
                }

                var searchBar = $('#employee-search-bar');
                if (searchBar.val()) {
                     // Clear the search bar value if it's not empty
                    searchBar.val('');
                }
            },
            error: function(xhr, status, error) {
                alert('Error fetching filtered users');
                console.log(xhr.responseText);
            }
        });
    });

    // Run when the document is fully loaded
    $(document).ready(function() {
        $('#modal-title').text('Assign Users');

        // Fetch job titles for the filter dropdown
        $.ajax({
            url: '$fetchTitlesUrl',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    var titleOptions = '<option value="">$addOptionJobsText</option>';
                    $.each(response.titles, function(index, title) {
                        titleOptions += '<option value="' + title + '">' + title + '</option>';
                    });
                    $('#title-select').html(titleOptions);
                }
            },
            error: function() {
                alert('Error fetching titles');
            }
        });

        // Fetch locations for the filter dropdown
        $.ajax({
            url: '$fetchLocationsUrl',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    var locationOptions = '<option value="">$addOptionLocationText</option>';
                    $.each(response.locations, function(index, location) {
                        locationOptions += '<option value="' + location + '">' + location + '</option>';
                    });
                    $('#location-select').html(locationOptions);
                }
            },
            error: function() {
                alert('Error fetching locations');
            }
        });

        // Event handler for showing or hiding the delete button based on training selection
        $('.training-checkbox').on('change', function() {
            var anyChecked = $('.training-checkbox:checked').length > 0;
            $('#delete-selected-btn').toggle(anyChecked);
        });

        // Event handler for deleting selected trainings
        $('#delete-selected-btn').on('click', function() {
            var selectedIds = $('.training-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (selectedIds.length > 0) {
                $.ajax({
                    url: '$deleteSelectedUrl',
                    type: 'POST',
                    data: {trainingIds: selectedIds},
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.message || 'Failed to delete selected trainings.');
                        }
                    }
                });
            } else {
                alert('Please select at least one training.');
            }
        });

        // Fetch job titles for the modal filter dropdown
        $.ajax({
        url: '$fetchTitlesUrl',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                var titleOptions = '<option value="">$addOptionJobsText</option>';
                $.each(response.titles, function(index, title) {
                    titleOptions += '<option value="' + title + '">' + title + '</option>';
                });
                $('#modal-title-select').html(titleOptions);
            }
        },
        error: function() {
            alert('Error fetching titles');
        }
    });

    // Fetch locations for the modal filter dropdown
    $.ajax({
        url: '$fetchLocationsUrl',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                var locationOptions = '<option value="">$addOptionLocationText</option>';
                $.each(response.locations, function(index, location) {
                    locationOptions += '<option value="' + location + '">' + location + '</option>';
                });
                $('#modal-location-select').html(locationOptions);
            }
        },
        error: function() {
            alert('Error fetching locations');
        }
    });

    // Event handler for filtering profiles in the modal based on job title and location
    $(document).on('click', '#modal-submit-filter-btn', function() {
        var selectedTitle = $('#modal-title-select').val();
        var selectedLocation = $('#modal-location-select').val();

        $.ajax({
            url: '$fetchFilteredUsersUrl',
            type: 'GET',
            data: {
                title: selectedTitle,
                location: selectedLocation
            },
            success: function(response) {
                if (response.success) {
                    // Clear the current profile list
                    var profileList = $('#profile-list');
                    profileList.empty();

                    // Iterate over the filtered users and append them to the profile list
                    if (response.users && response.users.length > 0) {
                        var profilesHtml = '';
                        $.each(response.users, function(index, user) {
                            profilesHtml += '<div class="profile-item"><input type="checkbox" class="profile-checkbox" value="' + user.id + '"> ' + user.firstname + ' ' + user.lastname + '</div>';
                        });
                        profileList.html(profilesHtml);
                    } else {
                        // If no users match the filter, display a message
                        profileList.html('<p>No users found for the selected criteria.</p>');
                    }
                } else {
                    alert('No users found for the selected criteria.');
                }
            },
            error: function(xhr, status, error) {
                alert('Error fetching filtered users');
                console.log(xhr.responseText);
            }
        });
    });
    
    // Variable to track the toggle state of the action (check/uncheck)
    var toggleState = false;

    // Search bar for filtering/searching users in the user information table
    $('#employee-search-bar').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();

        $('table.table-striped tbody tr').each(function() {
            var fullName = $(this).data('fullname').toLowerCase();

            // Check if the full name contains the search term
            if (fullName.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Search bar for filtering/searching profiles in the modal
    $('#modal-employee-search-bar').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();

        $('#profile-list .profile-item').each(function() {
            var fullName = $(this).text().toLowerCase();

            if (fullName.includes(searchTerm)) {
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