<?php

require 'inc.bootstrap.php';

$id = (int)$_GET['id'];
$transaction = $db->select('transactions', compact('id'), null, 'Transaction')->first();
if ( !$transaction ) {
	do_404();
	exit('Transaction not found.');
}

$subTransactions = $db->select('transactions', array('parent_transaction_id' => $id), null, 'Transaction')->all();
$hashClashes = array();

// DELETE
if ( @$_POST['_action'] == 'delete' ) {
	$db->delete('transactions', compact('id'));

	return do_redirect('index');
}

// UNSPLIT
if ( @$_POST['_action'] == 'unsplit' ) {
	// Delete children
	$db->delete('transactions', array('parent_transaction_id' => $id));

	// Unhide
	$db->update('transactions', array('ignore' => 0), compact('id'));

	return do_redirect('transaction', compact('id'));
}

// SAVE
else if ( isset($_POST['category_id'], $_POST['notes'], $_POST['tags']) ) {
	// Properties
	$db->update('transactions', array(
		'category_id' => $_POST['category_id'] ?: null,
		'notes' => trim($_POST['notes']),
	), compact('id'));

	// Tags
	$tags = array_filter(explode(' ', mb_strtolower(trim($_POST['tags']))));
	$transaction->saveTags($tags);

	return do_redirect('transaction', compact('id'));
}

// SPLIT
else if ( isset($_POST['amount'], $_POST['description'], $_POST['date']) ) {
	$existingHashes = $db->select_fields('transactions', 'hash, hash', 'parent_transaction_id <> ?', array($transaction->id));

	$subTransactions = $hashClashes = array();

	$totalAmount = 0;
	foreach ($_POST['amount'] as $i => $amount) {
		$description = $_POST['description'][$i];
		$date = $_POST['date'][$i];
		$category = $_POST['category'][$i] ?: NULL;
		$tags = $_POST['tags'][$i] ?: NULL;
		if ( $amount && $description && $date ) {
			$totalAmount += $amount;
			$subTransaction = array(
				'date' => $date,
				'summary' => $transaction->summary,
				'description' => $description,
				'type' => 'split',
				'account' => $transaction->account,
				'amount' => (float)$amount,
				'parent_transaction_id' => $transaction->id,
				'category_id' => $category,
				'tags' => trim(implode(' ', $transaction->tags) . ' ' . $tags),
			);
			$subTransaction['hash'] = get_transaction_hash($subTransaction);

			if ( isset($existingHashes[ $subTransaction['hash'] ]) ) {
				$hashClashes[$i] = $i;
			}
			$existingHashes[ $subTransaction['hash'] ] = $subTransaction['hash'];

			$subTransactions[] = $subTransaction;
		}
	}

	$error = false;

	if ( $totalAmount != $transaction->amount ) {
		if ( abs($totalAmount) == abs($transaction->amount) ) {
			$subTransactions = array_map(function($transaction) {
				$transaction['amount'] *= -1;
				return $transaction;
			}, $subTransactions);
		}
		else {
			$error = true;
			echo "<p class='error'>Totals don't match. Transaction says `" . number_format($transaction->amount, 2) . "`, your sub transactions say `" . number_format($totalAmount, 2) . "`.</p>";
			echo "\n\n";
		}
	}

	if ( $hashClashes ) {
		$error = true;
		echo "<p class='error'>All transactions must be unique. Manually add serial numbers to descriptions to make them unique.</p>";
		echo "\n\n";
	}

	if ( !$error ) {
		$db->begin();

		// Save parent
		$db->update('transactions', array(
			'ignore' => 1,
		), array('id' => $transaction->id));

		// Delete children
		$db->delete('transactions', array('parent_transaction_id' => $transaction->id));

		// Save children
		foreach ( $subTransactions as $subTransaction ) {
			Transaction::insert($subTransaction);
		}

		$db->commit();

		return do_redirect('index');
	}
}

require 'tpl.header.php';

$categories = $db->select_fields('categories', 'id, name', '1 ORDER BY name ASC');
$tags = $db->select_fields('tags', 'id, tag', '1 ORDER BY tag ASC');

?>
<style>
p.error {
	margin: 2em 0;
	color: red;
	font-weight: bold;
}

button + button {
	margin-left: .5em;
}
button.delete {
	background-color: #d00;
	border-color: #d00;
	color: white;
}

#split .amount {
	width: 5em;
}
#split .description {
	width: 22em;
}
#split .date {
	width: 11em;
}

.subTransaction.error input {
	border-color: red;
}
</style>

<form id="edit" method="post" action="">
	<table border="1">
		<tr>
			<th>Date</th>
			<td><?= $transaction->date ?></td>
		</tr>
		<tr class="dir-<?= $transaction->amount > 0 ? 'in' : 'out' ?>">
			<th>Amount</th>
			<td><?= html_money($transaction->amount, true) ?></td>
		</tr>
		<tr>
			<th>Type</th>
			<td><?= $transaction->type ?></td>
		</tr>
		<tr>
			<th>Account</th>
			<td>
				<a href="index.php?search=<?= html($transaction->account) ?>"><?= html($transaction->account) ?>
			</td>
		</tr>
		<tr>
			<th>Summary</th>
			<td><?= html($transaction->summary) ?></td>
		</tr>
		<tr>
			<th>Description</th>
			<td><?= html($transaction->description) ?></td>
		</tr>
		<tr>
			<th>
				Personal notes<br/>
				<div style="font-weight: normal; margin-top: .3em">(first line will be visible on overviews)</div>
			</th>
			<td>
				<textarea name="notes" rows="4" style="width: 100%; display: block"><?= html($transaction->notes) ?></textarea>
			</td>
		</tr>
		<tr>
			<th>Category</th>
			<td>
				<select name="category_id" class="<? if (!$transaction->category_id): ?>error<? endif ?>">
					<?= html_options($categories, $transaction->selected_category_id, '-- Unknown') ?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Tags</th>
			<td>
				<input name="tags" value="<?= html(implode(' ', $transaction->tags)) ?>" /> (space separated)
			</td>
		</tr>
	</table>

	<p>
		<button name="_action" value="save">Save</button>
		<button name="_action" value="delete" class="delete">Delete</button>
		<? if ($transaction->ignore): ?>
			<button name="_action" value="unsplit" class="delete">Unsplit</button>
		<? endif ?>
	</p>
</form>

<? if (!$transaction->parent_transaction_id): ?>
	<h2>Split transaction</h2>

	<form id="split" method="post" action="">

		<table border="1">
			<thead>
				<tr>
					<th>Amount</th>
					<th>Description</th>
					<th>Date</th>
					<th>Category</th>
					<th>Tags</th>
				</tr>
			</thead>
			<tbody>
				<? $subTransactions[] = array('date' => $transaction->date) ?>
				<? foreach ( $subTransactions as $i => $subTransaction ): ?>
					<tr class="subTransaction <?= isset($hashClashes[$i]) ? 'error' : '' ?>">
						<td><input name="amount[]" class="amount" type="number" step="any" value="<?= number_format(@$subTransaction['amount'] ?: 0, 2, '.', '') ?>" /></td>
						<td><input name="description[]" class="description" value="<?= html(@$subTransaction['description']) ?>" /></td>
						<td><input name="date[]" class="date" type="date" value="<?= html(@$subTransaction['date']) ?>" /></td>
						<td><select name="category[]" class="category"><?= html_options($categories, @$subTransaction['category_id'], '--') ?></select></td>
						<td><input name="tags[]" class="tags" list="data-tags" value="<?= html(implode(' ', (array)@$subTransaction['tags'])) ?>" /></td>
						<td>
							<?if (!empty($subTransaction['id'])): ?>
								<a href="transaction.php?id=<?= $subTransaction['id'] ?>">&gt;&gt;</a>
							<? endif ?>
						</td>
					</tr>
				<? endforeach ?>
			</tbody>
		</table>

		<datalist id="data-tags"><?= html_options($tags, '', '', true) ?></datalist>

		<p>
			<button name="_action" value="split">Split</button>
		</p>
	</form>
<? endif ?>

<script src="rjs-custom.js"></script>
<script>
// Confirm deletion
$$('#edit button.delete').on('click', function(e) {
	if ( !confirm('Really really?') ) {
		e.preventDefault();
	}
});

// Add more split rows
var $lastTR = $('split').getElement('tbody').getElement('.subTransaction:last-child').cloneNode(true);
// console.log($lastTR.parentNode);
$('split').on('input', function(e) {
	if ( e.target.firstAncestor('tr').matches(':last-child') ) {
		$('split').getElement('tbody').append($lastTR.cloneNode(true));
	}
});

// Validate split total
$('split').on('submit', function(e) {
	var total = this.getElements('input.amount').reduce(function(total, el) {
		return total + parseFloat(el.value);
	}, 0);
	total = Math.round(total * 100) / 100;

	if ( Math.abs(total) != Math.abs(<?= (float) $transaction->amount ?>) ) {
		total = String(total * 100);
		total = total.slice(0, -2) + '.' + total.slice(-2);
		total = total.replace(/(\d)((?:\d{3})+)\./, function(m, n, nnn) {
			return n + nnn.replace(/(\d{3})/g, ',$1') + '.';
		});
		alert("Totals don't match.\n\nSaved transactions: <?= number_format($transaction->amount, 2, '.', ',') ?>\nSplit total: " + total);
		e.preventDefault();
	}
});
</script>
<?php

require 'tpl.footer.php';
