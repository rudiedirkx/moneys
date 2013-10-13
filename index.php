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
			$db->insert('tagged', compact('tag_id', 'transaction_id'));
		}
		$db->commit();
	}

	return do_redirect();
}

else if ( isset($_POST['category']) ) {
	$db->begin();
	foreach ( $_POST['category'] as $trId => $catId ) {
		$db->update('transactions', array('category_id' => $catId ?: null), array('id' => $trId));
	}
	$db->commit();

	return do_redirect();
}

$conditions = array(1);
if ( !empty($_GET['category']) ) {
	$conditions[] = $db->replaceholders('category_id = ?', array($_GET['category']));
}
if ( !empty($_GET['tag']) ) {
	$conditions[] = $db->replaceholders('id IN (SELECT transaction_id FROM tagged WHERE tag_id = ?)', array($_GET['tag']));
}
if ( !empty($_GET['search']) ) {
	$q = '%' . $_GET['search'] . '%';
	$conditions[] = $db->replaceholders('(description LIKE ? OR summary LIKE ?)', array($q, $q));
}
// print_r($conditions);
$condSql = '(' . implode(' AND ', $conditions) . ')';

$offset = $page * $perPage;
$total = $db->count('transactions', $condSql);
$pages = ceil($total / $perPage);

$pager = @$conditions[1] ? '' : 'LIMIT ' . $perPage . ' OFFSET ' . $offset;
$transactions = $db->select('transactions', $condSql . ' ORDER BY date DESC ' . $pager, null, 'Transaction')->all();

$tids = array_map(function($tr) { return $tr->id; }, $transactions);
// print_r($tids);

$categories = $db->select_fields('categories', 'id, name', '1 ORDER BY name ASC');
// print_r($categories);

$tags = $db->select_fields('tags', 'id, tag', '1 ORDER BY tag ASC');
// print_r($tags);

$tagged = $db->select('tagged', array('transaction_id' => $tids))->all();
$tagged = array_reduce($tagged, function($tagged, $record) use ($tags) {
	$tagged[ $record->transaction_id ][] = $tags[ $record->tag_id ];
	return $tagged;
}, array());
// print_r($tagged);

require 'tpl.header.php';

?>
<style>
label {
	display: block;
}
</style>

<form action>
	<p>
		Category: <select name="category"><?= html_options($categories, @$_GET['category'], '-- All') ?></select>
		Tag: <select name="tag"><?= html_options($tags, @$_GET['tag'], '-- All') ?></select>
		Search: <input type="search" name="search" value="<?= @$_GET['search'] ?>" />
		<button>&gt;&gt;</button>
	</p>
</form>

<form method="post" action>
	<table>
		<thead>
			<tr>
				<td colspan="8">
					<a href="?page=<?= $page - 1?>">&lt;&lt;</a>
					|
					<?= $offset + 1 ?> - <?= $offset + count($transactions) ?> / <?= $total ?>
					|
					page <?= $page + 1 ?> / <?= $pages ?>
					|
					<a href="?page=<?= $page + 1?>">&gt;&gt;</a>
				</td>
			</tr>
			<tr>
				<th></th>
				<th><input type="checkbox" onclick="$$('tbody .cb').prop('checked', this.checked)" /></th>
				<th>Date</th>
				<th>Amount</th>
				<th>Type</th>
				<th>Summary</th>
				<th>Description</th>
				<th>Category</th>
				<th>Tags</th>
			</tr>
		</thead>
		<tbody>
			<? foreach ($transactions as $tr):
				$total += $tr->amount;
				?>
				<tr class="<?= implode(' ', $tr->classes) ?>">
					<th><label for="tr-<?= $tr->id ?>"><?= $tr->id ?></label></th>
					<td><input type="checkbox" name="check[]" value="<?= $tr->id ?>" class="cb" id="tr-<?= $tr->id ?>" /></td>
					<td class="date" nowrap><?= $tr->date ?></td>
					<td class="amount" nowrap><?= $tr->formatted_amount ?></td>
					<td class="type" nowrap><?= $tr->type ?></td>
					<td class="summary"><label for="tr-<?= $tr->id ?>"><?= html($tr->summary) ?></label></td>
					<td class="description"><label for="tr-<?= $tr->id ?>"><?= html($tr->description) ?></label></td>
					<td class="category <? if (!$tr->category_id): ?>empty<? endif ?>">
						<select name="category[<?= $tr->id ?>]"><?= html_options($categories, $tr->selected_category_id, '--') ?></select>
					</td>
					<td><?= implode('<br>', (array)@$tagged[$tr->id]) ?></td>
				</tr>
			<? endforeach ?>
		</tbody>
		<tfoot>
			<td colspan="3"></td>
			<td class="amount"><?= number_format($total, 2) ?></td>
			<td colspan="5"></td>
		</tfoot>
	</table>

	<p>
		<button>Save</button>
		Add tag: <input name="add_tag" />
	</p>
</form>

<script defer async src="rjs-custom.js"></script>
<?php

require 'tpl.footer.php';
