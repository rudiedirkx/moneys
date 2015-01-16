<?php

require 'inc.bootstrap.php';

if ( isset($_POST['categories']) ) {
	header('Content-type: text/plain');

	$db->begin();
	foreach ( $_POST['categories'] as $id => $cat ) {
		$name = trim($cat['name']);

		// Existing
		if ( $id ) {
			// Update
			if ( $name ) {
				$db->update('categories', $cat, compact('id'));
			}
			// Delete
			else {
				$db->update('transactions', array('category_id' => null), array('category_id' => $id));
				$db->delete('categories', compact('id'));
			}
		}
		// New
		else if ( $name ) {
			$db->insert('categories', $cat);
		}
	}
	$db->commit();

	return do_redirect('categories');
}

$expandYear = (int)@$_GET['year'];

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

if ( $expandYear ) {
	$spendingsPerMonth = array_reduce($db->fetch('
		SELECT category_id, SUBSTR(date, 1, 7) month, SUM(amount) amount
		FROM transactions
		WHERE category_id IS NOT NULL AND date LIKE ?
		GROUP BY category_id, month
		ORDER BY month DESC
	', array($expandYear . '-_%'))->all(), function($result, $record) {
		$result[ $record->month ][ $record->category_id ] = $record->amount;
		return $result;
	}, array());
	// print_r($spendingsPerMonth);
}

require 'tpl.header.php';

$categories[] = (object)array('id' => '', 'name' => '');

$months = cache_months();

?>
<style>
.expanded {
	background: #f7f7f7;
}
</style>

<form method="post" action>
	<table>
		<thead>
			<tr>
				<th>Name</th>
				<th>Total in/out</th>
				<th>Transactions</th>
				<? foreach ($spendingsPerYear as $year => $data):
					$expanded = $expandYear == $year;
					?>
					<th class="<?= $expanded ? 'expanded' : '' ?>">
						<a title="Toggle monthly stats" href="categories.php<?if (!$expanded): ?>?year=<?= $year ?><? endif ?>"><?= $year ?></a>
					</th>
					<?if ($expanded): ?>
						<? foreach ($spendingsPerMonth as $month => $data): ?>
							<th class="expanded"><?= html($months[ (int)substr($month, 5) ]) ?></th>
						<? endforeach ?>
					<? endif ?>
				<? endforeach ?>
			</tr>
		</thead>
		<tbody>
			<? foreach ($categories as $cat):
				$num = $db->count('transactions', array('category_id' => $cat->id ?: null));
				?>
				<tr>
					<td>
						<input name="categories[<?= $cat->id ?>][name]" value="<?= html($cat->name) ?>" placeholder="New category..." />
					</td>
					<td class="amount">
						<?= html_money(@$spendings[$cat->id] ?: 0, true) ?>
					</td>
					<td>
						<a href="index.php?category=<?= $cat->id ?: -1 ?>"><?= $num ?></a>
					</td>
					<? foreach ($spendingsPerYear as $year => $data):
						$expanded = $expandYear == $year;
						?>
						<td class="amount <?= $expanded ? 'expanded' : '' ?>"><a href="index.php?category=<?= $cat->id ?>&year=<?= $year ?>"><?= html_money(@$data[$cat->id], true) ?></a></td>
						<?if ($expanded): ?>
							<? foreach ($spendingsPerMonth as $month => $data): ?>
								<td class="expanded">
									<a href="index.php?category=<?= $cat->id ?>&year=<?= $month ?>"><?= html_money(@$data[$cat->id], true) ?></a>
								</td>
							<? endforeach ?>
						<? endif ?>
					<? endforeach ?>
				</tr>
			<? endforeach ?>
		<tbody>
	</table>

	<p><button>Save</button></p>
</form>
<?php

require 'tpl.footer.php';
