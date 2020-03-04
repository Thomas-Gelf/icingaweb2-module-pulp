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
            ->setAutorefreshInterval(300);

        $this->checkForOutdatedFiles();

        try {
            $repos = $this->getRepos();
            $this->addTitle(\sprintf(
                $this->translate('%s: %d Repositories'),
                $serverName,
                \count($repos)
            ));
            $table = new RepositoryTable(
                $serverName,
                $repos,
                $this->getConfig()->getServerConfig($serverName)
            );
            $table->setRepoUsers($this->getRepoUsage());
            $this->content()->add($table);
        } catch (Exception $e) {
            $this->showError($e->getMessage());
        }
    }
}
