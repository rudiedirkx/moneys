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
if ( !empty($_GET['min']) && !empty($_GET['max']) ) {
	$conditions[] = $db->replaceholders('amount BETWEEN ? AND ?', array($_GET['min'], $_GET['max']));
}
if ( !empty($_GET['search']) ) {
	$q = '%' . $_GET['search'] . '%';
	$conditions[] = $db->replaceholders('(description LIKE ? OR summary LIKE ?)', array($q, $q));
}
// print_r($conditions);
$condSql = $conditions ? '(' . implode(' AND ', $conditions) . ') AND' : '';

$offset = $page * $perPage;
$total = $db->count('transactions', $condSql . ' 1');
$pages = ceil($total / $perPage);

$pager = $conditions ? '' : 'LIMIT ' . $perPage . ' OFFSET ' . $offset;
$transactions = $db->select('transactions', $condSql . ' 1 ORDER BY date DESC ' . $pager, null, 'Transaction')->all();

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
.cb-checked {
	background-color: green;
}
form.saving-tags button.save-cats,
form:not(.saving-tags) button.save-tags {
	display: none;
}
label {
	display: block;
}
.pager {
	text-align: center;
}
tr + .new-month td,
tr + .new-month th {
	border-top-width: 7px;
}
@media (max-width: 1000px) {
	.col-id, .col-cb {
		display: none;
	}
}
.hide-sumdesc .col-sumdesc,
body:not(.hide-sumdesc) .show-sumdesc {
	display: none;
}
</style>

<form action>
	<p>
		Category: <select name="category"><?= html_options($categories, @$_GET['category'], '-- all') ?></select>
		Tag: <select name="tag"><?= html_options($tags, @$_GET['tag'], '-- all') ?></select>
		Amount: <input name="min" value="<?= @$_GET['min'] ?>" size="4" /> - <input name="max" value="<?= @$_GET['max'] ?>" size="4" />
		Search: <input type="search" name="search" value="<?= @$_GET['search'] ?>" />
		<button>&gt;&gt;</button>
	</p>
</form>

<!-- <pre><strong>Filtered:</strong> <?= implode(' AND ', $conditions) ?></pre> -->

<p class="show-sumdesc"><a href="javascript:void(0)" onclick="document.body.toggleClass('hide-sumdesc')">Show Summary &amp; Description</a></p>

<form method="post" action>
	<table>
		<thead>
			<? ob_start() ?>
				<tr class="pager">
					<td colspan="8">
						<? if ($pager): ?>
							<a href="?page=<?= $page - 1?>">&lt;&lt;</a>
							|
						<? endif ?>
						<?= $offset + 1 ?> - <?= $offset + count($transactions) ?> / <?= $total ?>
						<? if ($pager): ?>
							|
							page <?= $page + 1 ?> / <?= $pages ?>
							|
							<a href="?page=<?= $page + 1?>">&gt;&gt;</a>
						<? endif ?>
					</td>
				</tr>
			<? $pager_html = ob_get_contents() ?>
			<tr>
				<th class="col-id"></th>
				<th class="col-cb"><input type="checkbox" onclick="$$('tbody .cb').prop('checked', this.checked); onCheck()" /></th>
				<th>Date</th>
				<th>Amount</th>
				<th>Type</th>
				<th class="col-sumdesc"><a href="javascript:void(0)" onclick="document.body.toggleClass('hide-sumdesc')">Summary &amp; Description</a></th>
				<th>Category</th>
				<th>Tags</th>
			</tr>
		</thead>
		<tbody>
			<? foreach ($transactions as $tr):
				$total += $tr->amount;
				$tr->new_month = @$old_month != $tr->month;
				$old_month = $tr->month;
				?>
				<tr class="<?= implode(' ', $tr->classes) ?>">
					<th class="col-id"><label for="tr-<?= $tr->id ?>"><?= $tr->id ?></label></th>
					<td class="col-cb"><input type="checkbox" name="check[]" value="<?= $tr->id ?>" class="cb" id="tr-<?= $tr->id ?>" onclick="onCheck()" /></td>
					<td class="date" nowrap><?= $tr->date ?></td>
					<td class="amount" nowrap><label for="tr-<?= $tr->id ?>"><?= $tr->formatted_amount ?></label></td>
					<td class="type" nowrap><?= $tr->type ?></td>
					<td class="col-sumdesc"><?= html($tr->summary) ?> <?= html($tr->description) ?></td>
					<td class="category <? if (!$tr->category_id): ?>empty<? endif ?>">
						<select name="category[<?= $tr->id ?>]"><?= html_options($categories, $tr->selected_category_id, '--') ?></select>
					</td>
					<td><?= implode('<br>', (array)@$tagged[$tr->id]) ?></td>
				</tr>
			<? endforeach ?>
		</tbody>
		<tfoot>
			<?= $pager_html ?>
			<tr>
				<td colspan="3"></td>
				<td class="amount"><?= html_money($total, true) ?></td>
				<td colspan="4"></td>
			</tr>
		</tfoot>
	</table>

	<p>
		<button class="save-cats">Save</button>
		<button class="save-tags">Save tags</button>
		Add tag: <input name="add_tag" />
	</p>
</form>

<script>
function onCheck() {
	var m = $$('tbody .cb:checked').length ? 'addClass' : 'removeClass';
	$$('form')[m]('saving-tags');

	$$('tbody .cb').each(function(cb) {
		var m = cb.checked ? 'addClass' : 'removeClass';
		cb.parentNode[m]('cb-checked');
	});
}
</script>
<?php

require 'tpl.footer.php';
