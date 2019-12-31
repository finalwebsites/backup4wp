<?php
include_once 'func.php';

if (!empty($_POST['delid'])) {
	$db = new PDO('sqlite:../data/wpbackupsDb_PDO.sqlite');
	$sth = $db->prepare("SELECT dirname FROM wpbackups WHERE id = :id");
	$sth->bindValue(':id', $_POST['delid'], SQLITE3_INTEGER);
	$sth->execute();
	if ($result = $sth->fetch(PDO::FETCH_OBJ)) {
		$del = sprintf('rm -r %sfiles/%s', MYBACKUPDIR, $result->dirname);
		exec($del);
		if (!file_exists(MYBACKUPDIR.'files/'.$result->dirname)) {
			$del = $db->prepare("DELETE FROM wpbackups WHERE id = :id");
			$del->bindValue(':id', $_POST['delid'], SQLITE3_INTEGER);
			$del->execute();
			echo 'okay';
		}
	} else {
		// show some error
	}
}
