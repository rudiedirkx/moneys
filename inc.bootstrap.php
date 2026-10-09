<?php

require 'env.php';
require __DIR__ . '/vendor/autoload.php';

// do_auth();

// Connect to db
$db = new db_sqlite(DB_FILE);

$db->ensureSchema(require 'inc.db-schema.php');
db_generic_model::$_db = $db;

// Start UTF-8 everywhere, always
mb_internal_encoding('utf-8');
header('Content-type: text/html; charset=utf-8');
