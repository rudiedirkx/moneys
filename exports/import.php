<?php

require '../inc.bootstrap.php';

header('Content-type: text/plain');

$transactions = $db->select('transactions', 'category_id IS NULL')->all();
// print_r($transactions);

$cats = json_decode(file_get_contents('cats.json'), true);
// print_r($cats);

$db->begin();
$updates = 0;
foreach ( $transactions as $tr ) {
	$hash = md5(implode(':', array(
		$tr->date,
		$tr->type,
		(float)$tr->amount,
		$tr->summary,
	)));
	if ( isset($cats[$hash]) ) {
		$db->update('transactions', array(
			'category_id' => $cats[$hash],
		), array('id' => $tr->id));
		$updates++;
	}
}
$db->commit();

var_dump($updates);
