<?php
include_once 'libs/func.php';
if (false == check_cookie()) {
	die('Unauthorized access!');
}

if (!empty($_GET['dlid'])) {
	if ($db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite')) {
		$sth = $db->prepare("SELECT dirname FROM wpbackups WHERE id = :id");
		$sth->bindValue(':id', $_GET['dlid'], SQLITE3_INTEGER);
		$res = $sth->execute();
		if ($result = $res->fetchArray()) {
			$archive_file_name = $result['dirname'].'.zip';
			$str = sprintf('zip -r %s %s', $archive_file_name, $result['dirname']);
			exec($str);
			header("Content-type: application/zip");
			header("Content-Disposition: attachment; filename=" . $archive_file_name);
			header("Content-length: " . filesize($archive_file_name));
			header("Pragma: no-cache");
			header("Expires: 0");
			readfile($archive_file_name);
			exit;
		} else {
			// show some error
		}
	}
}
