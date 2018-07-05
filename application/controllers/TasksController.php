<?php

namespace Icinga\Module\Pulp\Controllers;

use Icinga\Module\Pulp\TasksTable;

class TasksController extends Controller
{
    public function indexAction()
    {
        $this
            ->handleMainTabs()
            ->setAutorefreshInterval(60)
            ->addTitle('Tasks: %s', $this->getServerName())
            ->content()->add(new TasksTable($this->api()->getTasks()));
    }
}
