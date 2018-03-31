<?php

require 'inc.bootstrap.php';

$account = Account::find(@$_GET['account']);

if ( isset($_FILES['csv']) ) {
	header('Content-type: text/plain');

	$data = csv_read_doc(file_get_contents($_FILES['csv']['tmp_name']), true);

	if ( headers_sent() ) {
		exit('Error # ' . __LINE__);
	}

	$directions = array('Af' => -1, 'Bij' => 1);
	$types = array('BA' => 'manual', 'GM' => 'atm', 'IC' => 'auto', 'VZ' => 'auto', 'GT' => 'manual', 'OV' => 'manual');

	$batch = time();

	$records = array_map(function($tr) use ($account, $batch, $directions, $types) {
		$dir = $directions[ trim($tr['Af Bij']) ];
		$type = trim(@$tr['Code']);
		$record = array(
			'date' => get_date_from_ymd($tr['Datum']),
			'summary' => trim($tr['Naam / Omschrijving']),
			'description' => trim(@$tr['Mededelingen']),
			'type' => @$types[$type] ?: $type,
			'account' => preg_replace('#\s+#', '', trim(@$tr['Tegenrekening'])) ?: null,
			'amount' => $dir * get_amount_from_eu($tr['Bedrag (EUR)']),
			'account_id' => $account ? $account->id : null,
			'batch' => $batch,
		);

		$record['hash'] = get_transaction_hash($record);

		return $record;
	}, $data);

	if ( headers_sent() ) {
		exit('Error # ' . __LINE__);
	}

	$existingHashes = $db->select_fields('transactions', 'hash, hash', '1');

	$db->begin();

	$inserts = 0;
	$new = array();
	foreach ( $records as $record ) {
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

	if ( !$account && count($records) == $inserts ) {
		exit("No doubles. That can't be right...");
	}

	echo "Saved " . $inserts . " of " . count($records) . " (" . (count($records) - $inserts) . " doubles)\n";

	if ( empty($_POST['preview']) ) {
		$db->commit();
	}
	else {
		echo "PREVIEW - NOT SAVED\n";
	}

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

<form method="post" action="<?= $account ? "?account={$account->id}" : '' ?>" enctype="multipart/form-data">
	<p>Upload CSV: <input type="file" name="csv" /></p>
	<p><label><input type="checkbox" name="preview" checked /> Preview</label></p>

	<p><button>Import</button></p>

</form>
<?php

require 'tpl.footer.php';
