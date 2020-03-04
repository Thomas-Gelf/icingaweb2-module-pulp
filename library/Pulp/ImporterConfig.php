<?php

namespace Icinga\Module\Pulp;

class ImporterConfig extends PulpObjectWithConfig
{
    /**
     * ->config:
     * 'feed',
     * 'ssl_ca_cert',
     * 'ssl_client_cert',
     * 'ssl_client_key',
     * 'proxy_host',
     * 'proxy_port',
     */

    public function hasEverBeenSynchronized()
    {
        return $this->get('last_sync') !== null;
    }

    public function syncIsOutdated($expiration = 86400)
    {
        $time = \strtotime($this->get('last_sync'));

        return $time < (time() - $expiration);
    }
}
