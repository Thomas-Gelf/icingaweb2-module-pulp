<?php

namespace Icinga\Module\Pulp;

use Icinga\Application\Config as IcingaConfig;
use InvalidArgumentException;
use RuntimeException;

class Config
{
    /** @var IcingaConfig */
    protected $servers;

    public function __construct()
    {
        $this->servers = IcingaConfig::module('pulp', 'servers');
    }

    public function getCacheDir()
    {
        return '/tmp';
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
}
