<?php

namespace Icinga\Module\Pulp;

use gipfl\IcingaWeb2\Link;
use gipfl\Translation\TranslationHelper;
use Icinga\Data\ConfigObject;
use Icinga\Module\Pulp\Web\Widget\Alert;
use Icinga\Module\Pulp\Web\Widget\Badge;
use ipl\Html\Table;

class RepositoryTable extends Table
{
    use TranslationHelper;

    protected $defaultAttributes = [
        'class'            => 'common-table table-row-selectable values-table',
        'data-base-target' => '_next',
    ];

    protected $serverName;

    protected $repos;

    protected $repoUsers = [];

    /** @var ConfigObject */
    protected $serverConfig;

    public function __construct($serverName, $repos, ConfigObject $serverConfig)
    {
        $this->serverName = $serverName;
        $this->repos = $repos;
        $this->serverConfig = $serverConfig;
    }

    /**
     * @param array $users reponame => [ hostname, ... ]
     * @return $this
     */
    public function setRepoUsers(array $users)
    {
        $this->repoUsers = $users;

        return $this;
    }

    protected function assemble()
    {
        $this->getHeader()->add(Table::row([
            $this->translate('Repository'),
            $this->translate('Units'),
            $this->translate('Import'),
            $this->translate('Distribution'),
            $this->translate('Users'),
        ], null, 'th'));
        $body = $this->getBody();
        foreach ($this->sortBy($this->repos, 'id') as $repo) {
            $body->add(Table::row([
                Link::create($repo->id, 'pulp/repository', [
                    'id' => $repo->id
                ], [
                    'data-base-target' => '_next'
                ]),
                $repo->total_repository_units === 0 ? Alert::warning([
                    'title' => $this->translate('This repository is empty')
                ]) : $repo->total_repository_units,
                $this->summarizeImporters($repo),
                $this->summarizeDistribution($repo),
                $this->summarizeUsage($repo),
            ]));
        }
    }

    protected function summarizeDistribution($repo)
    {
        $alerts = [];
        foreach ($repo->distributors as $raw) {
            $distributor = new DistributorConfig($raw);
            $checksumType = $distributor->getConfig('checksum_type');
            if (empty($checksumType)) {
                $alerts[] = Alert::critical(['title' => 'No checksums']);
            }
            if ($checksumType === 'sha1') {
                $alerts[] = Alert::warning(['title' => 'SHA1 checksums in use']);
            }
        }

        return $alerts;
    }

    protected function summarizeImporters($repo)
    {
        $alerts = [];
        foreach ($repo->importers as $raw) {
            $importer = new ImporterConfig($raw);
            if ($importer->hasEverBeenSynchronized()) {
                if ($importer->syncIsOutdated()) {
                    $alerts[] = Alert::warning([
                        'title' => 'Synchronization is outdated'
                    ]);
                }
            } else {
                $alerts[] = Alert::warning(['title' => 'Importer has never been synchronized']);
            }
        }

        return $alerts;
    }

    /**
     * @param $repo
     * @return \gipfl\IcingaWeb2\Icon|int
     */
    protected function summarizeUsage($repo)
    {
        $uses = 0;

        foreach ($repo->distributors as $raw) {
            $distributor = new DistributorConfig($raw);
            $uses += $this->countDistributorUses($distributor);
        }

        if ($uses === 0) {
            return Alert::warning([
                'title' => $this->translate('This distributor is unused')
            ]);
        }

        return $uses;
    }

    protected function countDistributorUses(DistributorConfig $distributor)
    {
        $url = $distributor->getConfig('relative_url');
        if ($url === null) {
            return 0;
        }

        if (isset($this->repoUsers[$url])) {
            return \count($this->repoUsers[$url]);
        }

        return 0;
    }

    protected function sortBy($repos, $key)
    {
        $new = [];
        foreach ($repos as $repo) {
            $new[$repo->$key] = $repo;
        }
        \ksort($new);

        return \array_values($new);
    }
}
