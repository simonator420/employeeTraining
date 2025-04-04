<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;


?>

<!-- TOOD Comment all the views and their purpose -->

<div class="training-question-container">
    <div class="training-questions-form">

        <!-- Header and button for going back to overview -->
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1>
                <strong> <?= Html::encode($trainingName) ?> </strong>
            </h1>
            <?= Html::a('&laquo; ' . Yii::t('employeeTraining', 'Back to overview'), Url::to(['role/admin']), ['class' => 'btn go-back-button']) ?>
        </div>

        <h3>
            <?= Yii::t('employeeTraining', 'Training ID: ') ?><strong><?= Html::encode($trainingId) ?></strong>
        </h3>

        <h3 style="display:flex; align-items: center; gap: 10px;">
            <?= Yii::t('employeeTraining', 'Deadline for completion in days: ') ?>
            <strong id="deadline-display"><?= Html::encode($deadlineForCompletion) ?></strong>
            <button id="edit-deadline-btn"><?= Yii::t('employeeTraining', 'Edit') ?></button>
        </h3>

        <div id="edit-deadline-form" style="display: none;">
            <input type="number" id="deadline-input" value="<?= Html::encode($deadlineForCompletion) ?>"
                class="form-control" style="width: 100px; display: inline;">
            <button id="submit-deadline-btn"
                class="btn btn-success"><?= Yii::t('employeeTraining', 'Submit') ?></button>
            <button id="cancel-deadline-btn" class="btn btn-danger"><?= Yii::t('employeeTraining', 'Cancel') ?></button>
        </div>

        <h3 style="display:flex; align-items:center; gap:10px" data-training-id="<?= Html::encode($trainingId) ?>">
            <?= Yii::t('employeeTraining', 'Number of assigned users: ') ?>
            <strong id="assigned-users-display"><?= Html::encode($assignedUsersCount) ?></strong>
            <button id="assign-users-btn"><?= Yii::t('employeeTraining', 'Assign') ?></button>
        </h3>

        <div id="assignUsersModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2 id="modal-title">Assign Users</h2>
                <div id="filter-list" class="flex-container">
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
                        <button type="button" id="submit-filter-btn"
                            class="btn btn-success"><?= Yii::t('employeeTraining', 'Filter') ?></button>
                    </form>
                </div>
                <div class="search-bar">
                    <input type="text" id="modal-user-search-bar"
                        placeholder="<?= Yii::t('employeeTraining', 'Search users...') ?>"
                        style="padding:10px; width:250px; margin-bottom:20px; border:2px solid lightgray; border-radius: 4px;">
                </div>
                <form id="assign-users-form">
                    <div id="profile-list"></div>
                    <br>
                    <button type="button" id="submit-assign-users">Assign</button>
                </form>
            </div>
        </div>

        <br>

        <?php if ($userRole == 'admin'): ?>
            <!-- Begin the ActiveForm -->
            <?php $form = ActiveForm::begin([
                'id' => 'training-questions-form',
                'options' => ['enctype' => 'multipart/form-data'],
                'enableAjaxValidation' => false,
                'enableClientValidation' => true,
            ]); ?>


            <!-- Hidden input to store the training ID -->
            <?= Html::hiddenInput('trainingId', $trainingId) ?>

            <!-- Video upload field -->
            <div class="form-group" style="padding-bottom: 25px;" id="training-file">
                <label for="training-file"><?= Yii::t('employeeTraining', 'Upload Training Video or PDF') ?></label>
                <input type="file" name="trainingFile" class="form-control" accept="video/*,application/pdf"
                    style="height:100%">
            </div>


            <!-- Container for displaying all question with their input fields -->
            <div id="questions-container">
                <!-- Questions are loaded here via JavaScript -->
            </div>

            <!-- Buttons for Adding/Removing question by user -->
            <div class="form-group">
                <button type="button" id="add-question-btn" class="btn btn-secondary">
                    <?= Yii::t('employeeTraining', '+ Add question') ?>
                </button>
                <button type="button" id="remove-question-btn" class="btn btn-danger" style="display: none;">
                    <?= Yii::t('employeeTraining', '- Remove question') ?>
                </button>
            </div>

            <!-- Button for submitting the form and sending data to the endpoint -->
            <div class="form-group">
                <?= Html::button(Yii::t('employeeTraining', 'Submit'), ['class' => 'btn btn-success', 'id' => 'submit-btn']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        <?php endif; ?>
    </div>
</div>

<?php
$saveQuestionsUrl = Url::to(['questions/save-questions']);
$fetchQuestionsUrl = Url::to(['questions/fetch-questions']);
$updateDeadlineUrl = Url::to(['questions/update-deadline']);
$fetchAllProfilesUrl = Url::to(['role/fetch-all-profiles']);
$fetchTitlesUrl = Url::to(['role/fetch-titles']);
$fetchLocationsUrl = Url::to(['role/fetch-locations']);
$fetchFilteredUsersUrl = Url::to(['role/fetch-filtered-users']);
$toggleTrainingUrl = Url::to(['training/toggle-training']);
$removeTrainingUrl = Url::to(['training/remove-training']);
$removeInitialFile = Url::to(['questions/remove-initial-file']);
$trainingIdJson = json_encode($trainingId);
$script = <<<JS

// Function to fetch questions for a given training ID
function fetchQuestions() {
    // Get the training ID from the JavaScript variable
    var trainingId = $trainingIdJson;
    // Make an AJAX request to fetch questions for the specified training
    $.ajax({
        url: '$fetchQuestionsUrl',
        type: 'GET',
        data: { id: trainingId },

        // Function to execute if the request is successful
        success: function(response) {
            if (response.success) {
                // If the response indicates success, populate the questions container with the received HTML
                $('#questions-container').html(response.html);

                // Check if there's an existing video section on the page
                if ($('#existing-file-section').length > 0) {
                    console.log('Video se nachazi na strance');
                    // Hide the section for inputing file if the video is loaded on the page
                    $('#training-file').hide();
                } else {
                    console.log('Nenachazi se');
                }

            } else {
                // If no questions were returned, create a default questions form
                $('#questions-container').html(
                    '<div class="question-item">' +
                        '<label>Question 1</label>' +
                        '<div class="form-group">' +
                            '<select name="TrainingQuestions[0][type]" class="form-control question-type" style="height: 100%; width: 140px;">' +
                                '<option value="text" selected>Text</option>' +
                                '<option value="number">Number (1-5)</option>' +
                                '<option value="range">Range</option>' +
                                '<option value="multiple_choice">Multiple Choice</option>' +
                            '</select>' +
                        '</div>' +
                        '<div class="form-group">' +
                            '<input type="text" name="TrainingQuestions[0][question]" class="form-control question-text" placeholder="Enter your question here">' +
                        '</div>' +
                        '<div class="form-group" style="display: flex; align-items: center;">' +
                            '<p style="margin-right: 7px; padding-top:10px; font-weight:bold; white-space: nowrap;">Correct answer:</p>' +
                            '<input type="text" name="TrainingQuestions[0][correct_answer]" class="form-control correct-answer" placeholder="Enter the correct answer here">' +
                        '</div>' +
                        '<div class="form-group">' +
                            '<input type="file" name="TrainingQuestions[0][image]" class="form-control question-image" style="height:100%">' +
                        '</div>' +
                    '</div>' +
                    '<br>' +
                    '<hr style="border-top: 1px solid">' +
                    '<br>'
                );
            }
            // Show the questions container and buttons for adding/removing questions
            $('#questions-container').show();
            $('#add-question-btn').show();
            $('#submit-btn').show();

            // Update the labels for questions (e.g., Question 1, Question 2, etc.)
            updateQuestionLabels();

            // Show or hide the "Remove Question" button based on the number of questions
            if ($('.question-item').length > 1) {
                $('#remove-question-btn').show();
            } else {
                $('#remove-question-btn').hide();
            }
        },

        // Function to execute if the request fails
        error: function(xhr, status, error) {
            alert('Error occurred while fetching questions.');
            console.log("Error details:", xhr.responseText, status, error);
        }
    });
}

// Event listener for adding a new option to a multiple-choice question
$(document).off('click', '.add-option-btn').on('click', '.add-option-btn', function() {
    var \$multipleChoiceContainer = $(this).closest('.multiple-choice-container');
    var questionIndex = \$multipleChoiceContainer.closest('.question-item').index('.question-item');
    var optionIndex = \$multipleChoiceContainer.find('.multiple-choice-options .input-group').length + 1;

    // Log the container, question index, and option index for debugging
    console.log(\$multipleChoiceContainer)
    console.log(questionIndex)
    console.log(optionIndex)

    // Create the HTML for the new option input
    var newOptionHtml = 
        '<div class="input-group" style="display:flex; align-items:center; padding-bottom: 10px; gap: 5px">' +
            '<div class="input-group-prepend">' +
                '<div class="input-group-text">' +
                    '<input type="checkbox" name="TrainingQuestions[' + questionIndex + '][correct' + optionIndex + ']">' +
                '</div>' +
            '</div>' +
            '<input type="text" name="TrainingQuestions[' + questionIndex + '][option' + optionIndex + ']" class="form-control" placeholder="Option ' + optionIndex + '">' +
        '</div>';

    // Append the new option to the multiple-choice options container
    \$multipleChoiceContainer.find('.multiple-choice-options').append(newOptionHtml);
});

// Event listener for removing the last option from a multiple-choice question
$(document).off('click', '.remove-option-btn').on('click', '.remove-option-btn', function() {
    var \$multipleChoiceContainer = $(this).closest('.multiple-choice-container');
    var \$lastOption = \$multipleChoiceContainer.find('.multiple-choice-options .input-group').last();

    // Remove the last option if there is mroe than one option
    if (\$multipleChoiceContainer.find('.multiple-choice-options .input-group').length > 1) {
        \$lastOption.remove();

    }
});

$(document).on('input', '#modal-user-search-bar', function() {
    var searchTerm = $(this).val().toLowerCase();

    $('#profile-list > div').each(function() {
        var userName = $(this).text().toLowerCase();

        // Check if the user's name contains the search term
        if (userName.includes(searchTerm)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
});

// Event listener for handling changes in question type
$(document).on('change', '.question-type', function() {
    handleQuestionTypeChange.call(this);
});

// Function to handle changes in question type (e.g., text, number, range, multiple choice)
function handleQuestionTypeChange() {
    var \$questionItem = $(this).closest('.question-item');
    var type = $(this).val();
    // Get the index of the current question item
    var questionIndex = \$questionItem.index('.question-item');
    \$questionItem.find('.multiple-choice-container').remove();

    if (type === 'multiple_choice') {
        var multipleChoiceHtml = 
            '<div class="multiple-choice-container">' +
                '<div class="form-group multiple-choice-options">' +
                    '<div class="input-group" style="display:flex; align-items:center; padding-bottom: 10px; gap: 5px">' +
                        '<div class="input-group-prepend">' +
                            '<div class="input-group-text">' +
                                '<input type="checkbox" name="TrainingQuestions[' + questionIndex + '][correct1]">' +
                            '</div>' +
                        '</div>' +
                        '<input type="text" name="TrainingQuestions[' + questionIndex + '][option1]" class="form-control" placeholder="Option 1">' +
                    '</div>' +
                    '<div class="input-group" style="display:flex; align-items:center; padding-bottom: 10px; gap: 5px">' +
                        '<div class="input-group-prepend">' +
                            '<div class="input-group-text">' +
                                '<input type="checkbox" name="TrainingQuestions[' + questionIndex + '][correct2]">' +
                            '</div>' +
                        '</div>' +
                        '<input type="text" name="TrainingQuestions[' + questionIndex + '][option2]" class="form-control" placeholder="Option 2">' +
                    '</div>' +
                    '<div class="input-group" style="display:flex; align-items:center; padding-bottom: 10px; gap: 5px">' +
                        '<div class="input-group-prepend">' +
                            '<div class="input-group-text">' +
                                '<input type="checkbox" name="TrainingQuestions[' + questionIndex + '][correct3]">' +
                            '</div>' +
                        '</div>' +
                        '<input type="text" name="TrainingQuestions[' + questionIndex + '][option3]" class="form-control" placeholder="Option 3">' +
                    '</div>' +
                '</div>' +
                '<div class="form-group">' +
                    '<button type="button" class="btn btn-secondary add-option-btn">+ Add Option</button>' +
                    '<button type="button" class="btn btn-danger remove-option-btn">- Remove Option</button>' +
                '</div>' +
            '</div>';

        // Insert the multiple-choice HTML before the last form-group (which contains the image input)
        \$questionItem.find('.form-group').last().before(multipleChoiceHtml);
    }


    if (type === 'text') {
        // Show the correct answer input field if the question type is text
        \$questionItem.find('.correct-answer').closest('.form-group').show();
    } else {
        // Hide the correct answer input field for other question types
        \$questionItem.find('.correct-answer').closest('.form-group').hide();
    }
}

var modal = document.getElementById("assignUsersModal");

var span = document.getElementsByClassName("close")[0];

var trainingId = $('h3[data-training-id]').data('training-id'); // Retrieve the trainingId from the data attribute


// Event listener for showing the modal to assign users to the training
$(document).on('click', '#assign-users-btn', function() {
    var title = 'Assign Users';
    $('#modal-title').text(title); // Update the modal title
    modal.style.display = "block"; // Show the modal
    $('body').css('overflow', 'hidden'); // Disable scrolling on the main page 

    // Fetch profiles and display them in the modal
    $.ajax({
        url: '$fetchAllProfilesUrl',
        type: 'GET',
        data: { trainingId: trainingId },
        success: function(response) {
            if (response.success) {
                var profilesHtml = '';
                // Iterate over the profiles and create divs with checkboxes
                $.each(response.profiles, function(index, profile) {
                    console.log('User ID:', profile.id, 'User Name:', profile.firstname + ' ' + profile.lastname);
                    profilesHtml += '<div><input type="checkbox" class="profile-checkbox" value="' + profile.id + '"' + 
                                (profile.isAssigned ? ' checked data-was-assigned="true"' : '') + '> ' + 
                                profile.firstname + ' ' + profile.lastname + '</div>';
                });
                // Insert the profiles into the modal
                $('#profile-list').html(profilesHtml);
            } else {
                // If no profiles found, display a message
                $('#profile-list').html('<p>No profiles found.</p>');
            }
        },
        error: function() {
            // Handle any errors that occur during the request
            $('#profile-list').html('<p>Error fetching profiles.</p>');
        }
    });

    // Fetch job titles and locatins for filtering users
    if ($('#filter-list').is(':visible')) {
        $.ajax({
            url: '$fetchTitlesUrl',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    // Populate the job title dropdown with fetched data
                    var titleOptions = '<option value="">All Jobs</option>';
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

        $.ajax({
            url: '$fetchLocationsUrl',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    // Populate the location dropdown with fetched data
                    var locationOptions = '<option value="">All Locations</option>';
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
    }
});

// Close the modal when the close button is clicked
span.onclick = function() {
    modal.style.display = "none";
    $('#modal-user-search-bar').val('');
    $('body').css('overflow', 'auto'); // Enable scrolling on the main page
}

// Close the modal if the user clicks outside of it
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
        $('#modal-user-search-bar').val('');
        $('body').css('overflow', 'auto'); // Enable scrolling on the main page
    }
}

// Event listener for filtering users based on job title and location
$(document).on('click', '#submit-filter-btn', function() {
    var selectedTitle = $('#title-select').val();
    var selectedLocation = $('#location-select').val();

    // Clear the previous user list to ensure no old data is displayed
    $('#profile-list').empty();

    // Fetch users based on the selected filters
    $.ajax({
        url: '$fetchFilteredUsersUrl',
        type: 'GET',
        data: {
            title: selectedTitle,
            location: selectedLocation
        },
        success: function(response) {
            if (response.success) {
                var usersHtml = '';
                $.each(response.users, function(index, user) {
                    console.log('User ID:', user.user_id, 'User Name:', user.firstname + ' ' + user.lastname);
                    usersHtml += '<div><input type="checkbox" class="profile-checkbox" value="' + user.user_id + '"> ' + user.firstname + ' ' + user.lastname + '</div>';
                });
                $('#profile-list').html(usersHtml);
            } else {
                $('#profile-list').html('<p>No users found.</p>');
            }
        },
        error: function(xhr, status, error) {
            alert('Error fetching filtered users');
            console.log(xhr.responseText);
        }
    });
});



// Event listener for submitting the selected users to assign or unassign training
$(document).on('click', '#submit-assign-users', function() {
    // Disable the button to prevent the multiple clicks (previously an issue with assigning multiple training instead of just one)
    $(this).prop('disabled', true);
    var selectedUserIds = [];
    var unassignUserIds = [];

    // Collect the IDs of selected users to assign or unassign training
    $('#profile-list').find('.profile-checkbox').each(function() {
        if ($(this).is(':checked')) {
            // Check if the user ID is not already in selectedUserIds
            if (!selectedUserIds.includes($(this).val())) {
                selectedUserIds.push($(this).val());
                console.log('Tenhle je checked: ', $(this).val());
            }
        } else if ($(this).data('was-assigned')) {
            if (!unassignUserIds.includes($(this).val())) {
                unassignUserIds.push($(this).val());
                console.log('Tenhle byl checked: ', $(this).val());
            }
        }
    });

    if (selectedUserIds.length === 0 && unassignUserIds.length === 0) {
        alert('Please select at least one user to assign or unassign the training.');
        $(this).prop('disabled', false);
        return;
    }

    var trainingId = $('h3[data-training-id]').data('training-id');
    
    // Get the current time in the specified timezone
    var currentTime = new Date().toLocaleString('en-GB', {
        timeZone: 'Europe/Berlin',
    }).replace(',', '');

    currentTime = currentTime.replace(/\//g, '-');
    // Unassign users first
    if (unassignUserIds.length > 0) {
        $.ajax({
            url: '$removeTrainingUrl',
            type: 'POST',
            data: {
                user_ids: unassignUserIds,
                training_id: trainingId,
                _csrf: yii.getCsrfToken()
            },
            success: function(response) {
                if (response.success) {
                    console.log(response.message);
                } else {
                    alert('Failed to unassign training from some users.');
                }
            },
        });
    }

    // Assign training to checked users
    if (selectedUserIds.length > 0) {
        $.ajax({
            url: '$toggleTrainingUrl',
            type: 'POST',
            data: {
                user_ids: selectedUserIds,
                assigned_training: 1,
                training_assigned_time: currentTime,
                training_id: trainingId,
                _csrf: yii.getCsrfToken()
            },
            success: function(response) {
                if (response.success) {
                    console.log(response.message);
                } else {
                    console.log('User has already training assigned.');
                }
            },
        });
    }

    if (selectedUserIds.length > 0 || unassignUserIds.length > 0) {
        location.reload();
    }

});

// Event handler for the "Add Question" click
$('#add-question-btn').on('click', function() {
    var questionIndex = $('.question-item').length;
    var newQuestionItem = 
        '<div class="question-item">' +
            '<label>Question ' + (questionIndex + 1) + '</label>' + 
            '<div class="form-group">' +
                '<select name="TrainingQuestions[' + questionIndex + '][type]" class="form-control question-type" style="height:100%; width: 140px;">' +
                    '<option value="text" selected>Text</option>' +
                    '<option value="number">Number (1-5)</option>' +
                    '<option value="range">Range</option>' +
                    '<option value="multiple_choice">Multiple Choice</option>' +
                '</select>' +
            '</div>' +
            '<div class="form-group">' +
                '<input type="text" name="TrainingQuestions[' + questionIndex + '][question]" class="form-control question-text" placeholder="Enter your question here">' +
            '</div>' +
            '<div class="form-group" style="display: flex; align-items: center;">' +
                '<p style="margin-right: 7px; padding-top:10px; font-weight:bold; white-space: nowrap;">Correct answer:</p>' +
                '<input type="text" name="TrainingQuestions[' + questionIndex + '][correct_answer]" class="form-control correct-answer" placeholder="Enter the correct answer here">' +
            '</div>' +
            '<div class="form-group">' +
                '<input type="file" name="TrainingQuestions[' + questionIndex + '][image]" class="form-control question-image" style="height:100%">' +
            '</div>' +
        '<br>' +
        '<hr style="border-top: 1px solid">' +
        '<br>' +
        '</div>';

    // Append the new question items to the question container
    $('#questions-container').append(newQuestionItem);
    // Update the labels after adding the new questions
    updateQuestionLabels();
    if ($('.question-item').length > 1) {
        // Show the remove button if there's more than one question
        $('#remove-question-btn').show();
    }
});

// Event handler for the "Remove File" button click
$(document).on('click', '.remove-file-btn', function() {
    // Find the parent form-group of the file input
    var fileContainer = $(this).closest('.form-group');
    
    // Remove the entire file input container
    fileContainer.remove();
    
    // Get the training ID from the data attribute
    var trainingId = $('h3[data-training-id]').data('training-id');
    
    // Show the file input section
    $('#training-file').show();

    // AJAX request to remove the initial file from the server
    $.ajax({
        url: '$removeInitialFile',
        type: 'POST',
        data : { deleteVid: true, trainingId: trainingId },
        success: function(response) {
            console.log('File removed successfully');
        },
        error: function(xhr, status, error) {
            console.log(xhr.responseText);
        }
    });
});

// Event handler for the "Remove Question" button click
$('#remove-question-btn').on('click', function() {
    var lastQuestionItem = $('.question-item').last();
    lastQuestionItem.next('br').remove();  // Remove the next <br> element
    lastQuestionItem.next('hr').remove();  // Remove the next <hr> element
    lastQuestionItem.remove();

    // Update the labels after removing the question
    updateQuestionLabels();

    // Hide the "Remove Question" button if there's only one question left
    if ($('.question-item').length <= 1) {
        $('#remove-question-btn').hide();
    }
});

// Event handler for "Submit" button click
$('#submit-btn').on('click', function() {
    var form = $('#training-questions-form')[0];
    var formData = new FormData(form);
    let isValid = true;
    var loadVid = $('video').length > 0;

    formData.append('loadVid', loadVid);
    console.log('Tohle je loadVid', loadVid);
    $('.question-item').each(function(index) {
        var fileInput = $(this).find('.question-image')[0];
        if (fileInput.files.length > 0) {
            formData.append('TrainingQuestions[' + index + '][image]', fileInput.files[0]);
        } else {
            formData.append('TrainingQuestions[' + index + '][image]', null);
        }

        // Collect multiple choice options if the question type is multiple_choice
        if ($(this).find('.question-type').val() === 'multiple_choice') {
            $(this).find('.multiple-choice-options .input-group').each(function(optionIndex) {
                var optionText = $(this).find('input[type="text"]').val();
                var isCorrect = $(this).find('input[type="checkbox"]').is(':checked');

                formData.append('TrainingQuestions[' + index + '][options][' + optionIndex + '][text]', optionText);
                formData.append('TrainingQuestions[' + index + '][options][' + optionIndex + '][correct]', isCorrect ? 1 : 0);

                if (isCorrect) {
                    console.log('Correct answer for question ' + (index + 1) + ': ' + optionText);
                }
            });
        }
        
        if ($(this).find('.question-type').val() === 'text') {
            var correctAnswer = $(this).find('.correct-answer').val();
            console.log('Correct answer for question ' + (index + 1) + ': ' + correctAnswer);
            formData.append('TrainingQuestions[' + index + '][correct_answer]', correctAnswer);
        }
    });

    $('.question-text').each(function() {
        let inputName = $(this).attr('name');
        let inputValue = $(this).val();

        if (!inputValue) {
            isValid = false;
            $(this).css('border', '2px solid red');
        } else {
            $(this).css('border', '1px solid #dee2e6');
        }
    });

    if (isValid) {
        $.ajax({
            url: '$saveQuestionsUrl',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to save questions.');
                    console.log(response.errors);
                }
            },
            error: function(xhr, status, error) {
                alert('Error occurred while saving questions.');
                console.log(xhr.responseText);
            }
        });
    }
});


$(document).on('click', '.remove-image-btn', function() {
    var index = $(this).data('index');
    var parentDiv = $(this).closest('.form-group');
    parentDiv.find('img').remove();
    $(this).remove();
    $('input[name="TrainingQuestions[' + index + '][existing_image]"]').remove();
    $('input[name="TrainingQuestions[' + index + '][remove_image]"]').val(1);
    parentDiv.find('.question-image').show(); // Show the file input
});

$('#all-users').on('change', function() {
    console.log("Click on checkbox");
    $('#title-select').prop('disabled', this.checked);
    console.log('Checkbox is ' + (this.checked ? 'checked' : 'unchecked'));
});

// Function to update labels of all question items
function updateQuestionLabels() {
    $('.question-item').each(function(index) {
        $(this).find('label').text('Question ' + (index + 1));
    });
}

// Document ready function to initialize the form
$(document).ready(function() {
    fetchQuestions();
    updateQuestionLabels();
    if ($('.question-item').length <= 1) {
        $('#remove-question-btn').hide();
    }
});

// Event handler for "Edit" button click
$('#edit-deadline-btn').on('click', function() {
    $('#deadline-display').hide();
    $('#edit-deadline-btn').hide();
    $('#edit-deadline-form').show();
});

// Event handler for "Submit" button click
$('#submit-deadline-btn').on('click', function() {
        var newDeadline = $('#deadline-input').val();
        var trainingId = $trainingIdJson;

        // AJAX request to update the deadline in the database
        $.ajax({
            url: '$updateDeadlineUrl', // Update this URL to match your endpoint
            type: 'POST',
            data: {
                id: trainingId,
                deadline: newDeadline,
                _csrf: yii.getCsrfToken() // Include CSRF token
            },
            success: function(response) {
                if (response.success) {
                    // Update the display with the new deadline and hide the input form
                    $('#deadline-display').text(newDeadline).show();
                    $('#edit-deadline-form').hide();
                    $('#edit-deadline-btn').show();
                } else {
                    alert('Failed to update deadline.');
                }
            },
            error: function(xhr, status, error) {
                alert('Error occurred while updating deadline.');
                console.log(xhr.responseText);
            }
        });
    });

// Event handler for "Cancel" button click
$('#cancel-deadline-btn').on('click', function() {
    $('#edit-deadline-form').hide();
    $('#edit-deadline-btn').show();
    $('#deadline-display').show();
});

JS;
$this->registerJs($script);
?>