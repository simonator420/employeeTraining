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

    // Function for displaying the admin page for ADMIN
    public function actionAdmin()
    {
        // Retrieve all users from database
        $users = User::find()->all();

        // Initialize arrays to store various data about users and trainings
        $latestAnswers = [];
        $openTrainingsCount = [];
        $completedTrainingsCount = [];
        $trainingCompleteTimes = [];
        $activeAssignedTrainingsCount = [];

        // Loop through each user to gather training data
        foreach ($users as $user) {
            // Retrieve the latest answers for each user
            $latestAnswers[$user->id] = Yii::$app->db->createCommand('
                SELECT tq.question, 
                       CASE 
                           WHEN ta.answer = "multiple_choice" THEN GROUP_CONCAT(tma.option_text SEPARATOR ", ") 
                           ELSE ta.answer 
                       END as answer
                FROM training_answers ta
                JOIN training_questions tq ON ta.question_id = tq.id
                LEFT JOIN training_multiple_choice_user_answers tmua ON ta.question_id = tmua.question_id AND ta.user_id = tmua.user_id
                LEFT JOIN training_multiple_choice_answers tma ON tmua.answer_id = tma.id
                WHERE ta.user_id = :user_id 
                AND ta.created_at = (
                    SELECT MAX(created_at) 
                    FROM training_answers 
                    WHERE user_id = :user_id
                )
                GROUP BY tq.question, ta.answer
                ORDER BY ta.created_at DESC
            ')
                ->bindValue(':user_id', $user->id)
                ->queryAll();

            // Count the number of completed training for each user
            $completed_trainings_count = Yii::$app->db->createCommand('
                SELECT COUNT(*) 
                FROM user_training 
                WHERE user_id = :userId 
                AND assigned_training = 0
            ')
                ->bindValue(':userId', $user->id)
                ->queryScalar();

            // Store the completed training count in the array
            $completedTrainingsCount[$user->id] = $completed_trainings_count;

            // Count the number of open (assigned but not completed) training for each user
            $open_trainings_count = Yii::$app->db->createCommand('
                SELECT COUNT(*) 
                FROM user_training 
                WHERE user_id = :userId 
                AND assigned_training = 1
            ')
                ->bindValue(':userId', $user->id)
                ->queryScalar();

            // Store the number of opent training count in the array
            $openTrainingsCount[$user->id] = $open_trainings_count;

            // Get the latest completion time for each user
            $latest_training_complete_time = Yii::$app->db->createCommand('
                SELECT MAX(training_assigned_time)
                FROM user_training
                WHERE user_id = :userId
                AND assigned_training = 0
            ')
                ->bindValue(':userId', $user->id)
                ->queryScalar();

            // Store the latest completion time in the array
            $trainingCompleteTimes[$user->id] = $latest_training_complete_time;
        }

        // Retrieve all training from the database
        $trainings = Yii::$app->db->createCommand('SELECT * FROM training')->queryAll();

        // Loop through each training to calculate the count of active assigned trainings
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

        // Get the current logged in user
        $currentUser = Yii::$app->user;

        // Retrieve the user's title and role from the profile a ted
        $title = $currentUser->identity->profile->title;
        $userRole = $currentUser->identity->profile->role;

        // Check if the user is not an admin or team leader
        if ($userRole !== 'admin' && $userRole !== 'team_leader') {
            return $this->redirect(['site/access-denied']);
        }

        // Retrieve distinct titles from the profile table
        $titles = Yii::$app->db->createCommand('SELECT DISTINCT title FROM profile')
            ->queryColumn();

        // Sort the titles alphabetically
        sort($titles);

        // Retrieve distinct storage locations from the profile table
        $storage_locations = Yii::$app->db->createCommand('SELECT DISTINCT storage_location FROM profile')
            ->queryColumn();

        // Sort the storage locations alphabetically
        sort($storage_locations);

        // Render the admin view with the gathered data
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

    // Function to fetch users based on their role
    public function actionFetchUsersByRole($role)
    {
        // Set response format to JSON
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Query to find users with the specified role, joining with the profile table
        $users = User::find()
            ->joinWith('profile')
            ->where(['profile.role' => $role])
            ->all();

        // Check if users were found
        if ($users) {
            // Initialize an array to store the user information
            $userList = [];
            // Loop through each user and add their details to the array
            foreach ($users as $user) {
                $userList[] = [
                    'id' => $user->id,
                    'firstname' => $user->profile->firstname,
                    'lastname' => $user->profile->lastname,
                ];
            }
            // Return a success response with the user list
            return ['success' => true, 'users' => $userList];
        } else {
            // Return a failure response if no users were found
            return ['success' => false, 'message' => 'No users found.'];
        }
    }

    // Function to fetch users by their role and title
    public function actionFetchUsersByRoleAndTitle($role, $title)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Query to find users with the specified role and title, joining with the profile table
        $users = User::find()
            ->joinWith('profile')
            ->where(['profile.role' => $role, 'profile.title' => $title])
            ->all();

        // Check if users were found
        if ($users) {
            // Initialize an array to store the user information
            $userList = [];
            // Loop through each user and add their details to the array
            foreach ($users as $user) {
                $userList[] = [
                    'id' => $user->id,
                    'firstname' => $user->profile->firstname,
                    'lastname' => $user->profile->lastname,
                ];
            }
            // Return a success response with the user list
            return ['success' => true, 'users' => $userList];
        } else {
            // Return a failure response if no users were found
            return ['success' => false, 'message' => 'No users found.'];
        }
    }

    // Function to fetch all profiles associated with a specific training
    public function actionFetchAllProfiles($trainingId)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Query to fetch all profiles along with their associated user data
        $profiles = Profile::find()
            ->joinWith('user')
            ->all();

        // Query to fetch user IDs of users assigned to the specific training
        $assignedUserIds = Yii::$app->db->createCommand('
            SELECT user_id 
            FROM user_training 
            WHERE training_id = :trainingId AND assigned_training = 1
        ')
            ->bindValue(':trainingId', $trainingId)
            ->queryColumn();

        // Check if any profiles were found
        if ($profiles) {
            // Initialize an array to store profile information
            $profileList = [];
            // Loop through each profile and add their details to the array
            foreach ($profiles as $profile) {
                $profileList[] = [
                    'id' => $profile->user->id,
                    'firstname' => $profile->firstname,
                    'lastname' => $profile->lastname,
                    'role' => $profile->role,
                    'isAssigned' => in_array($profile->user->id, $assignedUserIds),
                ];
            }
            // Return a success response with the profile list
            return ['success' => true, 'profiles' => $profileList];
        } else {
            // Return a failure response if no profiles were found
            return ['success' => false, 'message' => 'No profiles found.'];
        }
    }

    // Function to fetch all profiles, adjusted for addinf roles to users
    // adjusted actionFetchAllProfiles only for adding roles to users
    public function actionFetchProfiles()
    {
        // Set response format to json
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Query to fetch all profiles along with their associated user data
        $profiles = Profile::find()
            ->joinWith('user')
            ->all();

        // Check if any profiles were found
        if ($profiles) {
            // Initialize an array to store profile information
            $profileList = [];
            // Loop through each profile and add their details to the array
            foreach ($profiles as $profile) {
                $profileList[] = [
                    'id' => $profile->user->id,
                    'firstname' => $profile->firstname,
                    'lastname' => $profile->lastname,
                    'role' => $profile->role,
                ];
            }
            // Return a success response with the profile list
            return ['success' => true, 'profiles' => $profileList];
        } else {
            // Return a failure response if no profiles were found
            return ['success' => false, 'message' => 'No profiles found.'];
        }
    }

    // Function to fetch all distinct job titles from the profile table
    public function actionFetchTitles()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Query to fetch distinct titles from the profile table
        $titles = Yii::$app->db->createCommand('SELECT DISTINCT title FROM profile')
            ->queryColumn();

        // Check if any titles were found
        if ($titles) {
            // Return a success response with the list of titles
            return ['success' => true, 'titles' => $titles];
        }
        // Return a failure responsse with an empty array if no titles were found
        return ['success' => false, 'titles' => []];
    }

    // Function to fetch all distinct storage locations from the profile table
    public function actionFetchLocations()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Query to fetch all distinct storage locations from the profile table
        $locations = Yii::$app->db->createCommand('SELECT DISTINCT storage_location FROM profile')
            ->queryColumn();

        // Check if any locations were found
        if ($locations) {
            // Return a success response with the list of storage locations
            return ['success' => true, 'locations' => $locations];
        }
        // Return a failure response with an empty array if no storage loctaions were found
        return ['success' => false, 'locations' => []];
    }

    // Function to fetch distinct job titles based on specific role
    public function actionFetchCollapsibleTitles($role)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;


        try {
            // Query to fetch distinct titles where the role matches the provided tole and the title is not null or empty
            $titles = \Yii::$app->db->createCommand('
                SELECT DISTINCT title 
                FROM profile
                WHERE role = :role AND title IS NOT NULL AND title != ""
            ')
                ->bindValue(':role', $role)
                ->queryColumn();

            // Check if any titles were found
            if (!empty($titles)) {
                // Return a success response with the list of titles
                return [
                    'success' => true,
                    'titles' => $titles
                ];
            } else {
                // Return failure response if no titles were found
                return [
                    'success' => false,
                    'titles' => [],
                    'message' => 'No titles found for the selected role.'
                ];
            }
        } catch (\Exception $e) {
            // Log an error message if the query fails
            \Yii::error("Failed to fetch titles: " . $e->getMessage(), __METHOD__);

            // Return failure response with an error message
            return [
                'success' => false,
                'message' => 'An error occurred while fetching titles.'
            ];
        }
    }


    // Function to fetch users filtered by title and/or location
    public function actionFetchFilteredUsers($title = null, $location = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Create a query object to select user details from the profile table
        $query = (new \yii\db\Query())
            ->select(['user_id', 'firstname', 'lastname', 'title', 'storage_location'])  // Select necessary fields
            ->from('profile');

        // Add condition to filter by title if provided
        if ($title) {
            $query->andWhere(['title' => $title]);
        }

        // Add condition to filter by location if provided
        if ($location) {
            $query->andWhere(['storage_location' => $location]);
        }

        // Execute the query to fetch all matching users
        $users = $query->all();

        // If users are found, proceed to fetch additional details for each user
        if ($users) {
            foreach ($users as &$user) {
                // Calculate the count of completed trainings for each user
                $user['completed_trainings_count'] = Yii::$app->db->createCommand('
                    SELECT COUNT(*) 
                    FROM user_training 
                    WHERE user_id = :userId 
                    AND assigned_training = 0
                ')
                    ->bindValue(':userId', $user['user_id'])
                    ->queryScalar();

                // Calculate the count of open (assigned) trainings for each user
                $user['open_trainings_count'] = Yii::$app->db->createCommand('
                    SELECT COUNT(*) 
                    FROM user_training 
                    WHERE user_id = :userId 
                    AND assigned_training = 1
                ')
                    ->bindValue(':userId', $user['user_id'])
                    ->queryScalar();

                // Get latest training completion time for each user
                $latestTrainingTime = Yii::$app->db->createCommand('
                    SELECT MAX(training_assigned_time)
                    FROM user_training
                    WHERE user_id = :userId
                    AND assigned_training = 0
                ')
                    ->bindValue(':userId', $user['user_id'])
                    ->queryScalar();

                // If no training time is found, set the value to "N/A"
                $user['latest_training_complete_time'] = $latestTrainingTime ? $latestTrainingTime : 'N/A';
            }

            // Return a success response with the list of users and their calculated fields
            return ['success' => true, 'users' => $users];
        }

        // If no users are found, return a failure response with an empty list
        return ['success' => false, 'users' => []];
    }


    // Function to add a role to selected users
    public function actionAddRole()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $request = Yii::$app->request;

        // Check if the request is a POST request
        if ($request->isPost) {
            // Get the role and profile IDs from the request
            $role = $request->post('role');
            $profileIds = $request->post('profiles', []);

            // If a role and profile IDs are provided, proceed
            if ($role && !empty($profileIds)) {
                foreach ($profileIds as $profileId) {
                    // Find the user by profile ID
                    $user = User::findOne($profileId);
                    if ($user) {
                        // Update the profile role attribute
                        $user->profile->role = $role;
                        // Save the profile and check if it fails
                        if (!$user->profile->save()) {
                            Yii::error("Failed to save user profile for ID: $profileId", __METHOD__);
                            return ['success' => false, 'message' => 'Failed to save user profile.'];
                        }
                    } else {
                        // If user is not found, log an error and return a failure response
                        Yii::error("User not found with ID: $profileId", __METHOD__);
                        return ['success' => false, 'message' => "User not found with ID: $profileId"];
                    }
                }
                // Return a success response if all profiles were updated successfully
                return ['success' => true];
            }
        }
        // Return a failure response if the request was invalid
        return ['success' => false, 'message' => 'Invalid request'];
    }

    // Function to remove a role from selected users and set it to 'user'
    public function actionRemoveRole()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $request = Yii::$app->request;

        // Check if the request is a POST request
        if ($request->isPost) {
            // Get the role and user IDs from the request
            $role = $request->post('role');
            $userIds = $request->post('users', []);

            // If a role and user IDs are provided, proceed
            if ($role && !empty($userIds)) {
                foreach ($userIds as $userId) {
                    // Find the user by user ID
                    $user = User::findOne($userId);
                    if ($user) {
                        // Directly update the profile role attribute to 'user'
                        $user->profile->role = 'user';
                        // Save the profile and check if it fails
                        if (!$user->profile->save()) {
                            Yii::error("Failed to save user profile for ID: $userId", __METHOD__);
                            return ['success' => false, 'message' => 'Failed to save user profile.'];
                        }
                    } else {
                        // If user is not found, log an error and return a failure response
                        Yii::error("User not found with ID: $userId", __METHOD__);
                        return ['success' => false, 'message' => "User not found with ID: $userId"];
                    }
                }
                // Return a success reponse if all profiles were updated successfully
                return ['success' => true];
            }
        }

        // Return a failure response if the request was invalid
        return ['success' => false];
    }


    // Function for handling the request to display employee.php
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
            // Render the employee view with the training ID, passing the necessary data
            return $this->render('employee', [
                'title' => $title,
                'firstName' => $firstName,
                'trainingId' => $id
            ]);
        }

        // Redirect to the access denied page
        return $this->redirect(['site/access-denied']);
    }

    // Function to handle the AJAX request to toggle the training assignment for a user
    public function actionToggleTraining()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $userIds = Yii::$app->request->post('user_ids', []);
        $trainingId = Yii::$app->request->post('training_id');
        $assignedTraining = Yii::$app->request->post('assigned_training');
        $trainingAssignedTime = Yii::$app->request->post('training_assigned_time');

        $training = Yii::$app->db->createCommand('
            SELECT deadline_for_completion 
            FROM training 
            WHERE id = :training_id
        ')
            ->bindValue(':training_id', $trainingId)
            ->queryOne();

        $deadlineForCompletion = $training ? $training['deadline_for_completion'] : null;

        $successCount = 0;

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

            if ($deadlineForCompletion && $trainingAssignedTime) {
                $deadline = date('Y-m-d H:i:s', strtotime($trainingAssignedTime . ' + ' . $deadlineForCompletion . ' days'));
            } else {
                $deadline = null;
            }

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


    // Action to remove training assignments from users
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


    // Function to assign training to users
    public function actionAssignTraining()
    {
        // Set the response format to JSON
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Retrieve the posted data for selected time, titles, and locations
        $rawTime = \Yii::$app->request->post('selected_time');
        $selectedTitles = \Yii::$app->request->post('selected_titles');
        $selectedLocations = \Yii::$app->request->post('selected_locations');
        $selectedUsers = \Yii::$app->request->post('selected_users');

        // Convert the raw time to a standard format
        $selectedTime = date('Y-m-d H:i:s', strtotime($rawTime));

        // Find users whose profiles match the selected titles and storage locations
        if (empty($selectedUsers)) {
            // If no specific users are selected, find users based on title and location
            $users = User::find()
                ->joinWith('profile') // Join with the profile table to access user profile data
                ->andFilterWhere(['profile.title' => $selectedTitles]) // Filter by selected titles
                ->andFilterWhere(['profile.storage_location' => $selectedLocations]) // Filter by selected storage locations
                ->all();
        } else {
            // If specific users are selected, find them directly
            $users = User::find()
                ->joinWith('profile')
                ->andFilterWhere(['profile.user_id' => $selectedUsers])
                ->all();
        }

        // Iterate over each user and update their profile with the new training assignment details
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

    // Function for creating a new training record in the database
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

    // Function for retrieving all training instances where a specific user has submitted answers
    public function actionUserAnswers($id)
    {
        // Find the user by ID
        $user = User::findOne($id);

        // If the user is not found, throw a 404 error
        if (!$user) {
            throw new NotFoundHttpException("User not found");
        }

        // Fetch all unique training IDs where the user has submitted answers
        $trainingIds = Yii::$app->db->createCommand('
            SELECT DISTINCT tq.training_id
            FROM training_answers ta
            JOIN training_questions tq ON ta.question_id = tq.id
            WHERE ta.user_id = :userId
            AND tq.training_id IN (SELECT id FROM training)
        ')
            ->bindValue(':userId', $id)
            ->queryAll();

        // Initialize arrays to hold training data and answers
        $trainings = [];
        $answers = [];

        // Loop through each training ID
        foreach ($trainingIds as $trainingId) {
            $trainingIdValue = $trainingId['training_id'];

            // Fetch the name of the training by its ID
            $trainingName = Yii::$app->db->createCommand('
                SELECT name 
                FROM training 
                WHERE id = :trainingId
            ')
                ->bindValue(':trainingId', $trainingIdValue)
                ->queryScalar();

            // Fetch the user_training ID
            $userTrainingId = Yii::$app->db->createCommand('
                SELECT id 
                FROM user_training 
                WHERE id = :trainingId
            ')
                ->bindValue(':trainingId', $trainingIdValue)
                ->queryScalar();

            // Fetch distinct created_at timestamps for the user's answers in the training
            $instances = Yii::$app->db->createCommand('
                SELECT DISTINCT ta.created_at
                FROM training_answers ta
                JOIN training_questions tq ON ta.question_id = tq.id
                WHERE ta.user_id = :userId AND tq.training_id = :trainingId
                ORDER BY ta.created_at DESC
            ')
                ->bindValue(':userId', $id)
                ->bindValue(':trainingId', $trainingIdValue)
                ->queryAll();

            // Loop through each instance (timestamps)
            foreach ($instances as &$instance) {
                $instanceCreatedAt = $instance['created_at'];
                $totalScore = 0;
                $isScored = true;

                // Fetch the user's answers for this training and timestamp
                $trainingAnswers = Yii::$app->db->createCommand('
                    SELECT ta.*, tq.question AS question_text, ta.score
                    FROM training_answers ta
                    JOIN training_questions tq ON ta.question_id = tq.id
                    WHERE ta.user_id = :userId 
                    AND tq.training_id = :trainingId 
                    AND ta.created_at = :createdAt
                ')
                    ->bindValue(':userId', $id)
                    ->bindValue(':trainingId', $trainingIdValue)
                    ->bindValue(':createdAt', $instanceCreatedAt)
                    ->queryAll();

                $allNull = true;

                // Loop through teach answer
                foreach ($trainingAnswers as &$answer) {
                    // If the answer is of type 'multiple_choice'
                    if ($answer['answer'] == 'multiple_choice') {
                        $scoreAdded = false;
                        // Fetch multiple choice answers with ther correctness status
                        $multipleChoiceAnswers = Yii::$app->db->createCommand('
                            SELECT tma.option_text, tma.is_correct, tmua.score, tma.id 
                            FROM training_multiple_choice_user_answers tmua
                            JOIN training_multiple_choice_answers tma ON tmua.multiple_choice_answer_id = tma.id
                            WHERE tmua.answer_id = :answerId 
                            AND tmua.user_id = :userId
                        ')
                            ->bindValue(':answerId', $answer['id'])
                            ->bindValue(':userId', $id)
                            ->queryAll();

                        // Add the multiple choice answers to the answer array
                        $answer['multiple_choice_answers'] = $multipleChoiceAnswers;

                        // Loop through the multiple choice answers to add their scores
                        foreach ($multipleChoiceAnswers as $mca) {
                            if ($scoreAdded == false && isset($mca['score'])) {
                                $totalScore += $answer['score'];
                                $scoreAdded = true;
                                $allNull = false;
                            }
                        }
                    } else {
                        if (isset($answer['score'])) {
                            $totalScore += $answer['score']; // Add the score to the total score
                        }
                    }
                }

                // If all scores are null, mark the instace as not scored
                if ($allNull == true) {
                    $isScored = false;
                }

                // Store the scoring status and total score for this instance
                $instance['is_scored'] = $isScored;
                $instance['total_score'] = $totalScore;

                // Store the answers for this training and timestamp
                $answers[$trainingIdValue][$instanceCreatedAt] = $trainingAnswers;
            }

            // Add the training details and instances to the training array
            $trainings[] = [
                'training_id' => $trainingIdValue,
                'training_name' => $trainingName,
                'instances' => $instances,
            ];
        }

        // Sort trainings by the latest instance date (newest first)
        usort($trainings, function ($a, $b) {
            $a_latest = strtotime($a['instances'][0]['created_at']);
            $b_latest = strtotime($b['instances'][0]['created_at']);
            return $b_latest - $a_latest;
        });

        // Render the 'user-answers' view the user, trainings, and answer data
        return $this->render('/admin/user-answers', [
            'user' => $user,
            'trainings' => $trainings,
            'answers' => $answers,
        ]);
    }


    public function actionSaveScores()
    {
        // Read and decode the JSON input from the request body
        $data = json_decode(file_get_contents('php://input'), true);

        // Check if the data is empty or not
        if (empty($data)) {
            // If no data was received, return an error response
            return $this->asJson(['status' => 'error', 'message' => 'No data received']);
        }

        // Loop through each item in the received data
        foreach ($data as $item) {
            // Check if the current item is a multiple-choice type question
            if ($item['type'] === 'multiple_choice') {
                // Update the score in the 'training_multiple_choice_answers' table
                Yii::$app->db->createCommand()
                    ->update(
                        'training_multiple_choice_user_answers',
                        ['score' => $item['score']],
                        ['id' => $item['tmcua_id']]
                    )
                    ->execute();

                // Update the final score in the 'training_answers' table for the multiple-choice question
                Yii::$app->db->createCommand()
                    ->update(
                        'training_answers',
                        ['score' => $item['final_score']],
                        ['question_id' => $item['question_id'], 'user_training_id' => $item['user_training_id']]
                    )
                    ->execute();
            } else {
                // If the question is not multiple-choice, update the score directly in the 'training_answers' table
                Yii::$app->db->createCommand()
                    ->update(
                        'training_answers',
                        ['score' => $item['score']],
                        ['question_id' => $item['question_id'], 'user_training_id' => $item['user_training_id']]
                    )
                    ->execute();
            }
        }

        // Return a success response after all scores have been saved
        return $this->asJson(['status' => 'success', 'message' => 'Scores successfully saved']);
    }
}