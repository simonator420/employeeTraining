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
        $activeAssignedTrainingsCount = []; // To store the count of active assigned trainings for each training

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

            $completed_trainings_count = Yii::$app->db->createCommand('
                SELECT COUNT(*) 
                FROM user_training 
                WHERE user_id = :userId 
                AND assigned_training = 0
            ')
                ->bindValue(':userId', $user->id)
                ->queryScalar();

            $completedTrainingsCount[$user->id] = $completed_trainings_count;

            $open_trainings_count = Yii::$app->db->createCommand('
                SELECT COUNT(*) 
                FROM user_training 
                WHERE user_id = :userId 
                AND assigned_training = 1
            ')
                ->bindValue(':userId', $user->id)
                ->queryScalar();

            $openTrainingsCount[$user->id] = $open_trainings_count;

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

        $trainings = Yii::$app->db->createCommand('SELECT * FROM training')->queryAll();

        foreach ($trainings as $training) {
            $activeAssignedTrainingsCount[$training['id']] = Yii::$app->db->createCommand('
                SELECT COUNT(*) 
                FROM user_training 
                WHERE assigned_training = 1 
                AND training_id = :training_id
            ')
                ->bindValue(':training_id', $training['id'])
                ->queryScalar();
        }

        $currentUser = Yii::$app->user;
        $title = $currentUser->identity->profile->title;
        $userRole = $currentUser->identity->profile->role;

        if ($userRole !== 'admin' && $userRole !== 'team_leader') {
            return $this->redirect(['site/access-denied']);
        }

        $titles = Yii::$app->db->createCommand('SELECT DISTINCT title FROM profile')
            ->queryColumn();

        sort($titles);

        $storage_locations = Yii::$app->db->createCommand('SELECT DISTINCT storage_location FROM profile')
            ->queryColumn();

        sort($storage_locations);

        return $this->render('admin', [
            'users' => $users,
            'titles' => $titles,
            'storage_locations' => $storage_locations,
            'latestAnswers' => $latestAnswers,
            'trainings' => $trainings,
            'openTrainingsCount' => $openTrainingsCount,
            'completedTrainingsCount' => $completedTrainingsCount,
            'trainingCompleteTimes' => $trainingCompleteTimes,
            'userRole' => $userRole,
            'activeAssignedTrainingsCount' => $activeAssignedTrainingsCount, // Pass the data to the view
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

    public function actionFetchAllProfiles($trainingId)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $profiles = Profile::find()
            ->joinWith('user')
            ->all();

        $assignedUserIds = Yii::$app->db->createCommand('
            SELECT user_id 
            FROM user_training 
            WHERE training_id = :trainingId AND assigned_training = 1
        ')
            ->bindValue(':trainingId', $trainingId)
            ->queryColumn();

        if ($profiles) {
            $profileList = [];
            foreach ($profiles as $profile) {
                $profileList[] = [
                    'id' => $profile->user->id,
                    'firstname' => $profile->firstname,
                    'lastname' => $profile->lastname,
                    'role' => $profile->role,
                    'isAssigned' => in_array($profile->user->id, $assignedUserIds),
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
    public function actionEmployee($id)
    {
        // Retrieve the current logged-in user's information
        $user = Yii::$app->user;
        $userId = $user->getId();
        $profile = $user->identity->profile;
        $title = $profile->title ?? 'N/A';
        $firstName = $profile->firstname ?? 'N/A';

        // Check if the user has an assigned training record with the provided training ID
        $trainingRecord = Yii::$app->db->createCommand('
            SELECT * FROM user_training
            WHERE user_id = :userId AND training_id = :trainingId AND assigned_training = 1
        ')
            ->bindValue(':userId', $userId)
            ->bindValue(':trainingId', $id)
            ->queryOne();

        // Check if the training record exists and is valid
        if ($trainingRecord) {
            // Render the employee view with the training ID
            return $this->render('employee', [
                'title' => $title,
                'firstName' => $firstName,
                'trainingId' => $id
            ]);
        }

        // Redirect to access denied if the conditions are not met
        Yii::warning("Unauthorized access attempt to training ID: $id by user ID: $userId");
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
            $userTrainingRecord = Yii::$app->db->createCommand('
                SELECT * 
                FROM user_training 
                WHERE user_id = :user_id AND training_id = :training_id
            ')
                ->bindValue(':user_id', $userId)
                ->bindValue(':training_id', $trainingId)
                ->queryOne();

            if ($userTrainingRecord && $userTrainingRecord['assigned_training'] == 1) {
                // Update existing record if assigned_training is 1
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
                // Insert new record if assigned_training is 0 or record doesn't exist
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
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // List all question texts
        $questions = Yii::$app->db->createCommand('
            SELECT question FROM training_questions
        ')
            ->queryAll();

        foreach ($questions as $question) {
            Yii::warning("Question name " . $question['question'], __METHOD__);
            // Yii::warning("Question ID " . $question['training_id'], __METHOD__);
        }

        // Retrieve the id of the currently logged-in user
        $userId = Yii::$app->user->id;

        // Get the request data
        $requestData = Yii::$app->request->post();

        // Process and log the answers
        $allAnswers = [];
        if (isset($requestData['TrainingQuestions'])) {
            foreach ($requestData['TrainingQuestions'] as $questionId => $answer) {
                if (is_array($answer)) {
                    // Multiple-choice answer
                    $allAnswers[$questionId] = $answer;
                } else {
                    // Single answer (text, number, range, etc.)
                    $allAnswers[$questionId] = [$answer];
                }
            }
        }

        // Debugging: Log allAnswers
        Yii::info("All Answers: " . print_r($allAnswers, true), __METHOD__);

        // Fetch valid question IDs
        $validQuestionIds = Yii::$app->db->createCommand('
            SELECT id FROM training_questions
        ')
            ->queryColumn();

        foreach ($allAnswers as $questionId => $answerArray) {
            // Check if the question ID is valid
            if (!in_array($questionId, $validQuestionIds)) {
                Yii::warning("Question ID $questionId not found", __METHOD__);
                continue; // Skip to the next question if not found
            }

            // Fetch the question based on its ID
            $question = Yii::$app->db->createCommand('
                SELECT * FROM training_questions
                WHERE id = :questionId
            ')
                ->bindValue(':questionId', $questionId)
                ->queryOne();

            // Insert answers into the respective tables
            foreach ($answerArray as $answer) {
                if (is_array($answer)) {
                    foreach ($answer as $individualAnswer) {
                        // Ensure the multiple-choice answer ID is valid
                        $option = Yii::$app->db->createCommand('
                            SELECT * FROM training_multiple_choice_answers
                            WHERE id = :id AND question_id = :questionId
                        ')
                            ->bindValue(':id', $individualAnswer)
                            ->bindValue(':questionId', $questionId)
                            ->queryOne();

                        if (!$option) {
                            Yii::warning("Invalid option ID: $individualAnswer for question ID: $questionId", __METHOD__);
                            continue; // Skip to the next answer if not found
                        }

                        // Insert multiple-choice answer into the user-specific table
                        $result = Yii::$app->db->createCommand()
                            ->insert(
                                'training_multiple_choice_user_answers',
                                [
                                    'user_id' => $userId,
                                    'question_id' => $questionId,
                                    'answer_id' => $individualAnswer,
                                    'question_text' => $question['question'],
                                    'created_at' => new \yii\db\Expression('NOW()'),
                                ]
                            )
                            ->execute();

                        // Debugging: Log insert result
                        Yii::info("Inserted multiple_choice_user_answer: " . print_r($result, true), __METHOD__);
                    }
                } else {
                    // Insert single answer into the training_answers table
                    $result = Yii::$app->db->createCommand()
                        ->insert(
                            'training_answers',
                            [
                                'user_id' => $userId,
                                'question_text' => $question['question'],
                                'answer' => $answer,
                                'created_at' => new \yii\db\Expression('NOW()'),
                                'training_id' => $requestData['training_id'],
                            ]
                        )
                        ->execute();

                    // Debugging: Log insert result
                    Yii::info("Inserted answer: " . print_r($result, true), __METHOD__);
                }
            }
        }

        // Update the assigned_training attribute to 0
        $result = Yii::$app->db->createCommand()
            ->update(
                'user_training',
                ['assigned_training' => 0],
                [
                    'user_id' => $userId,
                    'training_id' => $requestData['training_id'],
                ]
            )
            ->execute();

        // Debugging: Log update result
        Yii::info("Updated user_training: " . print_r($result, true), __METHOD__);

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

    public function actionUserAnswers($id)
    {
        $user = User::findOne($id);
        if (!$user) {
            throw new NotFoundHttpException("User not found");
        }

        // Fetch distinct training IDs where the user has answers
        $trainingIds = Yii::$app->db->createCommand('
            SELECT DISTINCT training_id 
            FROM training_answers 
            WHERE user_id = :userId
            AND training_id IN (SELECT id FROM training)
        ')
            ->bindValue(':userId', $id)
            ->queryAll();

        $trainings = [];
        $answers = [];
        foreach ($trainingIds as $trainingId) {
            $trainingIdValue = $trainingId['training_id'];

            // Fetch the training name
            $trainingName = Yii::$app->db->createCommand('
                SELECT name 
                FROM training 
                WHERE id = :trainingId
            ')
                ->bindValue(':trainingId', $trainingIdValue)
                ->queryScalar();

            // Fetch distinct created_at timestamps for the training
            $instances = Yii::$app->db->createCommand('
                SELECT DISTINCT created_at 
                FROM training_answers 
                WHERE user_id = :userId AND training_id = :trainingId
                ORDER BY created_at DESC
            ')
                ->bindValue(':userId', $id)
                ->bindValue(':trainingId', $trainingIdValue)
                ->queryAll();

            $trainings[] = [
                'training_id' => $trainingIdValue,
                'training_name' => $trainingName,
                'instances' => $instances,
            ];

            foreach ($instances as $instance) {
                $instanceCreatedAt = $instance['created_at'];

                $trainingAnswers = Yii::$app->db->createCommand('
                    SELECT * 
                    FROM training_answers 
                    WHERE user_id = :userId AND training_id = :trainingId AND created_at = :createdAt
                ')
                    ->bindValue(':userId', $id)
                    ->bindValue(':trainingId', $trainingIdValue)
                    ->bindValue(':createdAt', $instanceCreatedAt)
                    ->queryAll();

                foreach ($trainingAnswers as &$answer) {
                    if ($answer['answer'] == 'multiple_choice') {
                        $multipleChoiceAnswers = Yii::$app->db->createCommand('
                            SELECT option_text 
                            FROM training_multiple_choice_answers 
                            WHERE id IN (
                                SELECT answer_id 
                                FROM training_multiple_choice_user_answers 
                                WHERE user_id = :userId AND question_id = (
                                    SELECT id 
                                    FROM training_questions 
                                    WHERE question = :question AND training_id = :trainingId
                                ) AND created_at = :createdAt
                            )
                        ')
                            ->bindValue(':userId', $id)
                            ->bindValue(':question', $answer['question_text'])
                            ->bindValue(':trainingId', $trainingIdValue)
                            ->bindValue(':createdAt', $instanceCreatedAt)
                            ->queryColumn();

                        $answer['answer'] = implode(', ', $multipleChoiceAnswers);
                    }
                }

                $answers[$trainingIdValue][$instanceCreatedAt] = $trainingAnswers;
            }
        }

        // Sort trainings by the latest instance date (newest first)
        usort($trainings, function ($a, $b) {
            $a_latest = strtotime($a['instances'][0]['created_at']);
            $b_latest = strtotime($b['instances'][0]['created_at']);
            return $b_latest - $a_latest;
        });

        /*
        // Sort trainings by the earliest instance date (oldest first)
        usort($trainings, function($a, $b) {
            $a_earliest = strtotime($a['instances'][0]['created_at']);
            $b_earliest = strtotime($b['instances'][0]['created_at']);
            return $a_earliest - $b_earliest;
        });
        */

        return $this->render('/admin/user-answers', [
            'user' => $user,
            'trainings' => $trainings,
            'answers' => $answers,
        ]);
    }

}