<?php
namespace TypechoPlugin\Notice\libs;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Typecho;

require_once __DIR__ . '/AtomicFile.php';

class Version
{
    private const CACHE_TTL = 86400;
    private const RETRY_INTERVAL = 300;

    public static function getNewRelease(): ?string
    {
        $now = time();
        $filename = __DIR__ . '/../cache/version.json';
        $cache = self::readCache($filename);
        $version = self::normalizeTag($cache['version'] ?? null);
        $updated = $cache['time'] ?? 0;
        if (!is_int($updated) || $updated <= 0 || $updated > $now) {
            $version = null;
            $updated = 0;
        }
        if ($version !== null && $updated > 0 && $now - $updated < self::CACHE_TTL) {
            return $version;
        }
        $retryAfter = $cache['retry_after'] ?? 0;
        if (is_int($retryAfter) && $retryAfter > $now && $retryAfter <= $now + self::RETRY_INTERVAL) {
            return $version;
        }

        $latest = self::getNewReleaseFromGithub();
        $data = [
            'version' => $latest ?? $version,
            'time' => $latest !== null ? $now : $updated,
            'retry_after' => $latest !== null ? 0 : $now + self::RETRY_INTERVAL
        ];
        // Failed refreshes retain the last valid version and its original timestamp.
        try {
            $content = json_encode($data, JSON_UNESCAPED_SLASHES);
            if ($content !== false) {
                AtomicFile::write($filename, $content);
            }
        } catch (\Throwable $e) {
            // Cache permissions or filesystem failures must not stop the settings page.
        }
        return $latest ?? $version;
    }

    private static function readCache(string $filename): array
    {
        try {
            if (!is_file($filename) || !is_readable($filename)) {
                return [];
            }
            $content = @file_get_contents($filename, false, null, 0, 16384);
            $cache = $content === false ? null : json_decode($content, true);
            return is_array($cache) ? $cache : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function normalizeTag($tag): ?string
    {
        if (!is_string($tag) || strlen($tag) > 128) {
            return null;
        }
        if (!preg_match('/\A[vV]?(\d+\.\d+\.\d+(?:-[0-9A-Za-z]+(?:[.-][0-9A-Za-z]+)*)?(?:\+[0-9A-Za-z]+(?:[.-][0-9A-Za-z]+)*)?)\z/', trim($tag), $matches)) {
            return null;
        }
        return $matches[1];
    }

    public static function getNewReleaseFromGithub(): ?string
    {
        try {
            $client = Typecho\Http\Client::get();
            if ($client === null) {
                return null;
            }
            $client->setTimeout(3)->setAgent('Typecho-Plugin-Notice')
                ->setHeader('Accept', 'application/vnd.github+json')
                ->setOption(CURLOPT_CONNECTTIMEOUT, 1);
            $client->send('https://api.github.com/repos/imzrme/Typecho-Plugin-Notice/releases/latest');
            if ($client->getResponseStatus() !== 200) {
                return null;
            }
            $data = json_decode($client->getResponseBody(), true);
            return is_array($data) ? self::normalizeTag($data['tag_name'] ?? null) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
