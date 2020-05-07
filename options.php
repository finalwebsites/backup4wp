<?php
include_once 'libs/func.php';
get_authorized();
$msg = '';
$alert_css = '';

$required = true;
if (function_exists('exec')) {
	if ('' == exec('rsync --version ')) {
		$alert_css = 'alert alert-warning';
		$msg = 'The required Linux tool "rsync" is not available.';
		$required = false;
	}
} else {
	$alert_css = 'alert alert-warning';
	$msg = 'The required PHP function "exec()" is not enabled.';
	$required = false;
}
        
if ($required) {
	$db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite');
	if ($res = $db->querySingle("SELECT sendgridapi, adminemail, emailfrom, confirmed FROM backupsettings WHERE id = 1", true)) {
		$sendgridapi = $res['sendgridapi'];
		$adminemail = $res['adminemail'];
		$emailfrom = $res['emailfrom'];
	} else {
		$sendgridapi = '';
		$adminemail = '';
		$emailfrom = '';
	}
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Options | MyBackup for WordPress</title>
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
             <li class="active"><a href="options.php">Options</a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>
    <div class="container outwrapper">
      <div class="starter-template">
        <h1>MyBackup <small>Options</small></h1>
        <p class="lead">We recomend to use <a href="https://sendgrid.com/" target="_blank">Sendgrid</a> as transactional email provider. They offer a free account and the delivery rates are much better compared to the native PHP mail function.</p>
        <div id="msg" class="<?php echo $alert_css; ?>" role="alert"><?php echo $msg; ?></div>
        
        
        <?php if ($required) { ?>
		<div class="settings-container">	
			<form class="form">
			  <div class="form-group">
				<label for="sendgridapi">Sendgrid API key</label>
				  <input type="text" class="form-control" id="sendgridapi" value="<?php echo $sendgridapi; ?>" aria-describedby="sendgridapihelp">
				  <span id="sendgridapihelp" class="help-block">Keep the field emtpty to use the native mail function.</span>
			  </div>
			  
			  <div class="form-group">
				<label for="emailfrom">Email address (from)</label>
				  <input type="email" class="form-control" id="emailfrom" value="<?php echo $emailfrom; ?>">
			  </div>
			  
			  <div class="form-group">
				<label for="adminemail">Email address (to)</label>
				  <input type="email" class="form-control" id="adminemail" value="<?php echo $adminemail; ?>" aria-describedby="emailhelp">
				  <span id="emailhelp" class="help-block">Both email addresses are used to send the authentication emails.</span>
			  </div>
			 
			  <div class="text-center">
				  <button type="button" class="btn btn-primary" id="saveoptions">Save options</button>
			  </div>
			</form>
        </div>  
		<?php } // required ?>
      </div>

    </div><!-- /.container -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
	<script>

	jQuery(document).ready(function($) {

        $('#saveoptions').click(function(e) {
			$('#msg').removeClass('alert-warning alert-success alert-danger alert').html('');
			var emailfrom = $('#emailfrom').val();
			var adminemail = $('#adminemail').val();
			var sendgridapi = $('#sendgridapi').val();
            if (!adminemail || !emailfrom) {
                $('#msg').addClass('alert alert-warning').html('Both email addresses are required.');
                return false;
            } else {
    			$.ajax({
    				url: "libs/save_options.php",
                    type: 'POST',
                    data: { sendgridapi: sendgridapi, emailfrom: emailfrom, adminemail: adminemail },
                    success: function (data) {
    					if (data == 'okay') {
    						$('#msg').addClass('alert alert-success').html('Your settings are saved.');
    					} else {
                            $('#msg').addClass('alert alert-warning').html(data);
    					}
                    }
    			});
            }
			e.preventDefault();
		});

	});

    </script>
  </body>
</html>
