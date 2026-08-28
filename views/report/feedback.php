<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var string|null $name */

$this->title = 'Report Feedback';
?>
<div class="report-feedback">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>Hi <?= Html::encode($name) ?>, please tell us what you think about this report.</p>

    <form method="post">
        <textarea name="comment" rows="4" cols="50"></textarea>
        <br>
        <button type="submit">Send feedback</button>
    </form>
</div>
