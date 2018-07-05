<?php

namespace Icinga\Module\Pulp\Controllers;

use Exception;
use Icinga\Module\Pulp\StatusTable;

class StatusController extends Controller
{
    public function indexAction()
    {
        $this
            ->handleMainTabs()
            ->setAutorefreshInterval(9)
            ->addTitle('Status: %s', $this->getServerName());
        try {
            $this->content()->add(new StatusTable($this->api()->getStatus()));
        } catch (Exception $e) {
            $this->showError($e->getMessage());
        }
    }
}
