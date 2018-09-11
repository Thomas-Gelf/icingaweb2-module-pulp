<?php

namespace Icinga\Module\Pulp\Controllers;

use dipl\Html\Html;
use dipl\Html\Link;
use dipl\Html\Table;
use Exception;
use Icinga\Module\Pulp\RepositoryTable;
use RuntimeException;

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

    public function repousersAction()
    {
        $serverName = $this->params->getRequired('server');
        $url = $this->params->getRequired('url');
        $this->addSingleTab($this->translate('Repo Users'));
        $this->addTitle($this->translate('Systems using %s/%s'), $serverName, $url);
        $this->content()->add(Html::tag('p', [
            'class' => 'information'
        ], $this->translate(
            'PuppetDB reports a related Yumrepo for this systems'
        )));

        $users = $this->getRepoUsage();
        if (array_key_exists($url, $users)) {
            sort($users[$url]);
            if ($this->params->get('format') === 'csv') {
                $this->dumpCsv($users[$url]);
                exit;
            }

            $this->actions()->add(
                Link::create(
                    $this->translate('Download CSV'),
                    $this->url()->with('format', 'csv'),
                    null,
                    [
                        'target' => '_blank',
                        'class'  => 'icon-download'
                    ]
                )
            );
            $table = new Table();
            $table->addAttributes(['class' => 'common-table']);
            $table->header()->add(Table::row(['Certname'], null, 'th'));
            $body = $table->body();
            foreach ($users[$url] as $host) {
                $body->add(Table::row([$host]));
            }
            $this->content()->add($table);
        } else {
            $this->content()->add(Html::tag('p', [
                'class' => 'warning'
            ], $this->translate(
                'According to PuppetDB no Yumrepo has been configured for this URL'
            )));
        }
    }

    protected function dumpCsv($hosts)
    {
        header('Content-type: text/csv');
        foreach ($hosts as $host) {
            echo '"' . addcslashes($host, '";') . "\"\n";
        }
    }

    protected function getRepoUsage()
    {
        $filename = $this->getFilename('repo-usage_%s.json');
        if (file_exists($filename)) {
            return (array) json_decode(file_get_contents($filename));
        } else {
            return [];
        }
    }

    protected function getRepos()
    {
        $filename = $this->getFilename('repos-%s.json');
        if (! file_exists($filename) || ! is_readable($filename)) {
            throw new RuntimeException(sprintf(
                'Unable to read cached repositories for %s',
                $this->getServerName()
            ));
        }

        return json_decode(file_get_contents($filename));
    }

    protected function getFilename($pattern)
    {
        return sprintf(
            '%s/%s',
            $this->getConfig()->getCacheDir(),
            sprintf($pattern, $this->getServerName())
        );
    }
}
