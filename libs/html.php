<?php

function mb_navigation($current = '') {
	$pages = array(
		'index' => array('url' => BASE_URL, 'label' => 'Manage Backups'),
		'options' => array('url' => BASE_URL.'options.php', 'label' => 'Options'),
    'update' => array('url' => BASE_URL.'update.php', 'label' => 'Update'),
		'home' => array('url' => '//'.$_SERVER['HTTP_HOST'], 'label' => 'Homepage'),
		'wpadmin' => array('url' => '//'.$_SERVER['HTTP_HOST'].'/wp-admin/', 'label' => 'WP Dashboard'),
		'help' => array('url' => 'https://backup4wp.com/getting-help/', 'label' => '<span class="glyphicon glyphicon-new-window" aria-hidden="true"></span> Getting help', 'target' => '_blank', 'class' => 'nav-highlight'),
		'logout' => array('url' => BASE_URL.'login.php?action=logout', 'label' => 'Log Out')
	);
	$html = '
	<nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="https://backup4wp.com/" target="_blank">backup4wp</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">';
	foreach ($pages as $key => $page) {
		$html .= '
            <li';
        if($key == $current) $html .= ' class="active"';
        $html .= '><a href="'.$page['url'].'"';
		if (isset($page['target'])) $html .= ' target="'.$page['target'].'"';
        if (isset($page['class'])) $html .= ' class="'.$page['class'].'"';
        $html .= '>'.$page['label'].'</a></li>';
    }
    $html .= '
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>';
	return $html;
}
