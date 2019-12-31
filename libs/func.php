<?php

define('MYBACKUPDIR', dirname(dirname(__FILE__)).'/');
define('ABSPATH', dirname(MYBACKUPDIR).'/');



function get_db_conn_vals($dir) {
	$wp_config = $dir.'wp-config.php';
	$conn = array();
	if ( file_exists($wp_config) ) {
		if ($fc = fopen($wp_config, 'r') ) {
			while (! feof($fc)) {
				$line = fgets($fc);
				if ( preg_match('/^\s*define\s*\(\s*[\'"]DB_NAME[\'"]\s*,\s*[\'"](.+?)[\'"]/', $line, $match) ) {
					$conn['DB_NAME'] = $match[1];
				} elseif ( preg_match('/^\s*define\s*\(\s*[\'"]DB_USER[\'"]\s*,\s*[\'"](.+?)[\'"]/', $line, $match) ) {
					$conn['DB_USER'] = $match[1];
				} elseif ( preg_match('/^\s*define\s*\(\s*[\'"]DB_PASSWORD[\'"]\s*,\s*([\'"])(.+?)\1/', $line, $match) ) {
					$conn['DB_PASSWORD'] = $match[2];
				} elseif ( preg_match('/^\s*define\s*\(\s*[\'"]DB_HOST[\'"]\s*,\s*[\'"](.+?)[\'"]/', $line, $match) ) {
					$conn['DB_HOST'] = $match[1];
				}
			}
			fclose($fc);
		}
	}
	return $conn;
}

function restore_database($host, $username, $password, $dbname, $sql_path){
    $db = new mysqli($host, $username, $password, $dbname);
    $templine = '';
    $lines = file($sql_path);
    $error = '';
    foreach ($lines as $line){
        // Continue it if it's a comment empty row
        if(substr($line, 0, 2) == '--' || $line == ''){
            continue;
        }
        $templine .= $line;
        // If it has a semicolon at the end, it's the end of the query
        if (substr(trim($line), -1, 1) == ';'){
            if(!$db->query($templine)){
                $error .= 'Error performing "<b>' . $templine . '</b>": ' . $db->error . '<br />';
            }
            $templine = '';
        }
    }
	$db->close();
    return ($error != '') ? $error : true;
}

// Credits to Arseny Mogilev who posted this function to the PHP manual
function filesizeConvert($bytes) {
    $bytes = floatval($bytes);
	$arBytes = array(
		0 => array(
			'UNIT' => 'TB',
			'VALUE' => pow(1024, 4)
		),
		1 => array(
			'UNIT' => 'GB',
			'VALUE' => pow(1024, 3)
		),
		2 => array(
			'UNIT' => 'MB',
			'VALUE' => pow(1024, 2)
		),
		3 => array(
			'UNIT' => 'KB',
			'VALUE' => 1024
		),
		4 => array(
			'UNIT' => 'B',
			'VALUE' => 1
		),
	);
    foreach($arBytes as $arItem) {
        if($bytes >= $arItem['VALUE']) {
            $result = $bytes / $arItem['VALUE'];
            $result = str_replace('.', ',' , strval(round($result, 2))).' '.$arItem['UNIT'];
            break;
        }
    }
    return $result;
}

function dirSize($directory) {
    $size = 0;
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file){
        $size+=$file->getSize();
    }
    return $size;
}
