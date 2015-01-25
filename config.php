<?php
Namespace Core;

if ( $_SERVER[ 'REMOTE_ADDR' ] == "127.0.0.1" ) {
	$_CORE_CONFIG = array(
		"host" => $_SERVER[ 'SERVER_NAME' ],
		"database_enabled" => false,
		"mysql_username" => "root",
		"mysql_password" => "password",
		"mysql_server" => "localhost",
		"database_name" => "wnittv_main",
		"core_directory" => __DIR__
	);
} else {
	$_CORE_CONFIG = array(
		"host" => $_SERVER[ 'SERVER_NAME' ],
		"database_enabled" => true,
		"mysql_username" => "root",
		"mysql_password" => "password",
		"mysql_server" => "localhost",
		"database_name" => "wnittv_main",
		"core_directory" => __DIR__
	);
}