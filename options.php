<?php
include_once 'libs/func.php';
include_once 'libs/html.php';

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


if ($required) { // system requirements are met
	$db = new SQLite3(DATAPATH.'wpbackupsDb.sqlite');
	$res = $db->querySingle("SELECT sendgridapi, smtpserver, smtpport, smtplogin, smtppassword, smtpsecure, adminemail, emailfrom, confirmed, emailtype, lastupdate FROM backupsettings WHERE id = 1", true);
	$slug = $db->querySingle("SELECT slug FROM logins WHERE 1 LIMIT 0,1");
	if ($res['confirmed'] == 'yes') {
		get_authorized();
	} elseif ($res['confirmed'] == 'no' && $slug != NULL) {
		header('Location: '.BASE_URL.'login.php?msg=inprogress');
		exit;
	} else {


	}

	$wp_db = get_db_conn_vals(ABSPATH);
	if ($db = mysqli_connect($wp_db['DB_HOST'], $wp_db['DB_USER'], $wp_db['DB_PASSWORD'], $wp_db['DB_NAME'])) {
		$sql = sprintf("SELECT option_name, option_value FROM %soptions WHERE option_name IN ('admin_email', 'sendgrid_api_key', 'wp_mail_smtp', 'swpsmtp_options') AND option_value != ''", $wp_db['DB_PREFIX']);
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

  // store here the admin email address !

	$sendgridapi = $res['sendgridapi'];
	$adminemail = $res['adminemail'];
	$emailfrom = $res['emailfrom'];
	$smtpserver = $res['smtpserver'];
	$smtpport = $res['smtpport'];
	$smtplogin = $res['smtplogin'];
	$smtppassword = $res['smtppassword'];
	$smtpsecure = $res['smtpsecure'];
	$emailtype = $res['emailtype'];
	if ($res['lastupdate'] == '') {
		$adminemail = $admin_email;
		if (!empty($swpsmtp_options)) { // read options from Easy SMTP
			$options = unserialize($swpsmtp_options);
			if ($options['smtp_settings']['host'] == '' && $options['smtp_settings']['username'] == 'apikey') {
				$sendgridapi = $options['password'];
			}
			$emailfrom  = $options['from_email_field'];
			$smtpserver = $options['smtp_settings']['host'];
			$smtpport = $options['smtp_settings']['port'];
			$smtplogin = $options['smtp_settings']['username'];
			$smtppassword = '';
			$smtpsecure = $options['smtp_settings']['type_encryption'];
		} elseif (!empty($wp_mail_smtp)) { // read options from WP Mail SMTP
			$smtp = unserialize($wp_mail_smtp);
			if (!empty($smtp['sendgrid']['api_key'])) $sendgridapi  = $smtp['sendgrid']['api_key'];
			if (!empty($smtp['mail']['from_email'])) $emailfrom  = $smtp['mail']['from_email'];
			if (!empty($smtp['smtp']['host'])) $smtpserver  = $smtp['smtp']['host'];
			if (!empty($smtp['smtp']['port'])) $smtpport  = $smtp['smtp']['port'];
			if (!empty($smtp['smtp']['user'])) $smtplogin  = $smtp['smtp']['user'];
			if (!empty($smtp['smtp']['pass'])) $smtppassword  = $smtp['smtp']['pass'];
			if (!empty($smtp['smtp']['encryption'])) $smtpsecure  = $smtp['smtp']['encryption'];
		} elseif (!empty($sendgrid_api_key)) { // API key setting from old Sendgrid plugin
			$sendgridapi = $sendgrid_api_key;
		}
	}
	$checked['tls'] = ($smtpsecure == 'tls') ? 'checked' : '';
	$checked['ssl'] = ($smtpsecure == 'ssl') ? 'checked' : '';
	$checked['mail'] = ($emailtype == 'mail') ? 'checked' : '';
	$checked['smtp'] = ($emailtype == 'smtp') ? 'checked' : '';
	$checked['sendgrid'] = ($emailtype == 'sendgrid') ? 'checked' : '';

}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Options | Backup4WP</title>
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
    <link href="mbr.css" rel="stylesheet">
  </head>
  <body>
    <?php echo mb_navigation('options'); ?>
    <div class="container outwrapper">
      <div class="starter-template">
        <h1>Backup4WP <small>Options</small></h1>
        <p class="lead">You can send the authentication emails via Sendrid, a SMTP server or the native PHP mail() function. Or you can use the <a href="setup-apache-authentication.php">IP based or login/password authentication</a> (for Apache based web servers)</p>
        <div id="msg" class="<?php echo $alert_css; ?>" role="alert"><?php echo $msg; ?></div>


        <?php if ($required) { ?>
		<div class="settings-container">
			<form class="form" id="optionform">
			  <div class="well well-sm">
				<div class="form-group row">
				  <div class="col-md-6">
					<label for="emailfrom">Email address (from)</label>
					<input type="email" class="form-control" id="emailfrom" name="emailfrom" value="<?php echo $emailfrom; ?>">
				 </div>
				 <div class="col-md-6">
					<label for="adminemail">Email address (to)</label>
					<input type="email" autocomplete="off" class="form-control" id="adminemail" name="adminemail" value="<?php echo $adminemail; ?>">
				 </div>
			    </div>
				<p>Both email addresses are required and used to send the Backup4WP authentication emails. Use a sender address that is authenticated for the email option you will choose below.</p>
			  </div>
			  <p>How do you like to send the authentication emails? If you switch the options, it's not necessary to empty the other fields.</p>
			  <div class="form-group">
				<strong>Send emails via </strong>
				<label class="radio-inline">
				  <input type="radio" id="mailtype_sendgrid" name="emailtype" value="sendgrid" <?php echo $checked['sendgrid']; ?>> Sendgrid
				</label>
				<label class="radio-inline">
				  <input type="radio" id="mailtype_smtp" name="emailtype" value="smtp" <?php echo $checked['smtp']; ?>> SMTP
				</label>
				<label class="radio-inline">
				  <input type="radio" id="mailtype_mail" name="emailtype" value="mail" <?php echo $checked['mail']; ?>> PHP mail()
				</label>
			  </div>
			  <div class="send-options" id="use-sendgrid">
				  <h2>Sendgrid</h2>
				  <p>We recomend to use <a href="https://sendgrid.com/" target="_blank">Sendgrid</a> as transactional email provider. They offer a free account and the delivery rates are much better compared to the native PHP mail function.</p>
				  <div class="form-group">
					<label for="sendgridapi">Sendgrid API key</label>
					  <input type="text" class="form-control" id="sendgridapi" name="sendgridapi" value="<?php echo $sendgridapi; ?>">

				  </div>
			  </div>
			  <div class="send-options" id="use-smtp">
				  <h2>SMTP</h2>
				  <div class="row">
					  <div class="form-group col-md-6">
						<label for="emailfrom">SMTP host or server</label>
						<input type="text" class="form-control" id="smtpserver" name="smtpserver" value="<?php echo $smtpserver; ?>">
					  </div>
					  <div class="form-group col-md-3">
						<label for="smtpport">SMTP port</label>
						<input type="number" class="form-control" id="smtpport" name="smtpport" value="<?php echo $smtpport; ?>">
					  </div>
					  <div class="form-group col-md-3">
						<label>SMTP encryption</label>
						<div>
							<label class="radio-inline">
								<input type="radio" id="smtpsecure_tls" name="smtpsecure" value="tls" <?php echo $checked['tls']; ?>> tls
							</label>
							<label class="radio-inline">
								<input type="radio" id="smtpsecure_ssl" name="smtpsecure" value="ssl" <?php echo $checked['ssl']; ?>> ssl
							</label>
						</div>
					  </div>
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
			  </div>
			  <div class="send-options" id="use-mail">
				  <h2>PHP mail()</h2>
				  <p><strong>Sending emails via the PHP's mail() function is not recommended.</strong> There are no options to configure...</p>
			  </div>



			  <div class="text-right">
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

		var curr_type = $("input[name='emailtype']:checked").val();
        if (curr_type) {
			$("#use-" + curr_type).show();
		}

		$("[name=emailtype]").click(function(){
			$('.send-options').hide();
			$("#use-" + $(this).val()).show();
		});

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
