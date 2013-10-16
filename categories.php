<?php

require 'inc.bootstrap.php';

if ( isset($_POST['categories']) ) {
	header('Content-type: text/plain');

	print_r($_POST['categories']);

	exit;
}

$categories = $db->select('categories', '1 ORDER BY name ASC')->all();
// print_r($categories);

$spendings = $db->fetch_fields('SELECT category_id, SUM(amount) FROM transactions GROUP BY category_id');
// print_r($spendings);

$spendingsPerYear = array_reduce($db->fetch('
	SELECT category_id, SUBSTR(date, 1, 4) year, SUM(amount) amount
	FROM transactions
	WHERE category_id IS NOT NULL
	GROUP BY category_id, year
	ORDER BY year DESC
')->all(), function($result, $record) {
	$result[ $record->year ][ $record->category_id ] = $record->amount;
	return $result;
}, array());
// print_r($spendingsPerYear);

// $spendingsPerMonth = array_reduce($db->fetch('
	// SELECT category_id, SUBSTR(date, 1, 7) month, SUM(amount) amount
	// FROM transactions
	// WHERE category_id IS NOT NULL
	// GROUP BY category_id, month
	// ORDER BY month DESC
// ')->all(), function($result, $record) {
	// $result[ $record->month ][ $record->category_id ] = $record->amount;
	// return $result;
// }, array());
// print_r($spendingsPerMonth);

require 'tpl.header.php';

$categories[] = (object)array('id' => '', 'name' => '');

?>
<form method="post" action>
	<table>
		<thead>
			<tr>
				<th>Name</th>
				<th>Total in/out</th>
				<th>Transactions</th>
				<? foreach ($spendingsPerYear as $year => $data): ?>
					<th><?= $year ?></th>
				<? endforeach ?>
			</tr>
		</thead>
		<tbody>
			<? foreach ($categories as $cat):
				$num = $db->count('transactions', array('category_id' => $cat->id ?: null));
				?>
				<tr>
					<td><input name="categories[<?= $cat->id ?>][name]" value="<?= html($cat->name) ?>" /></td>
					<td class="amount"><?= html_money(@$spendings[$cat->id] ?: 0) ?></td>
					<td><a href="index.php?category=<?= $cat->id ?: -1 ?>"><?= $num ?></a></td>
					<? foreach ($spendingsPerYear as $year => $data): ?>
						<td class="amount"><?= html_money(@$data[$cat->id]) ?></td>
					<? endforeach ?>
				</tr>
			<? endforeach ?>
		<tbody>
	</table>

	<p><button>Save</button></p>
</form>
<?php

require 'tpl.footer.php';
