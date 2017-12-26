<?php

require 'inc.bootstrap.php';

if ( isset($_FILES['csv']) ) {
	header('Content-type: text/plain');

	$data = csv_read_doc(file_get_contents($_FILES['csv']['tmp_name']), true);
// var_export($data);
// exit;

	if ( headers_sent() ) {
		exit('Error # ' . __LINE__);
	}

	// $directions = array('Af' => 'out', 'Bij' => 'in');
	$directions = array('Af' => -1, 'Bij' => 1);
	$types = array('BA' => 'manual', 'GM' => 'atm', 'IC' => 'auto', 'VZ' => 'auto', 'GT' => 'manual', 'OV' => 'manual');

	$records = array_map(function($tr) use ($directions, $types) {
		$dir = $directions[ trim($tr['Af Bij']) ];
		$record = array(
			'date' => get_date_from_ymd($tr['Datum']),
			'summary' => trim($tr['Naam / Omschrijving']),
			'description' => trim($tr['Mededelingen']),
			'type' => @$types[ trim($tr['Code']) ],
			'account' => preg_replace('#\s+#', '', trim($tr['Tegenrekening'])) ?: null,
			'amount' => $dir * get_amount_from_eu($tr['Bedrag (EUR)']),
		);

		$record['hash'] = get_transaction_hash($record);

		return $record;
	}, $data);
// print_r($records);
// exit;

	if ( headers_sent() ) {
		exit('Error # ' . __LINE__);
	}

	$existingHashes = $db->select_fields('transactions', 'hash, hash', '1');
// print_r($existingHashes);
// exit;

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

	$db->commit();

	echo "Saved " . $inserts . " of " . count($records) . " (" . (count($records) - $inserts) . " doubles)\n";

	exit;
}

require 'tpl.header.php';

?>
<form method="post" action enctype="multipart/form-data">

	<p>Upload CSV: <input type="file" name="csv" /></p>

	<p><button>Import</button></p>

</form>
<?php

require 'tpl.footer.php';
