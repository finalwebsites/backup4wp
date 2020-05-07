<?php
include_once 'libs/func.php';

$alert_css = '';
$msg = '';

$messages = array(
	'expiredlink' => 'The access URL or link is expired. Create a new one.',
	'invalidsession' => 'Invalid sessions or IP address.',
	'notfound' => 'The access URL or link is not valid.',
	'cookieexpired' => 'The cookie is not valid anymore. You need to request a new access URL.'
);

if (isset($_GET['msg']) && array_key_exists($_GET['msg'], $messages)) {
	$msg = $messages[$_GET['msg']];
	$alert_css = 'alert alert-warning';
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Request access | MyBackup for WordPress</title>
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
          <a class="navbar-brand" href="#">MyBackup for WordPress</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li><a href="index.php">Backup &amp; Restore</a></li>
            <li><a href="/">Home</a></li>
            <li><a href="../wp-admin/">WP Admin</a></li>
             <li><a href="options.php">Options</a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>
    <div class="container outwrapper">
      <div class="starter-template">
        <h1>MyBackup <small>Request access</small></h1>
        <p class="lead">Enter below the email address you've use during installation.</p>
        <div id="msg" class="<?php echo $alert_css; ?>" role="alert"><?php echo $msg; ?></div>
		<div class="settings-container">	
			<form class="form-inline text-center">
			  
			  <div class="form-group">
				  <label for="emailto" class="control-label">Email address</label>
				  <input type="email" class="form-control" id="emailto" placeholder="your@email.com">

			  </div>
			 
	
			  <button type="button" class="btn btn-primary" id="saveoptions">Request access</button>
			</form>
        </div>  

      </div>

    </div><!-- /.container -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
	<script>

	jQuery(document).ready(function($) {

        $('#saveoptions').click(function(e) {
			$('#msg').removeClass('alert alert-warning').html('');
			var emailto = $('#emailto').val();

            if (!emailto) {
                $('#msg').addClass('alert alert-warning').html('Please enter an email address.');
                return false;
            } else {
    			$.ajax({
    				url: "libs/send_login.php",
                    type: 'POST',
                    data: { mailto: emailto },
                    success: function (data) {
    					$('#msg').addClass('alert alert-warning').html(data);
                    }
    			});
            }
			e.preventDefault();
		});

	});

    </script>
  </body>
</html>
