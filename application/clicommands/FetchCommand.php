<?php

namespace Icinga\Module\Pulp\Clicommands;

use Icinga\Application\Benchmark;
use Icinga\Application\Logger;
use Icinga\Cli\Command;
use Icinga\Module\Pulp\Config;
use RuntimeException;

class FetchCommand extends Command
{
    protected $pdbResults = [];

    public function init()
    {
        parent::init();
        $this->app->getModuleManager()->loadEnabledModules();
    }

    public function allAction()
    {
        $this->reposAction();
        $this->puppetdbAction();
    }

    public function reposAction()
    {
        $config = new Config();
        $cacheDir = $config->getCacheDir();
        $servers = $config->listServerNames();
        Benchmark::measure(sprintf(
            'Ready to fetch repositories from %d PULP servers',
            count($servers)
        ));
        foreach ($servers as $name) {
            $api = $config->getApi($name);
            $repos = [];
            $repositories = $api->get('repositories/');
            if (! $repositories) {
                throw new RuntimeException("Got no repositories from '$name'");
            }
            Benchmark::measure(sprintf(
                'Ready to fetch %d repositories from %s',
                count($repositories),
                $name
            ));
            foreach ($repositories as $repo) {
                Logger::debug('Fetching repository %s from %s', $repo->id, $name);
                $repos[] = $api->get('repositories/' . $repo->id . '/?details=true');
            }

            Benchmark::measure(sprintf('Done with %s, storing JSON', $name));
            file_put_contents("$cacheDir/repos-$name.json", json_encode($repos));
            Logger::info('Fetched repositories for %s', $name);
        }
    }

    public function puppetdbAction()
    {
        $config = new Config();
        $cacheDir = $config->getCacheDir();
        $servers = $config->listServerNames();
        $msg = sprintf(
            'Ready to fetch Yumrepos from %d PuppetDB server(s)',
            count($servers)
        );
        Benchmark::measure($msg);
        Logger::debug($msg);
        $this->pdbResults = [];
        foreach ($servers as $name) {
            $filename = "$cacheDir/repo-usage_$name.json";
            $cfg = $config->getServerConfig($name);
            $pdbName = $cfg->get('puppetdb');
            if ($pdbName === null) {
                if (file_exists($filename)) {
                    unlink($filename);
                }
                continue;
            }
            if (! isset($this->pdbResults[$pdbName])) {
                $pdb = $config->getPuppetDb($pdbName);
                $this->pdbResults[$pdbName] = $pdb->fetchResourcesByType('Yumrepo');
                Benchmark::measure("Fetched Yumrepos from '$pdbName', extracting usage");
            }
            $repositories = $this->pdbResults[$pdbName];
            if (! $repositories) {
                throw new RuntimeException("Got no Yumrepo instances from PuppetDB '$pdbName'");
            }

            $prefixes = $config->listAllRepoUrls($name);
            $usage = $this->buildRepoUsage($repositories, $prefixes);
            file_put_contents($filename, json_encode($usage));
        }
    }

    protected function buildRepoUsage($repositories, $urlPrefix)
    {
        $usage = [];
        foreach ($repositories as $repo) {
            if (! isset($repo->parameters->baseurl)) {
                continue;
            }
            $baseUrl = $repo->parameters->baseurl;

            $url = null;
            foreach ($urlPrefix as $prefix) {
                if (substr($baseUrl, 0, strlen($prefix)) === $prefix) {
                    $url = substr($baseUrl, strlen($prefix));
                    break;
                }
            }

            if ($url === null) {
                continue;
            }

            if (isset($usage[$url])) {
                $usage[$url][] = $repo->certname;
            } else {
                $usage[$url] = [$repo->certname];
            }
        }

        return $usage;
    }
}
