Manage failed queued jobs in Yii 2
============================================================

When your job fails after max number of attempts in Yii 2, it is removed from a queue as completed.
This extension saves failed jobs in database table and helps to view and retry them later.

## Install

`composer require silverslice/yii2-queue-failed`

Apply database migration:

```shell
yii migrate --migrationPath=@vendor/silverslice/yii2-queue-failed/src/migrations/
```

## Configuration

Add `queueFailed` component to the console application config file:

```php
return [
    'components' => [
        'queueFailed' => [
            'class' => silverslice\queueFailed\QueueFailed::class,
        ],
    ],
];
```

Add `queueFailed` component to the `bootstrap`:

```php
return [
    'bootstrap' => [
        'queue', 'queueFailed'
    ],
    // ...
]
```

## Usage in console

#### Show all failed jobs:

```shell
yii queue-failed/list

╔════╤═══════════════════════════╤═════════════════════╗
║ Id │ Class                     │ Failed at           ║
╟────┼───────────────────────────┼─────────────────────╢
║ 1  │ app\models\jobs\FailedJob │ 2022-06-06 06:14:32 ║
╚════╧═══════════════════════════╧═════════════════════╝
```

Command displays job ID, job class and failure time. The job ID may be used to execute failed job again.

#### Show detailed information about a job by ID:

```shell
yii queue-failed/info ID
```

Command displays additional information about the job (job payload and error).


#### Execute a job by ID:

```shell
yii queue-failed/exec ID
```

#### Execute all jobs:

```shell
yii queue-failed/run
```

Pass --class option to filter jobs by class:

```shell
yii queue-failed/run --class='app\models\jobs\FailedJob'
```

#### Remove a job by ID:

```shell
yii queue-failed/remove ID
```

#### Clear all failed jobs:

```shell
yii queue-failed/clear
```
Pass --class option to filter jobs by class.

## Notes

Jobs are saved in `queue_failed` table by default.
You can change table name in the config (also you need to change name in migration):

```php
'queueFailed' => [
    'class' => silverslice\queueFailed\QueueFailed::class,
    'tableName' => 'failed_jobs'
],
```

Extension attaches behavior to save failed jobs to the `queue` component by default.
Change queue component name or add more queue components in the config if you need:

```php
'queueFailed' => [
    'class' => silverslice\queueFailed\QueueFailed::class,
    'queue' => ['queue', 'queueDb'],
],
```

Extension registers its own console commands based on its component id.
You can change it however you like:

```php
'failed' => [
    'class' => silverslice\queueFailed\QueueFailed::class,
    'queue' => ['queue', 'queueDb'],
],
```

Then use in console:

```shell
yii failed/list
```