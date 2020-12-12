<?php
include_once 'libs/func.php';
include_once 'libs/html.php';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Options - Apache authentication | Backup4WP</title>
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
    <link href="mbr.css" rel="stylesheet">
  </head>
  <body>
    <?php echo mb_navigation('options'); ?>
    <div class="container outwrapper">
      <div class="starter-template">
        <h1>Backup4WP <small>Options - Apache authentication</small></h1>
        
        <p class="lead">Protect the <em><?php echo basename(__DIR__); ?></em> directory. <a href="options.php">Click here</a> for the email options, if you prefer the magic link authentication.</h3>
        <?php
        if (substr(strtolower($_SERVER['SERVER_SOFTWARE']), 0, 6) != 'apache' ) {
			echo '
		<div id="msg" class="alert alert-warning" role="alert">It looks like you\'re not using Apache. Please use the magic link authentication feature.</div>';
	} elseif (file_exists(__DIR__.'/.htaccess')) {
			echo '
		<div id="msg" class="alert alert-warning" role="alert">For security reasons, your need to delete the old .htaccess file (via SSH/sFTP) first before you can start over!</div>';
		} else {
			echo '
        <p><strong>You can only use one of these options.</strong> Create your login/password or click the link below to setup the authentication based on your IP address.</p>
        <div id="msg" class="" role="alert"></div>
        <form class="form-inline" id="loginpw">
          <div class="form-group">
            <label class="sr-only" for="loginname">Login name</label>
            <input type="text" class="form-control" id="loginname" placeholder="Login name">
          </div>
          <div class="form-group">
            <label class="sr-only" for="password">Password</label>
            <input type="password" class="form-control" id="password" placeholder="Password">
          </div>
          <button type="button" class="btn btn-primary" id="savepasswd">Save</button>
        </form>
        <hr>
        <p id="allowip"><a href="javascript:void(0);" id="addipadr"><strong>Allow the current IP address</strong></a> ('.$_SERVER['REMOTE_ADDR'].') to access the "mybackup" directory.</p>';
		}
		?>

      </div>

    </div><!-- /.container -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
	<script>

	jQuery(document).ready(function($) {

        $('#savepasswd').click(function(e) {
			$('#msg').removeClass('alert alert-success').html('');
			var login = $('#loginname').val();
			var pwd = $('#password').val();
            if (!loginname || !password) {
                $('#msg').html('At least one of the form fields is empty.');
                return false;
            } else {
    			$.ajax({
    				url: "libs/password_protect.php",
                    type: 'POST',
                    data: { loginname: login, password: pwd },
                    success: function (data) {
    					if (data == 'okay') {
							$('#allowip').html('');
    						$('#msg').addClass('alert alert-success').html('Your login and password are saved in order to protect the "mybackup" directory. You need to refresh the page and login to manage the backups.');
    						
    					} else {
							if (data == 'exists') {
								$('#msg').addClass('alert alert-warning').html('Error, remove the old .htaccess and .htpasswd file before you can create a new login.');
							} else {
								$('#msg').addClass('alert alert-warning').html('Error, login and password are not saved.');
							}
    					}
                    }
    			});
            }
			e.preventDefault();
		});

        $('#addipadr').click(function(e) {
			$('#msg').removeClass('alert alert-success').html('<img src="img/loadingAnimation.gif" alt="Please wait...">');
			$.ajax({
				url: "libs/ip_protect.php",
                success: function (data) {
					if (data == 'okay') {
						$('#loginpw').html('');
						
						$('#msg').addClass('alert alert-success').html('Your IP address has access the "mybackup" directory from now on.');
					} else {
						if (data == 'exists') {
							$('#msg').addClass('alert alert-warning').html('Error, remove the old .htaccess file before you can create a new one.');
						} else {
							$('#msg').addClass('alert alert-warning').html('Error, your IP address is not whitelisted.');
						}
					}
                }
			});
			e.preventDefault();
		});

	});

    </script>
  </body>
</html>
