<?php

require 'inc.bootstrap.php';

$categories = Category::all('1 ORDER BY name ASC');

if ( isset($_POST['categories']) ) {
	header('Content-type: text/plain');

	$db->begin();
	foreach ( $_POST['categories'] as $id => $cat ) {
		$name = trim($cat['name']);

		// Existing
		if ( $id ) {
			$category = Category::find($id);

			// Update
			if ( $name ) {
				$category->update($cat);
			}
			// Delete
			else {
				$db->update('transactions', array('category_id' => null), array('category_id' => $id));
				$category->delete();
			}
		}
		// New
		elseif ( $name ) {
			$db->insert('categories', $cat);
		}
	}
	$db->commit();

	return do_redirect('categories');
}

$expandYear = (int)@$_GET['year'];

$spendings = $db->fetch_fields('SELECT category_id, SUM(amount) FROM transactions WHERE ignore = 0 GROUP BY category_id');
// print_r($spendings);

$transactionsPerYear = array();
$spendingsPerYear = array_reduce($db->fetch('
	SELECT category_id, SUBSTR(date, 1, 4) year, SUM(amount) amount, COUNT(1) AS num
	FROM transactions
	WHERE ignore = 0 AND category_id IS NOT NULL
	GROUP BY category_id, year
	ORDER BY year DESC
')->all(), function($result, $record) use (&$transactionsPerYear) {
	@$transactionsPerYear[ $record->year ] += $record->num;

	$result[ $record->year ][ $record->category_id ] = $record->amount;
	return $result;
}, array());
// print_r($spendingsPerYear);
// print_r($transactionsPerYear);

$spendingsPerMonth = $transactionsPerMonth = array();
if ( $expandYear ) {
	$spendingsPerMonth = array_reduce($db->fetch('
		SELECT category_id, SUBSTR(date, 1, 7) month, SUM(amount) amount, COUNT(1) AS num
		FROM transactions
		WHERE ignore = 0 AND category_id IS NOT NULL AND date LIKE ?
		GROUP BY category_id, month
		ORDER BY month DESC
	', array($expandYear . '-_%'))->all(), function($result, $record) use (&$transactionsPerMonth) {
		@$transactionsPerMonth[ $record->month ] += $record->num;

		$result[ $record->month ][ $record->category_id ] = $record->amount;
		return $result;
	}, array());
	// print_r($spendingsPerMonth);
	// print_r($transactionsPerMonth);
}

require 'tpl.header.php';

$categories[] = new Category(array('name' => ''));

$months = cache_months();

?>

<form method="post" action>
	<table class="per-year categories">
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
						<span class="num">(<?= (int) @$transactionsPerYear[$year] ?>)</span>
					</th>
					<?if ($expanded): ?>
						<? foreach ($spendingsPerMonth as $month => $data): ?>
							<th class="expanded">
								<?= html($months[ (int)substr($month, 5) ]) ?>
								<span class="num">(<?= (int) @$transactionsPerMonth[$month] ?>)</span>
							</th>
						<? endforeach ?>
					<? endif ?>
				<? endforeach ?>
			</tr>
		</thead>
		<tbody>
			<? foreach ($categories as $cat):
				$num = $db->count('transactions', array('ignore' => 0, 'category_id' => $cat->id ?: null));
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
						<td class="amount <?= $expanded ? 'expanded' : '' ?>">
							<? if ($cat->id): ?>
								<a href="index.php?category=<?= $cat->id ?>&year=<?= $year ?>"><?= html_money(@$data[$cat->id], true) ?></a>
							<? endif ?>
						</td>
						<?if ($expanded): ?>
							<? foreach ($spendingsPerMonth as $month => $data): ?>
								<td class="expanded">
									<? if ($cat->id): ?>
										<a href="index.php?category=<?= $cat->id ?>&year=<?= $month ?>"><?= html_money(@$data[$cat->id], true) ?></a>
									<? endif ?>
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
