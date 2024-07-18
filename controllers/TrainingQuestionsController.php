<?php

namespace humhub\modules\employeeTraining\controllers;

use Yii;
use humhub\components\Controller;
use yii\web\Response;
use yii\helpers\Html;

class TrainingQuestionsController extends Controller
{
    public function actionQuestions()
    {
        $currentUser = Yii::$app->user;

        if (!$currentUser->isAdmin()) {
            return $this->redirect(['site/access-denied']);
        }

        $titles = Yii::$app->db->createCommand('SELECT DISTINCT title FROM profile')->queryColumn();
        sort($titles);

        return $this->render('/admin/questions', [
            'titles' => $titles,
        ]);
    }

    public function actionFetchQuestions($title)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $questions = Yii::$app->db->createCommand('SELECT * FROM training_questions WHERE title = :title ORDER BY `order`')
            ->bindValue(':title', $title)
            ->queryAll();

        if ($questions) {
            foreach ($questions as $question) {
                Yii::info('Question: ' . $question['question']);
            }
        } else {
            Yii::info('No questions found for the title: ' . $title);
        }

        $html = '';
        if ($questions) {
            foreach ($questions as $index => $question) {
                Yii::info('Question ' . ($index + 1) . ': ' . json_encode($question)); // Log the question details
                $html .= '<div class="question-item">';
                $html .= '<label>Question ' . ($index + 1) . '</label>';
                $html .= '<div class="form-group">';
                $html .= Html::dropDownList("TrainingQuestions[$index][type]", $question['type'], ['text' => 'Text', 'number' => 'Number', 'range' => 'Range'], ['prompt' => 'Select Type', 'class' => 'form-control question-type']);
                $html .= '</div>';
                $html .= '<div class="form-group">';
                $html .= Html::textInput("TrainingQuestions[$index][question]", $question['question'], ['class' => 'form-control question-text', 'placeholder' => 'Enter your question here']);
                $html .= '</div>';
                $html .= '</div>';
            }
            return ['success' => true, 'html' => $html];
        } else {
            return ['success' => false];
        }
    }

    public function actionSaveQuestions()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $selectedTitle = Yii::$app->request->post('title', '');
        $questions = Yii::$app->request->post('TrainingQuestions', []);

        if (empty($selectedTitle)) {
            return ['success' => false, 'errors' => 'Title is required'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand()->delete('training_questions', ['title' => $selectedTitle])->execute();
            Yii::$app->db->createCommand("ALTER TABLE training_questions AUTO_INCREMENT = 1")->execute();

            foreach ($questions as $index => $questionData) {
                Yii::$app->db->createCommand()->insert('training_questions', [
                    'title' => $selectedTitle,
                    'type' => $questionData['type'],
                    'question' => $questionData['question'],
                    'order' => $index + 1,
                ])->execute();
            }

            $transaction->commit();
            return ['success' => true];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'errors' => $e->getMessage()];
        }
    }
}
