<?php
echo 'loaded=' . php_ini_loaded_file() . PHP_EOL;
echo 'scan=' . php_ini_scanned_files() . PHP_EOL;
echo 'extdir=' . ini_get('extension_dir') . PHP_EOL;
echo 'curl=' . (function_exists('curl_init') ? 'yes' : 'no') . PHP_EOL;
