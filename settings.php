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
             <li><a href="settings.php" target="_blank">Settings></a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>
    <div class="container outwrapper">
      <div class="starter-template">
        <h1>Backup &amp; Restore for WordPress <small>beta</small></h1>
        <h2>Settings page</h2>
        <h3>Protect the <em><?php echo basename(__DIR__); ?></em> directory.</h3>
        <p>This function works only for Apache based web servers!</p>
        <p>Add a login and password. If you safe a new password, the old one will be replaced (if exists).</p>
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
        <p><a href="javascript:void(0);" id="addipadr"><strong>Allow the current IP address</strong></a> (<?php echo $_SERVER['REMOTE_ADDR']; ?>) to access the directory.</p>


		<div id="msg" class="" role="alert"></div>

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
