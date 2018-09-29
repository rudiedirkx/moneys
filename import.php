<?php

use rdx\moneys\Importer;

require 'inc.bootstrap.php';

$account = Account::find(@$_GET['account']);

$importers = array_map('make_importer', MONEYS_IMPORTERS);

if ( isset($_POST['importer']) && (isset($_FILES['file']) || isset($_POST['filepath'])) ) {
	$importer = array_reduce($importers, function($match, Importer $importer) {
		return $importer->getTitle() === $_POST['importer'] ? $importer : $match;
	}, null);
	if ( !$importer ) {
		do_400();
		exit('Need importer.');
	}

	$batch = time();

	$transactions = $importer->extractTransactions($_POST['filepath'] ?? $_FILES['file']['tmp_name']);

	$transactions = array_map(function($tr) use ($batch, $account) {
		return $tr + [
			'account_id' => $account ? $account->id : null,
			'batch' => $batch,
			'hash' => get_transaction_hash($tr),
		];
	}, $transactions);
	$hashes = array_column($transactions, 'hash');

	$existingTransactions = Transaction::all(['hash' => $hashes]);
	$existingTransactions = array_reduce($existingTransactions, function(array $list, Transaction $tr) {
		return $list + [$tr->hash => $tr];
	}, []);

	if ( !empty($_POST['confirm']) ) {
		@unlink($_POST['filepath']);

		$db->begin();

		foreach ( $transactions as $tr ) {
			if ( !isset($existingTransactions[$tr['hash']]) ) {
				Transaction::insert($tr);
			}
		}

		$db->commit();

		return do_redirect('index');
	}

	usort($transactions, function($a, $b) {
		return strcmp($b['date'], $a['date']);
	});

	$filepath = tempnam(sys_get_temp_dir(), 'moneys');
	file_put_contents($filepath, file_get_contents($_FILES['file']['tmp_name']));

	?>
	<p>
		<?= count($existingTransactions) ?> transactions exists,
		<?= count($transactions) - count($existingTransactions) ?> to import:
	</p>
	<table border="1" cellpadding="10">
		<? foreach ($transactions as $tr):
			$exists = $existingTransactions[$tr['hash']] ?? null;
			?>
			<tr style="background-color: <?= $exists ? '#fdd' : '#dfd' ?>">
				<td nowrap><?= html($tr['date']) ?></td>
				<td nowrap align="right"><?= html($tr['amount']) ?></td>
				<td><?= html($tr['summary']) ?> <?= html($tr['description']) ?></td>
				<td></td>
			</tr>
			<? if ($exists): ?>
				<tr style="background-color: #fdd">
					<td colspan="2"></td>
					<td><?= html($exists->sumdesc) ?></td>
					<td nowrap><a href="transaction.php?id=<?= $exists->id ?>"><?= $exists->id ?></a></td>
				</tr>
			<? endif ?>
		<? endforeach ?>
	</table>

	<form action method="post">
		<input type="hidden" name="confirm" value="1" />
		<input type="hidden" name="importer" value="<?= html($_POST['importer']) ?>" />
		<input type="hidden" name="filepath" value="<?= html($filepath) ?>" />
		<p><button style="font-weight: bold; padding: 9px 14px">
			IMPORT <?= count($transactions) - count($existingTransactions) ?> TRANSACTIONS
		</button></p>
	</form>
	<?php

	exit;
}

require 'tpl.header.php';

?>
<h1>
	Import transactions
	<? if ($account): ?>
		into <em><?= html($account) ?></em>
	<? endif ?>
</h1>

<style>
label {
	display: block;
	margin-bottom: .5em;
}
.gutter {
	display: inline-block;
	width: 30px;
}
</style>

<form method="post" action enctype="multipart/form-data">
	<p>
		<? foreach ($importers as $importer):
			$title = $importer->getTitle();
			?>
			<label>
				<span class="gutter"><input type="radio" name="importer" value="<?= html($title) ?>" /></span>
				<?= html($title) ?><br>
				<span class="gutter"></span>
				<?= html($importer->getDescription()) ?>
			</label>
		<? endforeach ?>
	</p>
	<p>Upload file: <input type="file" name="file" /></p>

	<p><button>NEXT</button></p>

</form>
<?php

require 'tpl.footer.php';
