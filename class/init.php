<?php

//Initializes classes automatically
if ( $_SERVER['PHP_SELF'] == '/index.php' || $_SERVER['PHP_SELF'] == '/testfile.php' ) {
	spl_autoload_register(function($class) {
		require_once("class/{$class}.php");
	});
} else {
	spl_autoload_register(function($class) {
		require_once("{$class}.php");
	});	
}