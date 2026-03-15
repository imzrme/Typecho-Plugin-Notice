<?php
include "header.php";
include "menu.php";
TypechoPlugin\Notice\libs\Config::style();
$current = $request->get('type', 'mail');
$title = $current == 'mail' ? 'Notice 插件邮件配置测试' :
    ($current == 'qmsg'? 'Notice 插件Qmsg酱配置测试': 
    ($current == 'msgraph' ? 'Notice 插件Microsoft Graph配置测试' : 
    ($current == 'telegram' ? 'Notice 插件Telegram Bot配置测试' : 'Notice 插件Server酱配置测试')))
?>

<script>
    (function () {
        var title = 'Notice 插件配置测试';
        var currentTitle = document.title || '';
        var parts = currentTitle.split(' - ');
        document.title = parts.length > 1 ? title + ' - ' + parts.slice(1).join(' - ') : title;

        function upgradeInlineNotices() {
            var notices = document.querySelectorAll('.message');
            var baseOffset = window.innerWidth <= 640 ? 104 : 88;

            notices.forEach(function (notice, index) {
                if (document.body && notice.parentNode !== document.body) {
                    document.body.appendChild(notice);
                }
                notice.classList.add('notice-md3-inline-snackbar');
                notice.style.setProperty('--notice-md3-inline-snackbar-offset', (baseOffset + index * 76) + 'px');
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', upgradeInlineNotices);
        } else {
            upgradeInlineNotices();
        }

        window.addEventListener('load', upgradeInlineNotices);
        window.addEventListener('resize', upgradeInlineNotices);

        var noticeObserver = new MutationObserver(function () {
            upgradeInlineNotices();
        });

        if (document.documentElement) {
            noticeObserver.observe(document.documentElement, {childList: true, subtree: true});
        }
    })();
</script>

    <div class="main notice-md3-shell notice-md3-utility-page notice-md3-test-page">
        <div class="body container">
            <div class="notice-md3-utility-stack">
                <section class="notice-md3-hero notice-md3-hero--utility">
                    <div class="notice-md3-hero__backdrop"></div>
                    <div class="notice-md3-hero__badge">测试</div>
                    <div class="notice-md3-hero__main">
                        <div class="notice-md3-hero__copy">
                            <h2 class="notice-md3-hero__title"><?= $title; ?></h2>
                        </div>
                    </div>
                </section>
                <div class="notice-md3-tabbar" role="tablist" aria-label="测试类型切换">
                    <a class="notice-md3-tab<?=($current == 'mail' ? ' is-current' : '')?>" href="<?php $options->adminUrl('extending.php?panel=' . TypechoPlugin\Notice\Plugin::$panel_test . '&type=mail'); ?>"><?php _e('邮件发送测试'); ?></a>
                    <a class="notice-md3-tab<?=($current == 'msgraph' ? ' is-current' : '')?>" href="<?php $options->adminUrl('extending.php?panel=' . TypechoPlugin\Notice\Plugin::$panel_test . '&type=msgraph'); ?>"><?php _e('MSGraph发送测试'); ?></a>
                    <a class="notice-md3-tab<?=($current == 'qmsg' ? ' is-current' : '')?>" href="<?php $options->adminUrl('extending.php?panel=' . TypechoPlugin\Notice\Plugin::$panel_test . '&type=qmsg'); ?>"><?php _e('Qmsg酱发送测试'); ?></a>
                    <a class="notice-md3-tab<?=($current == 'serverchan' ? ' is-current' : '')?>" href="<?php $options->adminUrl('extending.php?panel=' . TypechoPlugin\Notice\Plugin::$panel_test . '&type=serverchan'); ?>"><?php _e('Server酱发送测试'); ?></a>
                    <a class="notice-md3-tab<?=($current == 'telegram' ? ' is-current' : '')?>" href="<?php $options->adminUrl('extending.php?panel=' . TypechoPlugin\Notice\Plugin::$panel_test . '&type=telegram'); ?>"><?php _e('Telegram Bot发送测试'); ?></a>
                </div>
                <div class="notice-md3-utility-card notice-md3-utility-form" role="main">
                    <?php Typecho\Widget::widget('Notice_libs_TestAction')->testForm($current)->render(); ?>
                </div>
            </div>
        </div>
    </div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
?>
