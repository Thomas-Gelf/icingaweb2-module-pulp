<?php

namespace Icinga\Module\Pulp\Web\Widget;

use gipfl\IcingaWeb2\Widget\NameValueTable;
use gipfl\Translation\TranslationHelper;
use Icinga\Data\ConfigObject;
use Icinga\Module\Pulp\DistributorConfig;
use ipl\Html\Html;

class DistributorDetails extends NameValueTable
{
    use TranslationHelper;

    /** @var ConfigObject */
    protected $serverConfig;

    /** @var DistributorConfig */
    protected $distributor;

    public function __construct(ConfigObject $serverConfig, DistributorConfig $distributor)
    {
        $this->serverConfig = $serverConfig;
        $this->distributor = $distributor;
    }

    protected function assemble()
    {
        $d = $this->distributor;
        $this->addNameValuePairs([
            $this->translate('Distributor')   => Html::tag('strong', $d->get('id')),
            $this->translate('Checksum Type') => $d->getConfig('checksum_type', [
                'no checksum ',
                Alert::critical()
            ]),
            $this->translate('Auto publish') => $this->yesNo($d->get('auto_publish', false)),
            $this->translate('HTTP') => $d->getConfig('http', false)
                ? $this->repoLink('http')
                : $this->translate('No'),
            $this->translate('HTTPS') => $d->getConfig('https', false)
                ? $this->repoLink('https')
                : $this->translate('No'),
            $this->translate('Relative URL') => $d->getConfig('relative_url', '(no url)'),
            $this->translate('Published') => Time::formatWithExpirationCheck($d->get('last_publish', 'never')),
            $this->translate('Updated') => Time::format($d->get('last_updated', 'never')),
        ]);
/*
        $table->add(Table::row([[
            Html::tag('br'),
            'Users: ',
            $sums[] = $this->getDistributorUsesBadge($distributor),
        ]]));
*/
    }

    protected function repoLink($schema = 'http')
    {
        $rel = $this->distributor->getConfig('relative_url');
        $url = sprintf(
            '%s:%s/%s',
            $schema,
            \rtrim($this->serverConfig->get('repo_url'), '/'),
            $rel
        );

        return Html::tag('a', [
            'href'   => $url,
            'target' => '_blank'
        ], $url);
    }

    protected function yesNo($value)
    {
        return $value
            ? $this->translate('Yes')
            : $this->translate('No');
    }
}
