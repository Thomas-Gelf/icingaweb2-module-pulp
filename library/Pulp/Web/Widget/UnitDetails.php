<?php

namespace Icinga\Module\Pulp\Web\Widget;

use gipfl\IcingaWeb2\Icon;
use gipfl\IcingaWeb2\Widget\NameValueTable;
use gipfl\Translation\TranslationHelper;
use ipl\Html\Html;

class UnitDetails extends NameValueTable
{
    use TranslationHelper;

    protected $counts;

    protected $total;

    public function __construct($counts, $total)
    {
        $this->counts = $counts;
        $this->total = $total;
    }

    protected function assemble()
    {
        $titles = [
            'distribution'           => $this->translate('Distributions'),
            'iso'                    => $this->translate('ISO Images'),
            'rpm'                    => $this->translate('RPMs'),
            'srpm'                   => $this->translate('Source RPMs'),
            'drpm'                   => $this->translate('DRPMs'),
            'package_group'          => $this->translate('Groups'),
            'package_category'       => $this->translate('Categories'),
            'package_environment'    => $this->translate('Environments'),
            'package_langpacks'      => $this->translate('Language Packs'),
            'erratum'                => $this->translate('Errata'),
            'yum_repo_metadata_file' => $this->translate('YUM Repo file'),
        ];

        $counts = $this->counts;
        foreach ($titles as $key => $title) {
            if (\property_exists($counts, $key)) {
                $this->addNameValueRow($title, $counts->$key);
            }
        }

        foreach ($counts as $key => $count) {
            if (! isset($titles[$key])) {
                $this->addNameValueRow($key, $count);
            }
        }

        if ($this->total > 0) {
            $total = Html::tag('strong', $this->total);
        } else {
            $total = Html::tag('strong', [
                Icon::create('warning-empty', ['style' => 'color: red']),
                $this->total
            ]);
        }
        $this->addNameValueRow($this->translate('Total'), $total);
    }
}
