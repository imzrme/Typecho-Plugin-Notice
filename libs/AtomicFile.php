<?php
namespace TypechoPlugin\Notice\libs;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class AtomicFile
{
    /** Replace only after the complete file has been written in the same directory. */
    public static function write(string $filename, string $content): bool
    {
        $directory = realpath(dirname($filename));
        if ($directory === false || !is_writable($directory) || is_link($filename)) {
            return false;
        }
        $exists = file_exists($filename);
        if ($exists && (!is_file($filename) || !is_writable($filename))) {
            return false;
        }
        $permissions = $exists ? @fileperms($filename) : 0644;
        if ($permissions === false) {
            return false;
        }

        $temporary = @tempnam($directory, '.notice-');
        if ($temporary === false) {
            return false;
        }
        $handle = null;
        try {
            // tempnam can fall back to the system temporary directory.
            if (realpath(dirname($temporary)) !== $directory) {
                return false;
            }
            $handle = @fopen($temporary, 'wb');
            if ($handle === false) {
                return false;
            }
            $length = strlen($content);
            $written = 0;
            while ($written < $length) {
                $bytes = @fwrite($handle, substr($content, $written));
                if ($bytes === false || $bytes === 0) {
                    return false;
                }
                $written += $bytes;
            }
            if (!@fflush($handle) || (function_exists('fsync') && !@fsync($handle))) {
                return false;
            }
            if (!@fclose($handle)) {
                return false;
            }
            $handle = null;

            if ($exists) {
                $group = @filegroup($filename);
                if ($group === false || (@filegroup($temporary) !== $group && !@chgrp($temporary, $group))) {
                    return false;
                }
            }
            if (!@chmod($temporary, $permissions & 0666)) {
                return false;
            }
            clearstatcache(true, $temporary);
            if (@filesize($temporary) !== $length) {
                return false;
            }
            return @rename($temporary, $filename);
        } finally {
            if (is_resource($handle)) {
                @fclose($handle);
            }
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }
}
