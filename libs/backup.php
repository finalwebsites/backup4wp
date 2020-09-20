<?php
include_once 'func.php';
include_once 'Mysqldump.php';
if (false == check_cookie()) {
	die('Unauthorized access!');
}

$excludes_options = array('cache', 'uploads', 'themes', 'plugins');
 
/** TODO **/
// Exlcude hidden files, wp-config.php

if (isset($_POST['Submitform'])) {
	$type = ($_POST['typebackup'] == 'full') ? 'full' : 'part';
	$description = filter_var($_POST['description'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
	$dirname = $type.'_'.date('Y-m-d_H:i:s').'_'.rand(1000,9999);
	$partbackup = false;
	if ($type == 'full') {
		$backup_src = ABSPATH;
		$backup_targ = DATAPATH.$dirname;
	} else {
		$backup_src = ABSPATH.'wp-content/';
		$backup_targ = DATAPATH.$dirname.'/wp-content';
		$partbackup = true;
	}
	mkdir($backup_targ, 0755, true);
	$excl_str = '';
	$excl_array = array();
	if (!empty($_POST['exclude'])) {
		$info .= 'Excl. ';
		foreach ($_POST['exclude'] as $excl) {
			if (in_array($excl, $excludes_options)) {
				$pathpart = ($partbackup) ? $excl : 'wp-content/'.$excl;
				$excl_str .= ' --exclude \'*.zip\' --exclude '.$pathpart;
				$excl_array[] = $excl;
			}
		}
	}
	$database = 0;
	if (empty($_POST['excldb'])) {
		$conn = get_db_conn_vals(ABSPATH);

		if (isset($conn['DB_NAME'], $conn['DB_USER'], $conn['DB_PASSWORD'])) {
			$database = 1;


			$dump = new Ifsnop\Mysqldump\Mysqldump('mysql:host='.$conn['DB_HOST'].';dbname='.$conn['DB_NAME'], $conn['DB_USER'], $conn['DB_PASSWORD'], array('add-drop-table' => true));
			$dump->start(DATAPATH.$dirname.'/database.sql');
		}
	}
	$sync = sprintf('rsync -av --exclude mybackup%s %s %s', $excl_str, $backup_src, $backup_targ);
	exec($sync);
	$dirsize = dirSize(DATAPATH.$dirname);
	if ($db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite')) {
		$stmt = $db->prepare("INSERT INTO wpbackups (dirname, dirsize, insertdate, excludedata, backuptype, database, description) VALUES (:dirname, :dirsize, :insertdate, :excludedata, :backuptype, :database, :description)");
		$stmt->bindValue(':dirname', $dirname, SQLITE3_TEXT);
		$stmt->bindValue(':dirsize', $dirsize, SQLITE3_INTEGER);
		$stmt->bindValue(':insertdate', time(), SQLITE3_INTEGER);
		$stmt->bindValue(':excludedata', serialize($excl_array), SQLITE3_TEXT);
		$stmt->bindValue(':backuptype', $type, SQLITE3_TEXT);
		$stmt->bindValue(':database', $database, SQLITE3_INTEGER);
		$stmt->bindValue(':description', $description, SQLITE3_TEXT);
		if ($stmt->execute()) {
			echo 'okay';
		}
	}
}
