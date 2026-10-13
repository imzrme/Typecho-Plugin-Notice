<?php

namespace TypechoPlugin\Notice;
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

require_once "libs/Config.php";
require_once "libs/db.php";
require_once "libs/Utils.php";
require_once __DIR__ . '/libs/MailRecipients.php';
require_once "libs/FormElement/MDFormElements.php";
require_once "libs/phpmailer/PHPMailer.php";
require_once "libs/phpmailer/Exception.php";
require_once "libs/phpmailer/SMTP.php";



use Typecho;
use Typecho\Plugin\PluginInterface;
use Utils;
use Widget;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

const __TYPECHO_PLUGIN_NOTICE_VERSION__ = '2.0.0';

/**
 * <strong style="color:#28B7FF;font-family: 楷体;">评论通知</strong>
 *
 * @package Notice
 * @author <strong style="color:#28B7FF;font-family: 楷体;">MZRME</strong>
 * @version 2.0.0
 * @link https://github.com/imzrme
 * @since 1.3.0
 */
class Plugin implements PluginInterface
{
    /** @var string 插件配置action前缀 */
    public static string $action_setting = 'Plugin-Notice-Setting';

    /** @var string 插件测试action前缀 */
    public static string $action_test = 'Plugin-Notice-Test';

    /** @var string 编辑插件模版action前缀 */
    public static string $action_edit_template = 'Plugin-Notice-Edit-Template';

    /** @var string 插件编辑模板面板 */
    public static string $panel_edit_template = 'Notice/page/edit-template.php';

    /** @var string 插件测试面板 */
    public static string $panel_test = 'Notice/page/test.php';

    /**
     * 构建独立提示框，并用样式压平 Typecho 默认的黄色 notice 外壳
     */
    private static function buildStandaloneNoticeBox(string $id, string $title, string $message): string
    {
        $title = (string)$title;
        $message = (string)$message;

        return <<<HTML
<style>
    .message.notice,
    .message.success,
    .message.error {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        min-height: 0 !important;
        overflow: visible !important;
    }
</style>
<div id="{$id}" style="border-radius:2px;box-shadow:1px 1px 50px rgba(0,0,0,.3);background-color:#fff;width:auto;height:auto;z-index:2501554;position:fixed;left:50%;top:50%;transform:translate(-50%, -50%);">
    <div style="text-align:center;height:42px;line-height:42px;border-bottom:1px solid #eee;font-size:14px;overflow:hidden;border-radius:2px 2px 0 0;font-weight:bold;position:relative;cursor:move;min-width:200px;box-sizing:border-box;background-color:#28B7FF;color:#fff;">
        {$title}
    </div>
    <div style="padding:15px;font-size:14px;min-width:150px;position:relative;box-sizing:border-box;height:50px;">
        {$message}
    </div>
    <div style="text-align:right;padding-bottom:15px;padding-right:10px;min-width:200px;box-sizing:border-box;">
        <button onclick="window.noticePluginCloseBox && window.noticePluginCloseBox('{$id}')" style="height:28px;line-height:28px;margin:15px 5px 0;padding:0 15px;border-radius:2px;font-weight:400;cursor:pointer;text-decoration:none;outline:none;background-color:#28B7FF;border:0;color:#fff;">
            关闭
        </button>
    </div>
</div>
<script>
    (function () {
        window.noticePluginCloseBox = window.noticePluginCloseBox || function (boxId) {
            var target = document.getElementById(boxId);
            if (target && target.parentNode) {
                target.parentNode.removeChild(target);
            }
        };
    })();
</script>
HTML;
    }

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return string
     * @throws Typecho\Plugin\Exception
     * @throws Typecho\Db\Exception
     */
    public static function activate(): string
    {
        $res = self::buildStandaloneNoticeBox(
            'typecho-plugin-notice-active-box',
            libs\DB::dbInstall(),
            '欢迎使用Notice插件，希望能让您喜欢！'
        );

        // 通知触发函数
        Typecho\Plugin::factory('Widget_Feedback')->finishComment = __CLASS__ . '::requestService';
        Typecho\Plugin::factory('Widget_Comments_Edit')->finishComment = __CLASS__ . '::requestService';
        Typecho\Plugin::factory('Widget_Comments_Edit')->mark = __CLASS__ . '::approvedMail';
        // 注册异步调用函数
        Typecho\Plugin::factory('Widget_Service')->sendSC = __CLASS__ . '::sendSC';
        Typecho\Plugin::factory('Widget_Service')->sendQmsg = __CLASS__ . '::sendQmsg';
        Typecho\Plugin::factory('Widget_Service')->sendMail = __CLASS__ . '::sendMail';
        Typecho\Plugin::factory('Widget_Service')->sendMSGraphMail = __CLASS__ . '::sendMSGraphMail';
        Typecho\Plugin::factory('Widget_Service')->sendApprovedMail = __CLASS__ . '::sendApprovedMail';
        Typecho\Plugin::factory('Widget_Service')->sendApprovedMSGraphMail = __CLASS__ . '::sendApprovedMSGraphMail';
        Typecho\Plugin::factory('Widget_Service')->sendTelegram = __CLASS__ . '::sendTelegram';


        Utils\Helper::addAction(self::$action_setting, 'TypechoPlugin\Notice\libs\SettingAction');
        Utils\Helper::addAction(self::$action_test, 'TypechoPlugin\Notice\libs\TestAction');
        Utils\Helper::addAction(self::$action_edit_template, 'TypechoPlugin\Notice\libs\TestAction');
        $index = Utils\Helper::addMenu("Notice");
        Utils\Helper::addPanel($index, self::$panel_edit_template, '编辑邮件模版', '', 'administrator');
        Utils\Helper::addPanel($index, self::$panel_test, '配置测试', '', 'administrator');


        return $res;
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return string
     * @throws Typecho\Db\Exception
     * @throws Typecho\Exception
     * @throws Typecho\Plugin\Exception
     */
    public static function deactivate(): string
    {
        Utils\Helper::removeAction(self::$action_setting);
        Utils\Helper::removeAction(self::$action_test);
        Utils\Helper::removeAction(self::$action_edit_template);
        $index = Utils\Helper::removeMenu("Notice");
        Utils\Helper::removePanel($index, self::$panel_edit_template);
        Utils\Helper::removePanel($index, self::$panel_test);

        $delDB = Utils\Helper::options()->plugin('Notice')->delDB;
        if ($delDB == 1) {
            $s = libs\DB::dbUninstall();
        } else {
            $s = _t('您的设置为不删除数据库！插件卸载成功！');
        }
        return self::buildStandaloneNoticeBox(
            'AS-SW',
            $s,
            '感谢您使用Notice，期待与您的下一次相遇！'
        );
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho\Widget\Helper\Form $form 配置面板
     * @return void
     */
    public static function config(Typecho\Widget\Helper\Form $form)
    {
        // CSS
        libs\Config::style($form);
        // header
        libs\Config::header($form);
        // 配置开始
        $form->addItem(new libs\FormElement\MDCustomLabel('<div class="notice-md3-panel-zone"><div class="mdui-panel notice-md3-panels">'));
        {
            // 插件配置
            libs\Config::Setting($form);

            // Server 酱
            libs\Config::Serverchan($form);

            // Qmsg 酱
            libs\Config::Qmsgchan($form);

            // SMTP
            libs\Config::SMTP($form);

            // Microsoft Graph
            libs\Config::MicrosoftGraph($form);

            // Telegram Bot
            libs\Config::Telegram($form);

            // Email Settings (Shared)
            libs\Config::EmailSettings($form);
        }
        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
        // 美化提交按钮
        $submit = new Typecho\Widget\Helper\Form\Element\Submit();
        $submit->input->setAttribute('class', 'mdui-btn mdui-color-theme-accent mdui-ripple submit_only');
        $submit->value(_t('保存设置'));
        $form->addItem($submit);
        // javascript
        libs\Config::script($form);

    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho\Widget\Helper\Form $form
     * @return void
     */
    public static function personalConfig(Typecho\Widget\Helper\Form $form)
    {
    }

    /**
     * 检查参数
     *
     * @param array $settings
     * @return string
     */
    public static function configCheck(array $settings): string
    {
        return libs\Config::check($settings);
    }

    public static function configHandle(array $settings, bool $isInit)
    {
        libs\Settings::save($settings);
    }

    /**
     * 评论通知回调
     *
     * @access public
     * @param $comment
     * @return void
     * @throws Typecho\Plugin\Exception
     * @throws Typecho\Db\Exception
     */
    public static function requestService($comment)
    {
        $coid = (int)$comment->coid;
        libs\DB::log($coid, 'log', '评论异步请求开始');
        $options = Utils\Helper::options()->plugin('Notice');
        $channels = libs\Settings::normalizeChannels($options->setting);
        if (in_array('mail', $channels, true) && !empty($options->host)) {
            libs\DB::log($coid, "log", "调用发送邮件异步");
            self::sendMail($coid);
        }
        if (in_array('msgraph', $channels, true) && !empty($options->msgraphClientId)) {
            libs\DB::log($coid, "log", "调用Microsoft Graph发送邮件异步");
            self::sendMSGraphMail($coid);
        }
        if (in_array('serverchan', $channels, true) && !empty($options->scKey)) {
            libs\DB::log($coid, "log", "调用Server酱异步");
            self::sendSC($coid);
        }
        if (in_array('qmsg', $channels, true) && !empty($options->QmsgKey)) {
            libs\DB::log($coid, "log", "调用Qmsg酱异步");
            self::sendQmsg($coid);
        }
        if (in_array('telegram', $channels, true) && !empty($options->tgBotToken)) {
            libs\DB::log($coid, "log", "调用Telegram Bot异步");
            self::sendTelegram($coid);
        }
        libs\DB::log($coid, 'log', '评论异步请求结束');
    }

    /**
     * 审核通过评论回调
     *
     * @access public
     * @param $comment
     * @param $edit
     * @param string $status
     * @return void
     * @throws Typecho\Db\Exception
     */
    public static function approvedMail($comment, $edit, $status)
    {
        libs\DB::log($comment['coid'], '评论通过异步请求开始', '');
        if ('approved' === $status) {
            self::sendApprovedMail($comment['coid']);
            self::sendApprovedMSGraphMail($comment['coid']);
        }
        libs\DB::log($comment['coid'], '评论通过异步请求结束', '');
    }

    /**
     * 异步发送微信 Powered By Server酱
     *
     * @param integer $coid 评论ID
     * @return void
     * @throws Typecho\Db\Exception
     * @throws Typecho\Plugin\Exception
     * @access public
     */
    public static function sendSC(int $coid)
    {
        libs\DB::log($coid, 'log', 'Server酱：通知开始');
        $options = Utils\Helper::options();
        $pluginOptions = $options->plugin('Notice');
        $comment = Utils\Helper::widgetById('comments', $coid);
        if (!in_array('serverchan', libs\Settings::normalizeChannels($pluginOptions->setting), true)
            || empty($pluginOptions->scKey)) {
            libs\DB::log($coid, 'log', 'Server酱：缺少sckey');
            return;
        }
        $key = $pluginOptions->scKey;
        if (!$comment->have() || empty($comment->mail)) {
            libs\DB::log($coid, 'log', 'Server酱：评论缺少关键信息');
            return;
        }
        if ($comment->authorId == 1) {
            libs\DB::log($coid, 'log', 'Server酱：博主评论，跳过');
            return;
        }

        $msg = $pluginOptions->scMsg;
        $msg = libs\ShortCut::replace($msg, $coid);

        $post_data = http_build_query(
            array(
                'text' => "有人在您的博客发表了评论",
                'desp' => $msg
            )
        );

        $opts = array('http' =>
            array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $post_data
            )
        );
        $context = stream_context_create($opts);
        $result = file_get_contents('https://sctapi.ftqq.com/' . $key . '.send', false, $context);

        libs\DB::log($coid, 'wechat', $result . "\n\n" . $msg);
        libs\DB::log($coid, 'log', 'Server酱：通知结束');
    }

    /**
     * 异步发送QQ Powered By Qmsg 酱
     *
     * @param integer $coid 评论ID
     * @return void
     * @throws Typecho\Db\Exception
     * @throws Typecho\Plugin\Exception
     * @access public
     */
    public static function sendQmsg(int $coid)
    {
        libs\DB::log($coid, 'log', 'Qmsg酱：通知开始');
        $options = Utils\Helper::options();
        $pluginOptions = $options->plugin('Notice');
        $comment = Utils\Helper::widgetById('comments', $coid);
        if (!in_array('qmsg', libs\Settings::normalizeChannels($pluginOptions->setting), true)
            || empty($pluginOptions->QmsgKey)) {
            libs\DB::log($coid, 'log', 'Qmsg酱：评论缺少qmsgkey');
            return;
        }
        $key = $pluginOptions->QmsgKey;
        if (!$comment->have() || empty($comment->mail)) {
            libs\DB::log($coid, 'log', "Qmsg酱：评论缺少关键信息");
            return;
        }
        if ($comment->authorId == 1) {
            libs\DB::log($coid, 'log', "Qmsg酱：博主评论，跳过");
            return;
        }

        $msg = $pluginOptions->QmsgMsg;
        $msg = libs\ShortCut::replace($msg, $coid);

        if ($pluginOptions->QmsgQQ == NULL) {
            $postdata = http_build_query(
                array(
                    'msg' => $msg
                )
            );
        } else {
            $postdata = http_build_query(
                array(
                    'msg' => $msg,
                    'qq' => $pluginOptions->QmsgQQ
                )
            );
        }

        $opts = array('http' =>
            array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );
        $context = stream_context_create($opts);
        $result = file_get_contents('https://qmsg.zendee.cn/send/' . $key, false, $context);

        libs\DB::log($coid, 'qq', $result . "\n\n" . $msg);
        libs\DB::log($coid, 'log', 'Qmsg酱：通知结束');
    }

    /**
     * 异步发送 Telegram Bot 通知
     *
     * @param integer $coid 评论ID
     * @return void
     * @throws Typecho\Db\Exception
     * @throws Typecho\Plugin\Exception
     * @access public
     */
    public static function sendTelegram(int $coid)
    {
        libs\DB::log($coid, 'log', 'Telegram：通知开始');
        $options = Utils\Helper::options();
        $pluginOptions = $options->plugin('Notice');
        $comment = Utils\Helper::widgetById('comments', $coid);
        
        // 验证配置
        if (!in_array('telegram', libs\Settings::normalizeChannels($pluginOptions->setting), true)
            || empty($pluginOptions->tgBotToken)) {
            libs\DB::log($coid, 'log', 'Telegram：缺少Bot Token');
            return;
        }
        if (empty($pluginOptions->tgChatId)) {
            libs\DB::log($coid, 'log', 'Telegram：缺少Chat ID');
            return;
        }
        if (!$comment->have() || empty($comment->mail)) {
            libs\DB::log($coid, 'log', 'Telegram：评论缺少关键信息');
            return;
        }
        if ($comment->authorId == 1) {
            libs\DB::log($coid, 'log', 'Telegram：博主评论，跳过');
            return;
        }
        
        // 构造消息内容
        $title = libs\ShortCut::replace($pluginOptions->titleForOwner, $coid);
        $authorName = $comment->author;
        $authorMail = $comment->mail;
        $authorUrl = $comment->url;
        $text = $comment->text;
        
        // 判断评论状态
        $isPending = $comment->status == 'waiting';
        $buttonUrl = $comment->permalink;
        $buttonText = _t('👀 查看评论');

        if ($isPending) {
            $title = "🎉 [" . $comment->title . "]一文有待审核的评论";
            // 构造管理页面链接
            $adminUrl = rtrim(Utils\Helper::options()->siteUrl, '/') . '/' . ltrim(__TYPECHO_ADMIN_DIR__, '/') . 'manage-comments.php?status=waiting';
            $buttonUrl = $adminUrl;
            $buttonText = _t('🔕 管理评论');
        }

        // 构造 Telegram 消息 (Markdown 格式)
        $msg = $isPending ? ($title . "\n") : ("🎉 " . $title . "\n");
        $msg .= "评论者: `" . $authorName . "`\n";
        $msg .= "邮箱: `" . $authorMail . "`\n";
        if (!empty($authorUrl)) {
            $msg .= "网站: `" . $authorUrl . "`\n";
        }
        $msg .= "评论内容:" . $text;
        
        // 构造按钮
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => $buttonText, 'url' => $buttonUrl]
                ]
            ]
        ];

        // 发送请求
        $botToken = $pluginOptions->tgBotToken;
        $chatId = $pluginOptions->tgChatId;
        $apiUrl = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
        
        $postdata = http_build_query([
            'chat_id' => $chatId,
            'text' => $msg,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode($keyboard)
        ]);
        
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            ]
        ];
        $context = stream_context_create($opts);
        $result = file_get_contents($apiUrl, false, $context);
        
        libs\DB::log($coid, 'telegram', $result . "\n\n" . $msg);
        libs\DB::log($coid, 'log', 'Telegram：通知结束');
    }

    /**
     * @throws PHPMailerException
     */
    public static function checkMailConfig($pluginOptions, $comment): ?PHPMailer
    {
        if (!in_array('mail', libs\Settings::normalizeChannels($pluginOptions->setting), true)) {
            return null;
        }

        if (empty($pluginOptions->host)) {
            return null;
        }

        if (!$comment->have()) {
            return null;
        }

        $mail = new PHPMailer(false);

        $mail->isSMTP();
        $mail->Host = $pluginOptions->host;
        $mail->SMTPAuth = !!$pluginOptions->auth;
        $mail->Username = $pluginOptions->user;
        $mail->Password = $pluginOptions->password;
        $mail->SMTPSecure = $pluginOptions->secure;
        $mail->Port = $pluginOptions->port;
        $mail->getSMTPInstance()->setTimeout(10);
        $mail->isHTML(true);
        $mail->CharSet = 'utf-8';
        $mail->setFrom($pluginOptions->from, $pluginOptions->from_name);
        return $mail;
    }

    /**
     * 异步发送通知邮件
     *
     * @access public
     * @param int $coid 评论id
     * @return void
     * @throws Typecho\Plugin\Exception
     * @throws Typecho\Widget\Exception
     * @throws PHPMailerException
     * @throws Typecho\Db\Exception
     */
    public static function sendMail(int $coid)
    {
        self::sendSmtpNotifications($coid, false);
    }

    /** Send approval confirmations without suppressing the commenter's own confirmation. */
    public static function sendApprovedMail(int $coid)
    {
        self::sendSmtpNotifications($coid, true);
    }

    private static function sendSmtpNotifications(int $coid, bool $approval)
    {
        $label = $approval ? '邮件：评论审核通过' : '邮件';
        libs\DB::log($coid, 'log', $label . '：发送开始');
        $pluginOptions = Utils\Helper::options()->plugin('Notice');
        $comment = Utils\Helper::widgetById('comments', $coid);
        if (!($comment instanceof Widget\Base\Comments) || !$comment->have()) {
            libs\DB::log($coid, 'log', $label . '：评论不存在，跳过');
            return;
        }
        $mail = self::checkMailConfig($pluginOptions, $comment);
        if ($mail === null) {
            libs\DB::log($coid, 'log', $label . '：渠道未启用或缺少关键参数');
            return;
        }

        $plainText = "作者：" . $comment->author . "\r\n链接：" . $comment->permalink
            . "\r\n评论：\r\n" . $comment->text;
        $recipients = libs\MailRecipients::forComment($comment, $approval);
        $subjects = [
            'owner' => (string)$pluginOptions->titleForOwner,
            'guest' => (string)$pluginOptions->titleForGuest,
            'approved' => (string)$pluginOptions->titleForApproved
        ];
        foreach ($recipients as $recipient) {
            // Each planned recipient gets one message with its own template.
            $mail->clearAllRecipients();
            if (!$mail->addAddress($recipient['mail'], $recipient['name'])) {
                libs\DB::log($coid, 'mail', '邮件：收件人地址无效，跳过');
                continue;
            }
            $template = $recipient['template'];
            $mail->Subject = libs\ShortCut::replace($subjects[$template], $coid);
            $mail->Body = libs\ShortCut::replace(libs\ShortCut::getTemplate($template), $coid);
            $mail->AltBody = $template === 'approved' ? "您的评论已通过审核。\n" : $plainText;
            if ($mail->send()) {
                libs\DB::log($coid, 'mail', $mail->Body);
            } else {
                libs\DB::log($coid, 'mail', '邮件：发送失败：' . $mail->ErrorInfo);
            }
        }
        libs\DB::log($coid, 'log', $label . '：发送结束');
    }

    /**
     * Microsoft Graph API 发送邮件逻辑
     *
     * @param string $to 收件人邮箱
     * @param string $toName 收件人名称
     * @param string $subject 邮件标题
     * @param string $body 邮件内容
     * @param object $config 配置对象
     * @return string|true 成功返回true，失败返回错误信息
     */
    public static function sendViaGraphApi(string $to, string $toName, string $subject, string $body, $config)
    {
        try {
            // Get Access Token
            $url = "https://login.microsoftonline.com/{$config->tenantId}/oauth2/v2.0/token";
            $data = [
                'client_id' => $config->clientId,
                'scope' => 'https://graph.microsoft.com/.default',
                'client_secret' => $config->clientSecret,
                'grant_type' => 'client_credentials',
            ];

            $options = [
                'http' => [
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($data)
                ]
            ];

            $context = stream_context_create($options);
            $result = @file_get_contents($url, false, $context);

            if ($result === false) {
                 return "Get Token Error: Request failed. Please check credentials.";
            }

            $response = json_decode($result, true);
            if (!isset($response['access_token'])) {
                return "Get Token Error: No access_token in response.";
            }
            $accessToken = $response['access_token'];

            // Send Mail
            $sendUrl = "https://graph.microsoft.com/v1.0/users/{$config->senderEmail}/sendMail";
            $emailData = [
                'message' => [
                    'subject' => $subject,
                    'body' => [
                        'contentType' => 'HTML',
                        'content' => $body
                    ],
                    'toRecipients' => [
                        [
                            'emailAddress' => [
                                'address' => $to,
                                'name' => $toName
                            ]
                        ]
                    ]
                ],
                'saveToSentItems' => 'false'
            ];

            if (!empty($config->senderName)) {
                $emailData['message']['from'] = [
                    'emailAddress' => [
                        'address' => $config->senderEmail,
                        'name' => $config->senderName
                    ]
                ];
            }

            $sendOptions = [
                'http' => [
                    'header'  => "Authorization: Bearer {$accessToken}\r\n" .
                                 "Content-Type: application/json\r\n",
                    'method'  => 'POST',
                    'content' => json_encode($emailData)
                ]
            ];

            $sendContext = stream_context_create($sendOptions);
            $sendResult = @file_get_contents($sendUrl, false, $sendContext);
            $headers = $http_response_header;

            // Check for 202 Accepted
            $status_line = $headers[0];
            preg_match('/HTTP\/\S+\s(\d{3})/', $status_line, $matches);
            $status = $matches[1] ?? '0';

            if ($status == '202') {
                return true;
            } else {
                 return "Send Mail Error: API returned status $status. $sendResult";
            }

        } catch (\Exception $e) {
            return "Exception: " . $e->getMessage();
        }
    }

    /**
     * 异步发送通知邮件 (Microsoft Graph)
     *
     * @access public
     * @param int $coid 评论id
     * @return void
     */
    public static function sendMSGraphMail(int $coid)
    {
        self::sendGraphNotifications($coid, false);
    }

    public static function sendApprovedMSGraphMail(int $coid)
    {
        self::sendGraphNotifications($coid, true);
    }

    private static function sendGraphNotifications(int $coid, bool $approval)
    {
        $label = $approval ? 'MSGraph邮件：评论审核通过' : 'MSGraph邮件';
        libs\DB::log($coid, 'log', $label . '：发送开始');
        $pluginOptions = Utils\Helper::options()->plugin('Notice');
        if (!in_array('msgraph', libs\Settings::normalizeChannels($pluginOptions->setting), true)
            || empty($pluginOptions->msgraphTenantId) || empty($pluginOptions->msgraphClientId)
            || empty($pluginOptions->msgraphClientSecret) || empty($pluginOptions->msgraphSenderEmail)) {
            libs\DB::log($coid, 'log', $label . '：渠道未启用或缺少关键参数');
            return;
        }
        $comment = Utils\Helper::widgetById('comments', $coid);
        if (!($comment instanceof Widget\Base\Comments) || !$comment->have()) {
            libs\DB::log($coid, 'log', $label . '：评论不存在，跳过');
            return;
        }
        $config = (object)[
            'tenantId' => $pluginOptions->msgraphTenantId,
            'clientId' => $pluginOptions->msgraphClientId,
            'clientSecret' => $pluginOptions->msgraphClientSecret,
            'senderEmail' => $pluginOptions->msgraphSenderEmail,
            'senderName' => !empty($pluginOptions->msgraphSenderName)
                ? $pluginOptions->msgraphSenderName : Utils\Helper::options()->title
        ];
        $recipients = libs\MailRecipients::forComment($comment, $approval);
        $subjects = [
            'owner' => (string)$pluginOptions->titleForOwner,
            'guest' => (string)$pluginOptions->titleForGuest,
            'approved' => (string)$pluginOptions->titleForApproved
        ];
        foreach ($recipients as $recipient) {
            $template = $recipient['template'];
            $subject = libs\ShortCut::replace($subjects[$template], $coid);
            $body = libs\ShortCut::replace(libs\ShortCut::getTemplate($template), $coid);
            $result = self::sendViaGraphApi($recipient['mail'], $recipient['name'], $subject, $body, $config);
            libs\DB::log($coid, 'mail', $result === true ? 'MSGraph发送成功' : 'MSGraph发送失败：' . $result);
        }
        libs\DB::log($coid, 'log', $label . '：发送结束');
    }

}
