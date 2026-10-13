(function () {
    function initialize() {
        var $ = window.jQuery;
        var rootElements = Array.prototype.filter.call(document.querySelectorAll('.notice-md3-config, .notice-md3-utility-page'), function (root) {
            return !root.hasAttribute('data-notice-ui-ready');
        });
        var panelSelector = '.notice-md3-section, .notice-md3-field';
        var headerSelector = '.notice-md3-section__header, .notice-md3-field__header';
        var controlSelector = 'input:not([type="hidden"]):not([type="submit"]), textarea, select';
        var nextId = 0;
        var activeSelect = null;

        function ensureId(element, prefix) {
            if (!element.id) {
                var id;
                do {
                    id = prefix + '-' + (++nextId);
                } while (document.getElementById(id));
                element.id = id;
            }
            return element.id;
        }

        function setPanelState(panel, open) {
            panel.classList.toggle('mdui-panel-item-open', open);
            Array.prototype.forEach.call(panel.children, function (child) {
                if (child.matches(headerSelector)) {
                    child.setAttribute('aria-expanded', String(open));
                }
            });
            if (!open && activeSelect && panel.contains(activeSelect.source[0])) {
                activeSelect.close(false);
            }
        }

        // Folding must work even when the CDN library is unavailable.
        rootElements.forEach(function (root) {
            root.setAttribute('data-notice-ui-ready', 'true');
            Array.prototype.forEach.call(root.querySelectorAll(panelSelector), function (panel) {
                var header;
                var body;
                Array.prototype.forEach.call(panel.children, function (child) {
                    if (child.matches(headerSelector)) { header = child; }
                    if (child.matches('.notice-md3-section__body, .notice-md3-field__body')) { body = child; }
                });
                if (header && body) {
                    var headingId = ensureId(header, 'notice-md3-heading');
                    header.setAttribute('aria-controls', ensureId(body, 'notice-md3-body'));
                    Array.prototype.forEach.call(panel.querySelectorAll('.notice-md3-control'), function (control) {
                        control.setAttribute('aria-labelledby', headingId);
                    });
                }
                setPanelState(panel, panel.classList.contains('mdui-panel-item-open'));
            });
            root.addEventListener('click', function (event) {
                var header = event.target.closest(headerSelector);
                if (header && root.contains(header)) {
                    var panel = header.parentNode;
                    setPanelState(panel, !panel.classList.contains('mdui-panel-item-open'));
                    return;
                }
                var action = event.target.closest('.showSettings, .hideSettings');
                if (action && root.contains(action)) {
                    var open = action.classList.contains('showSettings');
                    Array.prototype.forEach.call(root.querySelectorAll(panelSelector), function (panel) {
                        setPanelState(panel, open);
                    });
                }
            });
            root.addEventListener('keydown', function (event) {
                var header = event.target.closest(headerSelector);
                if (header && root.contains(header) && (event.key === 'Enter' || event.key === ' ' || event.key === 'Spacebar')) {
                    event.preventDefault();
                    if (!event.repeat) { header.click(); }
                }
            });
        });

        if (!$ || !rootElements.length) {
            return;
        }
        var roots = $(rootElements);

        function enhanceSelect(select, root) {
            var source = $(select);
            var wrapper = $('<div class="notice-md3-select"></div>').insertAfter(source);
            var trigger = $('<button type="button" class="notice-md3-control notice-md3-select-trigger" role="combobox" aria-haspopup="listbox" aria-expanded="false"><span class="notice-md3-select-value"></span></button>').appendTo(wrapper);
            var menu = $('<div class="notice-md3-select-menu" role="listbox" hidden></div>').appendTo(root);
            var menuId = ensureId(menu[0], 'notice-md3-select-options');
            var activeIndex = -1;
            var options = [];
            var search = '';
            var lastKeyTime = 0;
            trigger.attr('aria-controls', menuId);
            source.addClass('notice-md3-select-source').attr({'aria-hidden': 'true', tabindex: '-1'});

            function enabledIndices() {
                return options.map(function (item, index) {
                    return item.disabled ? -1 : index;
                }).filter(function (index) { return index !== -1; });
            }

            function setActive(index) {
                activeIndex = index;
                menu.children().removeClass('is-highlighted');
                if (index >= 0 && options[index]) {
                    var item = options[index].element;
                    item.addClass('is-highlighted');
                    trigger.attr('aria-activedescendant', item[0].id);
                    var top = item[0].offsetTop;
                    var bottom = top + item[0].offsetHeight;
                    if (top < menu[0].scrollTop) {
                        menu[0].scrollTop = top;
                    } else if (bottom > menu[0].scrollTop + menu[0].clientHeight) {
                        menu[0].scrollTop = bottom - menu[0].clientHeight;
                    }
                } else {
                    trigger.removeAttr('aria-activedescendant');
                }
            }

            function positionMenu() {
                var rect = trigger[0].getBoundingClientRect();
                if (rect.bottom < 0 || rect.top > window.innerHeight) {
                    close(false);
                    return;
                }
                var below = window.innerHeight - rect.bottom - 14;
                var above = rect.top - 14;
                var up = below < 180 && above > below;
                var width = Math.min(Math.max(rect.width, 180), window.innerWidth - 16);
                var left = Math.max(8, Math.min(rect.left, window.innerWidth - width - 8));
                menu.css({width: width, left: left, maxHeight: Math.max(60, Math.min(264, up ? above : below))});
                menu.css('top', up ? Math.max(8, rect.top - menu[0].offsetHeight - 6) : rect.bottom + 6);
                menu.toggleClass('is-above', up);
                wrapper.toggleClass('opens-up', up);
            }

            function close(focus) {
                menu.prop('hidden', true);
                trigger.attr('aria-expanded', 'false').removeAttr('aria-activedescendant');
                wrapper.removeClass('is-open opens-up');
                if (activeSelect === api) {
                    activeSelect = null;
                }
                if (focus) {
                    trigger[0].focus();
                }
            }

            function sync() {
                var selected = select.options[select.selectedIndex];
                trigger.find('.notice-md3-select-value').text(selected ? selected.text : '请选择');
                trigger.prop('disabled', select.disabled).attr('aria-disabled', String(select.disabled));
                var label = source.attr('aria-labelledby');
                if (!label) {
                    var labels = $(select.labels || []);
                    if (!labels.length) {
                        labels = source.closest('.typecho-option').find('.typecho-label');
                    }
                    label = labels.map(function () { return ensureId(this, 'notice-md3-select-label'); }).get().join(' ');
                }
                if (label) {
                    trigger.attr('aria-labelledby', label);
                    menu.attr('aria-labelledby', label);
                }
                ['aria-label', 'aria-describedby', 'aria-invalid'].forEach(function (attribute) {
                    var value = source.attr(attribute);
                    if (value === undefined) {
                        trigger.removeAttr(attribute);
                    } else {
                        trigger.attr(attribute, value);
                    }
                });
                options.forEach(function (item, index) {
                    item.element.attr('aria-selected', String(index === select.selectedIndex))
                        .toggleClass('is-selected', index === select.selectedIndex);
                });
                if (select.disabled) {
                    close(false);
                }
            }

            function renderOptions() {
                menu.empty();
                options = Array.prototype.map.call(select.options, function (option, index) {
                    var disabled = option.disabled || (option.parentNode.tagName === 'OPTGROUP' && option.parentNode.disabled);
                    var element = $('<div class="notice-md3-select-option" role="option"></div>').attr({
                        id: menuId + '-' + index,
                        'data-index': index,
                        'aria-disabled': String(disabled)
                    }).text(option.text).toggleClass('is-disabled', disabled).appendTo(menu);
                    return {element: element, disabled: disabled, text: option.text};
                });
                sync();
                if (activeSelect === api) {
                    setActive(select.selectedIndex);
                    positionMenu();
                }
            }

            function open() {
                if (select.disabled) {
                    return;
                }
                if (activeSelect) {
                    activeSelect.close(false);
                }
                sync();
                menu.prop('hidden', false);
                trigger.attr('aria-expanded', 'true');
                wrapper.addClass('is-open');
                activeSelect = api;
                positionMenu();
                var enabled = enabledIndices();
                setActive(enabled.indexOf(select.selectedIndex) >= 0 ? select.selectedIndex : (enabled[0] === undefined ? -1 : enabled[0]));
            }

            function choose(index) {
                if (index < 0 || !options[index] || options[index].disabled) {
                    return;
                }
                select.selectedIndex = index;
                sync();
                close(true);
                select.dispatchEvent(new Event('change', {bubbles: true}));
            }

            trigger.on('click', function () {
                if (activeSelect === api) {
                    close(false);
                } else {
                    open();
                }
            });

            trigger.on('keydown', function (event) {
                var key = event.key;
                var opened = activeSelect === api;
                if (key === 'Escape' || key === 'Tab') {
                    if (opened) {
                        close(false);
                        if (key === 'Escape') {
                            event.preventDefault();
                        }
                    }
                    return;
                }
                if (key === 'Enter' || key === ' ' || key === 'Spacebar') {
                    event.preventDefault();
                    if (!event.repeat) {
                        if (opened) {
                            choose(activeIndex);
                        } else {
                            open();
                        }
                    }
                    return;
                }
                var enabled = enabledIndices();
                if (key === 'ArrowDown' || key === 'ArrowUp' || key === 'Home' || key === 'End') {
                    event.preventDefault();
                    if (!opened) {
                        open();
                    }
                    if (key === 'Home' || key === 'End') {
                        setActive(enabled[key === 'Home' ? 0 : enabled.length - 1]);
                    } else if (opened) {
                        var next = enabled.indexOf(activeIndex) + (key === 'ArrowDown' ? 1 : -1);
                        setActive(enabled[Math.max(0, Math.min(next, enabled.length - 1))]);
                    }
                } else if (key && key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
                    event.preventDefault();
                    var now = Date.now();
                    search = (now - lastKeyTime > 700 ? '' : search) + key.toLocaleLowerCase();
                    lastKeyTime = now;
                    if (!opened) {
                        open();
                    }
                    var match = enabled.filter(function (index) {
                        return options[index].text.toLocaleLowerCase().indexOf(search) === 0;
                    })[0];
                    if (match !== undefined) {
                        setActive(match);
                    }
                }
            });

            menu.on('mousedown', function (event) { event.preventDefault(); });
            menu.on('click', '[role="option"]', function () { choose(Number($(this).attr('data-index'))); });
            source.on('change', sync);
            source.closest('form').on('reset', function () { setTimeout(sync, 0); });
            var api = {source: source, trigger: trigger, wrapper: wrapper, menu: menu, close: close, sync: sync, position: positionMenu};
            source.data('noticeSelect', api);
            renderOptions();
            if (window.MutationObserver) {
                new MutationObserver(renderOptions).observe(select, {
                    childList: true, subtree: true, characterData: true, attributes: true,
                    attributeFilter: ['disabled', 'selected', 'label']
                });
            }
        }

        function syncSelect(control) {
            var widget = $(control).data('noticeSelect');
            if (widget) {
                widget.sync();
            }
            return widget ? widget.trigger[0] : control;
        }

        roots.each(function () {
            var root = $(this);
            var errorState = [];
            var activeErrors = [];

            root.find('select').each(function () { enhanceSelect(this, root); });

            root.on('click', '.notice-md3-secret-toggle', function () {
                var input = document.getElementById($(this).attr('aria-controls'));
                if (!input || !root[0].contains(input)) {
                    return;
                }
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                $(this).text(show ? '隐藏' : '显示').attr({
                    'aria-label': show ? '隐藏密钥' : '显示密钥',
                    'aria-pressed': String(show)
                });
            });

            function syncChoices() {
                root.find('.notice-md3-choice').each(function () {
                    $(this).toggleClass('is-selected', $(this).find('input:checked').length > 0);
                });
            }

            syncChoices();
            root.on('change', '.notice-md3-choice input', syncChoices);

            // Display Typecho's existing errors; validation remains with Typecho.
            function syncErrors(focus) {
                var previousErrors = activeErrors;
                activeErrors = [];
                var newError = false;
                errorState.forEach(function (state) {
                    var control = $(state.control);
                    if (state.invalid === undefined) {
                        control.removeAttr('aria-invalid');
                    } else {
                        control.attr('aria-invalid', state.invalid);
                    }
                    var ids = (control.attr('aria-describedby') || '').split(/\s+/).filter(function (id) {
                        return id && state.ids.indexOf(id) === -1;
                    });
                    if (ids.length) {
                        control.attr('aria-describedby', ids.join(' '));
                    } else {
                        control.removeAttr('aria-describedby');
                    }
                    syncSelect(state.control);
                });
                errorState = [];
                var firstControl;

                root.find('.error.message').each(function () {
                    var error = $(this);
                    if (!$.trim(error.text()) || this.hidden || error.css('display') === 'none') {
                        return;
                    }
                    error.data('noticeError', true);
                    activeErrors.push(this);
                    var reveal = focus || previousErrors.indexOf(this) === -1;
                    newError = newError || reveal;
                    var field = error.closest('.notice-md3-field, .typecho-option');
                    var errorId = ensureId(this, 'notice-md3-error');
                    var controls = field.find(controlSelector);

                    if (reveal) {
                        error.parents(panelSelector).each(function () {
                            setPanelState(this, true);
                        });
                    }

                    controls.each(function () {
                        var control = $(this);
                        var state = errorState.filter(function (entry) {
                            return entry.control === control[0];
                        })[0];
                        if (!state) {
                            state = {control: this, invalid: control.attr('aria-invalid'), ids: []};
                            errorState.push(state);
                        }
                        var ids = (control.attr('aria-describedby') || '').split(/\s+/).filter(Boolean);
                        if (ids.indexOf(errorId) === -1) {
                            ids.push(errorId);
                            state.ids.push(errorId);
                        }
                        control.attr({'aria-invalid': 'true', 'aria-describedby': ids.join(' ')});
                        var focusTarget = syncSelect(this);
                        if (!firstControl && !this.disabled) {
                            firstControl = focusTarget;
                        }
                    });
                });

                if ((focus || newError) && firstControl) {
                    firstControl.focus();
                    firstControl.scrollIntoView({block: 'center', behavior: 'auto'});
                }
            }

            syncErrors(true);
            root.on('input change', controlSelector, function () {
                syncErrors(false);
            });

            if (window.MutationObserver) {
                var observer = new MutationObserver(function (mutations) {
                    var changed = false;
                    var added = false;
                    mutations.forEach(function (mutation) {
                        var target = $(mutation.target);
                        if (target.closest('.error.message').length || target.data('noticeError')) {
                            changed = true;
                        }
                        $(mutation.addedNodes).each(function () {
                            if ($(this).is('.error.message') || $(this).find('.error.message').length) {
                                changed = added = true;
                            }
                        });
                        $(mutation.removedNodes).each(function () {
                            if ($(this).is('.error.message') || $(this).find('.error.message').length) {
                                changed = true;
                            }
                        });
                    });
                    if (changed) {
                        syncErrors(added);
                    }
                });
                observer.observe(this, {
                    childList: true,
                    subtree: true,
                    characterData: true,
                    attributes: true,
                    attributeFilter: ['class', 'style', 'hidden']
                });
            }

            var editor = root.find('.notice-md3-editor-area')[0];
            if (editor) {
                var editorForm = $(editor).closest('form');
                var gutter = root.find('.notice-md3-editor-gutter');
                function syncEditor() {
                    var dirty = editor.value !== editor.defaultValue;
                    var lines = editor.value.split('\n').length;
                    var numbers = [];
                    for (var line = 1; line <= lines; line++) {
                        numbers.push(line);
                    }
                    gutter.find('pre').text(numbers.join('\n'));
                    root.find('.notice-md3-editor-status').text(dirty ? '有未保存的更改' : (editor.readOnly ? '只读模板' : '暂无更改'))
                        .toggleClass('is-dirty', dirty);
                    root.find('.notice-md3-editor-line-count').text(lines + ' 行');
                    root.find('.notice-md3-editor-reset').prop('disabled', !dirty || editor.readOnly);
                    gutter.scrollTop(editor.scrollTop);
                }
                syncEditor();
                $(editor).on('input', syncEditor).on('scroll', function () { gutter.scrollTop(editor.scrollTop); });
                root.on('click', '.notice-md3-editor-reset', function () { editorForm[0].reset(); });
                editorForm.on('reset', function () { setTimeout(syncEditor, 0); });
            }

            var form = root.find('.notice-md3-panels').closest('form');
            var submit = form.find('.submit_only');
            if (form.length && submit.length) {
                var initialValues = form.serialize();
                var status = $('<span class="notice-md3-save-status" role="status" aria-live="polite">暂无更改</span>');
                status.insertBefore(submit);
                function syncChanges() {
                    var dirty = form.serialize() !== initialValues;
                    status.text(dirty ? '有未保存的更改' : '暂无更改').toggleClass('is-dirty', dirty);
                }
                form.on('input change', ':input', syncChanges);
                form.on('reset', function () {
                    setTimeout(function () {
                        syncChanges();
                        syncChoices();
                        syncErrors(false);
                    }, 0);
                });
            }
        });

        $(document).on('mousedown', function (event) {
            if (activeSelect && !activeSelect.wrapper[0].contains(event.target) && !activeSelect.menu[0].contains(event.target)) {
                activeSelect.close(false);
            }
        });
        window.addEventListener('resize', function () {
            if (activeSelect) {
                activeSelect.position();
            }
        });
        window.addEventListener('scroll', function (event) {
            if (activeSelect && !activeSelect.menu[0].contains(event.target)) {
                activeSelect.position();
            }
        }, true);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
})();
