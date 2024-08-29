<?php

namespace humhub\modules\employeeTraining\controllers;

use humhub\components\Controller;
use humhub\modules\user\models\Profile;
use humhub\modules\user\models\User;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use Yii;


/**
 * Controller for handling actions related to roles and user actions within the Employee Training module.
 */
class RoleController extends Controller
{

    /**
     * Displays the admin page for ADMIN users.
     *
     * This function retrieves various user and training-related data, including the latest answers,
     * counts of completed and open trainings, and training completion times, and then renders
     * the admin view with this data.
     *
     * @return string
     */
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
                SELECT MAX(created_at)
                FROM training_answers
                WHERE user_id = :userId
            ')
                ->bindValue(':userId', $user->id)
                ->queryScalar();

            // Store the latest completion time in the array
            $trainingCompleteTimes[$user->id] = $latest_training_complete_time;
        }

        // Retrieve all training from the database
        $trainings = Yii::$app->db->createCommand('SELECT * FROM training WHERE is_active = 1')->queryAll();

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

        // Render the admin view with the gathered data that also passed to the view
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
            'activeAssignedTrainingsCount' => $activeAssignedTrainingsCount,
        ]);
    }

    /**
     * Fetches users based on their role.
     *
     * @param string $role The role to filter users by.
     * @return array The list of users matching the specified role.
     */
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

    /**
     * Fetches users by their role and title.
     *
     * @param string $role The role to filter users by.
     * @param string $title The title to filter users by.
     * @return array The list of users matching the specified role and title.
     */
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

    /**
     * Fetches all profiles associated with a specific training.
     *
     * @param string $trainingId The ID of the training to filter profiles by.
     * @return array The list of profiles associated with the specified training.
     */
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

    /**
     * Fetches all profiles, adjusted for adding roles to users.
     *
     * This function is a variant of actionFetchAllProfiles, specifically for adding roles to users.
     *
     * @return array The list of profiles.
     */
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

    /**
     * Fetches all distinct storage locations from the profile table.
     *
     * @return array The list of distinct storage locations.
     */
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

    /**
     * Fetches all distinct storage locations from the profile table.
     *
     * @return array The list of distinct storage locations.
     */
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

    /**
     * Fetches distinct job titles based on a specific role.
     *
     * @param string $role The role to filter job titles by.
     * @return array The list of job titles matching the specified role.
     */
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

    /**
     * Fetches users filtered by title and/or location.
     *
     * @param string|null $title The title to filter users by (optional).
     * @param string|null $location The location to filter users by (optional).
     * @return array The list of users matching the specified filters.
     */
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
                    SELECT MAX(created_at)
                    FROM training_answers
                    WHERE user_id = :userId
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

    /**
     * Adds a role to selected users.
     *
     * @return array The success or failure status of the operation.
     */
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

    /**
     * Removes a role from selected users and sets it to 'user'.
     *
     * @return array The success or failure status of the operation.
     */
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

    /**
     * Displays the employee view based on the provided training ID.
     *
     * @param string $id The ID of the training to display for the employee.
     * @return string|\yii\web\Response
     */
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
}