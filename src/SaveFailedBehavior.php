<?php

namespace silverslice\queueFailed;

use yii\base\Behavior;
use yii\queue\ExecEvent;
use yii\queue\Queue;

class SaveFailedBehavior extends Behavior
{
    /** @var QueueFailed component */
    public $queueFailed;

    /** @var string Queue component id. We need it to pass to the job's 'execute' method. */
    public $queue = 'queue';

    public function events()
    {
        return [
            Queue::EVENT_AFTER_ERROR => 'afterError',
        ];
    }

    /**
     * @param ExecEvent $event
     */
    public function afterError(ExecEvent $event)
    {
        if (!$event->retry) {
            $this->queueFailed->add($event->id, $event->job, $event->error, $this->queue);
        }
    }
}
