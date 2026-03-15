<?php

namespace TypechoPlugin\Notice\libs\FormElement;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Typecho;

class MDTextarea extends Typecho\Widget\Helper\Form\Element
{
    public function start()
    {
    }

    public function end()
    {
        echo '</ul></div></div></div>';
    }

    public function __construct($name = null, array $options = null, $value = null, $label = null, $description = null, $isOpen = true)
    {
        if ($isOpen) {
            $wrapper = '<div class="mdui-panel notice-md3-field-panels"><div class="mdui-panel-item notice-md3-field notice-md3-field--textarea mdui-panel-item-open"><div class="mdui-panel-item-header notice-md3-field__header" role="button" tabindex="0" onclick="this.parentNode.classList.toggle(\'mdui-panel-item-open\');">' . $label . '</div><div class="mdui-panel-item-body notice-md3-field__body"><ul class="notice-md3-option-list" style="padding-left: 0; list-style: none !important" id="typecho-option-item-' . $name . '-' . self::$uniqueId . '">';
        } else {
            $wrapper = '<div class="mdui-panel notice-md3-field-panels"><div class="mdui-panel-item notice-md3-field notice-md3-field--textarea"><div class="mdui-panel-item-header notice-md3-field__header" role="button" tabindex="0" onclick="this.parentNode.classList.toggle(\'mdui-panel-item-open\');">' . $label . '</div><div class="mdui-panel-item-body notice-md3-field__body"><ul class="notice-md3-option-list" style="padding-left: 0; list-style: none !important" id="typecho-option-item-' . $name . '-' . self::$uniqueId . '">';
        }
        $this->addItem(new MDCustomLabel($wrapper));
        $this->name = $name;
        self::$uniqueId++;

        $this->init();
        $this->input = $this->input($name, $options);

        if (null !== $value) {
            $this->value($value);
        }

        if (null !== $description) {
            $this->description($description);
        }
    }

    public function input(?string $name = null, ?array $options = null): ?Typecho\Widget\Helper\Layout
    {
        $this->addItem(new MDCustomLabel('<div class="mdui-textfield notice-md3-input-wrap">'));
        $input = new Typecho\Widget\Helper\Layout('textarea', array(
            'id' => $name . '-0-' . self::$uniqueId,
            'name' => $name,
            'class' => 'mdui-textfield-input notice-md3-control'
        ));
        $this->container($input->setClose(false));
        $this->addItem(new MDCustomLabel('</div>'));
        $this->inputs[] = $input;

        return $input;
    }

    protected function inputValue($value)
    {
        $this->input->html(htmlspecialchars($value));
    }
}
