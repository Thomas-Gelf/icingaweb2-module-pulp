<?php

namespace Icinga\Module\Pulp;

use dipl\Html\Html;
use dipl\Html\Icon;
use dipl\Html\Table;
use dipl\Translation\TranslationHelper;
use Icinga\Data\ConfigObject;
use Icinga\Date\DateFormatter;

class RepositoryTable extends Table
{
    use TranslationHelper;

    protected $defaultAttributes = [
        'class' => 'common-table'
    ];

    protected $repos;

    protected $repoUsers = [];

    /** @var ConfigObject */
    protected $serverConfig;

    public function __construct($repos, ConfigObject $serverConfig)
    {
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
        $this->header()->add(Table::row([
            $this->translate('Repository / Importers'),
            $this->translate('Distributors'),
        ], null, 'th'));
        $count = 0;
        $body = $this->body();
        foreach ($this->sortBy($this->repos, 'display_name') as $repo) {
            $count++;
            $body->add(Table::row([
                [
                    Html::tag(
                        'nobr',
                        $repo->display_name
                        . ($repo->description ? ': ' . $repo->description : '')
                    ),
                    Html::tag('br'),
                    $this->formatImporters($repo),
                    Html::tag('br'),
                    $this->repoUnitCounts($repo),
                ],
                $this->formatDistributors($repo)
            ]));
            if ($count >= 30) {
                // break;
                continue;
            }
        }
    }

    protected function repoUnitCounts($repo)
    {
        $titles = [
            'distribution'           => $this->translate('Distributions'),
            'rpm'                    => $this->translate('RPMs'),
            'drpm'                   => $this->translate('DRPMs'),
            'package_group'          => $this->translate('Groups'),
            'package_category'       => $this->translate('Categories'),
            'package_environment'    => $this->translate('Environments'),
            'package_langpacks'      => $this->translate('Language Packs'),
            'erratum'                => $this->translate('Errata'),
            'yum_repo_metadata_file' => $this->translate('YUM Repo file'),
        ];

        $sums = [];
        $counts = $repo->content_unit_counts;
        foreach ($titles as $key => $title) {
            if (property_exists($counts, $key)) {
                $sums[] = Html::tag('span', [
                    'class' => ['badge', $key],
                ], $counts->$key . ' ' . $title);
            }
        }

        return $sums;
    }

    /**
     * @param $repo
     * @return array|string
     */
    protected function formatImporters($repo)
    {
        if (empty($repo->importers)) {
            return '-';
        }
        $result = [];

        foreach ($repo->importers as $raw) {
            $importer = new ImporterConfig($raw);
            // $cert = $importer->getConfig('ssl_client_cert');
            // if (null !== $cert) {
            //     $cert = Html::tag('pre', $cert);
            // }

            // Html::tag('pre', json_encode($raw, JSON_PRETTY_PRINT)),
            $result[] = Html::sprintf(
                '%s (%s: %s)',
                $importer->getConfig('feed', 'no feed'),
                $importer->get('id'), // or importer_type_id?
                $this->formatTime($importer->get(
                    'last_sync',
                    'never'
                ))
            );
        }

        return $result;
    }

    /**
     * @param $repo
     * @return Table|string
     */
    protected function formatDistributors($repo)
    {
        if (empty($repo->distributors)) {
            return '-';
        }

        $table = new Table;
        foreach ($repo->distributors as $raw) {
            $distributor = new DistributorConfig($raw);

            $table->body()->add(Table::row([[
                Html::tag('strong', $distributor->get('id')),
                ': ',
                $distributor->getConfig('checksum_type', [
                    'no checksum ',
                    Icon::create('warning-empty', ['style' => 'color: red'])
                ]),
                $distributor->get('auto_publish', false)
                    ? null
                    : ', no auto publish',
                $distributor->getConfig('http', false)
                    ? [' ', $this->repoLink($distributor, 'http')]
                    : null,
                $distributor->getConfig('https', false)
                    ? [' ', $this->repoLink($distributor, 'https')]
                    : null,
                ' ' . $distributor->getConfig('relative_url', '(no url)') . '',
                Html::tag('br'),
                'Published: ',
                $this->formatTime($distributor->get('last_publish', 'never')),
                ' (updated: ',
                $this->formatTime($distributor->get('last_updated', 'never'), false),
                ')',
                Html::tag('br'),
                'Users: ',
                $sums[] = $this->getDistributorUsesBadge($distributor),
            ]]));
        }

        return $table;
    }

    protected function repoLink(DistributorConfig $distributor, $schema = 'http')
    {
        $rel = $distributor->getConfig('relative_url');
        $url = sprintf(
            '%s:%s/%s',
            $schema,
            rtrim($this->serverConfig->get('repo_url'), '/'),
            $rel
        );

        return Html::tag('a', [
            'href'   => $url,
            'target' => '_blank'
        ], $schema);
    }

    protected function getDistributorUsesBadge(DistributorConfig $distributor)
    {
        $uses = $this->countDistributorUses($distributor);

        if ($uses > 0) {
            return Html::tag('span', [
                'class' => ['badge', 'ok'],
                'title' => $this->listSomeDistributorUses($distributor)
            ], $uses);
        } else {
            return $this->badge($uses, 'warning');
        }
    }

    protected function badge($count, $extraClass = null)
    {
        $classes = ['badge'];
        if ($extraClass !== null) {
            $classes[] = $extraClass;
        }

        return Html::tag('span', ['class' => $classes], $count);
    }

    protected function listSomeDistributorUses(DistributorConfig $distributor)
    {
        $max = 4;
        $url = $distributor->getConfig('relative_url');
        $nodes = $this->repoUsers[$url];
        if (count($nodes) < $max) {
            return implode(', ', $nodes);
        } else {
            return implode(', ', array_slice($nodes, 0, $max))
                . sprintf(' and %d more', count($nodes) - $max);
        }
    }

    protected function countDistributorUses(DistributorConfig $distributor)
    {
        $url = $distributor->getConfig('relative_url');
        if ($url === null) {
            return 0;
        }

        if (isset($this->repoUsers[$url])) {
            return count($this->repoUsers[$url]);
        }

        return 0;
    }

    protected function formatTime($time, $critical = true)
    {
        if ($time === null || $time === 'never') {
            return $this->badge('never');
        }

        $time = strtotime($time);
        $formatted = DateFormatter::timeAgo($time, true);

        if (! $critical) {
            return $formatted;
        }
        if (time() - $time > 86400) {
            return [$formatted . ' ', Icon::create('warning-empty', ['style' => 'color: red'])];
        } else {
            return [$formatted . ' ', Icon::create('ok', ['style' => 'color: green'])];
        }
    }

    protected function sortBy($repos, $key)
    {
        $new = [];
        foreach ($repos as $repo) {
            $new[$repo->$key] = $repo;
        }

        ksort($new);

        return array_values($new);
    }
}
