<?php

namespace humhub\modules\employeeTraining\controllers;

use Yii;
use humhub\components\Controller;
use yii\web\Response;
use yii\helpers\Html;
use yii\web\UploadedFile;
use yii\helpers\Url;



class TrainingQuestionsController extends Controller
{
    // Function for displaying the questions page
    public function actionQuestions($id)
    {
        // Get the current user
        $currentUser = Yii::$app->user;

        $userRole = $currentUser->identity->profile->role;

        Yii::info("Userova role: " . $userRole);

        // Check if the logged in user is admin
        if ($userRole !== 'admin' && $userRole !== 'team_leader') {
            // Redirect to acces denied if the user isn't admin
            return $this->redirect(['site/access-denied']);
        }

        $trainingName = Yii::$app->db->createCommand('SELECT name FROM training WHERE id=:id')
            ->bindValue(':id', $id)
            ->queryScalar(); // Use the prepared query

        $deadline = Yii::$app->db->createCommand('SELECT deadline_for_completion FROM training WHERE id =:id')
            ->bindValue(':id', $id)
            ->queryScalar();

        $assignedUsersCount = Yii::$app->db->createCommand('SELECT COUNT(*) FROM user_training WHERE assigned_training = 1 AND training_id =:training_id')
            ->bindValue(':training_id', $id)
            ->queryScalar();

        // Fetch distinct titles from the profile table
        $titles = Yii::$app->db->createCommand('SELECT DISTINCT title FROM profile')->queryColumn();
        // Sort the titles alphabetically
        sort($titles);

        // Render the questions view for admin with the fetched titles
        return $this->render('/admin/questions', [
            'titles' => $titles,
            'trainingId' => $id,
            'trainingName' => $trainingName,
            'deadlineForCompletion' => $deadline,
            'assignedUsersCount' => $assignedUsersCount,
            'userRole' => $userRole,
        ]);
    }

    // Function for retrieving the questions from database and displaying them for ADMIN
    public function actionFetchQuestions($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $training = Yii::$app->db->createCommand('SELECT * FROM training WHERE id = :id')
            ->bindValue(':id', $id)
            ->queryOne();

        $questions = Yii::$app->db->createCommand('SELECT * FROM training_questions WHERE training_id = :id AND is_active = 1 ORDER BY `id`')
            ->bindValue(':id', $id)
            ->queryAll();

        $html = '';


        // Display video upload field
        if (!empty($training['video_url'])) {
            $html .= '<div class="form-group">';
            $html .= '<label for="existing-video">' . Yii::t('employeeTraining', 'Existing Training Video') . '</label>';
            $html .= '<video width="320" height="240" controls>';
            $html .= '<source src="' . Url::to('@web/' . $training['video_url']) . '" type="video/mp4">';
            $html .= 'Your browser does not support the video tag.';
            $html .= '</video>';
            $html .= '</div>';
        }

        if ($questions) {
            foreach ($questions as $index => $question) {
                $html .= '<div class="question-item">';
                $html .= '<label>Question ' . ($index + 1) . '</label>';
                $html .= '<div class="form-group">';
                $html .= Html::dropDownList("TrainingQuestions[$index][type]", $question['type'], ['text' => 'Text', 'number' => 'Number', 'range' => 'Range', 'multiple_choice' => 'Multiple Choice'], ['class' => 'form-control question-type']);
                $html .= '</div>';
                $html .= '<div class="form-group">';
                $html .= Html::textInput("TrainingQuestions[$index][question]", $question['question'], ['class' => 'form-control question-text', 'placeholder' => 'Enter your question here']);
                $html .= '</div>';

                if ($question['type'] == 'multiple_choice') {
                    $html .= '<div class="form-group multiple-choice-container">';
                    $options = Yii::$app->db->createCommand('SELECT * FROM training_multiple_choice_answers WHERE question_id = :question_id AND is_active = 1')
                        ->bindValue(':question_id', $question['id'])
                        ->queryAll();

                    $html .= '<div class="form-group multiple-choice-options">';
                    foreach ($options as $optionIndex => $option) {
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
                    $html .= '<div class="form-group">';
                    $html .= '<button type="button" class="btn btn-secondary add-option-btn">+ Add Option</button>';
                    $html .= '<button type="button" class="btn btn-danger remove-option-btn">- Remove Option</button>';
                    $html .= '</div>';
                    $html .= '</div>';
                }

                if ($question['type'] == 'text') {
                    $html .= '<div class="form-group" style="display: flex; align-items: center;">';
                    $html .= '<p style="margin-right: 7px; padding-top:10px; font-weight:bold;">Correct answer:</p>';
                    $html .= Html::textInput("TrainingQuestions[$index][correct_answer]", $question['correct_answer'], ['class' => 'form-control correct-answer', 'placeholder' => 'Enter the correct answer here', 'style' => 'flex: 1;']);
                    $html .= '</div>';
                }


                $html .= '<div class="form-group">';
                if ($question['image_url']) {
                    $html .= Html::img(Url::to('@web/' . $question['image_url']), ['alt' => 'Image', 'style' => 'max-width: 200px; max-height: 200px;']);
                    $html .= Html::hiddenInput("TrainingQuestions[$index][existing_image]", $question['image_url']);
                    $html .= Html::button('Remove image', ['class' => 'btn btn-danger remove-image-btn', 'data-index' => $index, 'style' => 'display: block;']);
                    $html .= Html::hiddenInput("TrainingQuestions[$index][remove_image]", 0, ['class' => 'remove-image-input']);
                }
                $html .= '<input type="file" name="TrainingQuestions[' . $index . '][image]" class="form-control question-image"' . ($question['image_url'] ? ' style="display:none;"' : '') . '>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<br>';
            }
            return ['success' => true, 'html' => $html];
        } else {
            return ['success' => false];
        }
    }

    // Function for saving questions into database by admin
    public function actionSaveQuestions()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $trainingId = Yii::$app->request->post('trainingId');
        $questions = Yii::$app->request->post('TrainingQuestions', []);
        $files = UploadedFile::getInstancesByName('TrainingQuestions');
        $videoFile = UploadedFile::getInstanceByName('trainingVideo');

        if (empty($trainingId)) {
            return ['success' => false, 'errors' => 'Training ID is required'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Mark existing questions as inactive
            Yii::$app->db->createCommand()->update('training_questions', [
                'is_active' => false
            ], ['training_id' => $trainingId])->execute();

            // Fetch the question IDs for the given training
            $questionIds = Yii::$app->db->createCommand('
                SELECT id FROM training_questions WHERE training_id = :trainingId
            ')
                ->bindValue(':trainingId', $trainingId)
                ->queryColumn();

            // Mark existing multiple choice answers as inactive
            if (!empty($questionIds)) {
                Yii::$app->db->createCommand()->update('training_multiple_choice_answers', [
                    'is_active' => false
                ], ['question_id' => $questionIds])->execute();
            }

            $newQuestionIds = [];

            $videoUrl = null;
            if ($videoFile) {
                $videoPath = 'uploads/' . $videoFile->baseName . '.' . $videoFile->extension;
                if ($videoFile->saveAs($videoPath)) {
                    $videoUrl = $videoPath;
                    // Save the video URL to the database
                    Yii::$app->db->createCommand()->update('training', [
                        'video_url' => $videoUrl
                    ], ['id' => $trainingId])->execute();
                } else {
                    return ['success' => false, 'errors' => 'Failed to save the video file.'];
                }
            }


            // Process each question
            foreach ($questions as $index => $questionData) {
                $questionText = $questionData['question'];
                $correctAnswer = isset($questionData['correct_answer']) ? $questionData['correct_answer'] : null;
                $questionType = $questionData['type'];

                // Handle image upload
                $imageFile = UploadedFile::getInstanceByName('TrainingQuestions[' . $index . '][image]');
                $imageUrl = isset($questionData['existing_image']) ? $questionData['existing_image'] : null;
                if ($imageFile) {
                    $imagePath = 'uploads/' . $imageFile->baseName . '.' . $imageFile->extension;
                    if ($imageFile->saveAs($imagePath)) {
                        $imageUrl = $imagePath;
                    } else {
                        return ['success' => false, 'errors' => 'Failed to save the image file.'];
                    }
                }

                // Insert new question
                Yii::$app->db->createCommand()->insert('training_questions', [
                    'training_id' => $trainingId,
                    'type' => $questionType,
                    'question' => $questionText,
                    'image_url' => $imageUrl,
                    'correct_answer' => $correctAnswer,
                    'is_active' => true
                ])->execute();
                $questionId = Yii::$app->db->getLastInsertID();
                $newQuestionIds[] = $questionId;

                if ($questionType == 'multiple_choice') {
                    foreach ($questionData['options'] as $optionIndex => $optionData) {
                        $optionText = $optionData['text'];
                        $isCorrect = isset($optionData['correct']) ? $optionData['correct'] : false;

                        // Insert new option
                        Yii::$app->db->createCommand()->insert('training_multiple_choice_answers', [
                            'question_id' => $questionId,
                            'option_text' => $optionText,
                            'is_correct' => $isCorrect,
                            'is_active' => true
                        ])->execute();
                    }
                }
            }

            if ($videoUrl) {
                Yii::warning('Je tady videooooo');
                Yii::$app->db->createCommand()->update('training', [
                    'video_url' => $videoUrl
                ], ['id' => $trainingId])->execute();
            }

            $transaction->commit();
            return ['success' => true];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'errors' => $e->getMessage()];
        }
    }

    // Function for displaying the questions from database in the form for the USER
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

        // Initialize HTML string
        $html = '';

        if ($questions) {
            foreach ($questions as $index => $question) {
                $html .= '<div class="question-item">';
                $html .= '<div class="form-group">';
                $html .= '<p class="question-employee"><b>' . Html::encode($question['question']) . '</b></p>';

                // Display image if available
                if (!empty($question['image_url'])) {
                    $html .= '<div class="question-image">';
                    $html .= Html::img(
                        Yii::$app->request->baseUrl . '/' . Html::encode($question['image_url']),
                        [
                            'class' => 'question-image',
                            'style' => 'max-height: 280px; width: auto; height: auto; display:block;'
                        ]
                    );
                    $html .= '</div>';
                    $html .= '<br>';
                }
                // Handle different question types
                switch ($question['type']) {
                    case 'text':
                        $html .= Html::input('text', "TrainingQuestions[$index][answer]", '', [
                            'class' => 'form-control question-input',
                            'placeholder' => 'Enter your answer here',
                            'data-question-id' => $question['id'],
                            'data-question-text' => $question['question'],
                            'data-question-type' => $question['type']
                        ]);
                        break;
                    case 'number':
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
                        // Fetch multiple choice options for the question
                        $options = Yii::$app->db->createCommand('
                                SELECT * FROM training_multiple_choice_answers 
                                WHERE question_id = :question_id AND is_active = 1
                            ')
                            ->bindValue(':question_id', $question['id'])
                            ->queryAll();

                        // Add checkboxes for each option
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

        // Return success response with generated HTML
        return ['success' => true, 'html' => $html];
    }



    // Function for updating the deadline
    public function actionUpdateDeadline()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (Yii::$app->request->isPost) {
            $id = Yii::$app->request->post('id');
            $deadline = Yii::$app->request->post('deadline');

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

    // Helper function to check if the deadline is the same, because I wasn't able to save the new date if it was the same as the previous one
    private function isDeadlineSame($id, $deadline)
    {
        $currentDeadline = Yii::$app->db->createCommand('SELECT deadline_for_completion FROM training WHERE id = :id')
            ->bindValue(':id', $id)
            ->queryScalar();

        return $currentDeadline == $deadline;
    }
}
