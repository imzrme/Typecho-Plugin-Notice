<?php
namespace TypechoPlugin\Notice\libs;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Typecho;

class Settings
{
    public const CHANNELS = ['serverchan', 'qmsg', 'mail', 'msgraph', 'telegram', 'updatetip'];

    private const FIELDS = [
        'setting', 'delDB', 'enableLog', 'scKey', 'scMsg', 'QmsgKey', 'QmsgQQ', 'QmsgMsg',
        'host', 'port', 'secure', 'auth', 'user', 'password', 'from', 'from_name',
        'titleForOwner', 'titleForGuest', 'titleForApproved',
        'msgraphTenantId', 'msgraphClientId', 'msgraphClientSecret',
        'msgraphSenderEmail', 'msgraphSenderName', 'tgBotToken', 'tgChatId'
    ];

    public static function normalizeChannels($value, array $allowed = self::CHANNELS): array
    {
        $channels = [];
        if (is_array($value)) {
            foreach ($value as $channel) {
                if (is_string($channel) && in_array($channel, $allowed, true)
                    && !in_array($channel, $channels, true)) {
                    $channels[] = $channel;
                }
            }
        }
        return $channels;
    }

    public static function normalize(array $settings): array
    {
        $settings = array_intersect_key($settings, array_flip(self::FIELDS));
        $settings['setting'] = self::normalizeChannels($settings['setting'] ?? null);
        return $settings;
    }

    /** Read current JSON settings and legacy array backups without instantiating objects. */
    public static function decode(string $value): ?array
    {
        $settings = json_decode($value, true);
        if (!is_array($settings) && strpos($value, 'a:') === 0) {
            $settings = @unserialize($value, ['allowed_classes' => false]);
        }

        if (!is_array($settings) || !array_intersect_key($settings, array_flip(self::FIELDS))) {
            return null;
        }
        foreach ($settings as $key => $item) {
            if (!is_string($key) || ($key !== 'setting' && $item !== null && !is_scalar($item))) {
                return null;
            }
            if ($key === 'setting' && is_object($item)) {
                return null;
            }
        }
        return self::normalize($settings);
    }

    /** Typecho 1.3 stores plugin settings as JSON; also handle pre-upgrade serialized rows. */
    public static function save(array $settings, bool $replace = false)
    {
        $db = Typecho\Db::get();
        $row = $db->fetchRow($db->select()->from('table.options')
            ->where('name = ?', 'plugin:Notice')->where('user = ?', 0)->limit(1));
        if (!$replace && $row) {
            $settings = array_merge(self::decode($row['value']) ?? [], $settings);
        }
        $value = json_encode(self::normalize($settings), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($value === false) {
            throw new Typecho\Plugin\Exception('配置包含无法保存的数据');
        }

        if ($row) {
            $db->query($db->update('table.options')->rows(['value' => $value])
                ->where('name = ?', 'plugin:Notice')->where('user = ?', 0));
        } else {
            $db->query($db->insert('table.options')
                ->rows(['name' => 'plugin:Notice', 'user' => 0, 'value' => $value]));
        }
    }
}
