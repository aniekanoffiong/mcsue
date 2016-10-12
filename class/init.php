<?php

//Initializes classes automatically
if ( $_SERVER['PHP_SELF'] == '/mcsueapp/index.php' || $_SERVER['PHP_SELF'] == '/mcsueapp/testfile.php' ) {
	spl_autoload_register(function($class) {
		require_once("class/{$class}.php");
	});
} else {
	spl_autoload_register(function($class) {
		require_once("{$class}.php");
	});	
}