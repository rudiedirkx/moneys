<?php

require 'inc.bootstrap.php';

$perPage = 100;
$page = (int)@$_GET['page'];

if ( isset($_POST['category']) ) {
	$db->begin();
	foreach ( $_POST['category'] as $trId => $catId ) {
		$db->update('transactions', array('category_id' => $catId ?: null), array('id' => $trId));
	}
	$db->commit();

	return do_redirect('index', compact('page'));
}

$offset = $page * $perPage;
$transactions = $db->select('transactions', '1 ORDER BY date DESC LIMIT ' . $perPage . ' OFFSET ' . $offset, null, 'Transaction');
$pages = ceil($db->count('transactions', '1') / $perPage);

$categories = $db->select_fields('categories', 'id, name', '1 ORDER BY name ASC');
// print_r($categories);

require 'tpl.header.php';

?>
<form method="post" action>
	<table>
		<thead>
			<tr>
				<td colspan="7">
					Page <?= $page + 1 ?> / <?= $pages ?> |
					<a href="?page=<?= $page + 1?>">&gt;&gt;</a>
				</td>
			</tr>
			<tr>
				<th></th>
				<th>Date</th>
				<th>Amount</th>
				<th>Type</th>
				<th>Summary</th>
				<th>Description</th>
				<th>Category</th>
			</tr>
		</thead>
		<tbody>
			<? foreach ($transactions as $tr): ?>
				<tr class="<?= implode(' ', $tr->classes) ?>">
					<th><?= $tr->id ?></th>
					<td class="date" nowrap><?= $tr->date ?></td>
					<td class="amount" nowrap><?= $tr->formatted_amount ?></td>
					<td class="type" nowrap><?= $tr->type ?></td>
					<td class="summary"><?= html($tr->summary) ?></td>
					<td class="description"><?= html($tr->description) ?></td>
					<td class="category <? if (!$tr->category_id): ?>empty<? endif ?>">
						<select name="category[<?= $tr->id ?>]"><?= html_options($categories, $tr->selected_category_id, '--') ?></select>
					</td>
				</tr>
			<? endforeach ?>
		</tbody>
	</table>

	<p><button>Save</button></p>
</form>
<?php

require 'tpl.footer.php';
