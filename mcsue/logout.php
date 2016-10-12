<?php 	
	//Find Session
	if (!isset( $_SESSION )) {
		session_start();
	}
	//Unset all the session variables
	$_SESSION = array();
	
	//Destroy the session cookie
	if(isset($_COOKIE[session_name()])) {
		setcookie(session_name(), '', time()-100000, '/');
	}
	
	if ($loginFail !== NULL ) {
		//Destroy the session; Cannot Destroy uninitialized session
		session_destroy();
	}
	
	header("Location: ../index.php?logout=success");
	exit;
