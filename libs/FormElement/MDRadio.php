<?php

namespace TypechoPlugin\Notice\libs\FormElement;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Typecho;

class MDRadio extends Typecho\Widget\Helper\Form\Element
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
            $wrapper = '<div class="mdui-panel notice-md3-field-panels"><div class="mdui-panel-item notice-md3-field notice-md3-field--radio mdui-panel-item-open"><div class="mdui-panel-item-header notice-md3-field__header" role="button" tabindex="0" onclick="this.parentNode.classList.toggle(\'mdui-panel-item-open\');">' . $label . '</div><div class="mdui-panel-item-body notice-md3-field__body"><ul class="typecho-option notice-md3-option-list" id="typecho-option-item-' . $name . '-' . self::$uniqueId . '">';
        } else {
            $wrapper = '<div class="mdui-panel notice-md3-field-panels"><div class="mdui-panel-item notice-md3-field notice-md3-field--radio"><div class="mdui-panel-item-header notice-md3-field__header" role="button" tabindex="0" onclick="this.parentNode.classList.toggle(\'mdui-panel-item-open\');">' . $label . '</div><div class="mdui-panel-item-body notice-md3-field__body"><ul class="typecho-option notice-md3-option-list" id="typecho-option-item-' . $name . '-' . self::$uniqueId . '">';
        }
        $this->addItem(new MDCustomLabel($wrapper));
        $this->name = $name;
        self::$uniqueId++;
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
        foreach ($options as $value => $label) {
            $this->_options[$value] = new Typecho\Widget\Helper\Layout('input');
            $item = new Typecho\Widget\Helper\Layout('li', array('class' => 'notice-md3-option-row'));
            $id = $this->name . '-' . $this->filterValue($value);
            $this->inputs[] = $this->_options[$value];

            $item->addItem(new MDCustomLabel('<label class="mdui-radio notice-md3-choice">'));
            $item->addItem($this->_options[$value]->setAttribute('name', $this->name)
                ->setAttribute('type', 'radio')
                ->setAttribute('value', $value)
                ->setAttribute('id', $id));
            $item->addItem(new MDCustomLabel('<i class="mdui-radio-icon"></i><span class="notice-md3-choice__label">' . $label . '</span></label>'));

            $this->container($item);
        }

        return current($this->_options);
    }

    protected function inputValue($value)
    {
        foreach ($this->_options as $option) {
            $option->removeAttribute('checked');
        }

        if (isset($this->_options[$value])) {
            $this->value = $value;
            $this->_options[$value]->setAttribute('checked', 'true');
            $this->input = $this->_options[$value];
        }
    }
}
