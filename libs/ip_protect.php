<?php
include_once 'func.php';

$str = 'order deny,allow
deny from all

# whitelist your own IP address
allow from '.$_SERVER['REMOTE_ADDR'];

$savePath = MYBACKUPDIR.'.htaccess';
$oldPasswdPath = MYBACKUPDIR.'.htpasswd';

if (file_exists($oldPasswdPath)) unlink($oldPasswdPath);

$handle = fopen($savePath, 'w+');
if (fwrite($handle, $str) === FALSE) {
    echo 'error';
} else {
    echo 'okay';
}
fclose($handle);
