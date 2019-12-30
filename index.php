<?php
include_once 'libs/func.php';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Backup &amp; Restore for WordPress</title>
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
    <link href="mbr.css" rel="stylesheet">
  </head>
  <body>
    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container outwrapper">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">WordPress backup</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li class="active"><a href="index.php">Backup &amp; Restore</a></li>
            <li><a href="/">Home</a></li>
            <li><a href="../wp-admin/">WP Admin</a></li>
            <li><a href="data/phpliteadmin.php" target="_blank">phpLiteAdmin</a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>
    <div class="container outwrapper">
      <div class="starter-template">
        <h1>Backup &amp; Restore for WordPress <small>beta</small></h1>
        <p class="lead">Create backups from your WordPress website and restore files if necessary.</p>
        <?php
        $required = true;
        if (function_exists('exec')) {
            if ('' == exec('rsync --version ')) {
                echo '
                <p class="text-warning">The required Linux tool "rsync" is not available.</p>';
                $required = false;
            }
        } else {
            echo '
                <p class="text-warning">The required PHP function "exec()" is not enabled.</p>';
                $required = false;
        }
        if ($required) {
        ?>
        <form role="form" id="myform">
			<input type="hidden" name="Submitform" value="1">
			<p>Check which directories you like to exclude from the backup. The "mybackup" directory is always excluded!</p>
			<div class="form-group">
				<label class="checkbox-inline">
				<input type="checkbox" name="exclude[]" value="cache" checked>
				excl. cache
			  </label>
			  <label class="checkbox-inline">
				<input type="checkbox" name="exclude[]" value="uploads" checked>
				excl. uploads
			  </label>
			  <label class="checkbox-inline">
				<input type="checkbox" name="exclude[]" value="themes">
				excl. themes
			  </label>
			  <label class="checkbox-inline">
				<input type="checkbox" name="exclude[]" value="plugins">
				excl. plugins
			  </label>
			  <label class="checkbox-inline text-muted">
				<input type="checkbox" name="excldb" value="1">
				excl. Database
			  </label>
			</div>
			<div class="form-group">
				<label>Backup description</label>
				<input type="text" class="form-control descr" name="description" placeholder="Optional...">
			</div>
            <button type="button" class="btn btn-default submitbtn" value="full">Full backup (incl. WP Core)</button>
            <button type="button" class="btn btn-primary submitbtn" value="part">Part. backup (wp-content dir)</button>
        </form>
        <?php } // end if $required ?>

		<div id="msg" class="" role="alert"></div>
		<table class="table table-striped filelist">
			<thead>
				<tr>
				  <th>#</th>
				  <th>Filename</th>
				  <th>Filesize</th>
				  <th>Backup date &amp; time</th>
				  <th colspan="2">Actions</th>
				</tr>
			</thead>
			<tbody>
			<?php
			$i = 1;
			try {
				//open the database
				$types = array('full' => 'Full backup', 'part' => 'Partial backup');
				$db = new PDO('sqlite:data/wpbackupsDb_PDO.sqlite');
				$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$default = '<tr><th>&nbsp;</th><td colspan="6">No backups right now!</td></tr>';
				$result = $db->query("SELECT * FROM wpbackups WHERE 1 ORDER BY insertdate DESC");
				$tablehtml = '';
				foreach ($result as $res) {
					$details = $types[$res['backuptype']];
					if ($res['database'] == 0) $details .= ' - No database';
					$details .= ' - Excl. directories: ';
					if ($excl = unserialize($res['excludedata'])) {
						$details .= (count($excl) > 0) ? implode(', ', $excl) : 'none';
					} else {
						$details .= 'none';
					}
					$tablehtml .= '
					<tr id="'.$res['id'].'">
					  <th scope="row">'.$i.'</th>
					  <td>'.$res['dirname'].'<br><em>'.$details.'</em><br><em><strong>'.$res['description'].'</strong></em></td>
					  <td>'.filesizeConvert($res['dirsize']).'</td>
					  <td>'.date('d-m-Y H:i:s', $res['insertdate']).'</td>
					  <td><a href="javascript:void(0);" class="btn btn-warning btn-xs restore">Restore</a></td>
					  <td><a href="javascript:void(0);" class="btn btn-danger btn-xs delete">Delete</a></td>
					</tr>';
					$i++;
				}
				echo ($tablehtml != '') ? $tablehtml : $default;
			} catch(PDOException $e) {
				echo '<tr><th>&nbsp;</th><td colspan="6">Exception : '.$e->getMessage().'</td></tr>';
			}
			?>
			</tbody>
		</table>
      </div>

    </div><!-- /.container -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
	<script>

	jQuery(document).ready(function($) {

		$('.submitbtn').click(function(e) {
			$('#msg').removeClass('alert alert-success').html('');
			var btn = $(this);
			var btntext = $(this).text();
			var btnval = $(this).val();
			btn.text('Please wait...');
			$.ajax({
				url: "libs/backup.php",
                type: 'POST',
                data: $('form#myform').serialize() + '&typebackup=' + btnval,
                success: function (data) {
					btn.text(btntext);
					if (data == 'okay') {
						$('#msg').addClass('alert alert-success').html('The WordPress backup was succesfully, <a href="index.php">click here</a> to refresh the backup list.');
                        setTimeout(location.reload.bind(location), 5000);
					} else {
                        $('#msg').addClass('alert alert-danger').html(data);
					}
                }
			});
			e.preventDefault();
		});

		$('.delete').click(function(e) {
			$('#msg').removeClass('alert alert-success').html('<img src="img/loadingAnimation.gif" alt="Please wait...">');
			var id = $(this).closest('tr').attr('id');
			$.ajax({
				url: "libs/delete.php",
                type: 'POST',
                data: 'delid=' + id,
                success: function (data) {
					if (data == 'okay') {
						$('#msg').addClass('alert alert-success').html('The WordPress backup is removed, <a href="index.php">click here</a> to refresh the backup list.');
						setTimeout(location.reload.bind(location), 5000);
					} else {
                        //
					}
                }
			});
			e.preventDefault();
		});

		$('.restore').click(function(e) {
			$('#msg').removeClass('alert alert-danger alert-success').html('<img src="img/loadingAnimation.gif" alt="Please wait...">');
			var id = $(this).closest('tr').attr('id');
			$.ajax({
				url: "libs/restore.php",
                type: 'POST',
                data: 'backupid=' + id,
                success: function (data) {
					if (data == 'okay') {
						$('#msg').addClass('alert alert-success').html('The WordPress backup is succesfully restored.');
						setTimeout(location.reload.bind(location), 5000);
					} else {
						$('#msg').addClass('alert alert-danger').html(data);
					}
                }
			});
			e.preventDefault();
		});

		$('.download').click(function(e) {
			alert('Not available, maybe in the next release.');
			e.preventDefault();
		});
	});

    </script>
  </body>
</html>
