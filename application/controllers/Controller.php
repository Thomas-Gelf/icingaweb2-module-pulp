<?php

namespace Icinga\Module\Pulp\Controllers;

use dipl\Html\Html;
use dipl\Web\CompatController;
use Icinga\Exception\IcingaException;
use Icinga\Exception\ProgrammingError;
use Icinga\Module\Pulp\Api;
use Icinga\Module\Pulp\Config;

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

    /**
     * @param $message
     * @return $this
     */
    protected function showError($message)
    {
        $this->content()->add(Html::tag('p', [
            'class' => 'error'
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
