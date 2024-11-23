<?php
include_once 'libs/func.php';
include_once 'libs/html.php';
get_authorized();

$msg = '';
$valid = true;
if (isset($_POST['update']) && $_POST['update'] == 'yes') {
	$output = shell_exec('composer update 2>&1');
} else {
	if (!file_exists('composer.json')) {
		$msg = 'The file composer.json doesn\'t exists. An update via the page is not possible.';
		$valid = false;
	}
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Update | Backup4WP</title>
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
    <link href="mbr.css" rel="stylesheet">
  </head>
  <body>
    <?php echo mb_navigation('update'); ?>
    <div class="container outwrapper">
      <div class="starter-template">
        <h1>Backup4WP <small>Update</small></h1>
        <p class="lead">Update Backup4WP using composer.</p>
        <?php 
        if ($valid) { 
        	if (!empty($output)) {
        		echo '<pre>'.$output.'</pre>';
        	} else {
        		echo '
        <form role="form" id="myform" method="post">
					<input type="hidden" name="update" value="yes">

					
					<div>
						<button type="submit" class="btn btn-primary submitbtn">Process update</button>
					</div>
        </form>';
        	}
      	} else {
      		echo '
      	<div id="msg" class="alert alert-error" role="alert">'.$msg.'</div>';
      	} 
      	?>
		
      </div>

    </div><!-- /.container -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
  </body>
</html>
