<?php

require 'inc.bootstrap.php';

$undouble = Tag::get('undouble');
$transactions = Transaction::query("
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
	WHERE t.ignore = 0 AND NOT EXISTS (SELECT * FROM tagged WHERE transaction_id = t.id AND tag_id = ?)
	ORDER BY date DESC, account, amount
", [$undouble->id]);

$categories = $db->select_fields('categories', 'id, name', '1 ORDER BY name ASC');
Transaction::$_categories = $categories;

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
