<?php

require 'inc.bootstrap.php';

echo '<pre>';

$db->begin();

$transactions = $db->select('transactions', '1 ORDER BY id DESC');
$i = 1;
foreach ($transactions as $transaction) {
	$hash = get_transaction_hash($transaction);

	$operator = $hash == $transaction->hash ? '==' : '!=';
	echo sprintf('%05d', $i) . '. ' . $transaction->hash . ' ' . $operator . ' ' . $hash . ' (' . $transaction->id . ")\n";
	$i++;

	$db->update('transactions', compact('hash'), array('id' => $transaction->id));
}

$db->commit();
