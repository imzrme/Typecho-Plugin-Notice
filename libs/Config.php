<?php

namespace TypechoPlugin\Notice\libs;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

require_once "FormElement/MDFormElements.php";
require_once "FormElement/MDCheckbox.php";
require_once "FormElement/MDRadio.php";
require_once "FormElement/MDSelect.php";
require_once "FormElement/MDText.php";
require_once "FormElement/MDTextarea.php";

use Typecho;
use TypechoPlugin\Notice\libs\FormElement\MDCheckbox;
use TypechoPlugin\Notice\libs\FormElement\MDRadio;
use TypechoPlugin\Notice\libs\FormElement\MDSelect;
use TypechoPlugin\Notice\libs\FormElement\MDText;
use TypechoPlugin\Notice\libs\FormElement\MDTextarea;
use TypechoPlugin\Notice\libs\FormElement\MDTitle;
use Utils;
use TypechoPlugin\Notice;
use const TypechoPlugin\Notice\__TYPECHO_PLUGIN_NOTICE_VERSION__;

class Config
{
    public static function style(?Typecho\Widget\Helper\Form $form = null)
    {
        $option = Utils\Helper::options();
        $cssVersion = @filemtime(__DIR__ . '/../assets/notice.css') ?: __TYPECHO_PLUGIN_NOTICE_VERSION__;
        $jsVersion = @filemtime(__DIR__ . '/../assets/notice.js') ?: __TYPECHO_PLUGIN_NOTICE_VERSION__;
        echo '<link href="https://cdn.jsdelivr.net/npm/mdui@0.4.3/dist/css/mdui.min.css" rel="stylesheet">';
        echo '<script src="https://cdn.jsdelivr.net/npm/mdui@0.4.3/dist/js/mdui.min.js"></script>';
        echo '<script src="https://cdn.jsdelivr.net/npm/jquery@2.2.4/dist/jquery.min.js" type="text/javascript"></script>';
        echo '<link href="' . $option->pluginUrl . '/Notice/assets/notice.css?v=' . $cssVersion . '" rel="stylesheet" type="text/css"/>';
        echo '<script src="' . $option->pluginUrl . '/Notice/assets/notice.js?v=' . $jsVersion . '"></script>';
    }

    public static function header(Typecho\Widget\Helper\Form $form)
    {
        $db = Typecho\Db::get();
        $version = __TYPECHO_PLUGIN_NOTICE_VERSION__;
        $hasBackup = $db->fetchRow($db->select()->from('table.options')->where('name = ?', 'plugin:Notice-Backup'));
        $backupBadge = $hasBackup
            ? '<span class="notice-md3-pill notice-md3-pill-success">已检测到配置备份</span>'
            : '<span class="notice-md3-pill notice-md3-pill-danger">当前没有配置备份</span>';

        $tag = Notice\libs\Version::getNewRelease();
        $tagCompare = version_compare($version, $tag);
        if ($tagCompare < 0) {
            $updateBadge = '<span class="notice-md3-pill notice-md3-pill-warning">发现新版本 ' . $tag . '</span>';
        } elseif ($tagCompare === 0) {
            $updateBadge = '<span class="notice-md3-pill notice-md3-pill-success">当前已是最新版本</span>';
        } else {
            $updateBadge = '<span class="notice-md3-pill notice-md3-pill-info">当前版本高于最新发布版</span>';
        }

        echo <<<EOF
<div class="notice-md3-shell">
  <section class="notice-md3-hero">
    <div class="notice-md3-hero__backdrop"></div>
    <div class="notice-md3-hero__badge">Notice</div>
    <div class="notice-md3-hero__main">
      <div class="notice-md3-hero__copy">
        <p class="notice-md3-hero__eyebrow">Typecho 评论通知中心</p>
        <h2 class="notice-md3-hero__title">Notice <span>v{$version}</span></h2>
        <div class="notice-md3-hero__pills">
          {$updateBadge}
          {$backupBadge}
        </div>
      </div>
      <div class="notice-md3-hero__aside">
        <div class="notice-md3-metric">
          <strong>5</strong>
          <span>通知渠道</span>
        </div>
        <div class="notice-md3-metric">
          <strong>3</strong>
          <span>邮件模板</span>
        </div>
      </div>
    </div>
    <div class="notice-md3-hero__actions">
      <a class="notice-md3-action notice-md3-action-primary" href="https://github.com/imzrme/Typecho-Plugin-Notice" target="_blank" rel="noopener">GitHub</a>
      <a class="notice-md3-action notice-md3-action-secondary" href="https://mzrme.com/" target="_blank" rel="noopener">作者博客</a>
      <button type="button" class="notice-md3-action notice-md3-action-tonal showSettings" onclick="Array.prototype.forEach.call(document.querySelectorAll('.notice-md3-section, .notice-md3-field'), function(el){ el.classList.add('mdui-panel-item-open'); }); return false;">展开全部</button>
      <button type="button" class="notice-md3-action notice-md3-action-tonal hideSettings" onclick="Array.prototype.forEach.call(document.querySelectorAll('.notice-md3-section, .notice-md3-field'), function(el){ el.classList.remove('mdui-panel-item-open'); }); return false;">收起全部</button>
      <button type="button" class="notice-md3-action notice-md3-action-soft recover_backup">恢复备份</button>
      <button type="button" class="notice-md3-action notice-md3-action-soft backup">备份配置</button>
      <button type="button" class="notice-md3-action notice-md3-action-danger del_backup">删除备份</button>
    </div>
  </section>
EOF;
    }

    public static function script(Typecho\Widget\Helper\Form $form)
    {
        $blogUrl = Utils\Helper::options()->siteUrl;
        $actionUrl = $blogUrl . 'action/' . Notice\Plugin::$action_setting;
        $strings = array(
            'confirmBackup' => '确认备份当前 Notice 配置吗？',
            'titleBackup' => '备份配置',
            'confirmDelete' => '确认删除现有备份吗？',
            'titleDelete' => '删除备份',
            'confirmRecover' => '确认恢复备份并覆盖当前配置吗？',
            'titleRecover' => '恢复备份',
            'backupSuccess' => '备份完成，正在刷新页面...',
            'backupFail' => '备份失败：',
            'deleteSuccess' => '备份已删除，正在刷新页面...',
            'deleteEmpty' => '当前没有可删除的备份。',
            'recoverSuccess' => '备份已恢复，正在刷新页面...',
            'recoverFail' => '恢复失败：',
            'confirmText' => '确认',
            'cancelText' => '取消'
        );
        $stringsJson = json_encode($strings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        echo <<<EOF
</div>
<script>
    $(function () {
        var strings = {$stringsJson};

        function showMessage(message) {
            mdui.snackbar({
                message: message,
                position: 'bottom'
            });
        }

        function reloadWithMessage(message) {
            showMessage(message);
            setTimeout(function () {
                location.reload();
            }, 1000);
        }

        function setPanelState(open) {
            var items = $('.notice-md3-section, .notice-md3-field');
            items.toggleClass('mdui-panel-item-open', open);
        }

        $(document).on('click', '.showSettings', function (event) {
            event.preventDefault();
            setPanelState(true);
        });

        $(document).on('click', '.hideSettings', function (event) {
            event.preventDefault();
            setPanelState(false);
        });

        $(document).on('click', '.backup', function (event) {
            event.preventDefault();
            mdui.confirm(strings.confirmBackup, strings.titleBackup, function () {
                $.ajax({
                    url: '$actionUrl',
                    data: {"do": "backup"},
                    success: function (data) {
                        if (data !== '-1') {
                            reloadWithMessage(strings.backupSuccess);
                        } else {
                            showMessage(strings.backupFail + data);
                        }
                    }
                });
            }, null, {"confirmText": strings.confirmText, "cancelText": strings.cancelText});
        });

        $(document).on('click', '.del_backup', function (event) {
            event.preventDefault();
            mdui.confirm(strings.confirmDelete, strings.titleDelete, function () {
                $.ajax({
                    url: '$actionUrl',
                    data: {"do": "del_backup"},
                    success: function (data) {
                        if (data !== '-1') {
                            reloadWithMessage(strings.deleteSuccess);
                        } else {
                            showMessage(strings.deleteEmpty);
                        }
                    }
                });
            }, null, {"confirmText": strings.confirmText, "cancelText": strings.cancelText});
        });

        $(document).on('click', '.recover_backup', function (event) {
            event.preventDefault();
            mdui.confirm(strings.confirmRecover, strings.titleRecover, function () {
                $.ajax({
                    url: '$actionUrl',
                    data: {"do": "recover_backup"},
                    success: function (data) {
                        if (data !== '-1') {
                            reloadWithMessage(strings.recoverSuccess);
                        } else {
                            showMessage(strings.recoverFail + data);
                        }
                    }
                });
            }, null, {"confirmText": strings.confirmText, "cancelText": strings.cancelText});
        });
    });
</script>
EOF;
    }

    public static function Setting(Typecho\Widget\Helper\Form $form)
    {
        $form->addItem(new MDTitle('插件设置', '通知通道开关、更新提醒、数据库与日志策略', false));

        $setting = new MDCheckbox('setting',
            array(
                'serverchan' => '启用 Server酱 Turbo',
                'qmsg' => '启用 Qmsg',
                'mail' => '启用 SMTP 邮件',
                'msgraph' => '启用 Microsoft Graph 邮件',
                'telegram' => '启用 Telegram Bot',
                'updatetip' => '启用更新提醒',
            ),
            array('updatetip'),
            '通知方式',
            _t('选择你要启用的通知方式。启用更新提醒后，插件有新版本时后台会显示提示。')
        );
        $form->addInput($setting->multiMode());

        $delDB = new MDRadio(
            'delDB',
            array(
                '1' => '是',
                '0' => '否'
            ),
            '1',
            _t('卸载插件时删除数据库'),
            _t('如果选“否”，插件的历史日志和记录会保留在数据库中。')
        );
        $form->addInput($delDB);

        $enableLog = new MDRadio(
            'enableLog',
            array(
                '2' => '调试',
                '1' => '生产',
                '0' => '关闭'
            ),
            '1',
            _t('日志级别'),
            _t('调试会记录更完整的过程日志；生产只记录发送结果；关闭则不写入日志。')
        );
        $form->addInput($enableLog);

        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
    }

    public static function Serverchan(Typecho\Widget\Helper\Form $form)
    {
        $form->addItem(new MDTitle('Server酱 Turbo 配置', 'SCKEY 与通知模板', false));

        $scKey = new MDText(
            'scKey',
            null,
            null,
            _t('Server酱 SCKEY'),
            _t('前往 <a href="https://sct.ftqq.com/" target="_blank" rel="noopener">Server酱 Turbo</a> 获取 SCKEY，并绑定接收通知的微信。')
        );
        $form->addInput($scKey);

        $scMsg = new MDTextarea(
            'scMsg',
            null,
            "评论人：**{author}**\n\n评论内容：\n> {text}\n\n链接：{permalink}",
            _t('Server酱通知模板'),
            _t('支持模板变量，变量列表见插件说明。')
        );
        $form->addInput($scMsg);

        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
    }

    public static function checkServerchan(array $settings)
    {
        if (in_array('serverchan', $settings['setting'] ?? array(), true)) {
            if (empty($settings['scKey'])) {
                return _t('请填写 Server酱 SCKEY');
            }
            if (empty($settings['scMsg'])) {
                return _t('请填写 Server酱通知模板');
            }
        }
        return '';
    }

    public static function Qmsgchan(Typecho\Widget\Helper\Form $form)
    {
        $form->addItem(new MDTitle('Qmsg 配置', 'Qmsg Key、QQ 号与通知模板', false));

        $qmsgKey = new MDText(
            'QmsgKey',
            null,
            null,
            _t('Qmsg Key'),
            _t('前往 <a href="https://qmsg.zendee.cn/api" target="_blank" rel="noopener">Qmsg 文档</a> 获取 Key，只需要填写 key，不要填完整链接。')
        );
        $form->addInput($qmsgKey);

        $qmsgQQ = new MDText(
            'QmsgQQ',
            null,
            null,
            _t('Qmsg QQ'),
            _t('可选。多个 QQ 号请使用英文逗号分隔；留空则发给当前应用下全部已绑定 QQ。')
        );
        $form->addInput($qmsgQQ);

        $qmsgMsg = new MDTextarea(
            'QmsgMsg',
            null,
            "评论人：{author}\n评论内容：\n{text}\n\n链接：{permalink}",
            _t('Qmsg 通知模板'),
            _t('支持模板变量，变量列表见插件说明。')
        );
        $form->addInput($qmsgMsg);

        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
    }

    public static function checkQmsgchan(array $settings)
    {
        if (in_array('qmsg', $settings['setting'] ?? array(), true)) {
            if (empty($settings['QmsgKey'])) {
                return _t('请填写 Qmsg Key');
            }
            if (empty($settings['QmsgMsg'])) {
                return _t('请填写 Qmsg 通知模板');
            }
        }
        return '';
    }

    public static function SMTP(Typecho\Widget\Helper\Form $form)
    {
        $form->addItem(new MDTitle('SMTP 配置', '服务器、鉴权与发件人信息', false));

        $host = new MDText('host', null, '', _t('SMTP 服务器地址'), _t('例如 smtp.example.com'));
        $form->addInput($host);

        $port = new MDText('port', null, 465, _t('端口'), _t('端口必须是数字，常见值如 465 或 587。'));
        $form->addInput($port->addRule('isInteger', _t('端口必须是数字')));

        $secure = new MDSelect(
            'secure',
            array('tls' => 'tls', 'ssl' => 'ssl'),
            'ssl',
            _t('连接加密方式')
        );
        $form->addInput($secure);

        $auth = new MDRadio(
            'auth',
            array(1 => '是', 0 => '否'),
            1,
            _t('启用身份验证'),
            _t('开启后必须填写用户名和密码。')
        );
        $form->addInput($auth);

        $user = new MDText('user', null, '', _t('用户名'), _t('一般为完整邮箱地址，例如 name@domain.com。'));
        $form->addInput($user);

        $password = new MDText('password', null, '', _t('密码'), _t('某些服务商需要使用专用密码或应用密码。'));
        $form->addInput($password);

        $from = new MDText('from', null, '', _t('发件邮箱'));
        $form->addInput($from->addRule('email', _t('请输入正确的邮箱地址')));

        $fromName = new MDText('from_name', null, Utils\Helper::options()->title, _t('发件人名称'), _t('默认使用站点标题。'));
        $form->addInput($fromName);

        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
    }

    public static function EmailSettings(Typecho\Widget\Helper\Form $form)
    {
        $form->addItem(new MDTitle('邮件通知内容', '适用于 SMTP 与 Microsoft Graph', false));

        $titleForOwner = new MDText('titleForOwner', null, '[{title}] 一文有新的评论', _t('站长接收邮件标题'));
        $form->addInput($titleForOwner->addRule('required', _t('站长接收邮件标题不能为空')));

        $titleForGuest = new MDText('titleForGuest', null, '您在 [{title}] 的评论有了回复', _t('访客接收邮件标题'));
        $form->addInput($titleForGuest->addRule('required', _t('访客接收邮件标题不能为空')));

        $titleForApproved = new MDText('titleForApproved', null, '您在 [{title}] 的评论已通过审核', _t('审核通过邮件标题'));
        $form->addInput($titleForApproved->addRule('required', _t('审核通过邮件标题不能为空')));

        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
    }

    public static function MicrosoftGraph(Typecho\Widget\Helper\Form $form)
    {
        $form->addItem(new MDTitle('Microsoft Graph API 配置', '使用 Microsoft Entra ID 应用发送邮件', false));

        $tenantId = new MDText('msgraphTenantId', null, null, _t('租户 ID (Tenant ID)'), _t('Microsoft Entra ID 中的租户 ID。'));
        $form->addInput($tenantId);

        $clientId = new MDText('msgraphClientId', null, null, _t('客户端 ID (Client ID)'), _t('应用程序的客户端 ID。'));
        $form->addInput($clientId);

        $clientSecret = new MDText('msgraphClientSecret', null, null, _t('客户端密钥 (Client Secret)'), _t('请妥善保管该密钥。'));
        $form->addInput($clientSecret);

        $senderEmail = new MDText(
            'msgraphSenderEmail',
            null,
            null,
            _t('发件邮箱'),
            _t('用于发送邮件的用户邮箱，必须是租户中的有效成员邮箱。')
        );
        $form->addInput($senderEmail->addRule('email', _t('请输入正确的邮箱地址')));

        $senderName = new MDText('msgraphSenderName', null, Utils\Helper::options()->title, _t('发件人名称'), _t('默认使用站点标题。'));
        $form->addInput($senderName);

        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
    }

    public static function Telegram(Typecho\Widget\Helper\Form $form)
    {
        $form->addItem(new MDTitle('Telegram Bot 配置', 'Bot Token 与 Chat ID', false));

        $tgBotToken = new MDText('tgBotToken', null, null, _t('Telegram Bot Token'), _t('通过 @BotFather 获取 Bot Token。'));
        $form->addInput($tgBotToken);

        $tgChatId = new MDText('tgChatId', null, null, _t('Telegram Chat ID'), _t('填写接收通知的用户 ID 或频道 ID。'));
        $form->addInput($tgChatId);

        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
    }

    public static function checkTelegram(array $settings)
    {
        if (in_array('telegram', $settings['setting'] ?? array(), true)) {
            if (empty($settings['tgBotToken'])) {
                return _t('请填写 Telegram Bot Token');
            }
            if (empty($settings['tgChatId'])) {
                return _t('请填写 Telegram Chat ID');
            }
        }
        return '';
    }

    public static function checkMicrosoftGraph(array $settings)
    {
        if (in_array('msgraph', $settings['setting'] ?? array(), true)) {
            if (empty($settings['msgraphTenantId'])) {
                return _t('请填写 Microsoft Graph 租户 ID');
            }
            if (empty($settings['msgraphClientId'])) {
                return _t('请填写 Microsoft Graph 客户端 ID');
            }
            if (empty($settings['msgraphClientSecret'])) {
                return _t('请填写 Microsoft Graph 客户端密钥');
            }
            if (empty($settings['msgraphSenderEmail'])) {
                return _t('请填写 Microsoft Graph 发件邮箱');
            }
        }
        return '';
    }

    public static function checkSMTP(array $settings)
    {
        if (in_array('mail', $settings['setting'] ?? array(), true)) {
            if (empty($settings['host'])) {
                return _t('请填写 SMTP 服务器地址');
            }
            if (empty($settings['port'])) {
                return _t('请填写 SMTP 端口');
            }
            if (($settings['auth'] ?? 1) == 1) {
                if (empty($settings['user'])) {
                    return _t('请填写 SMTP 用户名');
                }
                if (empty($settings['password'])) {
                    return _t('请填写 SMTP 密码');
                }
            }
            if (empty($settings['from'])) {
                return _t('请填写发件邮箱');
            }
        }
        return '';
    }

    public static function check(array $settings)
    {
        $s = self::checkServerchan($settings);
        if ($s !== '') {
            return $s;
        }

        $s = self::checkQmsgchan($settings);
        if ($s !== '') {
            return $s;
        }

        $s = self::checkSMTP($settings);
        if ($s !== '') {
            return $s;
        }

        $s = self::checkMicrosoftGraph($settings);
        if ($s !== '') {
            return $s;
        }

        $s = self::checkTelegram($settings);
        if ($s !== '') {
            return $s;
        }

        $s = self::checkEmailSettings($settings);
        if ($s !== '') {
            return $s;
        }

        return '';
    }

    public static function checkEmailSettings(array $settings)
    {
        if (in_array('mail', $settings['setting'] ?? array(), true) || in_array('msgraph', $settings['setting'] ?? array(), true)) {
            if (empty($settings['titleForOwner'])) {
                return _t('请填写站长接收邮件标题');
            }
            if (empty($settings['titleForGuest'])) {
                return _t('请填写访客接收邮件标题');
            }
            if (empty($settings['titleForApproved'])) {
                return _t('请填写审核通过邮件标题');
            }
        }
        return '';
    }
}
