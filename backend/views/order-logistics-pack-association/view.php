<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\OrderLogisticsPackAssociation */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Order Logistics Pack Associations', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="order-logistics-pack-association-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'logistics_pack_id',
            'order_id',
            'admin_id',
            'add_time:datetime',
            'update_time:datetime',
        ],
    ]) ?>

</div>
