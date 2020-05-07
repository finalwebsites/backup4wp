<?php
include_once 'func.php';
if (false == check_cookie()) {
	die('Unauthorized access!');
}

/** TODO **/
// exclude sql file for file restore

if (!empty($_POST['backupid'])) {
	if ($db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite')) {
		$sth = $db->prepare("SELECT dirname, excludedata, backuptype, database FROM wpbackups WHERE id = :id");
		$sth->bindValue(':id', $_POST['backupid'], SQLITE3_INTEGER);
		$res = $sth->execute();
		$result = $res->fetchArray();
		$excl = unserialize($result['excludedata']);
		$excl_str = ($result['backuptype'] == 'full') ? '--exclude mybackup' : '';
		if (count($excl) > 0) {
			foreach ($excl as $dir) {
				$pathpart = ($result['backuptype'] == 'part') ? $dir : 'wp-content/'.$dir;
				$excl_str .= ' --exclude '.$pathpart;
			}
		}
		$restore_targ = ($result['backuptype'] == 'part') ? ABSPATH.'wp-content/' : ABSPATH;
		$restore_src = ($result['backuptype'] == 'part') ? DATAPATH.$result['dirname'].'/wp-content/' : DATAPATH.$result['dirname'].'/';
		$restore = sprintf('rsync -a --delete-after %s %s %s 2>&1', $excl_str, $restore_src, $restore_targ);
		$restresp = shell_exec($restore);
		if ($restresp != '') die('An error occured while restoring the files.');

		if ($result['database'] == 1) {
			$conn = get_db_conn_vals(ABSPATH);
			if (isset($conn['DB_NAME'], $conn['DB_USER'], $conn['DB_PASSWORD'], $conn['DB_HOST'])) {
				$sqlpath = DATAPATH.$result['dirname'].'/database.sql';
				$response = restore_database($conn['DB_HOST'], $conn['DB_USER'], $conn['DB_PASSWORD'], $conn['DB_NAME'], $sqlpath);
				if (false ==  filter_var($response, FILTER_VALIDATE_BOOLEAN)) {
					die($response);
				}
			}
		}
		echo 'okay';
	}
} else {
	die('The backup ID is missing.');
}
