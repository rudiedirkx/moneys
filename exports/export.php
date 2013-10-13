<?php

require '../inc.bootstrap.php';

header('Content-type: text/plain');

$transactions = $db->select('transactions', 'category_id IS NOT NULL');

$export = array();
foreach ( $transactions as $tr ) {
	$hash = md5(implode(':', array(
		$tr->date,
		$tr->type,
		(float)$tr->amount,
		$tr->summary,
	)));
	if ( isset($export[$hash]) ) {
		exit('Not good enough' . "\n" . print_r($tr, 1));
	}

	$export[$hash] = $tr->category_id;
}

echo json_encode($export);
// print_r($export);
