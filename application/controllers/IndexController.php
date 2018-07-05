<?php

namespace Icinga\Module\Pulp\Controllers;

use Exception;
use Icinga\Module\Pulp\RepositoryTable;
use RuntimeException;

class IndexController extends Controller
{
    public function indexAction()
    {
        $this
            ->handleMainTabs()
            ->setAutorefreshInterval(300)
            ->addTitle('Repositories: %s', $this->getServerName());

        try {
            $table = new RepositoryTable(
                $this->getRepos(),
                $this->getConfig()->getServerConfig($this->getServerName())
            );
            $table->setRepoUsers($this->getRepoUsers());
            $this->content()->add($table);
        } catch (Exception $e) {
            $this->showError($e->getMessage());
        }
    }

    protected function getRepoUsers()
    {
        return [];
    }

    protected function getRepos()
    {
        $filename = sprintf(
            '%s/repos-%s.json',
            $this->getConfig()->getCacheDir(),
            $this->getServerName()
        );
        if (! file_exists($filename) || ! is_readable($filename)) {
            throw new RuntimeException(sprintf(
                'Unable to read cached repositories for %s',
                $this->getServerName()
            ));
        }

        return json_decode(file_get_contents($filename));
    }
}
