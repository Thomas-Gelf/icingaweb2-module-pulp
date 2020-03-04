<?php

namespace Icinga\Module\Pulp\Web\Widget;

use gipfl\IcingaWeb2\Widget\NameValueTable;
use gipfl\Translation\TranslationHelper;
use Icinga\Module\Pulp\ImporterConfig;

class ImporterDetails extends NameValueTable
{
    use TranslationHelper;

    public function __construct(ImporterConfig $importer)
    {
        $cert = $importer->getConfig('ssl_client_cert');
        if (null === $cert) {
            $certInfo = '-';
        } else {
            if (\strpos($cert, 'ENTITLEMENT')) {
                $certInfo = 'This importer uses an entitlement certificate';
            } else {
                $certInfo = 'This importer uses an entitlement certificate';
            }
            // $cert = [' ', Icon::create('lock', ['title' => $title])];
        }

        if ($proxy = $importer->getConfig('proxy_host')) {
            $proxy .= ':' . $importer->getConfig('proxy_port');
        } else {
            $proxy = '-';
        }
        // there is also ssl_ca_cert, a CA cert chain
        // ssl_client_cert is a string, with:
        // -----BEGIN CERTIFICATE-----
        // xxx
        // -----END CERTIFICATE-----
        // -----BEGIN ENTITLEMENT DATA-----
        // xxx
        // -----END ENTITLEMENT DATA-----
        // -----BEGIN RSA SIGNATURE-----
        // xxx
        // -----END RSA SIGNATURE-----
        // ssl_client_key has BEGIN RSA PRIVATE KEY

        $this->addNameValuePairs([
            $this->translate('Importer')    => $importer->get('id'),
            $this->translate('Feed')        => $importer->getConfig('feed', 'no feed'),
            $this->translate('Proxy')       => $proxy,
            $this->translate('SSL Verification') => $this->yesNo($importer->getConfig('ssl_verify', false)),
            $this->translate('Client Cert') => $certInfo,
            $this->translate('Last Sync')   => Time::formatWithExpirationCheck($importer->get(
                'last_sync',
                'never'
            )),
        ]);
    }

    protected function yesNo($value)
    {
        return $value
            ? $this->translate('Yes')
            : $this->translate('No');
    }
}
