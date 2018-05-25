<?php

use rdx\moneys\Importer;

require 'inc.bootstrap.php';

$account = Account::find(@$_GET['account']);

$importers = array_map(function($class) {
	return new $class();
}, MONEYS_IMPORTERS);

if ( isset($_POST['importer'], $_FILES['file']) ) {
	header('Content-type: text/plain');

	$importer = array_reduce($importers, function($match, Importer $importer) {
		return $importer->getTitle() === $_POST['importer'] ? $importer : $match;
	}, null);
	if ( !$importer ) {
		do_400();
		exit('Need importer.');
	}

	$batch = time();

	$transactions = $importer->extractTransactions($_FILES['file']['tmp_name']);

	$transactions = array_map(function($tr) use ($batch, $account) {
		return $tr + [
			'account_id' => $account ? $account->id : null,
			'batch' => $batch,
			'hash' => get_transaction_hash($tr),
		];
	}, $transactions);

	$existingHashes = $db->select_fields('transactions', 'hash, hash', '1');

	$db->begin();

	$inserts = 0;
	$new = array();
	foreach ( $transactions as $record ) {
		if ( !isset($existingHashes[ $record['hash'] ]) ) {
			// Doubles in the same import are allowed. Too bad we can't differentiate =(
			if ( isset($new[ $record['hash'] ]) ) {
				$record['hash'] .= '-' . rand(100, 999);
			}

			Transaction::insert($record);
			$inserts++;
			$new[ $record['hash'] ] = $record['hash'];
		}
	}

	if ( !$account && count($transactions) == $inserts ) {
		do_400();
		exit("No doubles. That can't be right...");
	}

	echo "Saved " . $inserts . " of " . count($transactions) . " (" . (count($transactions) - $inserts) . " doubles)\n";

	if ( empty($_POST['preview']) ) {
		$db->commit();
	}
	else {
		echo "\nPREVIEW - NOT SAVED\n";
	}

	// do_redirect('index', ['batch' => $batch]);
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

<form method="post" action enctype="multipart/form-data">
	<p>
		<? foreach ($importers as $importer):
			$title = $importer->getTitle();
			?>
			<label>
				<input type="radio" name="importer" value="<?= html($title) ?>" />
				<?= html($title) ?>
				&nbsp; -
				<?= html($importer->getDescription()) ?>
			</label><br>
		<? endforeach ?>
	</p>
	<p>Upload file: <input type="file" name="file" /></p>
	<p><label><input type="checkbox" name="preview" checked /> Preview</label></p>

	<p><button>Import</button></p>

</form>
<?php

require 'tpl.footer.php';
