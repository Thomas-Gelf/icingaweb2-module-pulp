<?php

namespace Icinga\Module\Pulp\Clicommands;

use Icinga\Application\Benchmark;
use Icinga\Application\Logger;
use Icinga\Cli\Command;
use Icinga\Module\Pulp\Config;

class FetchCommand extends Command
{
    public function init()
    {
        parent::init();
        $this->app->getModuleManager()->loadEnabledModules();
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
}
