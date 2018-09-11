<?php

namespace Icinga\Module\Pulp;

use Icinga\Application\Config as IcingaConfig;
use Icinga\Module\Puppetdb\PuppetDbApi;
use InvalidArgumentException;
use RuntimeException;

class Config
{
    /** @var IcingaConfig */
    protected $servers;

    /** @var IcingaConfig */
    protected $puppetdb;

    public function __construct()
    {
        $this->servers = IcingaConfig::module('pulp', 'servers');
        $this->puppetdb = IcingaConfig::module('pulp', 'puppetdb');
    }

    public function getCacheDir()
    {
        return '/var/spool/icingaweb2/pulp';
    }

    /**
     * @return bool
     */
    public function hasServers()
    {
        return $this->servers->count() > 0;
    }

    /**
     * @return string
     */
    public function getDefaultServerName()
    {
        foreach ($this->servers as $name => $section) {
            return $name;
        }

        throw new RuntimeException(
            'There is no API connection in servers.ini'
        );
    }

    /**
     * @return bool
     */
    public function hasPuppetDb()
    {
        return $this->puppetdb->count() > 0;
    }

    /**
     * @return string
     */
    public function getDefaultPuppetDbName()
    {
        foreach ($this->puppetdb as $name => $section) {
            return $name;
        }

        throw new RuntimeException(
            'There is no PuppetDB connection in puppetdb.ini'
        );
    }

    /**
     * @return array
     */
    public function listServerNames()
    {
        $names = [];
        foreach ($this->servers as $name => $section) {
            $names[] = $name;
        }

        return $names;
    }

    /**
     * @return array
     */
    public function listPuppetDbNames()
    {
        $names = [];
        foreach ($this->puppetdb as $name => $section) {
            $names[] = $name;
        }

        return $names;
    }

    /**
     * @return array
     */
    public function listAllRepoUrls($name)
    {
        $urls = [];
        $section = $this->getServerConfig($name);
        $url = $section->get('repo_url');
        $urls[] = "http:$url";
        $urls[] = "https:$url";
        foreach (preg_split(
            '/\s*,\s*/',
            $section->get('alternative_repo_urls'),
            -1,
            PREG_SPLIT_NO_EMPTY
        ) as $url) {
            $urls[] = "http:$url";
            $urls[] = "https:$url";
        }

        return $urls;
    }

    /**
     * @param $name
     * @return \Icinga\Data\ConfigObject
     */
    public function getServerConfig($name)
    {
        return $this->servers->getSection($name);
    }

    /**
     * @param $name
     * @return \Icinga\Data\ConfigObject
     */
    public function getPuppetDbConfig($name)
    {
        return $this->puppetdb->getSection($name);
    }

    /**
     * @param $name
     * @return Api
     */
    public function getApi($name)
    {
        if (! $this->servers->hasSection($name)) {
            throw new InvalidArgumentException(
                "There is no such API connection in servers.ini: '$name'"
            );
        }

        $config = $this->getServerConfig($name);

        return new Api(
            $config->get('api_url'),
            $config->get('api_username'),
            $config->get('api_password'),
            $config->get('proxy')
        );
    }

    /**
     * @param $name
     * @return PuppetDbApi
     */
    public function getPuppetDb($name)
    {
        if (! $this->puppetdb->hasSection($name)) {
            throw new InvalidArgumentException(
                "There is no such PuppetDB connection in puppetdb.ini: '$name'"
            );
        }

        $config = $this->getPuppetDbConfig($name);

        return new PuppetDbApi(
            $config->get('api'),
            $config->get('certname'),
            $config->get('host')
        );
    }
}
