<?php

namespace Icinga\Module\Pulp\Web\Widget;

use ipl\Html\BaseHtmlElement;

class Badge extends BaseHtmlElement
{
    protected $tag = 'span';

    protected $defaultAttributes = [
        'class' => 'badge',
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
