<?php

use yii\helpers\Html;

?>

<?php
// Fetch all active trainings assigned to the user
$activeTrainings = Yii::$app->db->createCommand('
    SELECT ut.training_id, ut.deadline, t.name as training_name
    FROM user_training ut
    JOIN training t ON t.id = ut.training_id
    WHERE ut.user_id = :userId AND ut.assigned_training = 1;
    ')
    ->bindValue(':userId', Yii::$app->user->id)
    ->queryAll();

// Determine the heading based on the number of trainings
$trainingCount = count($activeTrainings);
$heading = $trainingCount > 1 ? Yii::t('employeeTraining', "Mandatory Trainings") : Yii::t('employeeTraining', "Mandatory Training");
?>

<div class="side-panel">
    <h3> <?= $heading ?></h3>
    <p class="training-message">
        <?= Yii::t('employeeTraining', "Dear <b>{firstName}</b>, you have been assigned {trainingCount, plural, one{a mandatory training} other{mandatory trainings}}. Please complete {trainingCount, plural, one{it} other{them}} at your earliest convenience.", [
            'firstName' => Html::encode($firstName),
            'trainingCount' => $trainingCount,
        ]) ?>
    </p>

    <?php
    // Loop through each active training and display its corresponding button, name, and deadline
    foreach ($activeTrainings as $activeTraining) {
        $trainingId = $activeTraining['training_id'];
        $trainingName = $activeTraining['training_name'];
        $deadline = $activeTraining['deadline'];
        ?>
        <p class="training-name"><b><?= Html::encode(Yii::t('employeeTraining', $trainingName)) ?></b></p>
        <p class="deadline-message">
            <?= Yii::t('employeeTraining', "Time left to complete training is <b><span id='countdown-{$trainingId}'></span></b>.") ?>
        </p>
        <div class="text-center">
            <?= Html::a(Yii::t('employeeTraining', 'Go to Training'), ['/employeeTraining/role/employee', 'id' => $trainingId], ['class' => 'btn btn-primary']) ?>
        </div>
        <br> <!-- Add spacing between buttons -->

        <!-- Add a JavaScript block to handle the countdown timer -->
        <script>
            (function() {
                var countdownElement = document.getElementById('countdown-<?= $trainingId ?>');
                var deadline = new Date('<?= date('Y-m-d H:i:s', strtotime($deadline)) ?>').getTime();

                var updateCountdown = setInterval(function() {
                    var now = new Date().getTime();
                    var distance = deadline - now;

                    if (distance <= 0) {
                        clearInterval(updateCountdown);
                        countdownElement.innerHTML = 'Deadline has passed!';
                    } else {
                        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        var seconds = Math.floor((distance % (1000 * 60)) / 1000);

                        countdownElement.innerHTML = days + 'd ' + hours + 'h ' + minutes + 'm ' + seconds + 's';
                    }
                }, 1000);
            })();
        </script>
        <?php
    }
    ?>
</div>

<style>
    .side-panel {
        background-color: #fff;
        padding: 20px;
        margin-bottom: 15px;
        border: 3px solid #f08080;
        border-radius: 4px;
        text-align: center;
    }

    .side-panel h3 {
        margin-top: 0;
        font-size: 1.5em;
    }

    .side-panel .training-message {
        margin-bottom: 20px;
        color: #d9534f;
        font-size: 1.2em;
    }

    .side-panel .deadline-message {
        margin-bottom: 20px;
        color: rgb(85, 85, 85);
        font-size: 1.1em;
    }

    .side-panel .btn {
        background-color: #d9534f;
        color: #fff;
        text-decoration: none;
        padding: 10px 20px;
        border-radius: 4px;
        display: inline-block;
        transition: 0.3s;
    }

    .side-panel .btn:hover {
        background-color: darkslategray;
        text-decoration: none;
        cursor: pointer;
    }
</style>