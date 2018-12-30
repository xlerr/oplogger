<?php

namespace oplogger;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\db\BaseActiveRecord;
use yii\log\Dispatcher;

class Module extends \yii\base\Module implements BootstrapInterface
{
    public $controllerNamespace = __NAMESPACE__ . '\\controllers';

    public $logger = [
        'class' => Logger::class,
    ];

    public function bootstrap($app)
    {
        if (is_string($this->logger) || is_array($this->logger)) {
            $this->logger = Yii::createObject($this->logger);
        }

        $app->on($app::EVENT_BEFORE_REQUEST, function () {
            Event::on(BaseActiveRecord::class, BaseActiveRecord::EVENT_AFTER_INSERT, [$this->logger, 'insertLog']);
        });
    }
}
