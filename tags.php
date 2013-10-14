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

require 'tpl.header.php';

?>
<table>
	<thead>
		<tr>
			<th>Name</th>
			<th>Total spendings</th>
			<th>Transactions</th>
		</tr>
	</thead>
	<tbody>
		<? foreach ($tags as $tag): ?>
			<tr>
				<td><?= html($tag->tag) ?></td>
				<td class="amount"><?= html_money($spendings[$tag->id]) ?></td>
				<td><a href="index.php?tag=<?= $tag->id ?>"><?= $tag->num_transactions ?></a></td>
			</tr>
		<? endforeach ?>
	</tbody>
</table>
<?php

require 'tpl.footer.php';
