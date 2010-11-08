<?php
session_start();
require_once 'services/lib/OAuth/AppConfig.php';
require_once 'services/lib/OAuth/OAuthClient.php';

	// Get the "authenticated" request token here. The Service provider will append this token to the query string when
	// redirecting the user's browser to the Callback page
	$oauth_token = $_GET["oauth_token"];
	// The is the token secret which you got when you requested the request_token
	// You should get this because you appended this token secret when you got redirected to the
	// Service Provider's login screen
	$token_secret = $_GET["oauth_token_secret"];

	// Take the authenticated request token and secret and trade them for an access token and secret.
	$apiConsumer = new OAuthClient(AppConfig::$base_url, AppConfig::$consumer_key, AppConfig::$consumer_secret);
	$success = $apiConsumer->getAccessToken($oauth_token, $token_secret);
	$access_token = $apiConsumer->getToken();
	$token_secret = $apiConsumer->getTokenSecret();

	// Store them in the session
	$_SESSION["oauth_access_token"] = $access_token;
	$_SESSION["oauth_token_secret"] = $token_secret;

	// Redirect the user back to the homepage.
	header('Location: default.php');
	
?>