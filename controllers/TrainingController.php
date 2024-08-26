<?php

namespace humhub\modules\employeeTraining\controllers;

use Yii;
use humhub\components\Controller;
use yii\web\Response;
use yii\helpers\Html;
use yii\web\UploadedFile;
use humhub\modules\user\models\Profile;
use humhub\modules\user\models\User;
use yii\web\BadRequestHttpException;
use yii\helpers\Url;

/**
 * Controller for handling training questions within the Employee Training module.
 */
class TrainingController extends Controller
{
    /**
     * Handles the AJAX request to toggle the training assignment for a user.
     *
     * @return array The success or failure status of the operation.
     */
    public function actionToggleTraining()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $userIds = Yii::$app->request->post('user_ids', []);
        $trainingId = Yii::$app->request->post('training_id');
        $assignedTraining = Yii::$app->request->post('assigned_training');
        $trainingAssignedTime = Yii::$app->request->post('training_assigned_time');

        date_default_timezone_set('Europe/Berlin');

        $trainingAssignedTime = date('Y-m-d H:i:s', strtotime($trainingAssignedTime));

        // Fetch the training's deadline for completion.
        $training = Yii::$app->db->createCommand('
            SELECT deadline_for_completion 
            FROM training 
            WHERE id = :training_id
        ')
            ->bindValue(':training_id', $trainingId)
            ->queryOne();

        $deadlineForCompletion = $training ? $training['deadline_for_completion'] : null;

        $successCount = 0;

        // Loop through each user ID to process the training assignment.
        foreach (array_unique($userIds) as $usersId) {
            // Check if there is any record with assigned_training = 1
            $existingRecord = Yii::$app->db->createCommand(' 
                SELECT * 
                FROM user_training 
                WHERE user_id = :user_id AND training_id = :training_id AND assigned_training = 1
            ')
                ->bindValue(':user_id', $usersId)
                ->bindValue(':training_id', $trainingId)
                ->queryScalar();

            // Calculate the deadline for the training completion.
            if ($deadlineForCompletion && $trainingAssignedTime) {
                $deadline = date('Y-m-d H:i:s', strtotime($trainingAssignedTime . ' + ' . $deadlineForCompletion . ' days'));
            } else {
                $deadline = null;
            }

            // Insert the new training assignment if it doesn't already exist.            
            if (!$existingRecord) {
                Yii::$app->db->createCommand()
                    ->insert(
                        'user_training',
                        [
                            'user_id' => $usersId,
                            'training_id' => $trainingId,
                            'assigned_training' => $assignedTraining,
                            'training_assigned_time' => $trainingAssignedTime,
                            'deadline' => $deadline,
                        ]
                    )
                    ->execute();
                $successCount++;
                $user = User::findOne($usersId);
            }
        }

        if ($successCount > 0) {
            Yii::$app->db->createCommand()
                ->update(
                    'training',
                    ['assigned_users_count' => new \yii\db\Expression('(SELECT COUNT(*) FROM user_training WHERE training_id = :training_id AND assigned_training = 1)')],
                    ['id' => $trainingId]
                )
                ->bindValue(':training_id', $trainingId)
                ->execute();
        }

        if ($successCount > 0) {
            return ['success' => true, 'message' => "$successCount users assigned to training."];
        }

        return ['success' => false, 'message' => "No users assigned to training."];
    }

    /**
     * Removes training assignments from users.
     *
     * @return array The success or failure status of the operation.
     */
    public function actionRemoveTraining()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Retreive the array of user Ids and the training ID from the request
        $userIds = Yii::$app->request->post('user_ids', []);
        $trainingId = Yii::$app->request->post('training_id');

        $successCount = 0;

        // Loop through each user ID to remove the training assignment
        foreach (array_unique($userIds) as $userId) {
            // Delete the training assignment record for the user
            $result = Yii::$app->db->createCommand()
                ->delete('user_training', [
                    'user_id' => $userId,
                    'training_id' => $trainingId,
                    'assigned_training' => 1,
                ])
                ->execute();

            // If the deletion was successfil, increment the success count
            if ($result) {
                $successCount++;
            }
        }

        // If any training assignments were successfully removed
        if ($successCount > 0) {
            // Update the training's assigned_users_count to reflect the actual count of remaining assignments
            Yii::$app->db->createCommand()
                ->update(
                    'training',
                    ['assigned_users_count' => new \yii\db\Expression('(SELECT COUNT(*) FROM user_training WHERE training_id = :training_id AND assigned_training = 1)')],
                    ['id' => $trainingId]
                )
                ->bindValue(':training_id', $trainingId)
                ->execute();
        }

        // Return a success response if users were successfully unassigned
        if ($successCount > 0) {
            return ['success' => true, 'message' => "$successCount users unassigned from training."];
        }

        // Return a failure response if no users were unassigned
        return ['success' => false, 'message' => "No users unassigned from training."];
    }

    /**
     * Marks the training as complete for the current user.
     *
     * @return array The success or failure status of the operation.
     */
    public function actionCompleteTraining()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $userId = Yii::$app->user->id;
        $requestData = Yii::$app->request->post();

        // Extract the training ID from the requested data
        $trainingId = $requestData['training_id'];

        // Fetch the user_training_id for the given user and training, only if the training is assigned
        $userTrainingId = Yii::$app->db->createCommand('
            SELECT id 
            FROM user_training 
            WHERE user_id = :user_id AND training_id = :training_id AND assigned_training = 1
        ')
            ->bindValue(':user_id', $userId)
            ->bindValue(':training_id', $trainingId)
            ->queryScalar();

        // Check if there are any questions answered in the request data
        if (isset($requestData['TrainingQuestions'])) {
            // Loop through each question in the TrainingQuestions array
            foreach ($requestData['TrainingQuestions'] as $questionKey => $questionData) {
                // Extract question details
                $questionId = $questionData['question_id'];
                $questionText = $questionData['question'];
                $questionType = $questionData['question_type'];
                $answer = $questionData['answer'] ?? []; // Default to an empty array if no answer is provided

                // Insert the answer int the training_answers table
                Yii::$app->db->createCommand()
                    ->insert(
                        'training_answers',
                        [
                            'user_id' => $userId,
                            'question_id' => $questionId,
                            'answer' => $questionType === 'multiple_choice' ? "multiple_choice" : $answer,
                            'created_at' => new \yii\db\Expression('NOW()'),
                            'user_training_id' => $userTrainingId, // Save user_training_id
                        ]
                    )
                    ->execute();

                // Get the ID of the inserted answer record
                $answerId = Yii::$app->db->getLastInsertID();

                // If the question is of type 'multiple_choice' and there are answers provided
                if ($questionType === 'multiple_choice' && !empty($answer)) {
                    // Loop through each selected answer
                    foreach ($answer as $individualAnswer) {
                        Yii::$app->db->createCommand()
                            ->insert(
                                'training_multiple_choice_user_answers',
                                [
                                    'user_id' => $userId,
                                    'question_id' => $questionId,
                                    'multiple_choice_answer_id' => $individualAnswer,
                                    'answer_id' => $answerId,
                                ]
                            )
                            ->execute();
                    }
                }
            }
        } else {
            Yii::warning("TrainingQuestions is not set", __METHOD__);
        }

        // Update the assigned_training attribute to 0 (mark the assigned_training as completed for this user)
        $result = Yii::$app->db->createCommand()
            ->update(
                'user_training',
                ['assigned_training' => 0],
                [
                    'user_id' => $userId,
                    'training_id' => $trainingId,
                ]
            )
            ->execute();

        // Return a success response
        return ['success' => true];
    }

    /**
     * Creates a new training record in the database.
     *
     * @return array The success or failure status of the operation.
     * @throws BadRequestHttpException If the request method is not POST.
     */
    public function actionCreateTraining()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $request = \Yii::$app->request;

        // Check if the request is POST request
        if ($request->isPost) {
            // Decode the raw JSON body into an associative array
            $data = json_decode($request->getRawBody(), true);

            // Extract the training ID and name from the request data
            $trainingId = $data['id'];
            $trainingName = $data['name'];

            // Prepare an SQL command to insert the new training into the database
            $command = Yii::$app->db->createCommand()->insert('training', [
                'id' => $trainingId,
                'name' => $trainingName,
                'created_at' => new \yii\db\Expression('NOW()'),
                'assigned_users_count' => 0,
            ]);

            // Exexute the command and check if the insert was successful
            if ($command->execute()) {
                // Return successful response
                return ['success' => true];
            } else {
                // Return failure response
                return ['success' => false];
            }
        }

        // If the request is not a POST request, throw an error
        throw new BadRequestHttpException('Only POST requests are allowed');
    }
}