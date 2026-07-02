<?php
class Cache {
    function Wo_OpenCacheDir() {
        if (!file_exists('cache')) {
            $oldmask = umask(0);
            @mkdir('cache', 0777, true);
            @umask($oldmask);
        }
        if (!file_exists('cache/users')) {
            $oldmask = umask(0);
            @mkdir('cache/users', 0777, true);
            @umask($oldmask);
        }
        if (!file_exists('cache/groups')) {
            $oldmask = umask(0);
            @mkdir('cache/groups', 0777, true);
            @umask($oldmask);
        }
        if (!file_exists('cache/.htaccess')) {
            $f = @fopen("cache/.htaccess", "a+");
            if ($f) {
                @fwrite($f, "deny from all");
                @fclose($f);
            }
        }
        if (!file_exists('cache/index.html')) {
            $f = @fopen("cache/index.html", "a+");
            if ($f) {
                @fclose($f);
            }
        }
    }
    function read($fileName) {
        $fileName = 'cache/' . $fileName;
        if (file_exists($fileName)) {
            $handle   = fopen($fileName, 'rb');
            if ($handle) {
                $variable = fread($handle, filesize($fileName));
                fclose($handle);
                $data = @unserialize($variable);
                if (serialize($data) !== $variable) {
                    @unlink($fileName);
                    return null;
                }
                return $data;
            }
            return null;
        } else {
            return null;
        }
    }
    function write($fileName, $variable) {
        $fileName = 'cache/' . $fileName;
        $dir      = dirname($fileName);
        if (!is_dir($dir)) {
            $oldmask = umask(0);
            @mkdir($dir, 0777, true);
            @umask($oldmask);
        }
        $tmpFile = $fileName . '.' . getmypid() . '.tmp';
        if (file_put_contents($tmpFile, serialize($variable), LOCK_EX) !== false) {
            @rename($tmpFile, $fileName);
        } else {
            @unlink($tmpFile);
        }
    }
    function delete($fileName) {
        $fileName = 'cache/' . $fileName;
        @unlink($fileName);
    }
}
?>
