<?php
namespace TypechoPlugin\Notice\libs\FormElement;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Typecho;

class MDCustomLabel extends Typecho\Widget\Helper\Layout
{
    public function __construct($html)
    {
        $this->html($html);
        $this->start();
        $this->end();
    }

    public function start()
    {
    }

    public function end()
    {
    }
}

class MDEndSymbol extends Typecho\Widget\Helper\Layout
{
    public function __construct($num)
    {
        for ($i = 0; $i < $num; $i++) {
            $this->addItem(new MDCustomLabel("</div>"));
        }
    }

    public function start()
    {
    }

    public function end()
    {
    }
}

class MDTitle extends Typecho\Widget\Helper\Layout
{
    public function __construct($titleName, $subtitleName = null, $isOpen = true)
    {
        if ($isOpen) {
            $this->addItem(new MDCustomLabel('<div class="mdui-panel-item notice-md3-section mdui-panel-item-open">'));
        } else {
            $this->addItem(new MDCustomLabel('<div class="mdui-panel-item notice-md3-section">'));
        }

        $this->addItem(new MDCustomLabel(
            '<div class="mdui-panel-item-header notice-md3-section__header" role="button" tabindex="0" onclick="this.parentNode.classList.toggle(\'mdui-panel-item-open\');">' .
            $titleName .
            '<small class="mdui-panel-item-sub-header notice-md3-section__subtitle">' .
            $subtitleName .
            '</small></div>'
        ));

        $this->addItem(new MDCustomLabel('<div class="mdui-panel-item-body notice-md3-section__body">'));
    }

    public function start()
    {
    }

    public function end()
    {
    }
}
