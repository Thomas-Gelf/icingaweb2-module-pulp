<?php

namespace Icinga\Module\Pulp\Web\Widget;

use gipfl\IcingaWeb2\Link;
use gipfl\Translation\TranslationHelper;
use Icinga\Module\Pulp\DistributorConfig;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

class DistributorUsageInfo extends HtmlDocument
{
    use TranslationHelper;

    protected $repoId;

    protected $distributor;

    protected $serverName;

    protected $allUsers;

    public function __construct($repoId, DistributorConfig $distributor, $users, $serverName)
    {
        $this->repoId = $repoId;
        $this->distributor = $distributor;
        $this->allUsers = $users;
        $this->serverName = $serverName;
    }

    protected function assemble()
    {
        $users = $this->listUsers();

        if (empty($users)) {
            $this->add([
                Alert::warning(),
                $this->translate('PuppetDB reports no related Yumrepo resource definitions')
            ]);
        } else {
            $max = 5;
            $list = Html::tag('ul', [
                'style' => 'list-style-type: none; margin: 0; padding: 0;'
            ]);
            $list->add(Html::wrapEach(\array_slice($users, 0, $max), 'li'));
            if (\count($users) > $max) {
                $linkLabel = \sprintf(
                    $this->translate('...and %d more'),
                    \count($users) - $max
                );
            } else {
                $linkLabel = $this->translate('Show all');
            }
            $list->add(Html::tag('li', Link::create(
                $linkLabel,
                'pulp/repository/users',
                [
                    'server' => $this->serverName,
                    'id'     => $this->repoId,
                ]
            )));

            $this->add($list);
        }
    }

    protected function listUsers()
    {
        $url = $this->distributor->getConfig('relative_url');
        if ($url === null) {
            return [];
        }

        if (isset($this->allUsers[$url])) {
            return $this->allUsers[$url];
        }

        return [];
    }
}
