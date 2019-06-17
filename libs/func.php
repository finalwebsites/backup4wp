<?php

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
				}
			}
			fclose($fc);
		}
	}
	return $conn;
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
