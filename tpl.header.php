<?php

$lastImport = $db->select_one('transactions', 'date', "type <> 'split' ORDER BY date DESC LIMIT 1");
$daysSinceLastImport = $lastImport ? round((time() - strtotime($lastImport)) / 86400) : '?';

isset($pageTitle) or $pageTitle = substr(basename($_SERVER['PHP_SELF']), 0, -4);

?>
<!doctype html>
<html>

<head>
<meta charset="utf-8" />
<title>Moneys | <?= $pageTitle ?></title>
<meta name="viewport" content="initial-scale=0.2" />
<link rel="icon" type="image/png" href="favicon-128.png" sizes="128x128" />
<link rel="icon" href="favicon.ico" type="image/x-icon" />
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<style>
* {
	box-sizing: border-box;
}
table {
	border-collapse: collapse;
}
td, th {
	border: solid 1px #bbb;
	padding: 6px;
}
th {
	text-align: left;
}
thead td {
	font-size: 120%;
	text-align: center;
	font-weight: bold;
}
select.error,
input.error,
textarea.error {
	border-color: red;
	outline: solid 1px red;
}
textarea {
	tab-size: 4;
	white-space: nowrap;
}

body > .main-menu {
	margin-top: 0;
}

.c {
	text-align: center;
}
.descr {
	color: #aaa;
	font-style: italic;
}
.hidden {
	position: absolute;
	visibility: hidden;
}

tr.dir-in {
	background-color: #dfd;
}
tr.dir-out {
	background-color: #fdd;
}

td.amount {
	font-family: monospace;
	text-align: right;
}

td.category {
	text-align: center;
}
tr.dir-out td.category.empty {
	background: #faa;
}
tr.dir-in td.category.empty {
	background: #afa;
}

.per-year td,
.per-year th {
	white-space: nowrap;
}
.per-year .expanded {
	background: #f7f7f7;
}
.per-year th > .num {
	font-weight: normal;
	margin-left: .25em;
}

select:focus {
	outline: solid 4px black;
}
</style>
</head>

<body>

<p class="main-menu">
	<a href="index.php">Overview</a>
	|
	<a href="parties.php">Parties</a>
	|
	<a href="categories.php">Categories</a>
	|
	<a href="tags.php">Tags</a>
	|
	<a href="accounts.php">Accounts</a>
	|
	<a href="doubles.php">Doubles</a>
	|
	<a href="import-ing.php">Import ING</a>
	|
	<a href="types.php">Types</a>
	|
	<span title="Next time, import including <?= $lastImport ?>"><?= $daysSinceLastImport ?> days since last import</span>
</p>
