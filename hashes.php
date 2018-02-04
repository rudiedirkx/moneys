<?php

require 'inc.bootstrap.php';

if ( isset($_POST['rehash']) ) {
	echo '<pre>';

	$db->begin();

	$db->update('transactions', ['hash' => null], '1');

	$transactions = $db->select('transactions', '1 ORDER BY id DESC');

	$total = 0;
	$index = [];
	foreach ($transactions as $transaction) {
		$total++;

		$newHash = get_transaction_hash($transaction);

		if ( isset($index[$newHash]) ) {
			exit('Hash-clash for IDs <a target="_blank" href="transaction.php?id=' . $index[$newHash] . '">' . $index[$newHash] . '</a> and <a target="_blank" href="transaction.php?id=' . $transaction->id . '">' . $transaction->id . "</a>.\n");
		}

		$id = $transaction->id;
		$index[$newHash] = $id;

		$db->update('transactions', ['hash' => $newHash], ['id' => $id]);
	}

	$db->commit();

	echo "$total hashes saved.\n";

	exit;
}

require 'tpl.header.php';

$transactions = $db->count('transactions');

$hashes = $db->select_one('transactions', 'count(distinct hash)', '1');

?>
<p><?= $transactions ?> transactions</p>
<p><?= $hashes ?> different hashes</p>

<form method="post" action>
	<input type="hidden" name="rehash" value="1" />
	<p><button>Re-hash</button></p>
</form>
