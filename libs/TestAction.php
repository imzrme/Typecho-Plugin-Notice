<?php
namespace TypechoPlugin\Notice\libs;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

require_once "phpmailer/PHPMailer.php";
require_once "phpmailer/SMTP.php";
require_once "phpmailer/Exception.php";

use Typecho;
use Utils;
use Widget;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;



use TypechoPlugin\Notice;

class TestAction extends Typecho\Widget implements Widget\ActionInterface
{

    private Widget\Options $_option;
    private $_pluginOption;
    private string $_template_dir;
    private $_currentFile;

    /**
     * 执行函数
     *
     * @access public
     * @return void
     * @throws Typecho\Widget\Exception
     * @throws Typecho\Exception
     */
    public function execute()
    {
        /** 管理员权限 */
        $this->widget('Widget_User')->pass('administrator');
        $this->_template_dir = Utils\Helper::options()->pluginDir() . '/Notice/template';
        $files = glob($this->_template_dir . '/*.{html,HTML}', GLOB_BRACE);
        $this->_currentFile = $this->request->get('file', 'owner.html');

        if (preg_match("/^([_0-9a-z-\.\ ])+$/i", $this->_currentFile)
            && file_exists($this->_template_dir . '/' . $this->_currentFile)) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $file = basename($file);
                    $this->push(array(
                        'file' => $file,
                        'current' => ($file == $this->_currentFile)
                    ));
                }
            }

            return;
        }

        throw new Typecho\Widget\Exception('模板文件不存在', 404);
    }

    /**
     * 获取标题
     *
     * @access public
     * @return string
     */
    public function getTitle(): string
    {
        return _t('编辑邮件模版 %s', $this->_currentFile);
    }


    /**
     * 获取文件内容
     *
     * @access public
     * @return string
     */
    public function currentContent(): string
    {
        return htmlspecialchars(file_get_contents($this->_template_dir . '/' . $this->_currentFile));
    }

    /**
     * 获取文件是否可读
     *
     * @access public
     * @return string
     */
    public function currentIsWriteable(): string
    {
        return is_writeable($this->_template_dir . '/' . $this->_currentFile);
    }

    /**
     * 获取当前文件
     *
     * @access public
     * @return string
     */
    public function currentFile(): string
    {
        return $this->_currentFile;
    }

    /**
     * 邮件测试表单
     * @param $type string
     * @return Typecho\Widget\Helper\Form
     */
    public function testForm(string $type): Typecho\Widget\Helper\Form
    {
        /** 构建表单 */
        $options = Typecho\Widget::widget('Widget_Options');
        $action = array(
            'mail' => 'send_test_mail',
            'msgraph' => 'send_test_msgraph',
            'qmsg' => 'send_test_qmsgchan',
            'serverchan' => 'send_test_serverchan',
            'telegram' => 'send_test_telegram'
        );
        $form = new Typecho\Widget\Helper\Form(Typecho\Common::url('/action/' . Notice\Plugin::$action_test . '?do=' . $action[$type], $options->index),
            Typecho\Widget\Helper\Form::POST_METHOD);

        $title = new Typecho\Widget\Helper\Form\Element\Text('title', NULL, '测试文章标题', _t('文章标题'));
        $form->addInput($title->addRule('required', '必须填写文章标题'));

        $author = new Typecho\Widget\Helper\Form\Element\Text('author', NULL, '测试评论者', _t('评论者'));
        $form->addInput($author->addRule('required', '必须填写评论者名字'));

        $mail = new Typecho\Widget\Helper\Form\Element\Text('mail', NULL, NULL, _t('评论者邮箱'));
        $form->addInput($mail->addRule('required', '必须填写评论者邮箱')->addRule('email', _t('邮箱地址不正确')));

        $ip = new Typecho\Widget\Helper\Form\Element\Text('ip', NULL, '1.1.1.1', _t('评论者 IP'));
        $form->addInput($ip->addRule('required', '必须填写评论者ip'));

        $text = new Typecho\Widget\Helper\Form\Element\Textarea('text', NULL, '测试评论内容_(:з」∠)_', _t('评论内容'));
        $form->addInput($text->addRule('required', '必须填写评论内容'));

        if ($type != 'telegram') {
            $author_p = new Typecho\Widget\Helper\Form\Element\Text('author_p', NULL, NULL, _t('被评论者'));
            $form->addInput($author_p);

            $text_p = new Typecho\Widget\Helper\Form\Element\Textarea('text_p', NULL, NULL, _t('被评论内容'));
            $form->addInput($text_p);
        }

        $permalink = new Typecho\Widget\Helper\Form\Element\Text('permalink', NULL, Utils\Helper::options()->index, _t('评论链接'));
        $form->addInput($permalink);

        $status = new Typecho\Widget\Helper\Form\Element\Select('status', array(
            "通过"=>"通过", "待审"=>"待审", "垃圾"=>"垃圾"), "待审", '评论状态');
        $form->addInput($status);

        if ($type == 'mail' || $type == 'msgraph') {
            $senderName = new Typecho\Widget\Helper\Form\Element\Text('senderName', NULL,
                '', _t('发件人名称'), _t('留空则使用配置默认值'));
            $form->addInput($senderName);

            $toName = new Typecho\Widget\Helper\Form\Element\Text('toName', NULL,
                '', _t('收件人名称'));
            $form->addInput($toName->addRule('required', '必须填写接收人名称'));

            $to = new Typecho\Widget\Helper\Form\Element\Text('to', NULL,
                '', _t('收件人邮箱'));
            $form->addInput($to->addRule('required', '必须填写接收邮箱')->addRule('email', _t('邮箱地址不正确')));

            $template = new Typecho\Widget\Helper\Form\Element\Select('template', array(
                'owner' => 'owner',
                'guest' => 'guest',
                'approved' => 'approved'
            ), 'owner', '邮件模板', '选择发信模板');
            $form->addInput($template);
        }

        $submit = new Typecho\Widget\Helper\Form\Element\Submit();
        $submit->input->setAttribute('class', 'notice-md3-inline-submit');
        $form->addItem($submit);
        $submit->value('发送测试');


        return $form;
    }

    private function getArray(): array
    {
        $form = $this->request->from('title', 'author', 'mail', 'ip', 'text', 'author_p', 'text_p', 'permalink', 'status', 'time');
        $timestamp = isset($form['time']) && is_numeric($form['time']) ? (int)$form['time'] : time();
        $date = new Typecho\Date($timestamp);

        return array(
            $this->_option->title,
            $form['title'],
            $form['author'],
            $form['author_p'],
            $form['ip'],
            $form['mail'],
            $form['permalink'],
            $this->_option->siteUrl . __TYPECHO_ADMIN_DIR__ . "manage-comments.php",
            $form['text'],
            $form['text_p'],
            $date->format('Y-m-d H:i:s'),
            $form['status']
        );
    }

    /**
     * @throws Typecho\Widget\Exception
     */
    private function replace($type)
    {
        $msg = '';
        switch ($type) {
            case 'serverchan':
                $msg = $this->_pluginOption->scMsg;
                break;
            case 'qmsg':
                $msg = $this->_pluginOption->QmsgMsg;
                break;
            case 'mail':
            case 'msgraph':
                switch ($this->request->from('template')['template']) {
                    case 'owner':
                        $msg = Notice\libs\ShortCut::getTemplate('owner');
                        break;
                    case 'guest':
                        $msg = Notice\libs\ShortCut::getTemplate('guest');
                        break;
                    case 'approved':
                        $msg = Notice\libs\ShortCut::getTemplate('approved');
                        break;
                }
                break;
        }
        $replace = self::getArray();
        return Notice\libs\ShortCut::replaceArray($msg, $replace);
    }

    /**
     * @throws Typecho\Db\Exception
     * @throws Typecho\Widget\Exception|Typecho\Plugin\Exception
     */
    public function sendTestServerchan()
    {
        if (Typecho\Widget::widget('Notice_libs_TestAction')->testForm('serverchan')->validate()) {
            $this->response->goBack();
        }
        $msg = self::replace('serverchan');
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
        $result = file_get_contents('https://sc.ftqq.com/' . $this->_pluginOption->scKey . '.send', false, $context);
        /** 日志 */
        Notice\libs\DB::log('0', 'wechat', "测试\n" . $result . "\n\n" . $msg);
        $result = json_decode($result, true);
        /** 提示信息 */
        $this->widget('Widget_Notice')->set(isset($result['code']) && $result['code'] == 0 ? _t('发送成功') : _t('发送失败：' . ($result['message'] ?? '未知错误')),
            isset($result['code']) && $result['code'] == 0 ? 'success' : 'notice');

        /** 转向原页 */
        $this->response->goBack();
    }

    /**
     * @throws Typecho\Db\Exception
     * @throws Typecho\Widget\Exception|Typecho\Plugin\Exception
     */
    public function sendTestQmsgchan()
    {
        if (Typecho\Widget::widget('Notice_libs_TestAction')->testForm('qmsg')->validate()) {
            $this->response->goBack();
        }
        $msg = self::replace('qmsg');
        if ($this->_pluginOption->QmsgQQ == NULL) {
            $post_data = http_build_query(
                array(
                    'msg' => $msg
                )
            );
        } else {
            $post_data = http_build_query(
                array(
                    'msg' => $msg,
                    'qq' => $this->_pluginOption->QmsgQQ
                )
            );
        }

        $opts = array('http' =>
            array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $post_data
            )
        );
        $context = stream_context_create($opts);
        $result = file_get_contents('https://qmsg.zendee.cn/send/' . $this->_pluginOption->QmsgKey, false, $context);
        /** 日志 */
        Notice\libs\DB::log('0', 'qq', "测试\n" . $result . "\n\n" . $msg);
        $result = json_decode($result, true);
        /** 提示信息 */
        $this->widget('Widget_Notice')->set(true === $result['success'] ? _t('发送成功') : _t('发送失败：' . $result["reason"]),
            true === $result['success'] ? 'success' : 'notice');

        /** 转向原页 */
        $this->response->goBack();
    }

    /**
     * @throws PHPMailerException
     * @throws Typecho\Db\Exception
     * @throws Typecho\Widget\Exception|Typecho\Plugin\Exception
     */
    public function sendTestMail()
    {
        if (Typecho\Widget::widget('Notice_libs_TestAction')->testForm('mail')->validate()) {
            $this->response->goBack();
        }
        $msg = self::replace('mail');

        $mail = new PHPMailer(false);
        $mail->isSMTP();
        $mail->Host = $this->_pluginOption->host;
        $mail->SMTPAuth = !!$this->_pluginOption->auth;
        $mail->Username = $this->_pluginOption->user;
        $mail->Password = $this->_pluginOption->password;
        $mail->SMTPSecure = $this->_pluginOption->secure;
        $mail->Port = $this->_pluginOption->port;
        $mail->getSMTPInstance()->setTimeout(10);
        $mail->isHTML(true);
        $mail->CharSet = 'utf-8';
        
        $senderName = $this->request->get('senderName');
        $fromName = !empty($senderName) ? $senderName : $this->_pluginOption->from_name;
        $mail->setFrom($this->_pluginOption->from, $fromName);
        
        var_dump($this->request->get('to'));
        $mail->addAddress($this->request->get('to'), $this->request->get('toName'));
        $mail->Body = $msg;


        switch ($this->request->from('template')['template']) {
            case 'owner':
                $mail->Subject = Notice\libs\ShortCut::replaceArray($this->_pluginOption->titleForOwner, self::getArray());
                $mail->AltBody = "作者：" .
                    $this->request->get('author') . "\r\n链接：" .
                    $this->request->get('permalink') .
                    "\r\n评论：\r\n" .
                    $this->request->get('text');
                break;
            case 'guest':
                $mail->Subject = Notice\libs\ShortCut::replaceArray($this->_pluginOption->titleForGuest, self::getArray());
                $mail->AltBody = "作者：" .
                    $this->request->get('author') .
                    "\r\n链接：" .
                    $this->request->get('permalink') .
                    "\r\n评论：\r\n" .
                    $this->request->get('text');
                break;
            case 'approved':
                $mail->Subject = Notice\libs\ShortCut::replaceArray($this->_pluginOption->titleForApproved, self::getArray());
                $mail->AltBody = "您的评论已通过审核。\n";
                break;
        }

        $result = $mail->send();
        /** 日志 */
        Notice\libs\DB::log('0', 'mail', "测试\n" . $result . "\n\n" . $msg);
        /** 提示信息 */
        $this->widget('Widget_Notice')->set(true === $result ? _t('发送成功') : _t('发送失败：' . $result),
            true === $result ? 'success' : 'notice');

        /** 转向原页 */
        $this->response->goBack();


    }

    /**
     * @throws Typecho\Db\Exception
     * @throws Typecho\Widget\Exception|Typecho\Plugin\Exception
     */
    public function sendTestMSGraph()
    {
        if (Typecho\Widget::widget('Notice_libs_TestAction')->testForm('msgraph')->validate()) {
            $this->response->goBack();
        }
        $msg = self::replace('msgraph');



        $senderName = $this->request->get('senderName');
        if (empty($senderName)) {
            $senderName = !empty($this->_pluginOption->msgraphSenderName) ? $this->_pluginOption->msgraphSenderName : Utils\Helper::options()->title;
        }

        $config = (object)[
            'tenantId' => $this->_pluginOption->msgraphTenantId,
            'clientId' => $this->_pluginOption->msgraphClientId,
            'clientSecret' => $this->_pluginOption->msgraphClientSecret,
            'senderEmail' => $this->_pluginOption->msgraphSenderEmail,
            'senderName' => $senderName
        ];

        $to = $this->request->get('to');
        $toName = $this->request->get('toName');
        $content = $msg;
        $subject = ""; // Initialize subject

        switch ($this->request->from('template')['template']) {
            case 'owner':
                $subject = Notice\libs\ShortCut::replaceArray($this->_pluginOption->titleForOwner, self::getArray());
                break;
            case 'guest':
                $subject = Notice\libs\ShortCut::replaceArray($this->_pluginOption->titleForGuest, self::getArray());
                break;
            case 'approved':
                $subject = Notice\libs\ShortCut::replaceArray($this->_pluginOption->titleForApproved, self::getArray());
                break;
        }

        $result = Notice\Plugin::sendViaGraphApi($to, $toName, $subject, $content, $config);
        
        /** 日志 */
        Notice\libs\DB::log('0', 'mail', "MSGraph测试\n" . ($result === true ? "成功" : $result) . "\n\n" . $msg);
        
        /** 提示信息 */
        $this->widget('Widget_Notice')->set(true === $result ? _t('发送成功') : _t('发送失败：' . $result),
            true === $result ? 'success' : 'notice');

        /** 转向原页 */
        $this->response->goBack();
    }

    /**
     * @throws Typecho\Db\Exception
     * @throws Typecho\Widget\Exception|Typecho\Plugin\Exception
     */
    public function sendTestTelegram()
    {
        if (Typecho\Widget::widget('Notice_libs_TestAction')->testForm('telegram')->validate()) {
            $this->response->goBack();
        }
        
        $form = $this->request->from('title', 'author', 'mail', 'text', 'permalink', 'status');
        $title = Notice\libs\ShortCut::replaceArray($this->_pluginOption->titleForOwner, self::getArray());
        
        // 判断评论状态
        $isPending = $form['status'] == '待审';
        $buttonUrl = $form['permalink'];
        $buttonText = _t('👀 查看评论');

        if ($isPending) {
            $title = "🎉 [" . $form['title'] . "]一文有待审核的评论";
            // 构造管理页面链接
            $adminUrl = rtrim(Utils\Helper::options()->siteUrl, '/') . '/' . ltrim(__TYPECHO_ADMIN_DIR__, '/') . 'manage-comments.php?status=waiting';
            $buttonUrl = $adminUrl;
            $buttonText = _t('🔕 管理评论');
        }

        // 构造消息
        $msg = $isPending ? ($title . "\n") : ("🎉 " . $title . "\n");
        $msg .= "评论者: `" . $form['author'] . "`\n";
        $msg .= "邮箱: `" . $form['mail'] . "`\n";
        $msg .= "评论内容:" . $form['text'];
        
        // 构造按钮
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => $buttonText, 'url' => $buttonUrl]
                ]
            ]
        ];

        $botToken = $this->_pluginOption->tgBotToken;
        $chatId = $this->_pluginOption->tgChatId;
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
        
        Notice\libs\DB::log('0', 'telegram', "测试\n" . $result . "\n\n" . $msg);
        $resultArr = json_decode($result, true);
        
        $this->widget('Widget_Notice')->set(
            isset($resultArr['ok']) && $resultArr['ok'] === true ? _t('发送成功') : _t('发送失败：' . ($resultArr['description'] ?? '未知错误')),
            isset($resultArr['ok']) && $resultArr['ok'] === true ? 'success' : 'notice'
        );
        
        $this->response->goBack();
    }

    /**
     * 编辑模板文件
     * @param $file
     * @throws Typecho\Widget\Exception
     */
    public function editTheme($file)
    {
        $path = Utils\Helper::options()->pluginDir() . '/Notice/template/' . $file;
        if (file_exists($path) && is_writeable($path)) {
            $handle = fopen($path, 'wb');
            if ($handle && fwrite($handle, $this->request->content)) {
                fclose($handle);
                $this->widget('Widget_Notice')->set(_t("文件 %s 的更改已经保存", $file), 'success');
            } else {
                $this->widget('Widget_Notice')->set(_t("文件 %s 无法被写入", $file), 'error');
            }
            $this->response->goBack();
        } else {
            throw new Typecho\Widget\Exception(_t('您编辑的模板文件不存在'));
        }
    }

    /**
     * @throws Typecho\Plugin\Exception
     */
    public function init()
    {
        $this->_option = Utils\Helper::options();
        $this->_pluginOption = Utils\Helper::options()->plugin('Notice');
    }

    /**
     * @throws PHPMailerException
     * @throws Typecho\Db\Exception
     * @throws Typecho\Widget\Exception
     * @throws Typecho\Plugin\Exception
     */
    public function action()
    {
        Typecho\Widget::widget('Widget_User')->pass('administrator');
        $this->init();
        $this->on($this->request->is('do=send_test_serverchan'))->sendTestServerchan();
        $this->on($this->request->is('do=send_test_qmsgchan'))->sendTestQmsgchan();
        $this->on($this->request->is('do=send_test_mail'))->sendTestMail();
        $this->on($this->request->is('do=send_test_msgraph'))->sendTestMSGraph();
        $this->on($this->request->is('do=send_test_telegram'))->sendTestTelegram();
        $this->on($this->request->is('do=edit_theme'))->editTheme($this->request->file);
    }

}
