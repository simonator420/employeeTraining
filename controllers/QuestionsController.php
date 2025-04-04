<?php

namespace humhub\modules\employeeTraining\controllers;

use Yii;
use humhub\components\Controller;
use yii\web\Response;
use yii\helpers\Html;
use yii\web\UploadedFile;
use yii\helpers\Url;

/**
 * Controller for handling questions within the Employee Training module.
 */
class QuestionsController extends Controller
{
    /**
     * Displays the questions page for Admin or Team Leader.
     *
     * This function checks the user's role and retrieves the training details
     * (name, deadline, assigned users count) and renders the 'questions' view.
     *
     * @param string $id The ID of the training.
     * @return string|\yii\web\Response The rendered view or a redirect response.
     */
    public function actionQuestions($id)
    {
        // Get the current user
        $currentUser = Yii::$app->user;

        $userRole = $currentUser->identity->profile->role;

        $isActive = Yii::$app->db->createCommand('SELECT is_active FROM training WHERE id=:id')
            ->bindValue(':id', $id)
            ->queryScalar();

        // Check if the logged in user is admin
        if (($userRole !== 'admin' && $userRole !== 'team_leader') || $isActive != 1) {
            // Redirect to acces denied if the user isn't admin
            return $this->redirect(['site/access-denied']);
        }

        // Retrieve the training name for the specified training ID using a prepared query
        $trainingName = Yii::$app->db->createCommand('SELECT name FROM training WHERE id=:id')
            ->bindValue(':id', $id)
            ->queryScalar();

        // Retrieve the deadline for completing the training for the specified training ID
        $deadline = Yii::$app->db->createCommand('SELECT deadline_for_completion FROM training WHERE id =:id')
            ->bindValue(':id', $id)
            ->queryScalar();

        // Count the number of users assigned to the specified training
        $assignedUsersCount = Yii::$app->db->createCommand('SELECT COUNT(*) FROM user_training WHERE assigned_training = 1 AND training_id =:training_id')
            ->bindValue(':training_id', $id)
            ->queryScalar();

        // Fetch distinct titles from the profile table
        $titles = Yii::$app->db->createCommand('SELECT DISTINCT title FROM profile')->queryColumn();

        // Sort the titles alphabetically
        sort($titles);

        // Render the questions view for admin, passing the fetched titles, training details, and user role
        return $this->render('/admin/questions', [
            'titles' => $titles,
            'trainingId' => $id,
            'trainingName' => $trainingName,
            'deadlineForCompletion' => $deadline,
            'assignedUsersCount' => $assignedUsersCount,
            'userRole' => $userRole,
        ]);
    }

    /**
     * Retrieves the questions from the database and displays them for Admin at questions.php for editing.
     *
     * @param string $id The ID of the training.
     * @return array JSON response with the questions' HTML or a failure message.
     */
    public function actionFetchQuestions($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Fetch the training record based on the provided training ID
        $training = Yii::$app->db->createCommand('SELECT * FROM training WHERE id = :id')
            ->bindValue(':id', $id)
            ->queryOne();

        // Fetch all active questions related to the specified training, ordered by their ID
        $questions = Yii::$app->db->createCommand('SELECT * FROM training_questions WHERE training_id = :id AND is_active = 1 ORDER BY `id`')
            ->bindValue(':id', $id)
            ->queryAll();

        // Initialize an empty string to build the HTML content
        $html = '';

        // If there is a video associated with the training, display it
        if (!empty($training['initial_file_url'])) {
            $fileExtension = pathinfo($training['initial_file_url'], PATHINFO_EXTENSION);
            $html .= '<div id="existing-file-section" class="form-group">';

            // Check if the file is video or pdf
            if (in_array($fileExtension, ['mp4', 'webm', 'ogg'])) {
                $html .= '<video width="320" height="240" controls>';
                $html .= '<source src="' . Url::to('@web/' . $training['initial_file_url']) . '" type="video/' . $fileExtension . '">';
                $html .= 'Your browser does not support the video tag.';
                $html .= '</video>';
            } elseif (in_array($fileExtension, ['pdf'])) {
                $html .= '<embed src="' . Url::to('@web/' . $training['initial_file_url']) . '" width="360" height="300" alt="pdf" />';
            }

            $html .= '<br>';
            $html .= '<button type="button" class="btn remove-file-btn" style="margin-bottom: 25px;">' . Yii::t('employeeTraining', 'Remove File') . '</button>';
            $html .= '</div>';
        }

        if ($questions) {
            // Create a div for each question item
            foreach ($questions as $index => $question) {
                $html .= '<div class="question-item">';
                $html .= '<label>Question ' . ($index + 1) . '</label>';

                // Create a dropdown for selecting the question type (text, number, range, multiple choice)
                $html .= '<div class="form-group">';
                $html .= Html::dropDownList("TrainingQuestions[$index][type]", $question['type'], ['text' => 'Text', 'number' => 'Number (1-5)', 'range' => 'Range', 'multiple_choice' => 'Multiple Choice'], ['class' => 'form-control question-type', 'style' => 'height: 100%; width: 140px;']);
                $html .= '</div>';

                // Create a text input for entering the question text
                $html .= '<div class="form-group">';
                $html .= Html::textInput("TrainingQuestions[$index][question]", $question['question'], ['class' => 'form-control question-text', 'placeholder' => 'Enter your question here']);
                $html .= '</div>';

                // If the question type is multiple choice, display the options
                if ($question['type'] == 'multiple_choice') {
                    $html .= '<div class="form-group multiple-choice-container">';

                    // Fetch all active options for the current multiple choice question
                    $options = Yii::$app->db->createCommand('SELECT * FROM training_multiple_choice_answers WHERE question_id = :question_id AND is_active = 1')
                        ->bindValue(':question_id', $question['id'])
                        ->queryAll();

                    // Loop through each option and generate the corresponding HTML
                    $html .= '<div class="form-group multiple-choice-options">';
                    foreach ($options as $optionIndex => $option) {
                        // Create an input group for each option
                        $html .= '<div class="input-group" style="display: flex; align-items: center; padding-bottom: 10px; gap: 5px">';
                        $html .= '<div class="input-group-prepend">';
                        $html .= '<div class="input-group-text">';
                        $html .= Html::checkbox("TrainingQuestions[$index][options][$optionIndex][correct]", $option['is_correct']);
                        $html .= '</div>';
                        $html .= '</div>';
                        $html .= Html::textInput("TrainingQuestions[$index][options][$optionIndex][text]", $option['option_text'], ['class' => 'form-control', 'placeholder' => 'Option ' . ($optionIndex + 1)]);
                        $html .= '</div>';
                    }
                    $html .= '</div>';

                    // Buttons to add or remove options
                    $html .= '<div class="form-group">';
                    $html .= '<button type="button" class="btn btn-secondary add-option-btn">+ Add Option</button>';
                    $html .= '<button type="button" class="btn btn-danger remove-option-btn">- Remove Option</button>';
                    $html .= '</div>';
                    $html .= '</div>';
                }

                // If the question type is text, display the correct answer field
                if ($question['type'] == 'text') {
                    $html .= '<div class="form-group" style="display: flex; align-items: center;">';
                    $html .= '<p style="margin-right: 7px; padding-top:10px; font-weight:bold;">Correct answer:</p>';
                    $html .= Html::textInput("TrainingQuestions[$index][correct_answer]", $question['correct_answer'], ['class' => 'form-control correct-answer', 'placeholder' => 'Enter the correct answer here', 'style' => 'flex: 1;']);
                    $html .= '</div>';
                }

                // Display an existing image if one is associated with the question, with an option to remove it
                $html .= '<div class="form-group">';
                if ($question['image_url']) {
                    $html .= Html::img(Url::to('@web/' . $question['image_url']), ['alt' => 'Image', 'style' => 'max-width: 200px; max-height: 200px;']);
                    $html .= Html::hiddenInput("TrainingQuestions[$index][existing_image]", $question['image_url']);
                    $html .= Html::button('Remove image', ['class' => 'btn btn-danger remove-image-btn', 'data-index' => $index, 'style' => 'display: block;']);
                    $html .= Html::hiddenInput("TrainingQuestions[$index][remove_image]", 0, ['class' => 'remove-image-input']);
                }
                // Input for uploading a new image
                $html .= '<input type="file" name="TrainingQuestions[' . $index . '][image]" class="form-control question-image" style="height:100%;" accept="image/*"' . ($question['image_url'] ? ' style="display:none; height:100%;"' : '') . '>';
                $html .= '</div>';

                $html .= '<br>';
                $html .= '<hr style="border-top: 1px solid">';
                $html .= '<br>';
                $html .= '</div>';
            }

            // Return the generated HTML as a successful response
            return ['success' => true, 'html' => $html];
        } else {

            // If no questions are found, return a failure response
            return ['success' => false];
        }
    }

    /**
     * Saves questions into the database by Admin from questions.php.
     *
     * This function handles the saving of questions, including text, number, range,
     * and multiple choice types, along with associated images and video uploads.
     *
     * @return array JSON response indicating success or failure.
     */
    public function actionSaveQuestions()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Retrieve the training ID and questions data from the POST request
        $trainingId = Yii::$app->request->post('trainingId');
        $questions = Yii::$app->request->post('TrainingQuestions', []);
        $loadVid = Yii::$app->request->post('loadVid');

        // Retrieve uploaded files for questions and the training video
        $files = UploadedFile::getInstancesByName('TrainingQuestions');
        $uploadedFile = UploadedFile::getInstanceByName('trainingFile');

        // Check if training ID is provided
        if (empty($trainingId)) {
            return ['success' => false, 'errors' => 'Training ID is required'];
        }

        // Start a database transaction
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Mark existing questions for this training as inactive
            Yii::$app->db->createCommand()->update('training_questions', [
                'is_active' => false
            ], ['training_id' => $trainingId])->execute();

            // Fetch the IDs of existing questions for the given training 
            $questionIds = Yii::$app->db->createCommand('
                SELECT id FROM training_questions WHERE training_id = :trainingId
            ')
                ->bindValue(':trainingId', $trainingId)
                ->queryColumn();

            // If there are existing questions, mark their associated multiple choice answers as inactive
            if (!empty($questionIds)) {
                Yii::$app->db->createCommand()->update('training_multiple_choice_answers', [
                    'is_active' => false
                ], ['question_id' => $questionIds])->execute();
            }

            // Array the store the IDs of newly inserted questions
            $newQuestionIds = [];

            // Handle the video file upload, if provided
            $fileUrl = null;
            if ($uploadedFile) {
                $filePath = 'uploads/' . $uploadedFile->baseName . '.' . $uploadedFile->extension;
                if ($uploadedFile->saveAs($filePath)) {
                    $fileUrl = $filePath;
                    // Save the file URL to the training record in the database
                    Yii::$app->db->createCommand()->update('training', [
                        'initial_file_url' => $fileUrl
                    ], ['id' => $trainingId])->execute();
                } else {
                    return ['success' => false, 'errors' => 'Failed to save the file.'];
                }
            }

            // Process each question submitted in the form
            foreach ($questions as $index => $questionData) {
                $questionText = $questionData['question'];
                $correctAnswer = isset($questionData['correct_answer']) ? $questionData['correct_answer'] : null;
                $questionType = $questionData['type'];

                // Handle image file upload for the question, if provided
                $imageFile = UploadedFile::getInstanceByName('TrainingQuestions[' . $index . '][image]');
                $imageUrl = isset($questionData['existing_image']) ? $questionData['existing_image'] : null;
                if ($imageFile) {
                    // Define the path to save the image file
                    $imagePath = 'uploads/' . $imageFile->baseName . '.' . $imageFile->extension;
                    if ($imageFile->saveAs($imagePath)) {
                        $imageUrl = $imagePath;
                    } else {
                        return ['success' => false, 'errors' => 'Failed to save the image file.'];
                    }
                }

                // Insert the new question into the database
                Yii::$app->db->createCommand()->insert('training_questions', [
                    'training_id' => $trainingId,
                    'type' => $questionType,
                    'question' => $questionText,
                    'image_url' => $imageUrl,
                    'correct_answer' => $correctAnswer,
                    'is_active' => true
                ])->execute();

                // Get the id of the newly inserted question
                $questionId = Yii::$app->db->getLastInsertID();
                $newQuestionIds[] = $questionId;

                // If the question is of type 'multiple_choice', process the options
                if ($questionType == 'multiple_choice') {
                    foreach ($questionData['options'] as $optionIndex => $optionData) {
                        $optionText = $optionData['text'];
                        $isCorrect = isset($optionData['correct']) ? $optionData['correct'] : false;

                        // Insert each multiple choice option into the database
                        Yii::$app->db->createCommand()->insert('training_multiple_choice_answers', [
                            'question_id' => $questionId,
                            'option_text' => $optionText,
                            'is_correct' => $isCorrect,
                            'is_active' => true
                        ])->execute();
                    }
                }
            }

            // If a video was uploaded, update the training record with new url
            if ($fileUrl) {
                Yii::$app->db->createCommand()->update('training', [
                    'initial_file_url' => $fileUrl
                ], ['id' => $trainingId])->execute();
            } elseif ($loadVid === false || $loadVid === 'false' || $loadVid === null) {
                Yii::$app->db->createCommand()->update('training', [
                    'initial_file_url' => null
                ], ['id' => $trainingId])->execute();
            }

            // Commit the transaction
            $transaction->commit();
            return ['success' => true];

        } catch (\Exception $e) {
            // Roll back the transaction in case of any errors
            $transaction->rollBack();
            return ['success' => false, 'errors' => $e->getMessage()];
        }
    }

    /**
     * Displays the questions from the database in the form for the User at employee.php.
     *
     * This function generates and returns the HTML for displaying the questions,
     * based on the question type (text, number, range, multiple choice) for the user to answer.
     *
     * @param string $training_id The ID of the training.
     * @return array JSON response with the generated HTML content.
     */
    public function actionDisplayQuestions($training_id)
    {
        // Set response format to JSON
        Yii::$app->response->format = Response::FORMAT_JSON;

        $training = Yii::$app->db->createCommand('SELECT * FROM training WHERE id = :id')
            ->bindValue(':id', $training_id)
            ->queryOne();

        // Fetch questions for the given training_id, ordered by the 'order' column
        $questions = Yii::$app->db->createCommand('
                SELECT * FROM training_questions 
                WHERE training_id = :training_id
                AND is_active = 1
                ORDER BY `id`
            ')
            ->bindValue(':training_id', $training_id)
            ->queryAll();

        // Initialize the html string vubec jsem tohle neznal
        $html = '';

        // Check if there are any questions retrieved from the database
        if ($questions) {
            // Loop through each question and generate the corresponding HTML
            foreach ($questions as $index => $question) {
                $html .= '<div class="question-item">';
                $html .= '<div class="form-group">';
                // Display the question text
                $html .= '<p class="question-employee"><b>' . Html::encode($question['question']) . '</b></p>';

                // If the question has associated image, display it
                if (!empty($question['image_url'])) {
                    $html .= '<div class="question-image">';
                    $html .= Html::img(
                        Yii::$app->request->baseUrl . '/' . Html::encode($question['image_url']),
                        [
                            'class' => 'question-image',
                            'style' => 'max-height: 16vh; width: auto; height: 100%; max-width:100%; display:block;'
                        ]
                    );
                    $html .= '</div>';
                    $html .= '<br>';
                }

                // Handle the input fields based on the question type
                switch ($question['type']) {
                    case 'text':
                        // Generate a text input field for text-based questions
                        $html .= Html::input('text', "TrainingQuestions[$index][answer]", '', [
                            'class' => 'form-control question-input',
                            'placeholder' => 'Enter your answer here',
                            'data-question-id' => $question['id'],
                            'data-question-text' => $question['question'],
                            'data-question-type' => $question['type']
                        ]);
                        break;
                    case 'number':
                        // Generate a number input field for numeric questions
                        $html .= Html::input('number', "TrainingQuestions[$index][answer]", '', [
                            'class' => 'form-control question-input number-input',
                            'min' => '1',
                            'max' => '5',
                            'placeholder' => '1-5',
                            'style' => 'width: 60px;',
                            'data-question-id' => $question['id'],
                            'data-question-text' => $question['question'],
                            'data-question-type' => $question['type']
                        ]);
                        break;
                    case 'range':
                        // Generate a range input field with labels for range-based questions
                        $html .= '<div class="range-container">';
                        $html .= '<span>Not much</span>';
                        $html .= Html::input('range', "TrainingQuestions[$index][answer]", '50', [
                            'class' => 'form-control question-input',
                            'min' => '1',
                            'max' => '100',
                            'data-question-id' => $question['id'],
                            'data-question-text' => $question['question'],
                            'data-question-type' => $question['type']
                        ]);
                        $html .= '<span>Very much</span>';
                        $html .= '</div>';
                        break;
                    case 'multiple_choice':
                        // Fetch the multiple-choice options associated with the question
                        $options = Yii::$app->db->createCommand('
                                SELECT * FROM training_multiple_choice_answers 
                                WHERE question_id = :question_id AND is_active = 1
                            ')
                            ->bindValue(':question_id', $question['id'])
                            ->queryAll();

                        // Generate checkboxes for each multiple-choice option
                        $html .= '<div class="multiple-choice-options">';
                        foreach ($options as $option) {
                            $html .= Html::checkbox("TrainingQuestions[$index][answer][]", false, [
                                'label' => Html::encode($option['option_text']),
                                'value' => $option['id'],
                                'class' => 'multiple-choice-option',
                                'data-question-id' => $question['id'],
                                'data-question-text' => $question['question'],
                                'data-question-type' => $question['type']
                            ]);
                        }
                        $html .= '</div>';
                        break;
                }
                $html .= '</div>';
                $html .= '</div>';
            }
        }

        $html .= '</div>';

        // Return a JSON response with a success status and the generated HTML content
        return ['success' => true, 'html' => $html];
    }

    /**
     * Updates the deadline for a specific training.
     *
     * This function allows the Admin to update the deadline for completing a training.
     *
     * @return array JSON response indicating success or failure.
     */
    public function actionUpdateDeadline()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Check if the response method is POST
        if (Yii::$app->request->isPost) {
            // Get the training ID and the new deadline from the POST request            
            $id = Yii::$app->request->post('id');
            $deadline = Yii::$app->request->post('deadline');

            // Prepare the SQL command to update the deadline for the specified training ID
            $command = Yii::$app->db->createCommand()
                ->update('training', ['deadline_for_completion' => $deadline], 'id = :id', [':id' => $id]);

            // Execute the command and get the number of affected rows
            $affectedRows = $command->execute();

            // Check if any rows were affected, or if the deadline value was the same
            if ($affectedRows > 0 || $this->isDeadlineSame($id, $deadline)) {
                return ['success' => true];
            } else {
                return ['success' => false];
            }
        }

        return ['success' => false];
    }

    /**
     * Helper function to check if the deadline is the same as the current one.
     *
     * This function compares the new deadline with the existing deadline in the database.
     *
     * @param string $id The ID of the training.
     * @param string $deadline The new deadline to compare.
     * @return bool True if the deadlines are the same, false otherwise.
     */
    private function isDeadlineSame($id, $deadline)
    {
        // Fetch the current deadline for the specified training ID from the database
        $currentDeadline = Yii::$app->db->createCommand('SELECT deadline_for_completion FROM training WHERE id = :id')
            ->bindValue(':id', $id)
            ->queryScalar();

        // Compare the current deadline with the new deadline and return true if they are the same, otherwise false
        return $currentDeadline == $deadline;
    }

    /**
     * Removes the video file associated with a training.
     *
     * This function deletes the video file URL from the database record of a training.
     *
     * @return array JSON response indicating success or failure.
     */
    public function actionRemoveInitialFile()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (Yii::$app->request->isPost) {
            $deleteVid = Yii::$app->request->post('deleteVid');
            $trainingId = Yii::$app->request->post('trainingId');
            if ($deleteVid == true) {
                // Remove the file URL from the training record
                Yii::$app->db->createCommand()->update('training', [
                    'initial_file_url' => null
                ], ['id' => $trainingId])->execute();

                return ['success' => true];
            }
        }

        return ['success' => false];
    }
}
