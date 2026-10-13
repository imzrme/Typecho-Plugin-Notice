<?php
namespace TypechoPlugin\Notice\libs;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Typecho;
use Utils;
use Widget;

require_once __DIR__ . '/Settings.php';

class SettingAction extends Typecho\Widget implements Widget\ActionInterface
{
    private Typecho\Db $_db;
    private string $_pluginName='plugin:Notice';
    private string $_pluginBackupName='plugin:Notice-Backup';

    /**
     * @throws Typecho\Db\Exception
     */
    private function backup(): array
    {
        $setting = $this->findSetting($this->_pluginName);
        if (!$setting || Settings::decode($setting['value']) === null) {
            return ['success' => false, 'message' => '当前配置不存在或格式无效，无法备份。'];
        }
        $value = $setting['value'];
        if ($this->findSetting($this->_pluginBackupName)) {
            $update = $this->_db->update('table.options')->rows(array('value' => $value))
                ->where('name = ?', $this->_pluginBackupName)->where('user = ?', 0);
            $this->_db->query($update);
        } else {
            $insert = $this->_db->insert('table.options')->rows(array('name' => $this->_pluginBackupName, 'user' => '0', 'value' => $value));
            $this->_db->query($insert);
        }
        return ['success' => true, 'message' => '配置已备份。'];
    }

    /**
     * @throws Typecho\Db\Exception
     */
    private function del_backup(): array
    {
        if (!$this->findSetting($this->_pluginBackupName)) {
            return ['success' => false, 'message' => '当前没有可删除的备份。'];
        }
        $delete = $this->_db->delete('table.options')->where('name = ?', $this->_pluginBackupName)->where('user = ?', 0);
        $this->_db->query($delete);
        return ['success' => true, 'message' => '备份已删除。'];
    }

    /**
     * @throws Typecho\Db\Exception
     */
    private function recover_backup(): array
    {
        $backup = $this->findSetting($this->_pluginBackupName);
        if (!$backup) {
            return ['success' => false, 'message' => '当前没有可恢复的备份。'];
        }
        $settings = Settings::decode($backup['value']);
        if ($settings === null) {
            return ['success' => false, 'message' => '备份格式无效，当前配置未被覆盖。'];
        }
        Settings::save($settings, true);
        return ['success' => true, 'message' => '备份已恢复。'];
    }

    private function findSetting(string $name): array
    {
        return $this->_db->fetchRow($this->_db->select()->from('table.options')
            ->where('name = ?', $name)->where('user = ?', 0)->limit(1)) ?: [];
    }

    /**
     * @throws Typecho\Db\Exception
     */
    protected function init(){
        $this->_db = Typecho\Db::get();
    }

    /**
     * @throws Typecho\Db\Exception
     */
    public function action()
    {
        if (!$this->request->isPost()) {
            $this->response->setStatus(405)->setHeader('Allow', 'POST')
                ->throwJson(['success' => false, 'message' => '此操作只接受 POST 请求。']);
            return;
        }
        Typecho\Widget::widget('Widget_User')->pass('administrator');
        Utils\Helper::security()->protect();

        $operation = $this->request->get('do');
        try {
            switch ($operation) {
                case 'backup':
                    $result = $this->backup();
                    break;
                case 'del_backup':
                    $result = $this->del_backup();
                    break;
                case 'recover_backup':
                    $result = $this->recover_backup();
                    break;
                default:
                    $this->response->setStatus(400);
                    $result = ['success' => false, 'message' => '未知的配置操作。'];
            }
        } catch (\Exception $e) {
            $this->response->setStatus(500);
            $result = ['success' => false, 'message' => '配置操作失败，请检查数据库或配置数据。'];
        }
        $result['operation'] = $operation;
        $this->response->throwJson($result);
    }
}
