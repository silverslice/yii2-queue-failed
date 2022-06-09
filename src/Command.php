<?php

namespace silverslice\queueFailed;

use yii\console\Controller;
use yii\console\ExitCode;
use yii\console\widgets\Table;
use yii\helpers\Console;

/**
 * Manages failed queue jobs.
 */
class Command extends Controller
{
    /** @var QueueFailed */
    public $queueFailed;

    /**
     * @var string
     */
    public $defaultAction = 'list';

    public $class;

    protected $jobStartedAt;

    /**
     * {@inheritdoc}
     */
    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            $actionID === 'run' || $actionID === 'clear'
                ? ['class']
                : []
        );
    }

    /**
     * Displays all failed jobs.
     */
    public function actionList()
    {
        $messages = $this->queueFailed->getAll();
        if (!$messages) {
            $this->stdout("No failed jobs found");
            $this->stdout(PHP_EOL);
            return ExitCode::OK;
        }

        $rows = [];
        foreach ($messages as $message) {
            $rows[] = [$message['id'], $message['class'], $this->formatDate($message['failed_at'])];
        }

        $table = (new Table())
            ->setHeaders(['Id', 'Class', 'Failed at'])
            ->setRows($rows)
            ->run();
        $this->stdout($table);

        return ExitCode::OK;
    }

    /**
     * Displays detailed information about a job by id.
     *
     * @param string $id Job ID
     * @return int
     */
    public function actionInfo($id)
    {
        $message = $this->queueFailed->getById($id);
        if (!$message) {
            $this->stdout("Job not found", Console::FG_RED);
            $this->stdout(PHP_EOL);
            return 1;
        }

        $this->stdout("Id", Console::FG_BLUE, Console::BOLD);
        $this->stdout(PHP_EOL);
        $this->stdout($message['id']);
        $this->stdout(PHP_EOL . PHP_EOL);

        $this->stdout("Queue", Console::FG_BLUE, Console::BOLD);
        $this->stdout(PHP_EOL);
        $this->stdout($message['queue']);
        $this->stdout(PHP_EOL . PHP_EOL);

        $this->stdout("Class", Console::FG_BLUE, Console::BOLD);
        $this->stdout(PHP_EOL);
        $this->stdout($message['class']);
        $this->stdout(PHP_EOL . PHP_EOL);

        $this->stdout("Failed at", Console::FG_BLUE, Console::BOLD);
        $this->stdout(PHP_EOL);
        $this->stdout($this->formatDate($message['failed_at']));
        $this->stdout(PHP_EOL . PHP_EOL);

        $this->stdout("Job", Console::FG_BLUE, Console::BOLD);
        $this->stdout(PHP_EOL);
        $this->stdout($message['job']);
        $this->stdout(PHP_EOL . PHP_EOL);

        $this->stdout("Error", Console::FG_BLUE, Console::BOLD);
        $this->stdout(PHP_EOL);
        $this->stdout($message['error'], Console::FG_GREY);
        $this->stdout(PHP_EOL . PHP_EOL);

        return ExitCode::OK;
    }

    /**
     * Executes a job by id.
     *
     * @param string $id Job ID
     * @return int
     */
    public function actionExec($id)
    {
        $message = $this->queueFailed->getById($id);
        if (!$message) {
            $this->stdout("Job not found", Console::FG_RED);
            $this->stdout(PHP_EOL);
            return 1;
        }

        $this->execute($message);

        return ExitCode::OK;
    }

    /**
     * Executes all jobs. Pass --class option to filter jobs by class.
     *
     * @return int
     */
    public function actionRun()
    {
        $messages = $this->queueFailed->getAll($this->class);
        if (!$messages) {
            $this->stdout("No failed jobs found");
            $this->stdout(PHP_EOL);
            return ExitCode::OK;
        }

        $success = 0;
        $failed = 0;
        foreach ($messages as $message) {
            $res = $this->execute($message);
            if ($res) {
                $success++;
            } else {
                $failed++;
            }
        }

        $this->stdout(PHP_EOL);
        $this->stdout('Total: ', Console::BOLD);
        $this->stdout(count($messages));
        $this->stdout(PHP_EOL);
        $this->stdout('Success: ', Console::BOLD);
        $this->stdout($success);
        $this->stdout(PHP_EOL);
        $this->stdout('Failed: ', Console::BOLD);
        $this->stdout($failed);
        $this->stdout(PHP_EOL);

        return ExitCode::OK;
    }

    /**
     * Removes a job by id.
     *
     * @param string $id Job ID
     * @return int
     */
    public function actionRemove($id)
    {
        $message = $this->queueFailed->getById($id);
        if (!$message) {
            $this->stdout("Job not found", Console::FG_RED);
            $this->stdout(PHP_EOL);
            return 1;
        }

        $this->queueFailed->remove($id);
        $this->stdout("Job was removed", Console::FG_GREEN);
        $this->stdout(PHP_EOL);

        return ExitCode::OK;
    }

    /**
     * Clears failed jobs. Pass --class option to filter jobs by class.
     *
     * @return int
     */
    public function actionClear()
    {
        $count = $this->queueFailed->clear($this->class);
        if ($count) {
            if ($count === 1) {
                $this->stdout("$count job was removed", Console::FG_GREEN);
            } else {
                $this->stdout("$count jobs were removed", Console::FG_GREEN);
            }
        } else {
            $this->stdout("No jobs found");
        }
        $this->stdout(PHP_EOL);

        return ExitCode::OK;
    }

    protected function logStart(array $message)
    {
        $this->stdout(date('Y-m-d H:i:s'), Console::FG_YELLOW);
        $this->stdout(" [{$message['id']}] {$message['class']}", Console::FG_GREY);
        $this->stdout(' - ', Console::FG_YELLOW);
        $this->stdout('Started', Console::FG_GREEN);
        $this->stdout(PHP_EOL);
    }

    protected function logError(array $message, \Throwable $error)
    {
        $this->stdout(date('Y-m-d H:i:s'), Console::FG_YELLOW);
        $this->stdout(" [{$message['id']}] {$message['class']}", Console::FG_GREY);
        $this->stdout(' - ', Console::FG_YELLOW);
        $this->stdout('Error', Console::BG_RED);
        if ($this->jobStartedAt) {
            $duration = number_format(round(microtime(true) - $this->jobStartedAt, 3), 3);
            $this->stdout(" ($duration s)", Console::FG_YELLOW);
        }
        $this->stdout(PHP_EOL);
        $this->stdout('> ' . get_class($error) . ': ', Console::FG_RED);
        $message = explode("\n", ltrim($error->getMessage()), 2)[0]; // First line
        $this->stdout($message, Console::FG_GREY);
        $this->stdout(PHP_EOL);
        $this->stdout('Stack trace:', Console::FG_GREY);
        $this->stdout(PHP_EOL);
        $this->stdout($error->getTraceAsString(), Console::FG_GREY);
        $this->stdout(PHP_EOL);
    }

    protected function logDone(array $message)
    {
        $this->stdout(date('Y-m-d H:i:s'), Console::FG_YELLOW);
        $this->stdout(" [{$message['id']}] {$message['class']}", Console::FG_GREY);
        $this->stdout(' - ', Console::FG_YELLOW);
        $this->stdout('Done', Console::FG_GREEN);
        $duration = number_format(round(microtime(true) - $this->jobStartedAt, 3), 3);
        $memory = round(memory_get_peak_usage(false)/1024/1024, 2);
        $this->stdout(" ($duration s, $memory MiB)", Console::FG_YELLOW);
        $this->stdout(PHP_EOL);
    }

    /**
     * @param array $message
     * @return bool
     */
    protected function execute(array $message)
    {
        try {
            $this->logStart($message);
            $this->jobStartedAt = microtime(true);
            $job = unserialize($message['job']);
            $job->execute(\Yii::$app->get($message['queue']));
            $this->queueFailed->remove($message['id']);
            $this->logDone($message);
            return true;
        } catch (\Throwable $e) {
            $this->logError($message, $e);
            return false;
        }
    }

    protected function formatDate($date)
    {
        return \Yii::$app->formatter->asDatetime($date, 'yyyy-MM-dd HH:mm:ss');
    }
}

