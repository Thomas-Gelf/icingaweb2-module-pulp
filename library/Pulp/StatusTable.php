<?php

namespace Icinga\Module\Pulp;

use gipfl\IcingaWeb2\Icon;
use gipfl\Translation\TranslationHelper;
use gipfl\IcingaWeb2\Widget\NameValueTable;
use Icinga\Date\DateFormatter;
use InvalidArgumentException;
use ipl\Html\Html;

class StatusTable extends NameValueTable
{
    use TranslationHelper;

    protected $status;

    public function __construct($status)
    {
        if (! is_object($status)) {
            throw new InvalidArgumentException('Got invalid status data');
        }
        $this->status = $status;
    }

    protected function assemble()
    {
        $status = $this->status;
        $this->addNameValuePairs([
            $this->translate('Pulp Version') => $status->versions->platform_version,
            $this->translate('API Version') => $status->api_version,
            $this->translate('Messaging Connection') => $this->booleanIcon(
                $status->messaging_connection->connected
            ),
            $this->translate('Database Connection') => $this->booleanIcon(
                $status->database_connection->connected
            ),
            $this->translate('Workers (Heartbeat)') => $this->showWorkers(
                $status->known_workers
            ),
        ]);
    }

    protected function showWorkers($workers)
    {
        $output = [];
        $list = [];
        foreach ($workers as $worker) {
            $list[$worker->_id] = Html::sprintf(
                '%s (%s)',
                $worker->_id,
                Html::tag('span', [
                    'class' => 'time-ago'
                ], DateFormatter::timeAgo(strtotime($worker->last_heartbeat)))
            );
        }
        ksort($list);
        foreach ($list as $info) {
            $output[] = $info;
            $output[] = Html::tag('br');
        }

        return $output;
    }

    protected function booleanIcon($value)
    {
        return $value ? Icon::create('ok') : Icon::create('attention-circled');
    }
}
