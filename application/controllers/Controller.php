<?php

namespace Icinga\Module\Pulp\Controllers;

use gipfl\IcingaWeb2\CompatController;
use Icinga\Date\DateFormatter;
use Icinga\Exception\IcingaException;
use Icinga\Exception\ProgrammingError;
use Icinga\Module\Pulp\Api;
use Icinga\Module\Pulp\Config;
use Icinga\Module\Pulp\Web\Widget\StateHint;
use ipl\Html\Html;
use RuntimeException;

abstract class Controller extends CompatController
{
    protected $pulpConfig;

    protected $serverName;

    protected function getConfig()
    {
        if ($this->pulpConfig === null) {
            $this->pulpConfig = new Config();
        }

        return $this->pulpConfig;
    }

    /**
     * @return Api
     */
    protected function api()
    {
        return $this->getConfig()->getApi($this->getServerName());
    }

    /**
     * @return string
     */
    protected function getServerName()
    {
        $config = $this->getConfig();
        if (! $config->hasServers()) {
            $this->redirectNow('pulp/unconfigured');
        }

        if ($this->serverName === null) {
            $name = $this->params->get('server');
            if ($name === null) {
                $this->serverName = $config->getDefaultServerName();
            } else {
                $this->serverName = $name;
            }
        }

        return $this->serverName;
    }

    protected function dumpCsv($hosts)
    {
        header('Content-type: text/csv');
        foreach ($hosts as $host) {
            echo '"' . \addcslashes($host, '";') . "\"\n";
        }
    }

    protected function checkForOutdatedFiles()
    {
        $filename = $this->getFilename('repos-%s.json');
        if (! \file_exists($filename)) {
            // There is an exception, so here we don't care
            return;
        }

        if (\filemtime($filename) < \time() - 86400) {
            $this->addWarning(\sprintf(
                $this->translate(
                    'Repository cache is outdated, %s has been created %s'
                ),
                $filename,
                DateFormatter::timeAgo(\filemtime($filename))
            ));
        }

        $filename = $this->getFilename('repo-usage_%s.json');
        if (! \file_exists($filename)) {
            return;
        }
        if (\filemtime($filename) < \time() - 86400) {
            $this->addWarning(\sprintf(
                $this->translate(
                    'PuppetDB cache is outdated, %s has been created %s'
                ),
                $filename,
                DateFormatter::timeAgo(\filemtime($filename))
            ));
        }
    }

    protected function addWarning($text)
    {
        $this->content()->add(new StateHint($text, 'warning'));
    }

    protected function addCritical($text)
    {
        $this->content()->add(new StateHint($text, 'critical'));
    }

    protected function getRepoUsage()
    {
        $filename = $this->getFilename('repo-usage_%s.json');
        if (\file_exists($filename)) {
            return (array) \json_decode(\file_get_contents($filename));
        } else {
            return [];
        }
    }

    protected function getRepos()
    {
        $filename = $this->getFilename('repos-%s.json');
        if (! \file_exists($filename) || ! \is_readable($filename)) {
            throw new RuntimeException(sprintf(
                'Unable to read cached repositories for %s',
                $this->getServerName()
            ));
        }

        return \json_decode(\file_get_contents($filename));
    }

    protected function getFilename($pattern)
    {
        return \sprintf(
            '%s/%s',
            $this->getConfig()->getCacheDir(),
            \sprintf($pattern, $this->getServerName())
        );
    }

    /**
     * @param $message
     * @return $this
     */
    protected function showError($message)
    {
        return $this->showMessage($message, 'error');
    }

    /**
     * @param $message
     * @param string $class
     * @return $this
     */
    protected function showMessage($message, $class = 'information')
    {
        $this->content()->add(Html::tag('p', [
            'class' => $class
        ], $message));

        return $this;
    }

    /**
     * @return $this
     */
    protected function handleMainTabs()
    {
        if ($name = $this->params->get('server')) {
            $params = ['server' => $name];
        } else {
            $params = [];
        }

        try {
            $this->tabs()->add('index', [
                'url'       => 'pulp',
                'urlParams' => $params,
                'label'     => $this->translate('Pulp Repositories'),
            ])->add('status', [
                'url'       => 'pulp/status',
                'urlParams' => $params,
                'label'     => $this->translate('Status'),
            ])/*->add('tasks', [ // not yet
                'url'       => 'pulp/tasks',
                'urlParams' => $params,
                'label'     => $this->translate('Tasks'),
            ])*/->activate($this->getRequest()->getControllerName());
        } catch (IcingaException $e) {
            $this->addSingleTab($this->translate('Error'));
            $this->showError('Failed to handle tabs: ' . $e->getMessage());
        }

        return $this;
    }

    /**
     * Suppress errors
     *
     * @param $interval
     * @return $this
     */
    public function setAutorefreshInterval($interval)
    {
        try {
            parent::setAutorefreshInterval($interval);
        } catch (ProgrammingError $e) {
            $this->showError(
                "Unable to set autorefresh to $interval: " . $e->getMessage()
            );
        }

        return $this;
    }
}
