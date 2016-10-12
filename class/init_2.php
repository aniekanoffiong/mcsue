<?php

//Initializes classes automatically
spl_autoload_register(function($class) {
	require_once("class/{$class}.php");
});