<?php

require 'inc.bootstrap.php';

if ( isset($_POST['categories']) ) {
	header('Content-type: text/plain');

	print_r($_POST['categories']);

	exit;
}

$categories = $db->select('categories', '1 ORDER BY name ASC')->all();
// print_r($categories);

$spendings = $db->fetch_fields('SELECT category_id, SUM(amount) amount FROM transactions WHERE amount < 0 GROUP BY category_id');
// print_r($spendings);

require 'tpl.header.php';

$categories[] = (object)array('id' => '', 'name' => '');

?>
<form method="post" action>
	<table>
		<thead>
			<tr>
				<th>Name</th>
				<th>Total spendings</th>
			</tr>
		</thead>
		<tbody>
			<? foreach ($categories as $cat): ?>
				<tr>
					<td><input name="categories[<?= $cat->id ?>][name]" value="<?= html($cat->name) ?>" /></td>
					<td class="amount"><?= number_format($spendings[$cat->id], 2) ?></td>
				</tr>
			<? endforeach ?>
		<tbody>
	</table>

	<p><button>Save</button></p>
</form>
<?php

require 'tpl.footer.php';
