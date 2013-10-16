<?php

$lastImport = $db->select_one('transactions', 'date', '1 ORDER BY date DESC LIMIT 1');
$daysSinceLastImport = round((time() - strtotime($lastImport)) / 86400);

isset($pageTitle) or $pageTitle = substr(basename($_SERVER['PHP_SELF']), 0, -4);

?>
<!doctype html>
<html>

<head>
<meta charset="utf-8" />
<title>Moneys | <?= $pageTitle ?></title>
<meta name="viewport" content="initial-scale=0.2" />
<style>
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

.c {
	text-align: center;
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

tr.dir-out td.category.empty {
	background: #faa;
}

select:focus {
	outline: solid 4px black;
}
</style>
</head>

<body>

<p>
	<a href="index.php">Overview</a>
	|
	<a href="parties.php">Parties</a>
	|
	<a href="categories.php">Categories</a>
	|
	<a href="tags.php">Tags</a>
	|
	<a href="import-ing.php">Import ING</a>
	|
	<?= $daysSinceLastImport ?> days since last import
</p>
