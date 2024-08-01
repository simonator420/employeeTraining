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
    // TODO Rename the header to include ILLE, Not do isAdmin() but check the role attribute in profile table
    public function actionAdmin()
    {
        $users = User::find()->all();

        $latestAnswers = [];
        $openTrainingsCount = [];
        $completedTrainingsCount = [];
        $trainingCompleteTimes = [];

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

            // Get completed_trainings_count from user_training table where assigned_training is 0
            $completed_trainings_count = Yii::$app->db->createCommand('
                SELECT COUNT(*) 
                FROM user_training 
                WHERE user_id = :userId 
                AND assigned_training = 0
            ')
                ->bindValue(':userId', $user->id)
                ->queryScalar();

            $completedTrainingsCount[$user->id] = $completed_trainings_count;

            // Get open trainings count from user_training table where assigned_training is 1
            $open_trainings_count = Yii::$app->db->createCommand('
                SELECT COUNT(*) 
                FROM user_training 
                WHERE user_id = :userId 
                AND assigned_training = 1
            ')
                ->bindValue(':userId', $user->id)
                ->queryScalar();

            $openTrainingsCount[$user->id] = $open_trainings_count;

            // Get the latest training_complete_time from user_training table where assigned_training is 0
            $latest_training_complete_time = Yii::$app->db->createCommand('
                SELECT MAX(training_assigned_time)
                FROM user_training
                WHERE user_id = :userId
                AND assigned_training = 0
            ')
                ->bindValue(':userId', $user->id)
                ->queryScalar();

            $trainingCompleteTimes[$user->id] = $latest_training_complete_time;
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
            'openTrainingsCount' => $openTrainingsCount,
            'completedTrainingsCount' => $completedTrainingsCount,
            'trainingCompleteTimes' => $trainingCompleteTimes,
        ]);
    }


    public function actionFetchUsersByRole($role)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $users = User::find()
            ->joinWith('profile')
            ->where(['profile.role' => $role])
            ->all();

        if ($users) {
            $userList = [];
            foreach ($users as $user) {
                $userList[] = [
                    'id' => $user->id,
                    'firstname' => $user->profile->firstname,
                    'lastname' => $user->profile->lastname,
                ];
            }
            return ['success' => true, 'users' => $userList];
        } else {
            return ['success' => false, 'message' => 'No users found.'];
        }
    }

    public function actionFetchAllProfiles()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $profiles = Profile::find()
            ->joinWith('user')
            ->all();

        if ($profiles) {
            $profileList = [];
            foreach ($profiles as $profile) {
                $profileList[] = [
                    'id' => $profile->user->id,
                    'firstname' => $profile->firstname,
                    'lastname' => $profile->lastname,
                    'role' => $profile->role,
                ];
            }
            return ['success' => true, 'profiles' => $profileList];
        } else {
            return ['success' => false, 'message' => 'No profiles found.'];
        }
    }

    public function actionFetchTitles()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $titles = Yii::$app->db->createCommand('SELECT DISTINCT title FROM profile')
            ->queryColumn();

        if ($titles) {
            return ['success' => true, 'titles' => $titles];
        }

        return ['success' => false, 'titles' => []];
    }

    public function actionFetchLocations()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $locations = Yii::$app->db->createCommand('SELECT DISTINCT storage_location FROM profile')
            ->queryColumn();

        if ($locations) {
            return ['success' => true, 'locations' => $locations];
        }

        return ['success' => false, 'locations' => []];
    }

    public function actionFetchFilteredUsers($title = null, $location = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $query = (new \yii\db\Query())
            ->select(['user_id', 'firstname', 'lastname'])
            ->from('profile');

        if ($title) {
            $query->andWhere(['title' => $title]);
        }

        if ($location) {
            $query->andWhere(['storage_location' => $location]);
        }

        $users = $query->all();

        if ($users) {
            return ['success' => true, 'users' => $users];
        }

        return ['success' => false, 'users' => []];
    }


    public function actionAddRole()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $request = Yii::$app->request;

        if ($request->isPost) {
            $role = $request->post('role');
            $profileIds = $request->post('profiles', []);

            if ($role && !empty($profileIds)) {
                foreach ($profileIds as $profileId) {
                    $user = User::findOne($profileId);
                    if ($user) {
                        // Update the profile role attribute
                        $user->profile->role = $role;
                        if (!$user->profile->save()) {
                            Yii::error("Failed to save user profile for ID: $profileId", __METHOD__);
                            return ['success' => false, 'message' => 'Failed to save user profile.'];
                        }
                    } else {
                        Yii::error("User not found with ID: $profileId", __METHOD__);
                        return ['success' => false, 'message' => "User not found with ID: $profileId"];
                    }
                }
                return ['success' => true];
            }
        }

        return ['success' => false, 'message' => 'Invalid request'];
    }
    // Removing the current role and setting it to 'user'
    public function actionRemoveRole()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $request = Yii::$app->request;

        if ($request->isPost) {
            $role = $request->post('role');
            $userIds = $request->post('users', []);

            if ($role && !empty($userIds)) {
                foreach ($userIds as $userId) {
                    $user = User::findOne($userId);
                    if ($user) {
                        // Directly update the profile role attribute
                        $user->profile->role = 'user';
                        if (!$user->profile->save()) {
                            Yii::error("Failed to save user profile for ID: $userId", __METHOD__);
                            return ['success' => false, 'message' => 'Failed to save user profile.'];
                        }
                    } else {
                        Yii::error("User not found with ID: $userId", __METHOD__);
                        return ['success' => false, 'message' => "User not found with ID: $userId"];
                    }
                }
                return ['success' => true];
            }
        }

        return ['success' => false];
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
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $userIds = Yii::$app->request->post('user_ids', []); // Expect an array of user IDs
        $trainingId = Yii::$app->request->post('training_id');
        $assignedTraining = Yii::$app->request->post('assigned_training');
        $trainingAssignedTime = Yii::$app->request->post('training_assigned_time');

        // Fetch the deadline for completion from the training table
        $training = Yii::$app->db->createCommand('
            SELECT deadline_for_completion 
            FROM training 
            WHERE id = :training_id
        ')
            ->bindValue(':training_id', $trainingId)
            ->queryOne();

        $deadlineForCompletion = $training ? $training['deadline_for_completion'] : null;

        $successCount = 0;

        foreach ($userIds as $userId) {
            // Calculate the deadline
            if ($deadlineForCompletion && $trainingAssignedTime) {
                $deadline = date('Y-m-d H:i:s', strtotime($trainingAssignedTime . ' + ' . $deadlineForCompletion . ' days'));
            } else {
                $deadline = null;
            }

            // Check if the record already exists
            $userTrainingExists = Yii::$app->db->createCommand('
                SELECT COUNT(*) 
                FROM user_training 
                WHERE user_id = :user_id AND training_id = :training_id
            ')
                ->bindValue(':user_id', $userId)
                ->bindValue(':training_id', $trainingId)
                ->queryScalar();

            if ($userTrainingExists) {
                // Update existing record
                $result = Yii::$app->db->createCommand()
                    ->update(
                        'user_training',
                        [
                            'assigned_training' => $assignedTraining,
                            'training_assigned_time' => $trainingAssignedTime,
                            'deadline' => $deadline,
                        ],
                        [
                            'user_id' => $userId,
                            'training_id' => $trainingId,
                        ]
                    )
                    ->execute();
            } else {
                // Insert new record
                $result = Yii::$app->db->createCommand()
                    ->insert(
                        'user_training',
                        [
                            'user_id' => $userId,
                            'training_id' => $trainingId,
                            'assigned_training' => $assignedTraining,
                            'training_assigned_time' => $trainingAssignedTime,
                            'deadline' => $deadline,
                        ]
                    )
                    ->execute();
            }

            if ($result) {
                $successCount++;
            }
        }

        // Update the training's assigned_users_count with the actual count of user_training records
        if ($successCount > 0) {
            Yii::$app->db->createCommand()
                ->update(
                    'training',
                    ['assigned_users_count' => new \yii\db\Expression('(SELECT COUNT(*) FROM user_training WHERE training_id = :training_id)')],
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

    public function actionCreateTraining()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $request = \Yii::$app->request;

        if ($request->isPost) {
            $data = json_decode($request->getRawBody(), true);
            $trainingId = $data['id'];
            $trainingName = $data['name'];

            $command = Yii::$app->db->createCommand()->insert('training', [
                'id' => $trainingId,
                'name' => $trainingName,
                'created_at' => new \yii\db\Expression('NOW()'),
                'assigned_users_count' => 0,
            ]);

            if ($command->execute()) {
                return ['success' => true];
            } else {
                return ['success' => false];
            }
        }

        throw new BadRequestHttpException('Only POST requests are allowed');
    }
}