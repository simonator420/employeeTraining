<?php

namespace humhub\modules\employeeTraining\controllers;

use humhub\components\Controller;
use humhub\modules\user\models\Profile;
use humhub\modules\user\models\User;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use Yii;


// Controller for handling actions related to roles within the Employee Training module.
class RoleController extends Controller
{

    // Function for handling the request to display the Employee Training Overview page
    // TODO implement here to set assigned_training to 1 for every user that has been assigned the training with the date picker
    public function actionAdmin()
    {
        $users = User::find()->all();

        $latestAnswers = [];
        

        foreach ($users as $user) {
            $latestAnswers[$user->id] = Yii::$app->db->createCommand('
                SELECT question_text, answer 
                FROM training_answers 
                WHERE user_id = :user_id 
                AND created_at = (
                    SELECT MAX(created_at) 
                    FROM training_answers 
                    WHERE user_id = :user_id
                )
                ORDER BY created_at DESC
            ')
                ->bindValue(':user_id', $user->id)
                ->queryAll();

            $assigned_training = Yii::$app->db->createCommand('SELECT assigned_training FROM profile WHERE user_id=:userId')
                ->bindValue(':userId', $user->id)
                ->queryScalar();

            $user->profile->assigned_training = $assigned_training;

            $completed_trainings_count = Yii::$app->db->createCommand('SELECT completed_trainings_count FROM profile WHERE user_id=:userId')
                ->bindValue(':userId', $user->id)
                ->queryScalar();

            $user->profile->completed_trainings_count = $completed_trainings_count;
        }

        $currentUser = Yii::$app->user;
        $title = $currentUser->identity->profile->title;

        if (!$currentUser->isAdmin()) {
            return $this->redirect(['site/access-denied']);
        }

        $titles = Yii::$app->db->createCommand('SELECT DISTINCT title FROM profile')
            ->queryColumn();

        sort($titles);

        $storage_locations = Yii::$app->db->createCommand('SELECT DISTINCT storage_location FROM profile')
            ->queryColumn();

        sort($storage_locations);

        $trainings = Yii::$app->db->createCommand('SELECT * FROM training')->queryAll();

        return $this->render('admin', [
            'users' => $users,
            'titles' => $titles,
            'storage_locations' => $storage_locations,
            'latestAnswers' => $latestAnswers,
            'trainings' => $trainings,
        ]);
    }

    // Function for handling the request to display Employee Training Overview page
    public function actionEmployee()
    {
        // Retrieve the current logged-in user's information
        $user = Yii::$app->user;
        $userId = $user->getId();
        $title = $user->identity->profile->title;
        $firstName = $user->identity->profile->firstname;
        // Retrieve the assigned_training status from the profile table
        $assigned_training = Yii::$app->db->createCommand('SELECT assigned_training FROM profile WHERE user_id=:userId')
            ->bindValue(':userId', $userId) // Replaces userID in the SQL command with the actual user ID
            ->queryScalar(); // Executes the SQL command and returns a single scalar value (the value of the assigned_training for the current user)

        // Check if the user has an assigned training
        if ($assigned_training === 1) {
            // Render the driver view
            return $this->render('employee', ['title' => $title, 'firstName' => $firstName]);
        }

        // Redirect to access denied if the conditions are not met
        return $this->redirect(['site/access-denied']);
    }

    // Handling the AJAX request to toggle the training assignment for a user
    public function actionToggleTraining()
    {
        // Setting the response format to JSON
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Retrieving the data SENT via the POST request
        $userId = \Yii::$app->request->post('id');
        $assignedTraining = \Yii::$app->request->post('assigned_training');
        $trainingAssignedTime = \Yii::$app->request->post('training_assigned_time');

        // Finding the user in the database by the ID
        $user = User::findOne($userId);
        // Checking if the user exists
        if ($user) {
            // Setting the assigned_training status
            $user->profile->assigned_training = $assignedTraining;
            // Setting the time for training_assigned_time
            $user->profile->training_assigned_time = $trainingAssignedTime;

            // Clear training_complete_time if assigned_training is set to 1
            if ($assignedTraining) {
                $user->profile->training_complete_time = null;
            }

            // Attempts to save the updated user profile
            if ($user->profile->save()) {
                // If the profile is successfully saved
                return ['success' => true];
            }
        }
        // If the user is not found or the profile fails
        return ['success' => false];
    }

    public function actionAssignTraining()
    {
        // Set the response format to JSON
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Retrieve the posted data for selected time, titles, and locations
        $rawTime = \Yii::$app->request->post('selected_time'); // Raw time selected by the user
        $selectedTitles = \Yii::$app->request->post('selected_titles'); // Array of selected titles
        $selectedLocations = \Yii::$app->request->post('selected_locations'); // Array of selected storage locations
        $selectedUsers = \Yii::$app->request->post('selected_users');

        // Convert the raw time to a standard format
        $selectedTime = date('Y-m-d H:i:s', strtotime($rawTime));

        // Find users whose profiles match the selected titles and storage locations
        if (empty($selectedUsers)) {
            $users = User::find()
                ->joinWith('profile') // Join with the profile table
                ->andFilterWhere(['profile.title' => $selectedTitles]) // Filter by selected titles
                ->andFilterWhere(['profile.storage_location' => $selectedLocations]) // Filter by selected storage locations
                ->all(); // Get all matching users
        } else {
            $users = User::find()
                ->joinWith('profile')
                ->andFilterWhere(['profile.user_id' => $selectedUsers])
                ->all();
        }

        // Iterate over each user and update their profile
        foreach ($users as $user) {
            // Update training_assigned_time with the selected time
            $user->profile->training_assigned_time = $selectedTime;
            // Reset assigned_training to 0 (not assigned)
            $user->profile->assigned_training = 0;
            // Clear training_complete_time (set to null)
            $user->profile->training_complete_time = null;

            // Save the updated profile and check for errors
            if (!$user->profile->save()) {
                // If saving fails, return a failure response
                return ['success' => false];
            }
        }
        // Return a success response if all updates are successful
        return ['success' => true];
    }

    // Handling the AJAX request to mark the training as complete for the current user
    public function actionCompleteTraining()
    {
        // Set the response format to JSON
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Retrieve the id of the currently logged-in user
        $userId = \Yii::$app->user->id;
        $title = \Yii::$app->user->identity->profile->title;

        // Find the user by ID
        $user = User::findOne($userId);
        // $title = $user->identity->profile->title;

        // Get the request data
        $requestData = Yii::$app->request->post();

        // Extracting answers
        $answers = [];
        if (isset($requestData['TrainingQuestions'])) {
            foreach ($requestData['TrainingQuestions'] as $index => $question) {
                if (isset($question['answer'])) {
                    $answers[] = [
                        'index' => $index,
                        'answer' => $question['answer']
                    ];
                    $spravnyIndex = $index + 1;
                    $questionData = Yii::$app->db->createCommand('SELECT * FROM training_questions WHERE title = :title AND `order` = :spravnyIndex')
                        ->bindValue(':title', $title)
                        ->bindValue(':spravnyIndex', $spravnyIndex)
                        ->queryOne();

                    if ($questionData) {
                        Yii::info("Index: $spravnyIndex, Question: " . $questionData['question'] . ", Answer: " . $question['answer']);
                        Yii::$app->db->createCommand()->insert('training_answers', [
                            'user_id' => $userId,
                            'question_text' => $questionData['question'],
                            'answer' => $question['answer'],
                            'created_at' => new \yii\db\Expression('NOW()'),
                        ])->execute();
                    } else {
                        Yii::info("Index: $spravnyIndex, No question found, Answer: " . $question['answer']);
                    }
                }
            }
        }

        // Check if the user exists
        if ($user) {
            // Update the user's training_complete_time attribute with current time
            $user->profile->training_complete_time = new \yii\db\Expression('NOW()');
            // Update the user's assigned_training attribute to 0
            $user->profile->assigned_training = 0;
            // Save the users profile and return success if saved
            if ($user->profile->save()) {
                return ['success' => true];
            }
        }
        // Return failure if user not found or save failed
        return ['success' => false];
    }
}