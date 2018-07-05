<?php

namespace Icinga\Module\Pulp\Controllers;

class UnconfiguredController extends Controller
{
    public function indexAction()
    {
        if ($this->getConfig()->hasServers()) {
            $this->redirectNow('pulp');
        }
        $this
            ->setAutorefreshInterval(10)
            ->addSingleTab('Error')
            ->addTitle($this->translate('No PULP server has been configured'))
            ->showError('Please read the documentation and fix modules/pulp/servers.ini');
    }
}
