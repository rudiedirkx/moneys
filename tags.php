<?php

require 'inc.bootstrap.php';

$tags = $db->fetch('
	SELECT ta.*, COUNT(1) num_transactions
	FROM tags ta
	JOIN tagged tt ON tt.tag_id = ta.id
	JOIN transactions tr ON tr.id = tt.transaction_id
	GROUP BY ta.id
	ORDER BY tag ASC
')->all();
// print_r($tags);

$spendings = $db->fetch_fields('SELECT ta.tag_id, SUM(tr.amount) amount FROM transactions tr JOIN tagged ta ON ta.transaction_id = tr.id GROUP BY ta.tag_id');
// print_r($spendings);

$spendingsPerYear = array_reduce($db->fetch('
	SELECT ta.id, SUBSTR(tr.date, 1, 4) year, SUM(tr.amount) amount
	FROM tags ta
	JOIN tagged tt ON tt.tag_id = ta.id
	JOIN transactions tr ON tr.id = tt.transaction_id
	GROUP BY tag, year
	ORDER BY year DESC
')->all(), function($result, $record) {
	$result[ $record->year ][ $record->id ] = $record->amount;
	return $result;
}, array());
// print_r($spendingsPerYear);

require 'tpl.header.php';

?>
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
		<? foreach ($tags as $tag): ?>
			<tr>
				<td><?= html($tag->tag) ?></td>
				<td class="amount"><?= html_money($spendings[$tag->id], true) ?></td>
				<td><a href="index.php?tag=<?= $tag->id ?>"><?= $tag->num_transactions ?></a></td>
				<? foreach ($spendingsPerYear as $year => $data): ?>
					<td class="amount"><a href="index.php?tag=<?= $tag->id ?>&year=<?= $year ?>"><?= html_money(@$data[$tag->id], true) ?></a></td>
				<? endforeach ?>
			</tr>
		<? endforeach ?>
	</tbody>
</table>
<?php

require 'tpl.footer.php';
