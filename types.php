<?php

require 'inc.bootstrap.php';

$types = $db->fetch('
	SELECT type, SUM(amount) amount, COUNT(1) num_transactions
	FROM transactions
	GROUP BY type
	ORDER BY type ASC
')->all();
// print_r($types);

require 'tpl.header.php';

?>
<table>
	<thead>
		<tr>
			<th>Type</th>
			<th>Total in/out</th>
			<th>Transactions</th>
		</tr>
	</thead>
	<tbody>
		<? foreach ($types as $type): ?>
			<tr>
				<td><?= html($type->type ?: '?') ?></td>
				<td class="amount"><?= html_money($type->amount, true) ?></td>
				<td><?= $type->num_transactions ?></td>
			</tr>
		<? endforeach ?>
	</tbody>
</table>
<?php

require 'tpl.footer.php';
