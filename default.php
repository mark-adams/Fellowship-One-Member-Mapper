<?php
session_start();
require_once 'services/lib/OAuth/AppConfig.php';
require_once 'services/lib/OAuth/OAuthClient.php';

// If the oauth access token and oauth secret token are not present, require the user to authenticate.
	if ((!isset($_SESSION["oauth_access_token"])) || (!isset($_SESSION["oauth_token_secret"]))){
		$apiConsumer = new OAuthClient(AppConfig::$base_url, AppConfig::$consumer_key, AppConfig::$consumer_secret);
		$apiConsumer->authenticateUser();
	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Member Mapper</title>
	<link rel="stylesheet" href="style.css" />
	<meta http-equiv="X-UA-Compatible" content="IE=8" />
</head>
<body>
	<div id="header"><h1>Welcome to the Member Mapper!</h1>
	<div id="notice"><p>This map loads your church's members from Fellowship One and displays their addresses on the map below. It may take a few moments to load all of your members.</p><p>You can click on any of the red markers below in order to view the members living at that address. You can also click to edit a member's name, and it will update in Fellowship One automatically.</p></div>
	<div id="statusBox"></div></div>
	<div id="map_canvas"></div>
	
	<script type="text/javascript" src="scripts/lib/jquery-1.4.3.min.js"></script>
	<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
	<script type="text/javascript" src="scripts/client.js"></script>
</body>
</html>