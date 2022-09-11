<?php
include_once 'libs/func.php';
include_once 'libs/html.php';
// todo google recaptcha or other spam protection (Honeypot)
$alert_css = '';
$msg = '';

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
	delete_login_record();
	unset($_COOKIE['mybackup_access']); 
    setcookie('mybackup_access', null, 1, MBDIRNAME."/", $_SERVER['HTTP_HOST']); 
    header('Location: '.BASE_URL, true, 302);
	exit;
}

$messages = array(
	'expiredlink' => 'The access URL or link is expired. Create a new one.',
	'invalidsession' => 'Invalid sessions or IP address.',
	'notfound' => 'The access URL or link is not valid.',
	'cookieexpired' => 'The cookie is not valid anymore. You need to request a new access URL.',
	'inprogress' => 'Configuration in progress... Check your inbox and confirm you email address.'
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
    <title>Request access | Backup4WP</title>
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
    <link href="mbr.css" rel="stylesheet">
  </head>
  <body>
    <?php echo mb_navigation('login'); ?>
    <div class="container outwrapper">
      <div class="starter-template">
        <h1>Backup4WP <small>Login</small></h1>
        <p class="lead">Enter below the email address you've entered via the "Options" page.<br><strong>Tip!</strong> It's often your WordPress admin email address.</p>
        <div id="msg" class="<?php echo $alert_css; ?>" role="alert"><?php echo $msg; ?></div>
		<div class="settings-container">
			<form class="form-inline">

			  <div class="form-group">
				  <label for="emailto" class="control-label">Email address</label>
				  <input type="email" class="form-control" id="emailto" placeholder="your-email-address@domain.com">

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
