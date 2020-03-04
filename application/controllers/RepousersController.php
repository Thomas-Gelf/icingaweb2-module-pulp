<?php

namespace Icinga\Module\Pulp\Controllers;

use gipfl\IcingaWeb2\Link;
use ipl\Html\Table;

class RepousersController extends Controller
{
    public function indexAction()
    {
        $serverName = $this->params->getRequired('server');
        $url = $this->params->getRequired('url');
        $this->addSingleTab($this->translate('Repo Users'));
        $this->addTitle($this->translate('Systems using [%s]/%s'), $serverName, $url);
        $this->showMessage($this->translate(
            'PuppetDB reports a related Yumrepo for this systems'
        ));

        $users = $this->getRepoUsage();
        if (array_key_exists($url, $users)) {
            sort($users[$url]);
            if ($this->params->get('format') === 'csv') {
                $this->dumpCsv($users[$url]);
                exit;
            }

            $this->actions()->add(
                Link::create(
                    $this->translate('Download CSV'),
                    $this->url()->with('format', 'csv'),
                    null,
                    [
                        'target' => '_blank',
                        'class'  => 'icon-download'
                    ]
                )
            );
            $table = new Table();
            $table->addAttributes(['class' => 'common-table']);
            $table->getHeader()->add(Table::row(['Certname'], null, 'th'));
            foreach ($users[$url] as $host) {
                $table->add(Table::row([$host]));
            }
            $this->content()->add($table);
        } else {
            $this->showMessage($this->translate(
                'According to PuppetDB no Yumrepo has been configured for this URL'
            ), 'warning');
        }
    }
}
