<?php

require 'inc.bootstrap.php';

$perPage = 100;
$page = (int)@$_GET['page'];

if ( isset($_POST['check']) ) {
	if ( $tag = trim($_POST['add_tag']) ) {
		if ( !($tag_id = $db->select_one('tags', 'id', array('tag' => $_POST['add_tag']))) ) {
			$db->insert('tags', array('tag' => $_POST['add_tag']));
			$tag_id = $db->insert_id();
		}

		$db->begin();
		foreach ( $_POST['check'] as $transaction_id ) {
			try {
				$db->insert('tagged', compact('tag_id', 'transaction_id'));
			}
			catch (Exception $ex) {
				// Assume this is a duplicity error, and ignore it.
			}
		}
		$db->commit();
	}

	// exit;
	return do_redirect();
}

else if ( isset($_POST['category']) ) {
	$db->begin();
	foreach ( $_POST['category'] as $trId => $catId ) {
		$db->update('transactions', array('category_id' => $catId ?: null), array('id' => $trId));
	}
	$db->commit();

	// exit;
	return do_redirect();
}

$conditions = array();
if ( !empty($_GET['category']) ) {
	$cat = $_GET['category'] == -1 ? null : $_GET['category'];
	$conditions[] = $db->stringifyConditions(array('category_id' => $cat));
}
if ( !empty($_GET['tag']) ) {
	$conditions[] = $db->replaceholders('id IN (SELECT transaction_id FROM tagged WHERE tag_id = ?)', array($_GET['tag']));
}
if ( @$_GET['min'] != '' && @$_GET['max'] != '' ) {
	$min = (int)$_GET['min'];
	$max = (int)$_GET['max'];
	$max < $min and list($min, $max) = array($max, $min);
	$conditions[] = $db->replaceholders('amount BETWEEN ? AND ?', array($min, $max));
}
if ( !empty($_GET['year']) ) {
	$conditions[] = $db->replaceholders('date LIKE ?', array($_GET['year'] . '-_%'));
}
if ( !empty($_GET['search']) ) {
	$q = '%' . $_GET['search'] . '%';
	$conditions[] = $db->replaceholders('(description LIKE ? OR summary LIKE ? OR notes LIKE ? OR account LIKE ?)', array($q, $q, $q, $q));
}
// print_r($conditions);
$condSql = $conditions ? '(' . implode(' AND ', $conditions) . ') AND' : '';

$offset = $page * $perPage;
$totalRecords = $db->count('transactions', $condSql . ' 1 AND ignore = 0');
$pages = ceil($totalRecords / $perPage);

$sort = isset($_GET['sort']) ? preg_replace('#[^\w-]#', '', $_GET['sort']) : '-date';
// var_dump($sort);
$sortDirection = $sort[0] == '-' ? 'DESC' : 'ASC';
$sortColumn = ltrim($sort, '-');

$pager = $conditions ? '' : 'LIMIT ' . $perPage . ' OFFSET ' . $offset;
$query = $condSql . ' 1 AND ignore = 0 ORDER BY ' . $sortColumn . ' ' . $sortDirection . ', ABS(amount) DESC ' . $pager;
// echo $query . "\n";
$transactions = $db->select('transactions', $query, null, 'Transaction')->all();
// print_r($transactions);

$transactions = array_reduce($transactions, function($transactions, $transaction) {
	$transaction->tags = array();
	return $transactions + array($transaction->id => $transaction);
}, array());
// print_r($transactions);

$tids = array_keys($transactions);
// print_r($tids);

$categories = $db->select_fields('categories', 'id, name', '1 ORDER BY name ASC');
Transaction::$_categories = $categories;
// print_r($categories);

$years = array_reverse(range(date('Y')-5, date('Y')));
$years = array_reduce(range(0, 60), function($months, $offset) {
	$utc = strtotime('-' . $offset . ' months');
	$offset % date('n') == 0 and $months += array(date('Y', $utc) => date('Y', $utc));
	$months += array(date('Y-m', $utc) => strtolower(date('Y - M', $utc)));
	return $months;
}, array());

$tags = $db->select_fields('tags', 'id, tag', '1 ORDER BY tag ASC');
// print_r($tags);

$tagged = $db->select('tagged', array('transaction_id' => $tids))->all();
foreach ( $tagged as $record ) {
	$transactions[ $record->transaction_id ]->tags[] = $tags[ $record->tag_id ];
}
// print_r($transactions);

// Export as CSV
if ( isset($_GET['export']) ) {
	csv_file($transactions, array('id', 'date', 'amount', 'type', 'sumdesc', 'category', 'tags_as_string'), 'moneys.csv');
	exit;
}

require 'tpl.header.php';

?>
<form action>
	<input type="hidden" name="sort" value="<?= html($sort) ?>" />
	<p>
		Category: <select name="category"><?= html_options(array('-1' => '-- none') + $categories, @$_GET['category'], '-- all') ?></select>
		Tag: <select name="tag"><?= html_options($tags, @$_GET['tag'], '-- all') ?></select>
		Amount: <input name="min" value="<?= @$_GET['min'] ?>" size="4" /> - <input name="max" value="<?= @$_GET['max'] ?>" size="4" />
		Year: <select name="year"><?= html_options($years, @$_GET['year'], '-- all') ?></select>
		Search: <input id="search-transactions" type="search" name="search" value="<?= @$_GET['search'] ?>" placeholder="Summary, description, account no..." />
		<button>&gt;&gt;</button>
	</p>
</form>

<p class="show-sumdesc"><a href="javascript:void(0)" onclick="document.body.toggleClass('hide-sumdesc')">Show Summary &amp; Description</a></p>

<?php

$show_pager = true;
$with_sorting = true;
$grouper = 'month';
include 'tpl.transactions.php';

?>

<pre><strong>Query:</strong> <?= html($query); ?></pre>

<script>
document.addEventListener('keydown', function(e) {
	if ( document.activeElement.matches('body, a, button') ) {
		if ( e.keyCode == 191 && !e.altKey && !e.ctrlKey && !e.shiftKey ) {
			e.preventDefault();
			document.querySelector('#search-transactions').select();
		}
	}
});
</script>
<?php

require 'tpl.footer.php';
