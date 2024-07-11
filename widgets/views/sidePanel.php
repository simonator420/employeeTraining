<?php

use yii\helpers\Html;

?>

<div class="side-panel">
    <h3>Side Panel</h3>
    <p>This is a simple side panel.</p>
    <ul>
        <li><?= Html::a('Link 1', ['/site/index']) ?></li>
        <li><?= Html::a('Link 2', ['/site/about']) ?></li>
        <li><?= Html::a('Link 3', ['/site/contact']) ?></li>
    </ul>
</div>
