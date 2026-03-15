<?php
include "header.php";
include "menu.php";
TypechoPlugin\Notice\libs\Config::style();
Typecho\Widget::widget('Notice_libs_TestAction')->to($files);
?>

    <script>
        (function () {
            var title = '编辑邮件模板';
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

    <div class="main notice-md3-shell notice-md3-utility-page notice-md3-template-page">
        <div class="body container">
            <div class="notice-md3-utility-stack">
                <section class="notice-md3-hero notice-md3-hero--utility">
                    <div class="notice-md3-hero__backdrop"></div>
                    <div class="notice-md3-hero__badge">模板</div>
                    <div class="notice-md3-hero__main">
                        <div class="notice-md3-hero__copy">
                            <h2 class="notice-md3-hero__title"><?= $files->getTitle(); ?></h2>
                        </div>
                    </div>
                </section>
                <div class="notice-md3-editor-layout" role="main">
                    <div class="notice-md3-utility-card notice-md3-editor-card">
                        <form method="post" name="theme" id="theme" class="notice-md3-editor-form"
                              action="<?php $options->index('/action/' . TypechoPlugin\Notice\Plugin::$action_edit_template); ?>">
                            <label for="content" class="sr-only"><?php _e('编辑源码'); ?></label>
                            <div class="notice-md3-editor-area-shell">
                                <textarea name="content" id="content" class="notice-md3-editor-area mono"
                                          <?php if (!$files->currentIsWriteable()): ?>readonly<?php endif; ?>><?php echo $files->currentContent(); ?></textarea>
                            </div>
                            <p class="submit notice-md3-editor-submit">
                                <?php if ($files->currentIsWriteable()): ?>
                                    <input type="hidden" name="do" value="edit_theme"/>
                                    <input type="hidden" name="file" value="<?php echo $files->currentFile(); ?>"/>
                                    <button type="submit"
                                            class="notice-md3-inline-submit"><?php _e('保存文件'); ?></button>
                                <?php else: ?>
                                    <em class="notice-md3-editor-note"><?php _e('此文件无法写入'); ?></em>
                                <?php endif; ?>
                            </p>
                        </form>
                    </div>
                    <aside class="notice-md3-utility-card notice-md3-editor-sidebar">
                        <div class="notice-md3-editor-sidebar__title">模板文件</div>
                        <ul class="notice-md3-file-list">
                            <?php while ($files->next()): ?>
                                <li class="notice-md3-file-item<?php if ($files->current): ?> is-current<?php endif; ?>">
                                    <a href="<?php $options->adminUrl('extending.php?panel=' . TypechoPlugin\Notice\Plugin::$panel_edit_template . '&file=' . $files->file); ?>"><?php $files->file(); ?></a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </aside>
                </div>
            </div>
        </div>
    </div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
