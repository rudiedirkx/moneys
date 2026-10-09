<?php

use rdx\moneys\Tag;

require 'inc.bootstrap.php';

$expandYear = (int) ($_GET['year'] ?? 0);

$tags = Tag::all('1 ORDER BY tag ASC');
Tag::eager('num_transactions', $tags);
// print_r($tags);

$spendings = db()->fetch_fields('
	SELECT ta.tag_id, SUM(tr.amount) amount
	FROM transactions tr
	JOIN tagged ta ON ta.transaction_id = tr.id
	WHERE tr.ignore = 0
	GROUP BY ta.tag_id
');
// print_r($spendings);

$transactionsPerYear = array();
$spendingsPerYear = array_reduce(db()->fetch('
	SELECT ta.id, SUBSTR(tr.date, 1, 4) year, SUM(tr.amount) amount, COUNT(1) num
	FROM tags ta
	JOIN tagged tt ON tt.tag_id = ta.id
	JOIN transactions tr ON tr.id = tt.transaction_id
	WHERE tr.ignore = 0
	GROUP BY tag, year
	ORDER BY year DESC
'), function(array $result, stdClass $record) use (&$transactionsPerYear) {
	$transactionsPerYear[ $record->year ] ??= 0;
	$transactionsPerYear[ $record->year ] += $record->num;

	$result[ $record->year ][ $record->id ] = $record->amount;
	return $result;
}, array());
// print_r($spendingsPerYear);

$spendingsPerMonth = $transactionsPerMonth = array();
if ( $expandYear ) {
	$spendingsPerMonth = array_reduce(db()->fetch('
		SELECT ta.id, SUBSTR(tr.date, 1, 7) month, SUM(tr.amount) amount, COUNT(1) num
		FROM tags ta
		JOIN tagged tt ON tt.tag_id = ta.id
		JOIN transactions tr ON tr.id = tt.transaction_id
		WHERE ignore = 0 AND date LIKE ?
		GROUP BY tag, month
		ORDER BY month DESC
	', array($expandYear . '-_%')), function(array $result, stdClass $record) use (&$transactionsPerMonth) {
		$transactionsPerMonth[ $record->month ] ??= 0;
		$transactionsPerMonth[ $record->month ] += $record->num;

		$result[ $record->month ][ $record->id ] = $record->amount;
		return $result;
	}, array());
	// print_r($spendingsPerMonth);
}

require 'tpl.header.php';

$months = cache_months();

?>

<table class="per-year tags">
	<thead>
		<tr>
			<th>Name</th>
			<th>Total in/out</th>
			<th>Transactions</th>
			<? foreach ($spendingsPerYear as $year => $data):
				$expanded = $expandYear == $year;
				?>
				<th class="<?= $expanded ? 'expanded' : '' ?>">
					<a title="Toggle monthly stats" href="tags.php<?if (!$expanded): ?>?year=<?= $year ?><? endif ?>"><?= $year ?></a>
					<span class="num">(<?= $transactionsPerYear[$year] ?? 0 ?>)</span>
				</th>
				<?if ($expanded): ?>
					<? foreach ($spendingsPerMonth as $month => $data): ?>
						<th class="expanded">
							<?= html($months[ (int)substr($month, 5) ]) ?>
							<span class="num">(<?= $transactionsPerMonth[$month] ?? 0 ?>)</span>
						</th>
					<? endforeach ?>
				<? endif ?>
			<? endforeach ?>
		</tr>
	</thead>
	<tbody>
		<? foreach ($tags as $tag): ?>
			<tr>
				<td><?= html($tag->tag) ?></td>
				<td class="amount"><?= html_money($spendings[$tag->id] ?? null, true) ?></td>
				<td><a href="index.php?tag=<?= $tag->id ?>"><?= $tag->num_transactions ?></a></td>
				<? foreach ($spendingsPerYear as $year => $data):
					$expanded = $expandYear == $year;
					?>
					<td class="amount <?= $expanded ? 'expanded' : '' ?>">
						<a href="index.php?tag=<?= $tag->id ?>&year=<?= $year ?>"><?= html_money($data[$tag->id] ?? null, true) ?></a>
					</td>
					<?if ($expanded): ?>
						<? foreach ($spendingsPerMonth as $month => $data): ?>
							<td class="expanded">
								<a href="index.php?tag=<?= $tag->id ?>&year=<?= $month ?>"><?= html_money($data[$tag->id] ?? null, true) ?></a>
							</td>
						<? endforeach ?>
					<? endif ?>
				<? endforeach ?>
			</tr>
		<? endforeach ?>
	</tbody>
</table>
<?php

require 'tpl.footer.php';
