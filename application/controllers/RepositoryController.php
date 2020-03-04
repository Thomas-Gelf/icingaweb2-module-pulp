<?php

namespace Icinga\Module\Pulp\Controllers;

use gipfl\IcingaWeb2\Link;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Pulp\DistributorConfig;
use Icinga\Module\Pulp\ImporterConfig;
use Icinga\Module\Pulp\Web\Widget\DistributorDetails;
use Icinga\Module\Pulp\Web\Widget\ImporterDetails;
use Icinga\Module\Pulp\Web\Widget\UnitDetails;
use ipl\Html\Html;
use ipl\Html\Table;

class RepositoryController extends Controller
{
    /**
     * @throws NotFoundError
     * @throws \Icinga\Exception\MissingParameterException
     */
    public function indexAction()
    {
        $this->getTabs()->activate('index');
        $serverName = $this->getServerName();
        $serverConfig = $this->getConfig()->getServerConfig($serverName);
        $repo = $this->getRepo();
        $this->addTitle(\sprintf($this->translate('Repository: %s'), $repo->display_name));
        if ($repo->description) {
            $this->content()->add(Html::tag('p', $repo->description));
        }
        $this->content()->add([
            Html::tag('h3', $this->translate('Importers')),
            $this->formatImporters($repo),
            Html::tag('h3', $this->translate('Contents')),
            new UnitDetails($repo->content_unit_counts, $repo->total_repository_units),
            Html::tag('h3', $this->translate('Distributors')),
            $this->formatDistributors($serverConfig, $repo),
        ]);
    }

    /**
     * @throws NotFoundError
     * @throws \Icinga\Exception\MissingParameterException
     */
    public function usersAction()
    {
        $this->getTabs()->activate('users');
        $serverName = $this->getServerName();
        $repo = $this->getRepo();
        $url = null;
        foreach ($repo->distributors as $raw) {
            $distributor = new DistributorConfig($raw);
            $currentUrl = $distributor->getConfig('relative_url');
            if ($url !== null && $currentUrl !== $url) {
                $this->addCritical(\sprintf(
                    $this->translate(
                        'This Repository has multiple distribution URLs,'
                        . ' this is not supported: "%s" VS "%s"'
                    ),
                    $url,
                    $currentUrl
                ));
                return;
            }
            $url = $currentUrl;
        }
        if ($url === null) {
            $this->addCritical($this->translate('This Repository has no distribution URL'));
            return;
        }
        $this->addTitle($this->translate('Systems using [%s]/%s'), $serverName, $url);

        $users = $this->getRepoUsage();
        if (isset($users[$url]) && \count($users[$url]) > 0) {
            $users = $users[$url];
            \sort($users);
            $this->addHint($this->translate(
                'PuppetDB reports related Yumrepo resources for the following systems'
            ));
            $this->offerDownload($users);
            $table = new Table();
            $table->addAttributes(['class' => 'common-table']);
            $table->getHeader()->add(Table::row(['Certname'], null, 'th'));
            foreach ($users as $host) {
                $table->add(Table::row([$host]));
            }
            $this->content()->add($table);
        } else {
            $this->addWarning($this->translate(
                'PuppetDB reports no related Yumrepo resource definitions'
            ));
        }
    }

    protected function offerDownload($users)
    {
        if ($this->params->get('format') === 'csv') {
            $this->dumpCsv($users);
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
    }

    protected function getTabs()
    {
        $params = [
            'id'     => $this->getParam('id'),
            'server' => $this->getServerName(),
        ];

        return $this->tabs()->add('index', [
            'label' => $this->translate('Repository'),
            'url'   => 'pulp/repository',
            'urlParams' => $params
        ])->add('users', [
            'label' => $this->translate('Users'),
            'url'   => 'pulp/repository/users',
            'urlParams' => $params
        ]);
    }

    /**
     * @return mixed
     * @throws NotFoundError
     * @throws \Icinga\Exception\MissingParameterException
     */
    protected function getRepo()
    {
        $repoId = $this->params->getRequired('id');

        foreach ($this->getRepos() as $repo) {
            if ($repo->id === $repoId) {
                return $repo;
            }
        }

        throw new NotFoundError('No such repository has been found');
    }

    /**
     * @param $repo
     * @return array|string
     */
    protected function formatImporters($repo)
    {
        if (empty($repo->importers)) {
            return $this->translate('This Repository has no Importer');
        }
        $result = [];

        foreach ($repo->importers as $raw) {
            $result[] = new ImporterDetails(new ImporterConfig($raw));
        }

        return $result;
    }

    /**
     * @param $serverConfig
     * @param $repo
     * @return array|string
     */
    protected function formatDistributors($serverConfig, $repo)
    {
        if (empty($repo->distributors)) {
            return $this->translate('This Repository has no Distributor');
        }

        $result = [];
        foreach ($repo->distributors as $raw) {
            $result[] = new DistributorDetails($serverConfig, new DistributorConfig($raw));
        }

        return $result;
    }
}
