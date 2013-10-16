<?php

require 'inc.bootstrap.php';

$types = $db->fetch('
	SELECT type, SUM(amount) amount, COUNT(1) num_transactions
	FROM transactions
	GROUP BY type
	ORDER BY type ASC
')->all();
// print_r($types);

$spendingsPerYear = array_reduce($db->fetch('
	SELECT type, SUBSTR(date, 1, 4) year, SUM(amount) amount
	FROM transactions
	GROUP BY type, year
	ORDER BY year DESC
')->all(), function($result, $record) {
	$result[ $record->year ][ $record->type ] = $record->amount;
	return $result;
}, array());
// print_r($spendingsPerYear);

require 'tpl.header.php';

?>
<table>
	<thead>
		<tr>
			<th>Type</th>
			<th>Total in/out</th>
			<th>Transactions</th>
			<? foreach ($spendingsPerYear as $year => $data): ?>
				<th><?= $year ?></th>
			<? endforeach ?>
		</tr>
	</thead>
	<tbody>
		<? foreach ($types as $type): ?>
			<tr>
				<td><?= html($type->type ?: '?') ?></td>
				<td class="amount"><?= html_money($type->amount, true) ?></td>
				<td><?= $type->num_transactions ?></td>
				<? foreach ($spendingsPerYear as $year => $data): ?>
					<td class="amount"><?= html_money(@$data[$type->type], true) ?></td>
				<? endforeach ?>
			</tr>
		<? endforeach ?>
	</tbody>
</table>
<?php

require 'tpl.footer.php';
