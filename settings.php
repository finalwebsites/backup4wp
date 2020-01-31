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
            <li><a href="index.php">Backup &amp; Restore</a></li>
            <li><a href="/">Home</a></li>
            <li><a href="../wp-admin/">WP Admin</a></li>
             <li class="active"><a href="settings.php">Settings</a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>
    <div class="container outwrapper">
      <div class="starter-template">
        <h1>Backup &amp; Restore for WordPress <small>beta</small></h1>
        <h2>Settings page</h2>
        <h3>Protect the <em><?php echo basename(__DIR__); ?></em> directory.</h3>
        <?php
        if (substr(strtolower($_SERVER['SERVER_SOFTWARE']), 0, 6) != 'apache' ) {
			echo '
		<p>It looks like you\'re not using Apache. That\'s why the standard directory protection functions doesn\'t work for you.</p>
		<p>For Nginx based servers the following snippet might work to restrict the directory for IP adresses.</p>
		<pre class="smaller">
location /mybackup {
	# add your IP address below
	allow xxx.xxx.xxx.xxx;
	deny all;
}
		</pre>
		<p>Ask you hosting provider if you have no idea where to place this code snippet!</p>';
		} else {
			echo '
        <p>You can use onlt one of both options. The new method will replace the old one.<br>This function works only for web servers based on Apache!</p>
        <p>Add a login and password. If you safe a new password, the old one will be replaced (if exists).</p>
        <div id="msg" class="" role="alert"></div>
        <form class="form-inline">
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
        <p><a href="javascript:void(0);" id="addipadr"><strong>Allow the current IP address</strong></a> ('.$_SERVER['REMOTE_ADDR'].') to access the directory.<br>If you created a login before, this action will remove the old files!</p>
        <hr>
        <p><a href="data/phpliteadmin.php" target="_blank">Access phpLiteAdmin</a></p>';
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
    						$('#msg').addClass('alert alert-success').html('Your login and password are saved to protect the directory. You need to login first to access the page.');
    					} else {
                            $('#msg').addClass('alert alert-danger').html('Error, login and password are not saved.');
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
						$('#msg').addClass('alert alert-success').html('Only your IP address can access the directory from now on.');

					} else {
                        $('#msg').addClass('alert alert-success').html('Error, IP address protect is not installed.');
					}
                }
			});
			e.preventDefault();
		});

	});

    </script>
  </body>
</html>
