<?php
include_once 'func.php';
if (false == check_cookie()) {
	die('Unauthorized access!');
}


if (!empty($_POST['delid'])) {
	if ($db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite')) {
		$sth = $db->prepare("SELECT dirname FROM wpbackups WHERE id = :id");
		$sth->bindValue(':id', $_POST['delid'], SQLITE3_INTEGER);
		$res = $sth->execute();
		if ($result = $res->fetchArray()) {
			$del = sprintf('rm -r %s', DATAPATH.$result['dirname']);
			exec($del);
			if (!file_exists(DATAPATH.$result['dirname'])) {
				$del = $db->prepare("DELETE FROM wpbackups WHERE id = :id");
				$del->bindValue(':id', $_POST['delid'], SQLITE3_INTEGER);
				$del->execute();
				echo 'okay';
			}
		} else {
			// show some error
		}
	}
}
