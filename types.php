<?php

require 'inc.bootstrap.php';

$types = $db->fetch('
	SELECT type, SUM(ABS(amount)) amount, COUNT(1) num_transactions
	FROM transactions
	WHERE ignore = 0
	GROUP BY type
	ORDER BY type ASC
')->all();
// print_r($types);

$spendingsPerYear = array_reduce($db->fetch('
	SELECT type, SUBSTR(date, 1, 4) year, SUM(ABS(amount)) amount
	FROM transactions
	WHERE ignore = 0
	GROUP BY type, year
	ORDER BY year DESC
')->all(), function($result, $record) {
	$result[ $record->year ][ $record->type ] = $record->amount;
	return $result;
}, array());
// print_r($spendingsPerYear);

$labels = Transaction::getTypes();

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
				<td nowrap><?= html($type->type ?: '?') ?> <?= html($labels[$type->type] ?? '') ?></td>
				<td class="amount"><?= html_money($type->amount, false) ?></td>
				<td>
					<a href="index.php?type=<?= $type->type ?: -1 ?>"><?= $type->num_transactions ?></a>
				</td>
				<? foreach ($spendingsPerYear as $year => $data): ?>
					<td class="amount">
						<a href="index.php?type=<?= $type->type ?: -1 ?>&year=<?= $year ?>"><?= html_money(@$data[$type->type], false) ?></a>
					</td>
				<? endforeach ?>
			</tr>
		<? endforeach ?>
	</tbody>
</table>

<p>Hidden source transactions:</p>

<ul>
	<? foreach (Transaction::$_ignores as $key => $name): ?>
		<li><a href="index.php?ignore=<?= html($key) ?>"><?= html($name) ?></a></li>
	<? endforeach ?>
</ul>
<?php

require 'tpl.footer.php';
