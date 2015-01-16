<?php

require 'inc.bootstrap.php';

$id = (int)$_GET['id'];
$transaction = $db->select('transactions', compact('id'), null, 'Transaction')->first();

if ( @$_POST['_action'] == 'delete' ) {
	$db->delete('transactions', compact('id'));

	return do_redirect('index');
}
else if ( isset($_POST['category_id'], $_POST['tags']) ) {
	// Properties
	$db->update('transactions', array(
		'category_id' => $_POST['category_id'] ?: null,
	), compact('id'));

	// Tags
	$tags = array_filter(explode(' ', mb_strtolower(trim($_POST['tags']))));
	$db->begin();
	$db->delete('tagged', array('transaction_id' => $id));
	foreach ($tags as $tag) {
		$tagId = $db->select_one('tags', 'id', compact('tag'));
		if ( !$tagId ) {
			$db->insert('tags', compact('tag'));
			$tagId = $db->insert_id();
		}

		$db->insert('tagged', array(
			'transaction_id' => $id,
			'tag_id' => $tagId,
		));
	}
	$db->commit();

	return do_redirect('transaction', compact('id'));
}

require 'tpl.header.php';

$categories = $db->select_fields('categories', 'id, name', '1 ORDER BY name ASC');

?>
<style>
button + button {
	margin-left: .5em;
}
button.delete {
	background-color: red;
	border-color: red;
	color: white;
}
</style>

<form method="post" action>

	<table border="1">
		<tr>
			<th>Date</th>
			<td><?= $transaction->date ?></td>
		</tr>
		<tr class="dir-<?= $transaction->amount > 0 ? 'in' : 'out' ?>">
			<th>Amount</th>
			<td><?= html_money($transaction->amount, true) ?></td>
		</tr>
		<tr>
			<th>Type</th>
			<td><?= $transaction->type ?></td>
		</tr>
		<tr>
			<th>Account</th>
			<td>
				<a href="index.php?search=<?= html($transaction->account) ?>"><?= html($transaction->account) ?>
			</td>
		</tr>
		<tr>
			<th>Summary</th>
			<td><?= $transaction->summary ?></td>
		</tr>
		<tr>
			<th>Description</th>
			<td><?= $transaction->description ?></td>
		</tr>
		<tr>
			<th>Category</th>
			<td>
				<select name="category_id" class="<? if (!$transaction->category_id): ?>error<? endif ?>">
					<?= html_options($categories, $transaction->category_id, '-- Unknown') ?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Tags</th>
			<td>
				<input name="tags" value="<?= html(implode(' ', $transaction->tags)) ?>" /> (space separated)
			</td>
		</tr>
	</table>

	<p>
		<button name="_action" value="save">Save</button>
		<button name="_action" value="delete" class="delete">Delete</button>
	</p>

</form>

<script src="rjs-custom.js"></script>
<script>
$$('button.delete').on('click', function(e) {
	if ( !confirm('Really really?') ) {
		e.preventDefault();
	}
});
</script>
<?php

require 'tpl.footer.php';
