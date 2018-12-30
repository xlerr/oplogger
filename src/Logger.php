<?php

namespace oplogger;

use Yii;
use oplogger\models\OperateLog;
use yii\base\Component;
use yii\base\ModelEvent;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;
use yii\helpers\Json;

class Logger extends Component
{
    public $logs = [];

    public $except = [];

    public function init()
    {
        parent::init();

        $this->except[] = OperateLog::class;
    }

    public function insertLog(AfterSaveEvent $event, $isUpdate = false)
    {
        $changedAttributes = $event->changedAttributes;
        if (empty($changedAttributes)) {
            return null;
        }

        /** @var ActiveRecord $model */
        $model = $event->sender;

        if (in_array(get_class($model), $this->except)) {
            return null;
        }

        $log = [
            'table' => $model::getTableSchema()->name,
            'new' => Json::encode($changedAttributes),
            'old' => Json::encode(array_intersect_key($model->getOldAttributes(), $changedAttributes)),
            'user_id' => Yii::$app->user->id,
        ];

        array_push($this->logs, $log);
    }

    public function updateLog(AfterSaveEvent $event)
    {
        $this->insertLog($event, true);
    }

    public function deleteLog(ModelEvent $event)
    {
        /** @var ActiveRecord $model */
        $model = $event->sender;

        if (in_array(get_class($model), $this->except)) {
            return null;
        }

        $log = [
            'table' => $model::getTableSchema()->name,
            'new' => '',
            'old' => Json::encode($model->getAttributes()),
            'user_id' => Yii::$app->user->id,
        ];

        array_push($this->logs, $log);
    }

    public function __destruct()
    {
        OperateLog::getDb()->createCommand()->batchInsert(OperateLog::tableName(), [
            'table',
            'new',
            'old',
            'user_id',
        ], $this->logs)->execute();
    }
}
