<?php

namespace TypechoPlugin\Notice\libs\FormElement;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Typecho;

class MDText extends Typecho\Widget\Helper\Form\Element
{
    private $inputType = 'text';

    public function start()
    {
    }

    public function end()
    {
        echo '</ul></div></div></div>';
    }

    public function __construct($name = null, array $options = null, $value = null, $label = null, $description = null, $isOpen = true, $inputType = 'text')
    {
        $this->inputType = $inputType;
        if ($isOpen) {
            $wrapper = '<div class="mdui-panel notice-md3-field-panels"><div class="mdui-panel-item notice-md3-field notice-md3-field--text mdui-panel-item-open"><div class="mdui-panel-item-header notice-md3-field__header" role="button" tabindex="0" aria-expanded="' . ($isOpen ? 'true' : 'false') . '"><span class="notice-md3-field__name">' . $label . '</span><span class="notice-md3-fold" aria-hidden="true"></span></div><div class="mdui-panel-item-body notice-md3-field__body"><ul class="notice-md3-option-list" style="padding-left: 0; list-style: none !important" id="typecho-option-item-' . $name . '-' . self::$uniqueId . '">';
        } else {
            $wrapper = '<div class="mdui-panel notice-md3-field-panels"><div class="mdui-panel-item notice-md3-field notice-md3-field--text"><div class="mdui-panel-item-header notice-md3-field__header" role="button" tabindex="0" aria-expanded="' . ($isOpen ? 'true' : 'false') . '"><span class="notice-md3-field__name">' . $label . '</span><span class="notice-md3-fold" aria-hidden="true"></span></div><div class="mdui-panel-item-body notice-md3-field__body"><ul class="notice-md3-option-list" style="padding-left: 0; list-style: none !important" id="typecho-option-item-' . $name . '-' . self::$uniqueId . '">';
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
        $id = $name . '-0-' . self::$uniqueId;
        $isPassword = $this->inputType === 'password';
        $wrapperClass = 'mdui-textfield notice-md3-input-wrap' . ($isPassword ? ' notice-md3-secret-wrap' : '');
        $wrapper = new Typecho\Widget\Helper\Layout('div', array('class' => $wrapperClass));
        $this->container($wrapper);
        $input = new Typecho\Widget\Helper\Layout('input', array(
            'id' => $id,
            'name' => $name,
            'type' => $this->inputType,
            'class' => 'mdui-textfield-input notice-md3-control'
        ));
        $wrapper->addItem($input);
        if ($isPassword) {
            $toggle = new Typecho\Widget\Helper\Layout('button', array(
                'type' => 'button',
                'class' => 'notice-md3-secret-toggle',
                'aria-controls' => $id,
                'aria-label' => _t('显示密钥'),
                'aria-pressed' => 'false'
            ));
            $wrapper->addItem($toggle->html(_t('显示')));
        }
        $this->inputs[] = $input;

        return $input;
    }

    protected function inputValue($value)
    {
        $this->input->setAttribute('value', htmlspecialchars($value));
    }
}
