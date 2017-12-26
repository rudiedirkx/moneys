<?php

require 'env.php';

require __DIR__ . '/vendor/autoload.php';
do_auth();

// Connect to db
$db = db_sqlite::open(array('database' => __DIR__ . '/db/moneys.sqlite3'));
if ( !$db ) {
	exit("<p>No db...</p>");
}

db_generic_model::$_db = $db;

// Verify db schema
$schema = require 'inc.schema.php';
// $db->schema($schema);

// Start UTF-8 everywhere, always
mb_internal_encoding('utf-8');
header('Content-type: text/html; charset=utf-8');
