<?php

namespace Icinga\Module\Pulp\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Pulp\DistributorConfig;
use Icinga\Module\Pulp\ImporterConfig;
use Icinga\Module\Pulp\Web\Widget\DistributorDetails;
use Icinga\Module\Pulp\Web\Widget\ImporterDetails;
use Icinga\Module\Pulp\Web\Widget\UnitDetails;
use ipl\Html\Html;

class RepositoryController extends Controller
{
    /**
     * @throws NotFoundError
     * @throws \Icinga\Exception\MissingParameterException
     */
    public function indexAction()
    {
        $this->addSingleTab($this->translate('Repository'));
        $repoId = $this->params->getRequired('id');
        $serverName = $this->getServerName();
        $serverConfig = $this->getConfig()->getServerConfig($serverName);


        $repo = $this->getRepo($repoId);
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
     * @param $id
     * @return mixed
     * @throws NotFoundError
     */
    protected function getRepo($id)
    {
        foreach ($this->getRepos() as $repo) {
            if ($repo->id === $id) {
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
