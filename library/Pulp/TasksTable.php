<?php

namespace Icinga\Module\Pulp;

use gipfl\Translation\TranslationHelper;
use gipfl\IcingaWeb2\Widget\NameValueTable;
use Icinga\Date\DateFormatter;
use ipl\Html\Html;
use ipl\Html\Table;

class TasksTable extends Table
{
    use TranslationHelper;

    protected $defaultAttributes = [
        'class' => 'common-table'
    ];

    protected $tasks;

    public function __construct($tasks)
    {
        $this->tasks = $tasks;
    }

    protected function assemble()
    {
        $count = 0;
        $body = $this->getBody();
        foreach ($this->tasks as $task) {
            $count++;
            $body->add(Table::row($this->formatTask($task)));
            if ($count >= 30) {
                break;
            }
        }
    }

    protected function formatTask($task)
    {
        return [
            // $task->task_id
            // waiting, skipped, running, suspended, finished, error and canceled
            $task->state,
            DateFormatter::timeAgo(strtotime($task->start_time)),
            DateFormatter::timeAgo(strtotime($task->finish_time)),
            $this->formatTags($task->tags),
            // $task->progress_report -> proprietary object
            // $task->result -> if any
            // $task->traceback -> deprecated, null or array
            // $task->spawned_tasks -> array of objects with uri/id of spawned tasks
            // $task->worker -> worker name or empty if not yet assigned
            // $task->queue  -> queue   "   "   " ...
            // $task->error  -> null or object
            Html::tag('pre', print_r($task, 1))
        ];
    }

    protected function formatError($task)
    {
        /*
         * https://docs.pulpproject.org/en/2.16/dev-guide/conventions/exceptions.html#error-details
          {
            "code": "PLP0018",
             "description": "Duplicate resource: foo",
             "data": {"resource_id": "foo"},
             "sub_errors": []
            }
        */
    }

    protected function formatTags($tags)
    {
        $table = new NameValueTable();
        foreach ($tags as $tag) {
            if (preg_match('/pulp:([^:]+):(.+)$/', $tag, $match)) {
                $table->addNameValueRow($match[1], $match[2]);
            }
        }

        return $table;
    }
}
