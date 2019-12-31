<?php
include_once 'func.php';

$str2 = 'AuthGroupFile /dev/null
AuthName "Protected Area"
AuthType Basic
AuthUserFile '.MYBACKUPDIR.'.htpasswd
require valid-user';

if (!empty($_POST['loginname']) && !empty($_POST['password'])) {
    $login = filter_var($_POST['loginname'], FILTER_SANITIZE_EMAIL);
    $password = crypt($_POST['password'], base64_encode($_POST['password']));
    $str = $login.':'.$password;

    $savePath = MYBACKUPDIR.'.htpasswd';
    $handle = fopen($savePath, 'w+');
    if (fwrite($handle, $str) === FALSE) {
        echo 'error';
    } else {
        fclose($handle);
        $savePath = MYBACKUPDIR.'.htaccess';
        $handle2 = fopen($savePath, 'w+');
        if (fwrite($handle2, $str2) === FALSE) {
            echo 'error';
        } else {
            echo 'okay';
        }
        fclose($handle2);
    }
} else {
    echo 'error';
}
