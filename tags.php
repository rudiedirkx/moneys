<?php

require 'inc.bootstrap.php';

$expandYear = (int)@$_GET['year'];

$tags = $db->fetch('
	SELECT ta.*, COUNT(1) num_transactions
	FROM tags ta
	JOIN tagged tt ON tt.tag_id = ta.id
	JOIN transactions tr ON tr.id = tt.transaction_id
	WHERE tr.ignore = 0
	GROUP BY ta.id
	ORDER BY tag ASC
')->all();
// print_r($tags);

$spendings = $db->fetch_fields('
	SELECT ta.tag_id, SUM(tr.amount) amount
	FROM transactions tr
	JOIN tagged ta ON ta.transaction_id = tr.id
	WHERE tr.ignore = 0
	GROUP BY ta.tag_id
');
// print_r($spendings);

$spendingsPerYear = array_reduce($db->fetch('
	SELECT ta.id, SUBSTR(tr.date, 1, 4) year, SUM(tr.amount) amount
	FROM tags ta
	JOIN tagged tt ON tt.tag_id = ta.id
	JOIN transactions tr ON tr.id = tt.transaction_id
	WHERE tr.ignore = 0
	GROUP BY tag, year
	ORDER BY year DESC
')->all(), function($result, $record) {
	$result[ $record->year ][ $record->id ] = $record->amount;
	return $result;
}, array());
// print_r($spendingsPerYear);

if ( $expandYear ) {
	$spendingsPerMonth = array_reduce($db->fetch('
		SELECT ta.id, SUBSTR(tr.date, 1, 7) month, SUM(tr.amount) amount
		FROM tags ta
		JOIN tagged tt ON tt.tag_id = ta.id
		JOIN transactions tr ON tr.id = tt.transaction_id
		WHERE ignore = 0 AND date LIKE ?
		GROUP BY tag, month
		ORDER BY month DESC
	', array($expandYear . '-_%'))->all(), function($result, $record) {
		$result[ $record->month ][ $record->id ] = $record->amount;
		return $result;
	}, array());
	// print_r($spendingsPerMonth);
}

require 'tpl.header.php';

$months = cache_months();

?>
<style>
.expanded {
	background: #f7f7f7;
}
</style>

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
					<a title="Toggle monthly stats" href="tags.php<?if (!$expanded): ?>?year=<?= $year ?><? endif ?>"><?= $year ?></a>
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
		<? foreach ($tags as $tag): ?>
			<tr>
				<td><?= html($tag->tag) ?></td>
				<td class="amount"><?= html_money($spendings[$tag->id], true) ?></td>
				<td><a href="index.php?tag=<?= $tag->id ?>"><?= $tag->num_transactions ?></a></td>
				<? foreach ($spendingsPerYear as $year => $data):
					$expanded = $expandYear == $year;
					?>
					<td class="amount <?= $expanded ? 'expanded' : '' ?>"><a href="index.php?tag=<?= $tag->id ?>&year=<?= $year ?>"><?= html_money(@$data[$tag->id], true) ?></a></td>
					<?if ($expanded): ?>
						<? foreach ($spendingsPerMonth as $month => $data): ?>
							<td class="expanded"><?= html_money(@$data[$tag->id], true) ?></td>
						<? endforeach ?>
					<? endif ?>
				<? endforeach ?>
			</tr>
		<? endforeach ?>
	</tbody>
</table>
<?php

require 'tpl.footer.php';
