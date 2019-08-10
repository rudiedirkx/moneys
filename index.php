<?php

require 'inc.bootstrap.php';

$perPage = 100;
$page = (int)@$_GET['page'];
$export = isset($_GET['export']);

if ( isset($_POST['check']) ) {
	if ( $tags = trim($_POST['add_tag']) ) {
		$db->begin();
		foreach ( Tag::split($tags) as $tag ) {
			$delete = $tag[0] == '-';
			$tagId = Tag::ensure($tag);

			foreach ( $_POST['check'] as $trId ) {
				call_user_func([Transaction::class, $delete ? 'untag' : 'tag'], $trId, $tagId);
			}
		}
		$db->commit();
	}

	return do_redirect();
}

elseif ( isset($_POST['category']) ) {
	$db->begin();

	Transaction::all(['id' => array_keys($_POST['category'])]);

	// Save category dropdowns
	foreach ( $_POST['category'] as $trId => $catId ) {
		Transaction::find($trId)->update(['category_id' => $catId]);
	}

	// Save new tags
	foreach ( (array) @$_POST['trtags'] as $trId => $tags ) {
		foreach ( $tags as $tag ) {
			$tagId = Tag::ensure($tag);
			Transaction::tag($trId, $tagId);
		}
	}

	$db->commit();

	return do_redirect();
}

$conditions = array(
	'ignore' => 0,
);
if ( !empty($_GET['ignore']) ) {
	$conditions['ignore'] = $_GET['ignore'];
}
if ( !empty($_GET['account']) ) {
	$conditions['account_id'] = abs($_GET['account']);

	// Show some hidden transactions
	unset($conditions['ignore']);
	$conditions[] = $db->replaceholders($_GET['account'] < 0 ? 'ignore = ?' : 'ignore <> ?', [Transaction::IGNORE_ACCOUNT_PAY_FOR_BALANCE]);
}
if ( !empty($_GET['category']) ) {
	$conditions['category_id'] = $_GET['category'] == -1 ? null : $_GET['category'];
}
if ( !empty($_GET['tag']) ) {
	if ( $_GET['tag'] == -1 ) {
		$conditions[] = 'NOT EXISTS (SELECT * FROM tagged WHERE transaction_id = transactions.id)';
	}
	else {
		$conditions[] = $db->replaceholders('id IN (SELECT transaction_id FROM tagged WHERE tag_id = ?)', array($_GET['tag']));
	}
}
if ( @$_GET['min'] != '' && @$_GET['max'] != '' ) {
	$min = (float) $_GET['min'];
	$max = (float) $_GET['max'];
	$max < $min and list($min, $max) = array($max, $min);
	$conditions[] = $db->replaceholders('amount BETWEEN ? AND ?', array($min, $max));
}
if ( !empty($_GET['year']) ) {
	if ( preg_match('#^(\d{4})-q(\d)$#', $_GET['year'], $match) ) {
		$qend = $match[1] . '-' . str_pad($match[2] * 3, 2, '0', STR_PAD_LEFT) . '-31';
		$qstart = $match[1] . '-' . str_pad($match[2] * 3 - 2, 2, '0', STR_PAD_LEFT) . '-01';
		$conditions[] = $db->replaceholders('date BETWEEN ? AND ?', [$qstart, $qend]);
	}
	else {
		$conditions[] = $db->replaceholders('date LIKE ?', [$_GET['year'] . '-_%']);
	}
}
if ( !empty($_GET['type']) ) {
	$conditions['type'] = $_GET['type'] == -1 ? null : $_GET['type'];
}
if ( !empty($_GET['search']) ) {
	$q = '%' . $_GET['search'] . '%';
	$conditions[] = $db->replaceholders('(description LIKE ? OR summary LIKE ? OR notes LIKE ? OR account LIKE ?)', array($q, $q, $q, $q));
}
if ( $conditions ) {
	$perPage = 500;
}

$offset = $page * $perPage;
$totalRecords = $db->count('transactions', $conditions);
$pages = ceil($totalRecords / $perPage);

$sort = isset($_GET['sort']) ? preg_replace('#[^\w-]#', '', $_GET['sort']) : '-date';
$sortDirection = $sort[0] == '-' ? 'DESC' : 'ASC';
$sortColumn = ltrim($sort, '-');

$pager = $export ? '' : 'LIMIT ' . $perPage . ' OFFSET ' . $offset;
$query = ($db->stringifyConditions($conditions) ?: '1') . ' ORDER BY ' . $sortColumn . ' ' . $sortDirection . ', ABS(amount) DESC ' . $pager;
$transactions = Transaction::all($query);

$tids = array_keys($transactions);

$categories = $db->select_fields('categories', 'id, name', '1 ORDER BY name ASC');
Transaction::$_categories = $categories;

$years = Transaction::allMonths();

$tags = $db->select_fields('tags', 'id, tag', '1 ORDER BY tag ASC');
Tag::decorateTransactions($transactions, $tags);

// Export as CSV
if ( $export ) {
	csv_file(
		$transactions,
		array('id', 'date', 'amount2dec', 'type', 'sumdesc', 'notes', 'category', 'tags_as_string'),
		'moneys.csv'
	);
	exit;
}

require 'tpl.header.php';

?>
<form method="get" action>
	<input type="hidden" name="sort" value="<?= html($sort) ?>" />
	<input type="hidden" name="type" value="<?= html(@$_GET['type']) ?>" />
	<input type="hidden" name="account" value="<?= html(@$_GET['account']) ?>" />
	<input type="hidden" name="ignore" value="<?= html(@$_GET['ignore']) ?>" />
	<p>
		Category: <select name="category"><?= html_options(array('-1' => '-- none') + $categories, @$_GET['category'], '-- all') ?></select>
		Tag: <select name="tag"><?= html_options(array('-1' => '-- none') + $tags, @$_GET['tag'], '-- all') ?></select>
		Amount: <input name="min" value="<?= @$_GET['min'] ?>" size="4" /> - <input name="max" value="<?= @$_GET['max'] ?>" size="4" />
		Period: <select name="year"><?= html_options($years, @$_GET['year'], '-- all') ?></select>
		Search: <input id="search-transactions" type="search" name="search" value="<?= @$_GET['search'] ?>" placeholder="Summ, Desc, Acc.no, Notes" />
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

<details>
	<summary>Conditions</summary>
	<pre><?= html(print_r($conditions, 1)) ?></pre>
</details>

<details>
	<summary>Query</summary>
	<pre><?= html($query) ?></pre>
</details>

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
