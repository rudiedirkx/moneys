<?php

require 'env.php';



// Find db name
$ip = $_SERVER['REMOTE_ADDR'];
$local = preg_match('#^192\.168\.#', $ip) || preg_match('#^127\.0\.#', $ip);

$db = 'moneys';
if ( !$local ) {
	// Find user auth
	if ( empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW']) ) {
		header('WWW-Authenticate: Basic realm="Every user + pass creates a new encrypted db, so remember them!"');
		header('HTTP/1.0 401 Unauthorized');
		echo "You really need a unique login... If you can't remember, all data is lost, because encrypted.";
		exit;
	}

	$user = preg_replace('#[^\w\d]+#', '', $_SERVER['PHP_AUTH_USER']);
	$db .= '-' . $user . '-' . md5($_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW']);
}



// Connect to db
require WHERE_DB_GENERIC_AT . '/db_sqlite.php'; // https://github.com/rudiedirkx/db_generic
$db = db_sqlite::open(array('database' => __DIR__ . '/db/' . $db . '.sqlite3'));

if ( !$db ) {
	exit("<p>No db...</p>");
}

// Peripherals
require 'inc.functions.php';
require 'inc.transaction.php';

// Verify db schema
$schema = require 'inc.schema.php';
$db->schema($schema);

// Start UTF-8 everywhere, always
mb_internal_encoding('utf-8');
header('Content-type: text/html; charset=utf-8');

