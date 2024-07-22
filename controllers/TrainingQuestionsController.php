<?php

namespace humhub\modules\employeeTraining\controllers;

use Yii;
use humhub\components\Controller;
use yii\web\Response;
use yii\helpers\Html;


class TrainingQuestionsController extends Controller
{
    // Function for displaying the questions page
    public function actionQuestions()
    {

        // Get the current user
        $currentUser = Yii::$app->user;

        // Check if the logged in user is admin
        if (!$currentUser->isAdmin()) {
            // Redirect to acces denied if the user isn't admin
            return $this->redirect(['site/access-denied']);
        }

        // Fetch distinct titles from the profile table
        $titles = Yii::$app->db->createCommand('SELECT DISTINCT title FROM profile')->queryColumn();
        // Sort the titles alphabetically
        sort($titles);

        // Render the questions view for admin with the fetched titles
        return $this->render('/admin/questions', [
            'titles' => $titles,
        ]);
    }

    // Function for retrieving the questions from database and displaying them for ADMIN
    public function actionFetchQuestions($title)
    {
        // Set the response format to JSON
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Fetch questions for the given table, ordered by the 'order' column
        $questions = Yii::$app->db->createCommand('SELECT * FROM training_questions WHERE title = :title ORDER BY `order`')
            ->bindValue(':title', $title)
            ->queryAll();

        // Initialize HTML string
        $html = '';
        if ($questions) {
            // Iterate over each question and adding strings to the HTML variable
            foreach ($questions as $index => $question) {
                $html .= '<div class="question-item">';
                $html .= '<label>Question ' . ($index + 1) . '</label>';
                $html .= '<div class="form-group">';
                $html .= Html::dropDownList("TrainingQuestions[$index][type]", 'text', ['text' => 'Text', 'number' => 'Number', 'range' => 'Range'], ['class' => 'form-control question-type']);
                $html .= '</div>';
                $html .= '<div class="form-group">';
                $html .= Html::textInput("TrainingQuestions[$index][question]", $question['question'], ['class' => 'form-control question-text', 'placeholder' => 'Enter your question here']);
                $html .= '</div>';
                $html .= '</div>';
            }
            // Return success response with generated HTML
            return ['success' => true, 'html' => $html];
        } else {
            // Return failure response if no questions found
            return ['success' => false];
        }
    }
    // Function for saving questions into database by admin
    // TODO adjust the size of the dropboxes
    public function actionSaveQuestions()
    {
        // Set response format to JSON
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Get posted title and questions data
        $selectedTitle = Yii::$app->request->post('title', '');
        $questions = Yii::$app->request->post('TrainingQuestions', []);

        // Return failure response if no title is provided
        if (empty($selectedTitle)) {
            return ['success' => false, 'errors' => 'Title is required'];
        }

        // Start a database transaction
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Delete existing questions for the selected title
            Yii::$app->db->createCommand()->delete('training_questions', ['title' => $selectedTitle])->execute();

            // Fetch all existing IDs
            $existingIds = Yii::$app->db->createCommand('SELECT id FROM training_questions')->queryColumn();
            $nextId = 1;
            $usedIds = [];

            // Iterate over each question and generate HTML for display
            foreach ($questions as $index => $questionData) {

                // Find the lowest available ID
                while (in_array($nextId, $existingIds) || in_array($nextId, $usedIds)) {
                    $nextId++;
                }
                $usedIds[] = $nextId;

                // Validate question data
                $type = $questionData['type'];
                $question = $questionData['question'];
                if ($type == 'number') {
                    if (!is_numeric($question) || $question < 1 || $question > 5) {
                        return ['success' => false, 'errors' => 'Number questions must be between 1 and 5.'];
                    }
                }

                // Insert each question into the database
                Yii::$app->db->createCommand()->insert('training_questions', [
                    'id' => $nextId,
                    'title' => $selectedTitle,
                    'type' => $questionData['type'],
                    'question' => $questionData['question'],
                    'order' => $index + 1,
                ])->execute();
            }
            // Commit the transaction
            $transaction->commit();
            // Return success response
            return ['success' => true];

        } catch (\Exception $e) {
            // Rollback the transaction in case of an error (erase all modifications made from the start of the transaction)
            $transaction->rollBack();
            // Return failure response with error message
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
}
