<?php
//ini_set('display_errors', 'Off');
//For Session Expiration
//ini_set('session.gc_maxlifetime', 900);
//ini_set('session.gc_probability', 1);
//ini_set('session.gc_divisor', 1);
/**	Requiring Database Constants set in seperate file 
*	outside of www folder
*/
if ( $_SERVER['PHP_SELF'] == '/mcsueapp/index.php' || $_SERVER['PHP_SELF'] == '/mcsueapp/testfile.php' || $_SERVER['PHP_SELF'] == '/mcsueapp/forgotpassword.php' ) {
	require_once('../../mcsueConstants.php');
} else {
	require_once('../../../mcsueConstants.php');
}

	/**	Creating PDO parameters
	*/
	
	$charset = "utf8";
	$dsn = "mysql:host=".DB_SERVER.";dbname=".DB_NAME.";charset=$charset";
	$opt = [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_PERSISTENT=>true, PDO::MYSQL_ATTR_FOUND_ROWS => true,];
	
	/**	Instantiate New PDO Connection Instance 
	*/
	if ( isset($_SESSION['userType']) && isset($_SESSION['userId']) ) {
		//Setting User Access Type based on UserType Login
		if ( $_SESSION['userType'] == 'Admin' ) {
			$pdo = new PDO($dsn, DB_USER, DB_PASS, $opt);
		} elseif ( $_SESSION['userType'] == 'Customer' ) {
			$pdo = new PDO($dsn, DB_USER1, DB_PASS1, $opt);
		} elseif ( $_SESSION['userType'] == 'Student' ) {
			$pdo = new PDO($dsn, DB_USER2, DB_PASS2, $opt);
		}
	} else {
		$pdo = new PDO($dsn, DB_USER, DB_PASS, $opt);
	}