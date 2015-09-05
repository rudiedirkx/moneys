<?php

require 'inc.bootstrap.php';

$transactions = $db->fetch("
	SELECT t.*
	FROM (
		SELECT date, account, amount, COUNT(1) AS num
		FROM transactions
		WHERE ignore = 0
		GROUP BY date, account, amount
		HAVING num > 1
	) x
	JOIN transactions t
		ON (x.date = t.date AND COALESCE(x.account, '') = COALESCE(t.account, '') AND x.amount = t.amount)
	WHERE t.ignore = 0
	ORDER BY date DESC, account, amount
", 'Transaction')->all();

$transactions = array_reduce($transactions, function($transactions, $transaction) {
	$transaction->tags = array();
	return $transactions + array($transaction->id => $transaction);
}, array());

$tids = array_keys($transactions);

$categories = $db->select_fields('categories', 'id, name', '1 ORDER BY name ASC');
Transaction::$_categories = $categories;

$tags = $db->select_fields('tags', 'id, tag', '1 ORDER BY tag ASC');

$tagged = $db->select('tagged', array('transaction_id' => $tids))->all();
foreach ( $tagged as $record ) {
	if ( $tags[ $record->tag_id ] == 'undouble' ) {
		unset($transactions[ $record->transaction_id ]);
	}
	else if ( isset($transactions[ $record->transaction_id ]) ) {
		$transactions[ $record->transaction_id ]->tags[] = $tags[ $record->tag_id ];
	}
}

require 'tpl.header.php';

?>
<h1>Potential doubles</h1>

<p>Add the tag <code>"undouble"</code> to ignore transactions. They won't show up here anymore.</p>
<?php

$show_pager = false;
$with_sorting = false;
$grouper = 'simple_uniq';
include 'tpl.transactions.php';

require 'tpl.footer.php';
