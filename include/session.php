<?php

//Prevents Session from being accessible through javascript
ini_set('session.cookie_httponly', true);

session_start(); 

//Sets Session to the IP Address of the user
if ( !isset($_SESSION['last_ip']) ) {
	$_SESSION['last_ip'] = $_SERVER['REMOTE_ADDR'];
	//Use for security related matters only
}
