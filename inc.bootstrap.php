<?php

require 'env.php';

// Connect to db
require WHERE_DB_GENERIC_AT . '/db_sqlite.php'; // https://github.com/rudiedirkx/db_generic
$db = db_sqlite::open(array('database' => __DIR__ . '/db/moneys.sqlite3'));

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

