<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

// TODO Enable assigning trainings on this page

?>

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
                <button id="toggle-filter-btn"><?= Yii::t('employeeTraining', 'Filter') ?><span id="arrow-down">
                        ▼</span></button>
                <div id="filter-list" style="display:none;">
                    <form id="filter-form">
                        <div class="form-group">
                            <label for="title-select"><?= Yii::t('employeeTraining', 'Select Title') ?></label>
                            <select id="title-select" class="form-control">
                                <!-- Options will be populated dynamically -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="location-select"><?= Yii::t('employeeTraining', 'Select Location') ?></label>
                            <select id="location-select" class="form-control">
                                <!-- Options will be populated dynamically -->
                            </select>
                        </div>
                        <button type="button" id="submit-filter-btn"
                            class="btn btn-success"><?= Yii::t('employeeTraining', 'Submit') ?></button>
                    </form>
                </div>
                <form id="assign-users-form">
                    <div id="profile-list"></div>
                    <br>
                    <button type="button" id="submit-assign-users">Assign</button>
                </form>
            </div>
        </div>



        <br>

        <!-- Begin the ActiveForm -->
        <?php $form = ActiveForm::begin([
            'id' => 'training-questions-form',
            'options' => ['enctype' => 'multipart/form-data'],
            'enableAjaxValidation' => false,
            'enableClientValidation' => true,
        ]); ?>


        <!-- Hidden input to store the training ID -->
        <?= Html::hiddenInput('trainingId', $trainingId) ?>

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

        <!-- Button for Advanced settings and checkbox for selecting all users with checkbox -->
        <div class="form-group">
            <button type="button" id="advanced-settings-btn" tabindex="1" style="display:none;">
                <?= Yii::t('employeeTraining', 'Advanced settings ') ?>
                <span id="arrow-down">▼</span></button>
            <div id="assign-to-all" style="display: none;">
                <input type="checkbox" id="all-users" name="all-users" class="assign-to-all-checkbox">
                <label for="all-users">
                    <?= Yii::t('employeeTraining', 'Assign question(s) to all titles') ?>
                </label>
            </div>
        </div>

        <!-- Button for submitting the form and sending data to the endpoint -->
        <div class="form-group">
            <?= Html::button(Yii::t('employeeTraining', 'Submit'), ['class' => 'btn btn-success', 'id' => 'submit-btn']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php
$createQuestionsUrl = Url::to(['training-questions/save-questions']);
$fetchQuestionsUrl = Url::to(['training-questions/fetch-questions']);
$updateDeadlineUrl = Url::to(['training-questions/update-deadline']);
$fetchAllProfilesUrl = Url::to(['role/fetch-all-profiles']);
$fetchTitlesUrl = Url::to(['role/fetch-titles']);
$fetchLocationsUrl = Url::to(['role/fetch-locations']);
$fetchFilteredUsersUrl = Url::to(['role/fetch-filtered-users']);
$toggleTrainingUrl = Url::to(['role/toggle-training']);
$trainingIdJson = json_encode($trainingId);
$script = <<<JS

function fetchQuestions() {
    var trainingId = $trainingIdJson;
    $.ajax({
        url: '$fetchQuestionsUrl',
        type: 'GET',
        data: { id: trainingId },
        success: function(response) {
            if (response.success) {
                $('#questions-container').html(response.html);
                attachEventHandlers(); // Attach event handlers after loading content
            } else {
                $('#questions-container').html(
                    '<div class="question-item">' +
                        '<label>Question 1</label>' +
                        '<div class="form-group">' +
                            '<select name="TrainingQuestions[0][type]" class="form-control question-type">' +
                                '<option value="text" selected>Text</option>' +
                                '<option value="number">Number</option>' +
                                '<option value="range">Range</option>' +
                                '<option value="multiple_choice">Multiple Choice</option>' +
                            '</select>' +
                        '</div>' +
                        '<div class="form-group">' +
                            '<input type="text" name="TrainingQuestions[0][question]" class="form-control question-text" placeholder="Enter your question here">' +
                        '</div>' +
                        '<div class="form-group">' +
                            '<input type="file" name="TrainingQuestions[0][image]" class="form-control question-image">' +
                        '</div>' +
                    '</div>'
                );
                attachEventHandlers(); // Attach event handlers for default content
            }
            $('#questions-container').show();
            $('#add-question-btn').show();
            $('#submit-btn').show();
            updateQuestionLabels();
            if ($('.question-item').length > 1) {
                $('#remove-question-btn').show();
            } else {
                $('#remove-question-btn').hide();
            }
        },
        error: function(xhr, status, error) {
            alert('Error occurred while fetching questions.');
            console.log("Error details:", xhr.responseText, status, error);
        }
    });
}

function attachEventHandlers() {
    $(document).on('change', '.question-type', function() {
        handleQuestionTypeChange.call(this); // Call the function with the correct context
    });

    $(document).on('click', '.add-option-btn', function() {
        var \$multipleChoiceContainer = $(this).closest('.multiple-choice-container');
        var questionIndex = \$multipleChoiceContainer.closest('.question-item').index('.question-item');
        var optionIndex = \$multipleChoiceContainer.find('.multiple-choice-options .input-group').length + 1;

        var newOptionHtml = 
            '<div class="input-group" style="display:flex; align-items:center; padding-bottom: 10px; gap: 5px">' +
                '<div class="input-group-prepend">' +
                    '<div class="input-group-text">' +
                        '<input type="checkbox" name="TrainingQuestions[' + questionIndex + '][correct' + optionIndex + ']">' +
                    '</div>' +
                '</div>' +
                '<input type="text" name="TrainingQuestions[' + questionIndex + '][option' + optionIndex + ']" class="form-control" placeholder="Option ' + optionIndex + '">' +
            '</div>';
        \$multipleChoiceContainer.find('.multiple-choice-options').append(newOptionHtml);
    });

    $(document).on('click', '.remove-option-btn', function() {
        var \$multipleChoiceContainer = $(this).closest('.multiple-choice-container');
        var \$lastOption = \$multipleChoiceContainer.find('.multiple-choice-options .input-group').last();

        if (\$multipleChoiceContainer.find('.multiple-choice-options .input-group').length > 1) {
            \$lastOption.remove();
        }
    });
}

$(document).on('change', '.question-type', function() {
    handleQuestionTypeChange.call(this); // Call the function with the correct context
});

function handleQuestionTypeChange() {
    var \$questionItem = $(this).closest('.question-item');
    var type = $(this).val();
    var questionIndex = \$questionItem.index('.question-item'); // Get the index of the current question item
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
        \$questionItem.find('.form-group').last().before(multipleChoiceHtml); // Insert the multiple choice HTML before the last form-group (which contains the image input)
    }
}

var modal = document.getElementById("assignUsersModal");

var span = document.getElementsByClassName("close")[0];

$(document).on('click', '#assign-users-btn', function() {
    var title = 'Assign Users'; // Adjust the title as needed
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
                    profilesHtml += '<li><input type="checkbox" class="profile-checkbox" value="' + profile.id + '"> ' + profile.firstname + ' ' + profile.lastname + '</li>';
                });
                profilesHtml += '</ul>';
                $('#profile-list').html(profilesHtml);
            } else {
                $('#profile-list').html('<p>No profiles found.</p>');
            }
        },
        error: function() {
            $('#profile-list').html('<p>Error fetching profiles.</p>');
        }
    });
});

span.onclick = function() {
    modal.style.display = "none";
    $('body').css('overflow', 'auto'); // Enable scrolling
}

window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
        $('body').css('overflow', 'auto'); // Enable scrolling
    }
}

$(document).on('click', '#toggle-filter-btn', function() {
    $('#filter-list').toggle(); // Toggle the visibility of the filter list
    var arrow = $('#arrow-down');
    arrow.toggleClass('rotated');

    if ($('#filter-list').is(':visible')) {
        // Fetch and populate titles and locations if filter list is now visible
        $.ajax({
            url: '$fetchTitlesUrl',
            type: 'GET',
            success: function(response) {
                if (response.success) {
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

$(document).on('click', '#submit-filter-btn', function() {
    var selectedTitle = $('#title-select').val();
    var selectedLocation = $('#location-select').val();

    $.ajax({
        url: '$fetchFilteredUsersUrl',
        type: 'GET',
        data: {
            title: selectedTitle,
            location: selectedLocation
        },
        success: function(response) {
            if (response.success) {
                var usersHtml = '<ul>';
                $.each(response.users, function(index, user) {
                    usersHtml += '<li><input type="checkbox" class="profile-checkbox" value="' + user.id + '"> ' + user.firstname + ' ' + user.lastname + '</li>';
                });
                usersHtml += '</ul>';
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

$(document).on('click', '#submit-assign-users', function() {
    var selectedUserIds = [];
    $('#profile-list').find('.profile-checkbox:checked').each(function() {
        selectedUserIds.push($(this).val());
    });

    if (selectedUserIds.length === 0) {
        alert('Please select at least one user to assign the training.');
        return;
    }

    var trainingId = $('h3[data-training-id]').data('training-id'); // Retrieve the trainingId from the data attribute
    var currentTime = new Date().toISOString().slice(0, 19).replace('T', ' ');

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
                alert(response.count + ' users have been assigned the training.');
                location.reload(); // Reload the page to see the updated assigned users count
            } else {
                alert('Failed to assign training to users.');
            }
        },
        error: function(xhr, status, error) {
            alert('Error occurred while assigning training.');
            console.log("Error details:", xhr.responseText, status, error);
        }
    });
});




// Event handler for the "Add Question" click
$('#add-question-btn').on('click', function() {
    var questionIndex = $('.question-item').length;
    var newQuestionItem = 
        '<div class="question-item">' +
            '<label>Question ' + (questionIndex + 1) + '</label>' + 
            '<div class="form-group">' +
                '<select name="TrainingQuestions[' + questionIndex + '][type]" class="form-control question-type">' +
                    '<option value="text" selected>Text</option>' +
                    '<option value="number">Number</option>' +
                    '<option value="range">Range</option>' +
                    '<option value="multiple_choice">Multiple Choice</option>' +
                '</select>' +
            '</div>' +
            '<div class="form-group">' +
                '<input type="text" name="TrainingQuestions[' + questionIndex + '][question]" class="form-control question-text" placeholder="Enter your question here">' +
            '</div>' +
            '<div class="form-group">' +
                '<input type="file" name="TrainingQuestions[' + questionIndex + '][image]" class="form-control question-image">' +
            '</div>' +
        '</div>';
    $('#questions-container').append(newQuestionItem);
    updateQuestionLabels();
    if ($('.question-item').length > 1) {
        $('#remove-question-btn').show();
    }
});

// Event handler for the "Remove Question" button click
$('#remove-question-btn').on('click', function() {
    $('.question-item').last().remove();
    updateQuestionLabels();
    if ($('.question-item').length <= 1) {
        $('#remove-question-btn').hide();
    }
});

// TODO make button unchecked if not toggled
$('#advanced-settings-btn').on('click', function() {
    var allUsers = $('#assign-to-all');
    allUsers.toggle();
    var arrow = $('#arrow-down');

    // Toggle the rotated class for the arrow animation
    arrow.toggleClass('rotated');

    if (!allUsers.is(':visible')) {
        $('#all-users').prop('checked', false);
        $('#title-select').prop('disabled', false);
    }
});

// Event handler for "Submit" button click
$('#submit-btn').on('click', function() {
    var form = $('#training-questions-form')[0];
    var formData = new FormData(form);
    let isValid = true;

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
            });
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
            url: '$createQuestionsUrl',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('Questions saved successfully!');
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

<style>
    .title-dropdown {
        width: 190px;
    }

    /* Modal styling */
    .modal {
        display: none;
        position: fixed;
        z-index: 1050;
        padding-top: 100px;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgb(0, 0, 0);
        background-color: rgba(0, 0, 0, 0.5);
        /* Updated for a more pronounced backdrop */
    }

    .modal-content {
        background-color: #fefefe;
        margin: auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        z-index: 1051;
        /* Ensure modal content is above the backdrop */
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
</style>