<?php

namespace Icinga\Module\Pulp\Controllers;

use Exception;
use Icinga\Module\Pulp\RepositoryTable;

class IndexController extends Controller
{
    public function indexAction()
    {
        $serverName = $this->getServerName();
        $this
            ->handleMainTabs()
            ->setAutorefreshInterval(300)
            ->addTitle('Repositories: %s', $serverName);

        try {
            $table = new RepositoryTable(
                $serverName,
                $this->getRepos(),
                $this->getConfig()->getServerConfig($serverName)
            );
            $table->setRepoUsers($this->getRepoUsage());
            $this->content()->add($table);
        } catch (Exception $e) {
            $this->showError($e->getMessage());
        }
    }
}
