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

        // Check if the logged in user is admin
        if (!$currentUser->isAdmin()) {
            // Redirect to acces denied if the user isn't admin
            return $this->redirect(['site/access-denied']);
        }

        $trainingName = Yii::$app->db->createCommand('SELECT name FROM training WHERE id=:id')
            ->bindValue(':id', $id)
            ->queryScalar(); // Use the prepared query

        $deadline = Yii::$app->db->createCommand('SELECT deadline_for_completion FROM training WHERE id =:id')
            ->bindValue(':id', $id)
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
        ]);
    }

    // Function for retrieving the questions from database and displaying them for ADMIN
    public function actionFetchQuestions($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
    
        $questions = Yii::$app->db->createCommand('SELECT * FROM training_questions WHERE training_id = :id ORDER BY `order`')
            ->bindValue(':id', $id)
            ->queryAll();
    
        $html = '';
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
                    $options = Yii::$app->db->createCommand('SELECT * FROM training_multiple_choice_answers WHERE question_id = :question_id')
                        ->bindValue(':question_id', $question['id'])
                        ->queryAll();
    
                    $html .= '<div class="form-group multiple-choice-options">';
                    foreach ($options as $optionIndex => $option) {
                        $html .= '<div class="input-group" style="display: flex; align-items: center;">';
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
    // TODO adjust the size of the dropboxes
// Function for saving questions into database by admin
    public function actionSaveQuestions()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $trainingId = Yii::$app->request->post('trainingId');
        $questions = Yii::$app->request->post('TrainingQuestions', []);
        $files = UploadedFile::getInstancesByName('TrainingQuestions');

        if (empty($trainingId)) {
            return ['success' => false, 'errors' => 'Training ID is required'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand()->delete('training_questions', ['training_id' => $trainingId])->execute();
            Yii::$app->db->createCommand()->delete('training_multiple_choice_answers', ['question_id' => $trainingId])->execute();

            $existingIds = Yii::$app->db->createCommand('SELECT id FROM training_questions')->queryColumn();
            $nextId = 1;
            $usedIds = [];

            foreach ($questions as $index => $questionData) {
                while (in_array($nextId, $existingIds) || in_array($nextId, $usedIds)) {
                    $nextId++;
                }
                $usedIds[] = $nextId;

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

                Yii::$app->db->createCommand()->insert('training_questions', [
                    'id' => $nextId,
                    'training_id' => $trainingId,
                    'type' => $questionData['type'],
                    'question' => $questionData['question'],
                    'image_url' => $imageUrl,
                    'order' => $index + 1,
                ])->execute();

                if ($questionData['type'] == 'multiple_choice') {
                    foreach ($questionData['options'] as $optionIndex => $optionData) {
                        Yii::$app->db->createCommand()->insert('training_multiple_choice_answers', [
                            'question_id' => $nextId,
                            'option_text' => $optionData['text'],
                            'is_correct' => isset($optionData['correct']) ? $optionData['correct'] : false,
                        ])->execute();
                    }
                }
            }

            $transaction->commit();
            return ['success' => true];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'errors' => $e->getMessage()];
        }
    }

    // Function for displaying the questions from database in the form for the USER
    public function actionDisplayQuestions($title)
    {
        // Set response format to JSON
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Fetch question for the given title, ordered by the 'order' column
        $questions = Yii::$app->db->createCommand('SELECT * FROM training_questions WHERE title = :title ORDER BY `order`')
            ->bindValue(':title', $title)
            ->queryAll();

        // Initialize HTML string
        $html = '';
        if ($questions) {
            // Iterate over each question and adding strings to the HTML variable
            foreach ($questions as $index => $question) {
                $html .= '<div class="question-item">';
                $html .= '<div class="form-group">';
                $html .= '<p class="question-employee"><b>' . Html::encode($question['question']) . '</b></p>';

                // Use a switch statement to handle different question types
                switch ($question['type']) {
                    case 'text':
                        $html .= Html::input('text', "TrainingQuestions[$index][answer]", '', ['class' => 'form-control question-input', 'placeholder' => 'Enter your answer here']);
                        break;
                    case 'number':
                        $html .= Html::input('number', "TrainingQuestions[$index][answer]", '', ['class' => 'form-control question-input number-input', 'min' => '1', 'max' => '5', 'placeholder' => '1-5', 'style' => 'width: 60px;']);
                        break;
                    case 'range':
                        $html .= '<div class="range-container">';
                        $html .= '<span>Not much</span>';
                        $html .= Html::input('range', "TrainingQuestions[$index][answer]", '50', ['class' => 'form-control question-input', 'min' => '1', 'max' => '100']);
                        $html .= '<span>Very much</span>';
                        $html .= '</div>';
                        break;
                }
                $html .= '</div>';
                $html .= '</div>';
            }

            // Add JavaScript to handle number input constraints
            $html .= '<script>';
            $html .= '$(document).ready(function() {';
            $html .= '$(".form-control[type=\"number\"]").on("input", function() {
                var value = $(this).val();
                if (value < 1) {
                    $(this).val(1);
                } else if (value > 5) {
                    $(this).val(5);
                }
            });';
            $html .= '$(".form-control[type=\"number\"]").on("keypress", function(e) {
                if (e.which < 48 || e.which > 57) {
                    e.preventDefault();
                }
            });';
            $html .= '});';
            $html .= '</script>';

            // Return success response with generated HTML
            return ['success' => true, 'html' => $html];
        } else {
            // Return failure response if no question found
            return ['success' => false];
        }
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

            if ($command->execute()) {
                return ['success' => true];
            } else {
                return ['success' => false];
            }
        }

        return ['success' => false];
    }
}
