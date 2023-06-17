<?php

namespace silverslice\queueFailed;

use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\console\Application as ConsoleApp;
use yii\db\Connection;
use yii\db\Query;
use yii\di\Instance;
use yii\helpers\Inflector;
use yii\queue\JobInterface;

class QueueFailed extends Component implements BootstrapInterface
{
    /**
     * @var Connection|array|string
     */
    public $db = 'db';

    /**
     * @var string table name
     */
    public $tableName = '{{%queue_failed}}';

    /** @var string|array Queue component id */
    public $queue = 'queue';

    /**
     * @var string command class name
     */
    public $commandClass = Command::class;
    /**
     * @var array of additional options of command
     */
    public $commandOptions = [];

    public function init()
    {
        $this->db = Instance::ensure($this->db, Connection::class);
    }

    public function bootstrap($app)
    {
        // register console commands
        if ($app instanceof ConsoleApp) {
            $app->controllerMap[$this->getCommandId()] = [
                    'class' => $this->commandClass,
                    'queueFailed' => $this,
                ] + $this->commandOptions;
        }

        // attach behavior to each queue components
        $queues = (array)$this->queue;
        foreach ($queues as $id) {
            \Yii::$app->get($id)->attachBehavior('queueFailed', [
                'class' => SaveFailedBehavior::class,
                'queue' => $id,
                'queueFailed' => $this,
            ]);
        }
    }

    /**
     * Saves job in failed table.
     *
     * @param string|null $jobId unique id of the job
     * @param JobInterface $job
     * @param \Throwable $error
     * @param string $queue Queue component id
     * @return void
     * @throws \yii\db\Exception
     */
    public function add($jobId, JobInterface $job, $error, $queue)
    {
        $this->db->createCommand()->insert($this->tableName, [
            'queue' => $queue,
            'class' => get_class($job),
            'original_job_id' => $jobId,
            'job' => serialize($job),
            'error' => $error,
            'failed_at' => time(),
        ])->execute();
    }

    /**
     * Returns all failed jobs.
     *
     * @param string $class Class to filter jobs
     * @return array
     */
    public function getAll($class = null)
    {
        $query = (new Query())
            ->from($this->tableName)
            ->orderBy('id');
        if ($class) {
            $query->andWhere(['class' => $class]);
        }
        return $query->all($this->db);
    }

    /**
     * Returns job by id.
     *
     * @param $id
     * @return array|bool
     */
    public function getById($id)
    {
        return (new Query())
            ->from($this->tableName)
            ->orderBy('id DESC')
            ->andWhere(['id' => $id])
            ->one($this->db);
    }

    /**
     * Removes job by id.
     *
     * @param $id
     * @return bool
     * @throws \yii\db\Exception
     */
    public function remove($id)
    {
        return (bool)$this->db->createCommand()
            ->delete($this->tableName, ['id' => $id])
            ->execute();
    }

    /**
     * Removes all jobs.
     *
     * @param $class
     * @return int
     * @throws \yii\db\Exception
     */
    public function clear($class)
    {
        $cond = [];
        if ($class) {
            $cond['class'] = $class;
        }
        return $this->db->createCommand()
            ->delete($this->tableName, $cond)
            ->execute();
    }

    /**
     * @return string command id
     * @throws
     */
    protected function getCommandId()
    {
        foreach (\Yii::$app->getComponents(false) as $id => $component) {
            if ($component === $this) {
                return Inflector::camel2id($id);
            }
        }
        throw new InvalidConfigException('QueueFailed must be an application component.');
    }
}
