<?php

namespace Icinga\Module\Pulp\Web\Widget;

use ipl\Html\BaseHtmlElement;

class StateHint extends BaseHtmlElement
{
    protected $tag = 'p';

    protected $defaultAttributes = [
        'class' => 'state-hint',
    ];

    /**
     * Badge constructor.
     * @param $content
     * @param array|string|null $extraClasses
     */
    public function __construct($content, $extraClasses = null)
    {
        $this->addAttributes(['class' => $extraClasses]);
        $this->setContent($content);
    }
}
