<?php
include_once 'libs/func.php';


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

$admin_email = '';
$sendgrid_api_key = '';

$wp_db = get_db_conn_vals(ABSPATH);
if ($db = mysqli_connect($wp_db['DB_HOST'], $wp_db['DB_USER'], $wp_db['DB_PASSWORD'], $wp_db['DB_NAME'])) {
    $sql = sprintf("SELECT option_name, option_value FROM %soptions WHERE option_name IN ('admin_email', 'sendgrid_api_key', 'wp_mail_smtp') AND option_value != ''", $wp_db['DB_PREFIX']);
    if ($result = mysqli_query($db, $sql)) {
		while( $obj = mysqli_fetch_object( $result) ) {
			$name = $obj->option_name;
			$$name = $obj->option_value;
		}
	} else {
		$msg = 'WP MySQL error: ' . mysqli_error();
	}
} else {
    $msg = 'WP MySQL connect error: ' . mysqli_connect_error();
}



if ($required) {
	$db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite');
	$res = $db->querySingle("SELECT sendgridapi, smtpserver, smtpport, smtplogin, smtppassword, smtpsecure, adminemail, emailfrom, confirmed, lastupdate FROM backupsettings WHERE id = 1", true);
	print_r($res);
	if ($res['confirmed'] == 'yes') {
		get_authorized();
	}
	$sendgridapi = $res['sendgridapi'];
	$adminemail = $res['adminemail'];
	$emailfrom = $res['emailfrom'];
	$smtpserver = $res['smtpserver'];
	$smtpport = $res['smtpport'];
	$smtplogin = $res['smtplogin'];
	$smtppassword = $res['smtppassword'];
	$smtpsecure = $res['smtpsecure'];
	if ($res['lastupdate'] == '') {
		$sendgridapi = $sendgrid_api_key;
		$adminemail = $admin_email;
		if ($wp_mail_smtp) $smtp = unserialize($wp_mail_smtp);
		if ($sendgridapi == '' && !empty($smtp['sendgrid']['api_key'])) $sendgridapi  = $smtp['sendgrid']['api_key'];
		if (!empty($smtp['mail']['from_email'])) $emailfrom  = $smtp['mail']['from_email'];
		if (!empty($smtp['smtp']['host'])) $smtpserver  = $smtp['smtp']['host'];
		if (!empty($smtp['smtp']['port'])) $smtpport  = $smtp['smtp']['port'];
		if (!empty($smtp['smtp']['user'])) $smtplogin  = $smtp['smtp']['user'];
		if (!empty($smtp['smtp']['pass'])) $smtppassword  = $smtp['smtp']['pass'];
		if (!empty($smtp['smtp']['encryption'])) $smtpsecure  = $smtp['smtp']['encryption'];
	}
	$checked['tls'] = ($smtpsecure == 'tls') ? 'checked' : '';
	$checked['ssl'] = ($smtpsecure == 'ssl') ? 'checked' : '';
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
        <p class="lead">We recomend to use <a href="https://sendgrid.com/" target="_blank">Sendgrid</a> as transactional email provider. They offer a free account and the delivery rates are much better compared to the native PHP mail function. Of course you can use a SMTP server too.</p>
        <div id="msg" class="<?php echo $alert_css; ?>" role="alert"><?php echo $msg; ?></div>
        
        
        <?php if ($required) { ?>
		<div class="settings-container">
			<p>If you enter the Sengrid API, the SMTP options are ingnored. Keep both empty to use the PHP mail() function.</p>
			<form class="form" id="optionform">
			  <div class="form-group">
				<label for="sendgridapi">Sendgrid API key</label>
				  <input type="text" class="form-control" id="sendgridapi" name="sendgridapi" value="<?php echo $sendgridapi; ?>" aria-describedby="sendgridapihelp">
				  <span id="sendgridapihelp" class="help-block">Make this field emtpty to use the SMTP options below.</span>
			  </div>
			  
			  <div class="form-group row">
				  <div class="col-md-6">
					<label for="emailfrom">SMTP host or server</label>
					<input type="text" class="form-control" id="smtpserver" name="smtpserver" value="<?php echo $smtpserver; ?>">
				  </div>
				  <div class="col-md-6">
					<label for="smtpport">SMTP port</label>
					<input type="number" class="form-control" id="smtpport" name="smtpport" value="<?php echo $smtpport; ?>">
				  </div>
			  </div>
			  <div class="form-group">
				<strong>SMTP encryption</strong>
				<label class="radio-inline">
				  <input type="radio" id="smtpsecure_tls" name="smtpsecure" value="tls" <?php echo $checked['tls']; ?>> tls
				</label>
				<label class="radio-inline">
				  <input type="radio" id="smtpsecure_ssl" name="smtpsecure" value="ssl" <?php echo $checked['ssl']; ?>> ssl
				</label>
			  </div>
			  
			  <div class="form-group row">
				  <div class="col-md-6">
					<label for="smtplogin">SMTP login</label>
					<input type="text" class="form-control" id="smtplogin" name="smtplogin" value="<?php echo $smtplogin; ?>">
				  </div>
				  <div class="col-md-6">
					<label for="smtppassword">SMTP password</label>
					<input type="text" class="form-control" id="smtppassword" name="smtppassword" value="<?php echo $smtppassword; ?>">
				  </div>
			  </div>
			  
			  <div class="form-group row">
				  <div class="col-md-6">
					<label for="emailfrom">Email address (from)</label>
					<input type="email" class="form-control" id="emailfrom" name="emailfrom" value="<?php echo $emailfrom; ?>">
				 </div>
				 <div class="col-md-6">
					<label for="adminemail">Email address (to)</label>
					<input type="email" class="form-control" id="adminemail" name="adminemail" value="<?php echo $adminemail; ?>">
				 </div>
			  </div>
			  
			 
			  <div class="text-center">
				   <p>Both email addresses are used to send the authentication emails.</p>
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
			
            if (!adminemail || !emailfrom) {
                $('#msg').addClass('alert alert-warning').html('Both email addresses are required.');
                return false;
            } else {
    			$.ajax({
    				url: "libs/save_options.php",
                    type: 'POST',
                    data: $('#optionform').serialize(),
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
