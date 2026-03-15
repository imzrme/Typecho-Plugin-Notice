<?php

namespace TypechoPlugin\Notice\libs\FormElement;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Typecho;

class MDSelect extends Typecho\Widget\Helper\Form\Element
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
            $wrapper = '<div class="mdui-panel notice-md3-field-panels"><div class="mdui-panel-item notice-md3-field notice-md3-field--select mdui-panel-item-open"><div class="mdui-panel-item-header notice-md3-field__header" role="button" tabindex="0" onclick="this.parentNode.classList.toggle(\'mdui-panel-item-open\');">' . $label . '</div><div class="mdui-panel-item-body notice-md3-field__body"><ul class="notice-md3-option-list" style="padding-left: 0; list-style: none !important" id="typecho-option-item-' . $name . '-' . self::$uniqueId . '">';
        } else {
            $wrapper = '<div class="mdui-panel notice-md3-field-panels"><div class="mdui-panel-item notice-md3-field notice-md3-field--select"><div class="mdui-panel-item-header notice-md3-field__header" role="button" tabindex="0" onclick="this.parentNode.classList.toggle(\'mdui-panel-item-open\');">' . $label . '</div><div class="mdui-panel-item-body notice-md3-field__body"><ul class="notice-md3-option-list" style="padding-left: 0; list-style: none !important" id="typecho-option-item-' . $name . '-' . self::$uniqueId . '">';
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

    private $_options = array();

    public function input(?string $name = null, ?array $options = null): ?Typecho\Widget\Helper\Layout
    {
        $input = new Typecho\Widget\Helper\Layout('select');
        $this->container($input->setAttribute('name', $name)
            ->setAttribute('id', $name . '-0-' . self::$uniqueId)
            ->setAttribute('class', 'mdui-select notice-md3-control notice-md3-control--select'));
        $this->inputs[] = $input;

        foreach ($options as $value => $label) {
            $this->_options[$value] = new Typecho\Widget\Helper\Layout('option');
            $input->addItem($this->_options[$value]->setAttribute('value', $value)->html($label));
        }

        return $input;
    }

    protected function inputValue($value)
    {
        foreach ($this->_options as $option) {
            $option->removeAttribute('selected');
        }

        if (isset($this->_options[$value])) {
            $this->_options[$value]->setAttribute('selected', 'true');
        }
    }
}
